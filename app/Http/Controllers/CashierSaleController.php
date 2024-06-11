<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashierSale;
use App\Models\Finance;
use App\Models\ProductStore;
use App\Models\Professional;
use App\Services\TraceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashierSaleController extends Controller
{
    
    private TraceService $traceService;

    public function __construct(TraceService $traceService)
    {
        $this->traceService = $traceService;
    }

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
     * Store a newly created resource in storage.
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
            
            $productStore = ProductStore::where('id', $validatedData['product_store_id'])->first();
                $product = $productStore->product()->first();
                $sale_price = $product->sale_price;
                $percent_wint = $sale_price - $product->purchase_price;
                    
            $cashierSale = new CashierSale();
            $cashierSale->branch_id = $validatedData['branch_id'];
            $cashierSale->professional_id = $validatedData['professional_id'];
            $cashierSale->product_store_id = $validatedData['product_store_id'];
            $cashierSale->data = Carbon::now();
            $cashierSale->price = $sale_price * $validatedData['cant'];
            $cashierSale->cant = $validatedData['cant'];
            $cashierSale->percent_wint = $percent_wint * $validatedData['cant'];
            $cashierSale->save();

            $productStore->product_quantity = 1;
                $productStore->product_exit = $productStore->product_exit - $validatedData['cant'];
                $productStore->save();

                $trace = [
                    'branch' => $branch->name,
                    'cashier' => $request->nameProfessional,
                    'client' => '',
                    'amount' => $sale_price * $validatedData['cant'],
                    'operation' => 'Venta de Productos',
                    'details' => 'Vende producto: '.$product->name,
                    'description' => 'Cantidad vendida'. $validatedData['cant'],
                ];
                $this->traceService->store($trace);
                
                //$professional = Professional::find($validatedData['professional_id']);

            
            DB::commit();
            return response()->json($cashierSale, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear la venta de caja.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'branch_id' => 'required|integer',
                'professional_id' => 'required|integer'
            ]);
            $sales = [];
            
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
                    'image_product' => $product['image_product']
                ];
            }
    
            return response()->json(['sales' => $sales], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage().   'Error al crear la venta de caja.'], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        //
    }
    

    /**
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'id' => 'required|numeric'
            ]);
            $cashierSale = CashierSale::findOrFail($validatedData['id']);
            $cashierSale->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar la venta de caja.'], 500);
        }
    }
}
