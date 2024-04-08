<?php

namespace App\Http\Controllers;

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
                'cant' => 'required|numeric'

            ]);
            Log::info($data);

            $productstore = ProductStore::find($data['id']);
            $productstore->product_quantity = 1;
            $productstore->product_exit = $productstore->product_exit - 1;
            $productstore->save();

            $price = $productstore->product->value('sale_price');

            $productSale = new ProductSale();
            $productSale->product_store_id = $data['id'];
            $productSale->student_id = $data['student_id'];
            $productSale->enrollment_id = $data['enrollment_id'];
            $productSale->cant = $data['cant'];
            $productSale->price = $price;
            $productSale->data = Carbon::now();
            $productSale->save();
            
             return response()->json(['msg' =>'Producto asigando correctamente',], 200);
        } catch (\Throwable $th) {
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
                'enrollment_id' => 'required|numeric'
            ]);
            Log::info("Entra a buscar los almacenes con los productos pertenecientes en el");
            $productStudent = ProductSale::where('enrollment_id', $data['enrollment_id'])->whereHas('student.courses', function ($query) use ($data){
                $query->where('course_id', $data['course_id']);
            })->with(['productStore.product', 'productStore.store'])->get()->map(function ($query) {
                return [
                    'id' => $query->id,
                    'product_id' => $query->productstore->product_id,
                    'store_id' => $query->productstore->store_id,
                    'nameProduct' => $query->productstore->product->name,
                    'price' => $query->price,
                    'image_product' => $query->productstore->product->image_product,
                    'nameStudent' => $query->student->name.' '.$query->student->surname.' '.$query->student->second_surname,
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
        Log::info("Compra de Productos");
        try {
            $data = $request->validate([
                'id' => 'required|numeric',

            ]);
            Log::info($data);
            $productSale = ProductSale::find($data['id']);
            $productstore = ProductStore::where('id', $productSale->product_store_id)->first();
            $productstore->product_quantity = 1;
            $productstore->product_exit = $productstore->product_exit + 1;
            $productstore->save();

            $productSale->delete();
            
             return response()->json(['msg' =>'Producto asigando correctamente',], 200);
        } catch (\Throwable $th) {
        return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }
}
