<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductStore;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductStoreController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Entra a buscar los almacenes con los productos pertenecientes en el");
            return response()->json(['stores' => Store::with('products')->get()], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => "Error al mostrar los productos"], 500);
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
                //'product_exit' => 'required|numeric',
                'number_notification' => 'nullable|numeric'
            ]);
            $product = Product::find($data['product_id']);
            $store = Store::find($data['store_id']);
                
            $productstore = ProductStore::where('product_id', $data['product_id'])->where
            ('store_id', $data['store_id'])->first();
            if ($productstore != null) {
                $existencia = $data['product_quantity'] + $productstore['product_exit'];
                $product->stores()->updateExistingPivot($store->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$existencia,'number_notification'=>$data['number_notification']]);
            }
            else {
                $product->stores()->attach($store->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$data['product_quantity'],'number_notification'=>$data['number_notification']]);
            }
            return response()->json(['msg' => 'Producto asignado correctamente al almacén'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' =>'Error al asignar el producto a este almacén'], 500);
        }
    }

    public function show(Request $request)
    {
        try {             
            Log::info( "Entra a buscar los productos de un almacén");
            $data = $request->validate([
                'store_id' => 'numeric',
                'product_id' => 'numeric'
            ]);
            if ($data['store_id'] && $data['product_id'] == null) {
                return response()->json(['stores' => Store::find($data['store_id'])->products], 200);
            }
            if ($data['product_id'] && $data['store_id'] == null) {
                return response()->json(['products' => Product::find($data['product_id'])->stores],200); 
            } else {
                return response()->json(['stores' => Product::find($data['product_id'])->stores, 'products' => Store::find($data['store_id'])->products],200); 
            }
            
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => "Error al mostrar los productos"], 500);
        }
    }

    public function update(Request $request)
    {
        Log::info("Actualizar asignacion de Producto a un almacén");
        Log::info($request);
        try {
            $data = $request->validate([
                'product_id' => 'required|numeric',
                'store_id' => 'required|numeric',
                'product_quantity' => 'required|numeric',
                //'product_exit' => 'required|numeric',
                'number_notification' => 'nullable|numeric'
            ]);
            $product = Product::find($data['product_id']);
            $store = Store::find($data['store_id']);
            $productstore = ProductStore::where('product_id', $data['product_id'])->where
            ('store_id', $data['store_id'])->first();
            if ($data['product_quantity']<$productstore['product_quantity']) {
                $data['product_exit'] = ($productstore['product_quantity']-$data['product_quantity'])+$productstore['product_exit'];
            }
            if ($data['product_quantity']>$productstore['product_quantity']) {
                $data['product_exit'] = $productstore['product_exit']-($data['product_quantity']-$productstore['product_quantity']);
                if ($data['product_exit']<0) {
                return response()->json(['msg' => 'Error al actualizar el producto a este almacén, la cantidad excede lo existente'], 500);
                }
            }   
            $data['product_exit']=$productstore['product_exit'];       
            $product->stores()->updateExistingPivot($store->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$data['product_exit'],'number_notification'=>$data['number_notification']]);
            
            return response()->json(['msg' => 'Producto actualizado correctamente al almacén'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => 'Error al actualizar el producto a este almacén'], 500);
        }
    }

    public function destroy(Request $request)
    {
        Log::info("Eliminar asignacion de Producto a un almacén");
        Log::info($request);
        try {
            $data = $request->validate([
                'product_id' => 'required|numeric',
                'store_id' => 'required|numeric',
                'product_quantity' => 'required|numeric',
                //'product_exit' => 'required|numeric',
                'number_notification' => 'nullable|numeric'
            ]);
            $product = Product::find($data['product_id']);
            $store = Store::find($data['store_id']);
             $productstore = ProductStore::where('product_id', $data['product_id'])->where
            ('store_id', $data['store_id'])->first();
            if ($data['product_quantity']>$productstore['product_exit']) {
                return response()->json(['msg' => 'La cantidad del producto excede lo existente'], 500);
            }
            elseif ($data['product_quantity']==$productstore['product_exit']) {
                $product->stores()->detach($store->id); 
            }
            else {
                $data['product_exit'] = $productstore['product_exit']-$data['product_quantity'];
                $product->stores()->updateExistingPivot($store->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$data['product_exit'],'number_notification'=>$data['number_notification']]);
            }           
            return response()->json(['msg' => 'Operación realizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => 'Error al desasociar el producto de este almacén'], 500);
        }
    }
}
