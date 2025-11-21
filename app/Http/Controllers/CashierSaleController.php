<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashierSale;
use App\Models\Finance;
use App\Models\Notification;
use App\Models\ProductStore;
use App\Models\Professional;
use App\Services\TraceService;
use App\Traits\ProductExitTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashierSaleController extends Controller
{
    use ProductExitTrait;
    
    private TraceService $traceService;

    public function __construct(TraceService $traceService)
    {
        $this->traceService = $traceService;
    }

    /**
 * Obtiene todas las ventas de productos realizadas por cajeros(as).
 *
 * @authenticated
 *
 * @response 200 [
 *   {
 *     "id": 123,
 *     "branch_id": 5,
 *     "professional_id": 789,
 *     "product_store_id": 456,
 *     "data": "2025-11-21 10:30:00",
 *     "price": 15000.00,
 *     "cant": 3,
 *     "percent_wint": 5000.00,
 *     "commission_amount": 5000.00,
 *     "commission_rate": 0,
 *     "pay": 0
 *   }
 * ]
 * @response 500 {"error": "Error al obtener las ventas de caja."}
 */
    public function index()
    {
        try {
            $cashierSales = CashierSale::all();
            return response()->json($cashierSales, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener las ventas de caja.'], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
 * Registra una nueva venta de producto desde la caja.
 *
 * Actualiza el stock del producto y registra trazabilidad.
 *
 * @authenticated
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam professional_id integer required ID del cajero(a). Example: 123
 * @bodyParam product_store_id integer required ID del producto en almacén. Example: 456
 * @bodyParam cant integer required Cantidad vendida. Example: 2
 * @bodyParam nameProfessional string required Nombre del cajero(a) para trazabilidad. Example: Yasmany
 *
 * @response 201 {
 *   "id": 124,
 *   "branch_id": 5,
 *   "professional_id": 123,
 *   "product_store_id": 456,
 *   "data": "2025-11-21 11:00:00",
 *   "price": 20000.00,
 *   "cant": 2,
 *   "percent_wint": 6000.00,
 *   "commission_amount": 6000.00,
 *   "commission_rate": 0,
 *   "pay": 0
 * }
 * @response 500 {"error": "Error al crear la venta de caja."}
 */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validate([
                'branch_id' => 'required|integer',
                'professional_id' => 'required|integer',
                'product_store_id' => 'required|integer',
                'cant' => 'required|integer',
                'nameProfessional' => 'required'
            ]);

            $branch = Branch::where('id', $validatedData['branch_id'])->first();
            
            $productStore = ProductStore::find($validatedData['product_store_id']);
                $product = $productStore->product()->first();
                $percent_wint = $product->sale_price - $product->purchase_price;
            
            // Obtener la categoría del producto y calcular comisión
            $category = $product->productCategory; // Asumiendo que existe relación 'category' en el modelo Product
            $commissionAmount = 0;
            $commissionRate = $product->commission_rate ? $product->commission_rate : 0;
            if ($category && $category->gives_commission) {
                $commissionAmount = $percent_wint * $validatedData['cant'];
            }
                    
            $cashierSale = new CashierSale();
            $cashierSale->branch_id = $validatedData['branch_id'];
            $cashierSale->professional_id = $validatedData['professional_id'];
            $cashierSale->product_store_id = $validatedData['product_store_id'];
            $cashierSale->data = Carbon::now();
            $cashierSale->price = $product->sale_price * $validatedData['cant'];
            $cashierSale->cant = $validatedData['cant'];
            $cashierSale->percent_wint = $percent_wint * $validatedData['cant'];
            $cashierSale->commission_amount = $commissionAmount; // Nuevo campo para almacenar la comisión
            $cashierSale->commission_rate = $commissionRate; // Nuevo campo para almacenar el porcentaje de comisión
            $cashierSale->save();

            $productStore->product_quantity = $validatedData['cant'];
                $productStore->product_exit = $productStore->product_exit - $validatedData['cant'];
                $productStore->save();
            //todo pendiente para revisar importante
            $this->actualizarProductExit($productStore, $validatedData['branch_id']);      
                $trace = [
                    'branch' => $branch->name,
                    'cashier' => $request->nameProfessional,
                    'client' => '',
                    'amount' => $product->sale_price * $validatedData['cant'],
                    'operation' => 'Venta de Productos',
                    'details' => $validatedData['cant']. ' '.$product->name,
                    'description' => '',
                ];
                $this->traceService->store($trace);
                            
            DB::commit();
            return response()->json($cashierSale, 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Error al crear la venta de caja.'], 500);
        }
    }

    /**
 * Obtiene las ventas de productos del día para un cajero(a) en una sucursal.
 *
 * - Si el usuario es **Administrador**, ve **todas** las ventas de la sucursal.
 * - Si es **cajero(a)**, ve solo **sus propias** ventas.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam professional_id integer required ID del cajero(a). Example: 123
 *
 * @response 201 {
 *   "sales": [
 *     {
 *       "id": 123,
 *       "price": 15000,
 *       "sale_price": 7500,
 *       "pay": 1,
 *       "cant": 2,
 *       "name": "Gel fijador",
 *       "image_product": "products/gel.jpg"
 *     }
 *   ]
 * }
 * @response 500 {"error": "Error al crear la venta de caja."}
 */
    public function show(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'branch_id' => 'required|integer',
                'professional_id' => 'required|integer'
            ]);
            $sales = [];
            if (auth()->user()->professional->charge->name == 'Administrador') {
                $cashierSales = CashierSale::where('branch_id', $validatedData['branch_id'])->whereDate('data', Carbon::now())->orderBy('pay')->orderByDesc('id')->get();
            foreach ($cashierSales as $cashierSale) {
                $product = $cashierSale['productStore']['product'];
                $sales[] = [
                    'id' => $cashierSale['id'],
                    'price' => intval($cashierSale['price']),
                    'sale_price' => intval($product['sale_price']),
                    'pay' => $cashierSale['pay'],
                    'cant' => $cashierSale['cant'],
                    'name' => $product['name'],
                    'image_product' => $product['image_product'],
                ];
            }
            }else {
                $cashierSales = CashierSale::where('professional_id', $validatedData['professional_id'])->where('branch_id', $validatedData['branch_id'])->whereDate('data', Carbon::now())->orderBy('pay')->orderByDesc('id')->get();
            foreach ($cashierSales as $cashierSale) {
                $product = $cashierSale['productStore']['product'];
                $sales[] = [
                    'id' => $cashierSale['id'],
                    'price' => intval($cashierSale['price']),
                    'sale_price' => intval($product['sale_price']),
                    'pay' => $cashierSale['pay'],
                    'cant' => $cashierSale['cant'],
                    'name' => $product['name'],
                    'image_product' => $product['image_product'],
                ];
            }
            }
    
            return response()->json(['sales' => $sales], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage().   'Error al crear la venta de caja.'], 500);
        }
    }

    /**
 * Deniega una solicitud de eliminación de venta de caja.
 *
 * Restaura el estado de pago a `0` (pendiente) y notifica al cajero.
 *
 * @authenticated
 * @bodyParam id integer required ID de la venta. Example: 123
 * @bodyParam professional_id integer required ID del administrador que deniega. Example: 100
 *
 * @response 200 {"msg": "Estado de la venta modificado correctamente"}
 * @response 500 {"msg": "Error al hacer la solicitud de eliminar la venta"}
 */
    public function cashiersale_denegar(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $cashierSale = CashierSale::find($data['id']);
            $branch = Branch::where('id', $cashierSale->branch_id)->first();              
            $notification = new Notification();
            $notification->professional_id = $data['professional_id'];
            $notification->tittle = 'Denegada';
            $notification->description = 'Solicitud de eliminación de producto '.$cashierSale->productStore->product->name.' denegada';
            $notification->type = 'Caja';
            $branch->notifications()->save($notification);
            //}
        //}
            $cashierSale->pay = 0;
            $cashierSale->save();
            return response()->json(['msg' => 'Estado de la venta modificado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al hacer la solicitud de eliminar la venta'], 500);
        }
    }

    /**
 * Solicita la eliminación de una venta de caja (requiere aprobación de administrador).
 *
 * Marca la venta con `pay = 3` y genera notificación.
 *
 * @authenticated
 * @bodyParam id integer required ID de la venta. Example: 123
 * @bodyParam professional_id integer optional ID del cajero que hace la solicitud. Example: 124
 * @bodyParam branch_id integer required ID de la sucursal (para notificación). Example: 5
 * @bodyParam nameProfessional string required Nombre del cajero para trazabilidad. Example: Yasmany
 *
 * @response 200 {"msg": "Carro eliminado correctamente"}
 * @response 500 {"msg": "Error al eliminar el carro"}
 */
    public function destroy_solicitud(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'professional_id' => 'nullable'
            ]);
            $cashierSale = CashierSale::find($data['id']);
            $branch = Branch::where('id', $request->branch_id)->first();
            $product = $cashierSale->productStore->product;
            $professional = $cashierSale->professional;
            $trace = [
                'branch' => $branch->name,
                'cashier' => $request->nameProfessional,
                'client' => '',
                'amount' => $cashierSale->price,
                'operation' => 'Hace solicitud de eliminar venta de producto'. $product->name.' en la caja',
                'details' => 'Cantidad del producto '.$cashierSale->cant,
                'description' => '',
            ];
            $this->traceService->store($trace);
            $cashierSale->pay = 3;
            $cashierSale->save();
           
            $notification = new Notification();
            $notification->professional_id = $data['professional_id'];
            $notification->tittle = 'Solicitud';
            $notification->description = 'Solicitud de eliminación de venta de producto en la caja: ' . $cashierSale->id;
            $notification->type = 'Administrador';
            $branch->notifications()->save($notification);
            //}
            //}
            //$car->delete();
            return response()->json(['msg' => 'Carro eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el carro'], 500);
        }
    }
    

    /**
 * Actualiza los datos de una venta de caja existente.
 *
 * ⚠️ Solo para uso administrativo o corrección.
 *
 * @authenticated
 * @bodyParam id integer required ID de la venta. Example: 123
 * @bodyParam branch_id integer required Nueva sucursal. Example: 6
 * @bodyParam professional_id integer required Nuevo cajero. Example: 124
 * @bodyParam product_store_id integer required Nuevo producto en almacén. Example: 457
 * @bodyParam date string required Nueva fecha (Y-m-d). Example: 2025-11-20
 * @bodyParam price number required Nuevo monto total. Example: 20000.00
 * @bodyParam quantity integer required Nueva cantidad. Example: 3
 * @bodyParam pay integer required Nuevo estado de pago (0: pendiente, 1: pagado, 3: solicitud eliminación). Example: 1
 * @bodyParam percent_wint number required Nueva utilidad. Example: 7000.00
 *
 * @response 200 { ... }
 * @response 500 {"error": "Error al actualizar la venta de caja."}
 */
    public function update(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'id' => 'required|numeric',
                'branch_id' => 'required|integer',
                'professional_id' => 'required|integer',
                'product_store_id' => 'required|integer',
                'date' => 'required|date',
                'price' => 'required|numeric',
                'quantity' => 'required|integer',
                'pay' => 'required|integer',
                'percent_wint' => 'required|numeric',
            ]);
    
            $cashierSale = CashierSale::findOrFail($validatedData['id']);
            $cashierSale->branch_id = $validatedData['branch_id'];
            $cashierSale->professional_id = $validatedData['professional_id'];
            $cashierSale->product_store_id = $validatedData['product_store_id'];
            $cashierSale->date = $validatedData['date'];
            $cashierSale->price = $validatedData['price'];
            $cashierSale->quantity = $validatedData['quantity'];
            $cashierSale->pay = $validatedData['pay'];
            $cashierSale->percent_wint = $validatedData['percent_wint'];
            $cashierSale->save();
    
            return response()->json($cashierSale, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar la venta de caja.'], 500);
        }
    }

    /**
 * Elimina una venta de caja y restaura el stock del producto.
 *
 * Solo se puede ejecutar después de **aprobar la solicitud de eliminación**.
 *
 * @authenticated
 * @bodyParam id integer required ID de la venta a eliminar. Example: 123
 * @bodyParam professional_id integer required ID del cajero(a) que realiza la acción. Example: 124
 *
 * @response 200 {"msg": "Venta eliminada correctamente"}
 * @response 500 {"error": "Error al eliminar la venta de caja."}
 */
    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $cashierSale = CashierSale::find($data['id']);
            $branch = Branch::where('id', $cashierSale->branch_id)->first();     
            
            $productstore = ProductStore::find($cashierSale->product_store_id);
            //$product = $productstore->product;
            $cant = $cashierSale->cant;
            $productstore->product_quantity = $cant;
            $productstore->product_exit = $productstore->product_exit + $cant;
            $productstore->save();

            $notification = new Notification();
            $notification->professional_id = $data['professional_id'];
            $notification->tittle = 'Aceptada';
            $notification->description = 'Solicitud de eliminación de producto '.$cashierSale->productStore->product->name.' aceptada';
            $notification->type = 'Caja';
            $branch->notifications()->save($notification);
            //}
        //}
            $cashierSale->delete();
                    
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar la venta de caja.'], 500);
        }
    }
}
