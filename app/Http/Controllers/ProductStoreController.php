<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductStoreController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Entra a buscar productos por almacenes");
            return response()->json(['stores' => Store::all()], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()/*"Error al mostrar los productos"*/], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info("Asignar Productos a un almacen");
        Log::info($request);
        try {
            $data = $request->validate([
                'product_id' => 'required|numeric',
                'store_id' => 'required|numeric',
                'product_quantity' => 'required|numeric',
                'product_exit' => 'required|numeric',
                'number_notification' => 'nullable|numeric'
            ]);
            $product = Product::find($data['product_id']);
            $store = Store::find($data['store_id']);
            $product->stores()->attach($store->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$data['product_exit'],'number_notification'=>$data['number_notification']]);
            
            return response()->json(['msg' => 'Producto asignado correctamente al almacen '], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => 'Error al asignar el producto a este almacen'], 500);
        }
    }

    public function show(Request $request)
    {
        try {             
            Log::info( "Entra a buscar los productos de un almacén");
            $data = $request->validate([
                'store_id' => 'required|numeric'
            ]);
            return response()->json(['stores' => Store::find($data['store_id'])->products], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => "Error al mostrar los productos"], 500);
        }
    }

    public function update(Request $request)
    {
        Log::info("Actualizar asignacion de Producto a un almacen");
        Log::info($request);
        try {
            $data = $request->validate([
                'product_id' => 'required|numeric',
                'store_id' => 'required|numeric',
                'product_quantity' => 'required|numeric',
                'product_exit' => 'required|numeric',
                'number_notification' => 'nullable|numeric'
            ]);
            $product = Product::find($data['product_id']);
            $store = Store::find($data['store_id']);
            $product->stores()->updateExistingPivot($store->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$data['product_exit'],'number_notification'=>$data['number_notification']]);
            
            return response()->json(['msg' => 'Producto actualizado correctamente al almacen '], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => 'Error al asignar el producto a este almacen'], 500);
        }
    }

    public function destroy(Request $request)
    {
        Log::info("Eliminar asignacion de Producto a un almacen");
        Log::info($request);
        try {
            $data = $request->validate([
                'product_id' => 'required|numeric',
                'store_id' => 'required|numeric'
            ]);
            $product = Product::find($data['product_id']);
            $store = Store::find($data['store_id']);
            $product->stores()->detach($store->id);
            
            return response()->json(['msg' => 'Producto desasociado correctamente del almacen '], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => 'Error al desasociar el producto de este almacen'], 500);
        }
    }
}
