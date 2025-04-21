<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Finance;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductStore;
use App\Models\Professional;
use App\Models\WorkerPurchase;
use App\Services\TraceService;
use App\Traits\ProductExitTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkerPurchaseController extends Controller
{

    use ProductExitTrait;
    private TraceService $traceService;

    public function __construct(TraceService $traceService)
    {
        $this->traceService = $traceService;
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validate([
                'branch_id' => 'required|integer|exists:branches,id',
                'professional_id' => 'required|integer|exists:professionals,id',
                'id' => 'required|integer|exists:product_store,id',
                'cant' => 'required|integer|min:1'
            ]);

            $branch = Branch::where('id', $validatedData['branch_id'])->first();
            $professional = Professional::where('id', $validatedData['professional_id'])->first();
            // Obtener datos relacionados
            $productStore = ProductStore::find($validatedData['id']);
            $product = $productStore->product()->first();

            // Verificar disponibilidad de stock
            if ($productStore->product_exit < $validatedData['cant']) {
                throw new \Exception('No hay suficiente stock disponible');
            }

            // Calcular precios y descuentos
            $sale_price = $product->sale_price;
            $worker_discount = $product->worker_discount ?? 10; // 10% por defecto si no está definido
            $discounted_price = $sale_price * (1 - ($worker_discount / 100));
            $total = $discounted_price * $validatedData['cant'];
            $percent_wint = ($discounted_price - $product->purchase_price) * $validatedData['cant'];

            // Crear registro de compra para trabajador
            $workerPurchase = new WorkerPurchase();
            $workerPurchase->branch_id = $validatedData['branch_id'];
            $workerPurchase->professional_id = $validatedData['professional_id'];
            $workerPurchase->product_id = $product->id;
            $workerPurchase->data = Carbon::now();
            $workerPurchase->price = $sale_price;
            $workerPurchase->discount = $worker_discount;
            $workerPurchase->cant = $validatedData['cant'];
            $workerPurchase->total = $total;
            $workerPurchase->percent_wint = $percent_wint;
            $workerPurchase->status = 0; // Pendiente por defecto
            $workerPurchase->save();

            $notification = new Notification();
            $notification->professional_id = $validatedData['professional_id'];
            $notification->tittle = 'Solicitud de Compra de producto';
            $notification->description = 'Profesional ' . $professional->name . ' solicita comprar' . $validatedData['cant'] . $product->name;
            $notification->type = 'Cajera';
            $branch->notifications()->save($notification);
            Log::info('WorkerPurchase creada:', ['workerPurchase' => $workerPurchase]);

            DB::commit();
            return response()->json($workerPurchase, 201);
        } catch (\Exception $e) {
            Log::error('Error en WorkerPurchaseController@store: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);
            DB::rollback();
            return response()->json(['error' => 'Error al registrar la compra para trabajador: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $userName = $user->name;
        $userId = $user->id;
        DB::beginTransaction();
        try {
            $validatedData = $request->validate([
                'id' => 'integer|exists:worker_purchases,id',
                'status' => 'required|integer|in:0,1,2' // 0=Pendiente, 1=Confirmado, 2=Cancelado
            ]);

            $workerPurchase = WorkerPurchase::with([
                'professional:id,name',
                'branch:id,name',
                'product:id,name'])
                ->findOrFail($validatedData['id']);
            $oldStatus = $workerPurchase->status;
            $product = $workerPurchase->product()->first();
            // Validar transición de estado
            if ($oldStatus == 2) {
                throw new \Exception('No se puede modificar una compra cancelada');
            }

            // Actualizar estado
            $workerPurchase->status = $validatedData['status'];
            $workerPurchase->user_id = $userId;
            $workerPurchase->save();

            // Manejo de inventario solo para estado Confirmado (1)
            if ($validatedData['status'] == 1) {
                $this->processInventory($workerPurchase);
                $this->registerFinancialTransaction($workerPurchase);
                $notification = new Notification();
                    $notification->professional_id = $workerPurchase->professional_id;
                    $notification->tittle = 'Solicitud de compra aprobada';
                    $notification->description = 'Compra del producto ' . $product->name. ' aprobada';
                    $notification->type = 'Barbero';
                    $workerPurchase->branch->notifications()->save($notification);

                    $trace = [
                        'branch' => $workerPurchase->branch->name,
                        'cashier' => $userName,
                        'client' => '',
                        'amount' => $workerPurchase->total,
                        'operation' => 'Aprobada solicitud de compra de producto',
                        'description' => $workerPurchase->professional->name,
                        'details' => 'Solicitud de compra de '. $workerPurchase->cant . ' ' . $product->name. ' aprobada correctamente',
                    ];
                    
                    // Guardar traza usando el servicio de trazas
                    $this->traceService->store($trace);     

            }else if($validatedData['status'] == 2) {
                $notification = new Notification();
                    $notification->professional_id = $workerPurchase->professional_id;
                    $notification->tittle = 'Solicitud de compra denegada';
                    $notification->description = 'Compra del producto ' . $product->name. ' denegada';
                    $notification->type = 'Barbero';
                    $workerPurchase->branch->notifications()->save($notification);

                    $trace = [
                        'branch' => $workerPurchase->branch->name,
                        'cashier' => $userName,
                        'client' => '',
                        'amount' => $workerPurchase->total,
                        'operation' => 'Denegada solicitud de compra de producto',
                        'description' => $workerPurchase->professional->name,
                        'details' => 'Solicitud de compra de '. $workerPurchase->cant . ' ' . $product->name. ' denegada correctamente',
                    ];
                    
                    // Guardar traza usando el servicio de trazas
                    $this->traceService->store($trace); 
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar estado: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function processInventory($workerPurchase)
    {
        $productStore = ProductStore::where('product_id', $workerPurchase->product_id)->whereHas('store.branches', function ($query) use ($workerPurchase) {
            $query->where('branches.id', $workerPurchase->branch_id);
        })->first();
        if (!$productStore) {
            throw new \Exception('No se encontró el inventario para este producto en la sucursal');
        }

        if ($productStore->product_exit < $workerPurchase->cant) {
            throw new \Exception('No hay suficiente stock disponible');
        }

        // Actualizar inventario
        $productStore->product_exit -= $workerPurchase->cant;
        $productStore->save();

        $this->actualizarProductExit($productStore, $workerPurchase->branch_id);

        Log::info('Inventario actualizado por compra confirmada', [
            'worker_purchase_id' => $workerPurchase->id,
            'product_store_id' => $productStore->id,
            'cant_deducted' => $workerPurchase->cant
        ]);
    }

    protected function registerFinancialTransaction(WorkerPurchase $workerPurchase)
    {
        // Obtener el último número de control
        $lastFinance = Finance::orderBy('control', 'desc')->first();
        $control = $lastFinance ? $lastFinance->control + 1 : 1;

        // Crear registro financiero
        $finance = new Finance();
        $finance->control = $control;
        $finance->operation = 'Ingreso';
        $finance->amount = $workerPurchase->total;
        $finance->comment = 'Ingreso por compra de producto por trabajador ' . $workerPurchase->professional->name;
        $finance->branch_id = $workerPurchase->branch_id;
        $finance->type = 'Sucursal';
        $finance->revenue_id = 7; // Asumiendo que 7 es el ID para este tipo de ingreso
        $finance->data = Carbon::now();
        $finance->file = '';
        // $finance->car_id = null; // No necesario para compras de trabajadores
        $finance->save();

        Log::info('Registro financiero creado', [
            'finance_id' => $finance->id,
            'worker_purchase_id' => $workerPurchase->id,
            'amount' => $workerPurchase->total
        ]);
    }

    protected function revertInventory(WorkerPurchase $workerPurchase)
    {
        // Buscar el product_store correspondiente
        $productStore = ProductStore::where('product_id', $workerPurchase->product_id)
            ->whereHas('store', function ($q) use ($workerPurchase) {
                $q->where('branch_id', $workerPurchase->branch_id);
            })
            ->first();

        if ($productStore) {
            $productStore->product_exit += $workerPurchase->cant;
            $productStore->save();

            Log::info('Inventario revertido por cancelación', [
                'worker_purchase_id' => $workerPurchase->id,
                'product_store_id' => $productStore->id,
                'cant_added' => $workerPurchase->cant
            ]);
        }
    }

    public function storeBulk(Request $request)
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validate([
                'branch_id' => 'required|integer|exists:branches,id',
                'professional_id' => 'required|integer|exists:professionals,id',
                'products' => 'required|array|min:1',
                'products.*.id' => 'required|integer|exists:product_store,id',
                'products.*.cant' => 'required|integer|min:1'
            ]);

            $workerPurchases = [];
            $branch = Branch::where('id', $validatedData['branch_id'])->first();
            $professional = Professional::where('id', $validatedData['professional_id'])->first();
            // Validar stock primero (preventivo)
            foreach ($validatedData['products'] as $item) {
                $productStore = ProductStore::find($item['id']);
                if ($productStore->product_exit < $item['cant']) {
                    throw new \Exception("No hay suficiente stock para el producto ID: {$productStore->product_id}");
                }
            }

            // Procesar cada producto
            foreach ($validatedData['products'] as $item) {
                $productStore = ProductStore::with('product')->find($item['id']);
                $product = $productStore->product;

                // Calcular valores
                $worker_discount = $product->worker_discount ?? 10;
                $discounted_price = $product->sale_price * (1 - ($worker_discount / 100));
                $total = $discounted_price * $item['cant'];
                $percent_wint = ($discounted_price - $product->purchase_price) * $item['cant'];

                $workerPurchase = new WorkerPurchase();
                $workerPurchase->branch_id = $validatedData['branch_id'];
                $workerPurchase->professional_id = $validatedData['professional_id'];
                $workerPurchase->product_id = $product->id;
                $workerPurchase->data = Carbon::now();
                $workerPurchase->price = $product->sale_price;
                $workerPurchase->discount = $worker_discount;
                $workerPurchase->cant = $item['cant'];
                $workerPurchase->total = $total;
                $workerPurchase->percent_wint = $percent_wint;
                $workerPurchase->status = 0; // Pendiente por defecto
                $workerPurchase->save();

                $notification = new Notification();
                $notification->professional_id = $validatedData['professional_id'];
                $notification->tittle = 'Solicitud de Compra de producto';
                $notification->description = 'Profesional ' . $professional->name . ' solicita comprar' . $item['cant'] . $product->name;
                $notification->type = 'Cajera';
                $branch->notifications()->save($notification);

                $workerPurchases[] = $workerPurchase;
            }

            // Registrar en logs
            Log::info('Compra múltiple creada', [
                'professional_id' => $validatedData['professional_id'],
                'branch_id' => $validatedData['branch_id'],
                'total_products' => count($workerPurchases),
                'total_amount' => array_sum(array_column($workerPurchases, 'total'))
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compra múltiple registrada correctamente',
                'data' => $workerPurchases,
                'total' => array_sum(array_column($workerPurchases, 'total'))
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en storeBulk: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByDateAndBranch(Request $request)
    {
        try {
            $validated = $request->validate([
                'branch_id' => 'required|integer|exists:branches,id',
                'data' => 'nullable|date_format:Y-m-d'
            ]);

            $data = $validated['data'] ?? Carbon::now()->format('Y-m-d');

            $purchases = WorkerPurchase::with(['professional:id,name,image_url', 'product:id,name,image_product'])
                ->where('branch_id', $validated['branch_id'])
                ->whereDate('data', $data)
                ->get()
                ->map(function ($purchase) {
                    return [
                        'id' => $purchase->id,
                        'productName' => $purchase->product->name,
                        'productImage' => $purchase->product->image_product,
                        'discount' => $purchase->discount,
                        'cant' => $purchase->cant,
                        'total' => $purchase->total,
                        'data' => $purchase->data,
                        'professionalName' => $purchase->professional->name,
                        'professionalImage' => $purchase->professional->image_url,
                        'status' => $purchase->status
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $purchases,
                'count' => $purchases->count(),
                'totalAmount' => $purchases->sum('total')
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting worker purchases: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener las compras de trabajadores'
            ], 500);
        }
    }
}
