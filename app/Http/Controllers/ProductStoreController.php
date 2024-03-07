<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductStore;
use App\Models\Store;
use App\Traits\ProductExitTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductStoreController extends Controller
{
    use ProductExitTrait;
    
    public function index()
    {
        try {             
            Log::info( "Entra a buscar los almacenes con los productos pertenecientes en el");
            $productStore = ProductStore::with('product', 'store')->where('product_exit', '>', 0)->get()->map(function ($query){
                return [
                    'id' => $query->id,
                    //'product_quantity' => $query->product_quantity,
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
            return response()->json(['products' => $productStore], 200, [], JSON_NUMERIC_CHECK);
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
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);         
            Log::info( "Entra a buscar los almacenes con los productos pertenecientes en el");
            $productStore = ProductStore::whereHas('store.branches', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            })->with('product', 'store')->where('product_exit', '>', 0)->get()->map(function ($query){
                return [
                    'id' => $query->id,
                    //'product_quantity' => $query->product_quantity,
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
            return response()->json(['products' => $productStore], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los productos"], 500);
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
           return response()->json(['category_products' => $productsArray], 200, [], JSON_NUMERIC_CHECK);
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
        Log::info("Mover productos de un almacén o otro");
        Log::info($request);
        try {           
            $data = $request->validate([
                'product_id' => 'required|numeric',
                'store_id' => 'required|numeric',
                'store_idM' => 'required|numeric',
                'product_quantity' => 'required|numeric'
            ]);
            $productexist = Product::find($data['product_id']);
            $storeexist = Store::find($data['store_id']);
            $store = Store::find($data['store_idM']);
            Log::info($productexist);
            $productstoreexist = $storeexist->products()->wherePivot('product_id', $productexist->id)->first();
            $storeexist->products()->updateExistingPivot($productexist->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$productstoreexist->pivot->product_exit-$data['product_quantity']]);
            $productstore = $store->products()->wherePivot('product_id', $productexist->id)->first();
            if ($productstore) {
                $store->products()->updateExistingPivot($productexist->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$productstore->pivot->product_exit + $data['product_quantity']]);
            }
            else {
                $store->products()->attach($productexist->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$data['product_quantity']]);
            }
            //todo pendiente para revisar importante
           // $this->actualizarProductExit($productexist->id, $storeexist->id);
            return response()->json(['msg' => 'Producto movido correctamente al almacén'], 200);
        } catch (\Throwable $th) {
        return response()->json(['msg' => $th->getMessage().'Error al mover el producto a este almacén'], 500);
        } 
    }
}
