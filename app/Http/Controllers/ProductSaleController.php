<?php

namespace App\Http\Controllers;

use App\Models\Finance;
use App\Models\ProductSale;
use App\Models\ProductStore;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ProductSaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info("Compra de Productos");
        try {
            $data = $request->validate([
                'enrollment_id' => 'required|numeric',
                'id' => 'required|numeric',
                'student_id' => 'required|numeric',
                'cant' => 'required|numeric',
                'course_id' => 'required|numeric'

            ]);
            Log::info($data);
            $productstore = ProductStore::find($data['id']);
            $productstore->product_quantity = $data['cant'];
            $productstore->product_exit = $productstore->product_exit - $data['cant'];
            $productstore->save();

            $price = $productstore->product->value('sale_price');
            Log::info($price);
            $productSale = ProductSale::where('enrollment_id', $data['enrollment_id'])->where('product_store_id', $data['id'])->where('course_id', $data['course_id'])->where('student_id', $data['student_id'])->whereDate('data', Carbon::now())->first();
            
            if ($productSale) {
                Log::info('Existe');
                Log::info($productSale);
                $productSale->cant = $productSale->cant + $data['cant'];
                $productSale->price = $productSale->price + $price*$data['cant'];
                $productSale->save();
            }
            else{
                $productSale = new ProductSale();
                $productSale->product_store_id = $data['id'];
                $productSale->student_id = $data['student_id'];
                $productSale->enrollment_id = $data['enrollment_id'];
                $productSale->course_id = $data['course_id'];
                $productSale->cant = $data['cant'];
                $productSale->price = $price*$data['cant'];
                $productSale->data = Carbon::now();
                $productSale->save();
            }
            //$productSale = new ProductSale();
            //agregar a finanzas a ingresos
            /*$finance = Finance::where('enrollment_id', $data['enrollment_id'])->where('revenue_id', 4)->whereDate('data', Carbon::now())->first();
            if($finance){
                Log::info('existe');
                $finance->operation = 'Ingreso';
                $finance->amount = $finance->amount + $price*$data['cant'];
                $finance->comment = 'Venta de Productos';
                $finance->enrollment_id = $data['enrollment_id'];
                $finance->type = 'Academia';
                $finance->revenue_id = 4;
                $finance->data = Carbon::now();                
                $finance->file = '';
                $finance->save();
            }
            else{
                Log::info('no existe');*/
                $finance = Finance::orderBy('control', 'desc')->first();
                if($finance)
                    {
                        $control = $finance->control+1;
                    }
                    else {
                        $control = 1;
                    }
                $finance = new Finance();
                $finance->control = $control;
                $finance->operation = 'Ingreso';
                $finance->amount = $price*$data['cant'];
                $finance->comment = 'Venta de Productos';
                $finance->enrollment_id = $data['enrollment_id'];
                $finance->type = 'Academia';
                $finance->revenue_id = 4;
                $finance->data = Carbon::now();                
                $finance->file = '';
                $finance->save();
            //}
            
             return response()->json(['msg' =>'Producto asigando correctamente',], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'course_id' => 'required|numeric',
                'enrollment_id' => 'required|numeric',
                'student_id' => 'required|numeric'
            ]);
            Log::info("Entra a buscar los almacenes con los productos pertenecientes en el");
            $productStudent = ProductSale::where('course_id', $data['course_id'])->where('enrollment_id', $data['enrollment_id'])->where('student_id', $data['student_id'])->get()->map(function ($query) {
                return [
                    'id' => $query->id,
                    'product_id' => $query->productstore->product_id,
                    'store_id' => $query->productstore->store_id,
                    'nameProduct' => $query->productstore->product->name,
                    'price' => $query->price,
                    'cant' => $query->cant,
                    'image_product' => $query->productstore->product->image_product,
                    //'nameStudent' => $query->student->name.' '.$query->student->surname.' '.$query->student->second_surname,
                    'student_id' => $query->student_id,
                    'data' => $query->data
                ];
            })->sortByDesc('data')->values();
            return response()->json(['productsales' => $productStudent], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los productos"], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductSale $productSale)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        Log::info("Eliminacion de compra de Productos");
        try {
            $data = $request->validate([
                'id' => 'required|numeric',

            ]);
            Log::info($data);
            $productSale = ProductSale::find($data['id']);
            $productstore = ProductStore::where('id', $productSale->product_store_id)->first();
            $productstore->product_quantity = $productSale->cant;
            $productstore->product_exit = $productstore->product_exit + $productSale->cant;
            $productstore->save();
            $finance = Finance::where('enrollment_id', $productSale->enrollment_id)->whereDate('data', $productSale->data)->orderByDesc('control')->first();
            if($finance){
                Log::info('existe');
                $temp = $finance->amount - $productSale->price;
                if($temp <= 0){
                    $finance->delete();
                }else{
                    $finance->amount = $temp;
                    $finance->save();
                }
                
            }
            $productSale->delete();
            
             return response()->json(['msg' =>'Producto desasigando correctamente',], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }
}
