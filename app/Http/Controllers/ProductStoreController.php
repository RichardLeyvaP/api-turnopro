<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductStore;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductStoreController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Entra a buscar los almacenes con los productos pertenecientes en el");
            $productStore = ProductStore::with('product', 'store')->where('product_exit', '>', 0)->get()->map(function ($query){
                return [
                    'id' => $query->id,
                    'product_exit' => $query->product_exit,
                    'product_id' => $query->product_id,
                    'store_id' => $query->store_id,
                    'name' => $query->product->name,
                    'reference' => $query->product->reference,
                    'code' => $query->product->code,
                    'status_product' => $query->product->status_product,
                    'sale_price' => $query->product->sale_price,
                    'purchase_price' => $query->product->purchase_price,
                    'image_product' => $query->product->image_product,
                    'direccionStore' => $query->store->address,
                    'storetReference' => $query->store->reference
                ];
            });
            return response()->json(['products' => $productStore], 200);
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
                //'number_notification' => 'nullable|numeric'
            ]);
            $product = Product::find($data['product_id']);
            $store = Store::find($data['store_id']);
            $productstore = $store->products()->wherePivot('product_id', $product->id)->first();
            if ($productstore) {
                $cuantity = $productstore->pivot->product_exit + $data['product_quantity'];
                $store->products()->updateExistingPivot($product->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$cuantity]);
            }
            else {
                $store->products()->attach($product->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$data['product_quantity']]);
            }
            return response()->json(['msg' => 'Producto asignado correctamente al almacén'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' =>$th->getMessage().'Error al asignar el producto a este almacén'], 500);
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

    public function category_products(Request $request)
    {
        try {
            $data = $request->validate([
               'id' => 'required|numeric',
               'branch_id' => 'required|numeric'
           ]);
            $productStores = ProductStore::whereHas('product', function ($query) use ($data){
               $query->where('product_category_id', $data['id']);
           })->whereHas('store.branches', function ($query) use ($data){              
                   $query->where('branches.id', $data['branch_id']);
           })->where('product_exit', '>', 0)->get();
           $productsArray = $productStores->map(function ($productStore){
               return [
                   'id' => $productStore->id,
                   'product_exit' => $productStore->product_exit,
                   'product_id' => $productStore->product_id,
                   'name' => $productStore->product->name,
                   'reference' => $productStore->product->reference,
                   'code' => $productStore->product->code,
                   'description' => $productStore->product->description,
                   'status_product' => $productStore->product->status_product,
                   'purchase_price' => $productStore->product->purchase_price,
                   'sale_price' => $productStore->product->sale_price,
                   'image_product' => $productStore->product->image_product
               ];
           });
           return response()->json(['category_products' => $productsArray], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => "Error al mostrar la categoría de producto"], 500);
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
                //'number_notification' => 'nullable|numeric'
            ]);
            $product = Product::find($data['product_id']);
            $store = Store::find($data['store_id']);            
            $store->products()->updateExistingPivot($product->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$data['product_quantity']]);     
            return response()->json(['msg' => 'Asignación actualizada correctamente al almacén'], 200);
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
                'store_id' => 'required|numeric'
            ]);
            $product = Product::find($data['product_id']);
            $store = Store::find($data['store_id']);
                $store->products()->updateExistingPivot($product->id,['product_exit'=>0]);         
            return response()->json(['msg' => 'Operación realizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => 'Error al desasociar el producto de este almacén'], 500);
        }
    }
    public function move_product_store(Request $request){
        Log::info("Mover productos de Producto de un almacén o otro");
        Log::info($request);
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'product_id' => 'required|numeric',
                'store_id' => 'required|numeric',
                'product_quantity' => 'required|numeric'
            ]);
            $productstore = ProductStore::find($data['id']);
            $productexist = Product::find($productstore['product_id']);
            $storeexist = Store::find($productstore['store_id']);
            
            if ($productstore['product_exit']<$data['product_quantity']) {
                return response()->json(['msg' => 'Error al mover el producto a este almacén, la cantidad excede la existente'], 500);
            }
            else {
                $productexist->stores()->updateExistingPivot($storeexist->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$productstore['product_exit']-$data['product_quantity']]);  
            }
            $producstorenew = ProductStore::where('product_id', $data['product_id'])->where('store_id', $data['store_id'])->first();
            $product = Product::find($data['product_id']);
            $store = Store::find($data['store_id']);
            
            if ($producstorenew) {
            $product->stores()->updateExistingPivot($store->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$data['product_quantity']+$producstorenew['product_exit']]);
            }
            else {
                $product->stores()->attach($store->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$data['product_quantity']]);
            }
            return response()->json(['msg' => 'Producto asignado correctamente al almacén'], 200);
        } catch (\Throwable $th) {
        return response()->json(['msg' => 'Error al mover el producto a este almacén'], 500);
        } 
    }
}
