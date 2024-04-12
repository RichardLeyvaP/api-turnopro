<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\MovementProduct;
use App\Models\Product;
use App\Models\ProductStore;
use App\Models\Store;
use App\Traits\ProductExitTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductStoreController extends Controller
{
    use ProductExitTrait;

    public function index()
    {
        try {
            Log::info("Entra a buscar los almacenes con los productos pertenecientes en el");
            $productStore = ProductStore::with('product', 'store')->where('product_exit', '>', 0)->get()->map(function ($query) {
                return [
                    'id' => $query->id,
                    //'product_quantity' => $query->product_quantity,
                    'product_exit' => $query->product_exit,
                    'product_id' => $query->product_id,
                    'store_id' => $query->store_id,
                    'stock_depletion' => $query->stock_depletion,
                    'name' => $query->product->name,
                    'reference' => $query->product->reference,
                    'code' => $query->product->code,
                    'status_product' => $query->product->status_product,
                    'sale_price' => $query->product->sale_price,
                    'purchase_price' => $query->product->purchase_price,
                    'image_product' => $query->product->image_product,
                    'direccionStore' => $query->store->address,
                    'storetReference' => $query->store->reference,                    
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
                'stock_depletion' => 'required|numeric',
                //'enrollment_id' => 'nullable'
                //'product_exit' => 'required|numeric',
                //'number_notification' => 'nullable|numeric'
            ]);
            $product = Product::find($data['product_id']);
            $store = Store::find($data['store_id']);
            //Log::info($request->has('branch_id'));
            $productstore = $store->products()->wherePivot('product_id', $product->id)->first();
            if($productstore){
                //return $productstore->pivot;
                //$productstore->product_exit += $data['product_quantity'];
                //$productstore->product_quantity = $data['product_quantity'];
                //$productstore->save();
                $existencia = $data['product_quantity'] + $productstore->pivot['product_exit'];
                $product->stores()->updateExistingPivot($store->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$existencia, 'stock_depletion' => $data['stock_depletion']]);
            }
            else
            {
                $store->products()->attach($product->id, ['product_quantity' => $data['product_quantity'], 'product_exit' => $data['product_quantity'], 'stock_depletion' => $data['stock_depletion']]);
            }
            //
            /*if($request->has('branch_id') && $data['branch_id'] != null){
            $productStoreBranch = $store->products()
                ->wherePivot('product_id', $product->id)
                ->wherePivot('branch_id', $data['branch_id'])
                ->first();
            if ($productStoreBranch) {
                Log::info('tiene valor');
                
            } else {
                $store->products()->attach($product->id, ['product_quantity' => $data['product_quantity'], 'product_exit' => $data['product_quantity'], 'branch_id' => $data['branch_id']]);
            }
            }
            else{
                $productStoreAcademy = $store->products()
                ->wherePivot('product_id', $product->id)
                ->wherePivot('enrollment_id', $data['enrollment_id'])
                ->first();
            if ($productStoreAcademy) {
                Log::info('tiene valor');
                $productstore = ProductStore::where('id', $productStoreAcademy->pivot->id)->first();
                $productstore->product_exit += $data['product_quantity'];
                $productstore->product_quantity = $data['product_quantity'];
                $productstore->save();
            } else {
                $store->products()->attach($product->id, ['product_quantity' => $data['product_quantity'], 'product_exit' => $data['product_quantity'], 'enrollment_id' => $data['enrollment_id']]);
            } 
            }*/
            return response()->json(['msg' => 'Producto asignado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error interno del sistema'], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            Log::info("Entra a buscar los almacenes con los productos pertenecientes en el");
            $productStore = ProductStore::where('product_exit', '>', 0)->with('product', 'store')->get()->map(function ($query) {
                return [
                    'id' => $query->id,
                    //'product_quantity' => $query->product_quantity,
                    'product_exit' => $query->product_exit,
                    'product_id' => $query->product_id,
                    'store_id' => $query->store_id,
                    'stock_depletion' => $query->stock_depletion,
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
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los productos"], 500);
        }
    }

    public function show_branch(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            Log::info("Entra a buscar los almacenes con los productos pertenecientes en el");
            $productStore = ProductStore::where('branch_id', $data['branch_id'])->where('product_exit', '>', 0)->with('product', 'store')->get()->map(function ($query) {
                return [
                    'id' => $query->id,
                    //'product_quantity' => $query->product_quantity,
                    'product_exit' => $query->product_exit,
                    'product_id' => $query->product_id,
                    'store_id' => $query->store_id,
                    'stock_depletion' => $query->stock_depletion,
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
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los productos"], 500);
        }
    }

    public function academy_show(Request $request)
    {
        try {
            $data = $request->validate([
                'enrollment_id' => 'required|numeric'
            ]);
            Log::info("Entra a buscar los almacenes con los productos pertenecientes en el");
            $productStore = ProductStore::whereHas('store.enrollments', function ($query) use ($data){              
                $query->where('enrollments.id', $data['enrollment_id']);
        })->where('product_exit', '>', 0)->with('product', 'store')->get()->map(function ($query) {
                return [
                    'id' => $query->id,
                    //'product_quantity' => $query->product_quantity,
                    'product_exit' => $query->product_exit,
                    'product_id' => $query->product_id,
                    'store_id' => $query->store_id,
                    'stock_depletion' => $query->stock_depletion,
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
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los productos"], 500);
        }
    }

    public function products_academy_show(Request $request)
    {
        try {
            $data = $request->validate([
                'enrollment_id' => 'required|numeric'
            ]);
            Log::info("Entra a buscar los almacenes con los productos pertenecientes en el");
            $productStore = ProductStore::whereHas('store.enrollments', function ($query) use ($data){              
                $query->where('enrollments.id', $data['enrollment_id']);
        })->where('product_exit', '>', 0)->whereHas('product', function ($query){
                $query->where('status_product', 'En venta');
            })->with('product', 'store')->get()->map(function ($query) {
                return [
                    'id' => $query->id,
                    'product_exit' => $query->product_exit,
                    'name' => $query->product->name.' ('.'Almacén:'.$query->store->address.')'                ];
            });
            return response()->json(['products' => $productStore], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los productos"], 500);
        }
    }

    public function product_show_web(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            Log::info("Entra a buscar los productos de la branch");
            $productStores = ProductStore::whereHas('product', function ($query) use ($data) {
                $query->where('status_product', 'En venta');
            })->whereHas('store.branches', function ($query) use ($data){              
                $query->where('branches.id', $data['branch_id']);
        })->where('product_exit', '>', 0)->get()->map(function ($productStore) {
                return [
                    'id' => $productStore->id,
                    'product_exit' => $productStore->product_exit,
                    'name' => $productStore->product->name.' ('.'Almacén:'.$productStore->store->address.')'
                ];
            });
            return response()->json(['products' => $productStores], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los productos"], 500);
        }
    }
    

    public function product_show_academy_web(Request $request)
    {
        try {
            $data = $request->validate([
                'enrollment_id' => 'required|numeric'
            ]);
            Log::info("Entra a buscar los productos de la academia");
            $productStores = ProductStore::whereHas('product', function ($query) use ($data) {
                $query->where('status_product', 'En venta');
            })->whereHas('store.enrollments', function ($query) use ($data){              
                $query->where('enrollments.id', $data['enrollment_id']);
        })->where('product_exit', '>', 0)->get()->map(function ($productStore) {
                return [
                    'id' => $productStore->id,
                    'name' => $productStore->product->name.' ('.'Almacén:'.$productStore->store->address.')'
                ];
            });
            return response()->json(['products' => $productStores], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los productos"], 500);
        }
    }

    public function category_products(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            /*$productStores = ProductStore::whereHas('product', function ($query) use ($data) {
                $query->where('product_category_id', $data['id'])->where('status_product', 'En venta');
            })->where('branch_id', $data['branch_id'])->where('product_exit', '>', 0)->get();*/
            $productStores = ProductStore::whereHas('product', function ($query) use ($data){
                $query->where('product_category_id', $data['id'])->where('status_product', 'En venta');
            })->whereHas('store.branches', function ($query) use ($data){              
                    $query->where('branches.id', $data['branch_id']);
            })->where('product_exit', '>', 0)->get();
            $productsArray = $productStores->map(function ($productStore) {
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
            return response()->json(['msg' => $th->getMessage()."Error al mostrar la categoría de producto"], 500);
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
                //'branch_id' => 'nullable'//,
                //'enrollment_id' => 'nullable',
                'product_quantity' => 'required|numeric',
                'stock_depletion' => 'required|numeric'
                //'product_exit' => 'required|numeric',
                //'number_notification' => 'nullable|numeric'
            ]);
            $product = Product::find($data['product_id']);
            $store = Store::find($data['store_id']);
            $productstore = $store->products()->wherePivot('product_id', $product->id)->first();
            if($productstore){
                //return $productstore->pivot;
                //$productstore->product_exit += $data['product_quantity'];
                //$productstore->product_quantity = $data['product_quantity'];
                //$productstore->save();
                //$existencia = $data['product_quantity'] + $productstore->pivot['product_exit'];
                $product->stores()->updateExistingPivot($store->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$data['product_quantity'], 'stock_depletion' => $data['stock_depletion']]);
            }
            else
            {
                $store->products()->attach($product->id, ['product_quantity' => $data['product_quantity'], 'product_exit' => $data['product_quantity'], 'stock_depletion' => $data['stock_depletion']]);
            }
            /*if($request->has('branch_id') && $data['branch_id'] != null){
            $productStore = $store->products()
                ->wherePivot('product_id', $product->id)
                ->wherePivot('branch_id', $data['branch_id'])
                ->first();
            if ($productStore) {
                Log::info('tiene valor');
                $productstore = ProductStore::where('id', $productStore->pivot->id)->first();
                $productstore->product_exit = $data['product_quantity'];
                $productstore->product_quantity = $data['product_quantity'];
                $productstore->save();
            }
            }
            else{
                $productStore = $store->products()
                ->wherePivot('product_id', $product->id)
                ->wherePivot('enrollment_id', $data['enrollment_id'])
                ->first();
            if ($productStore) {
                Log::info('tiene valor');
                $productstore = ProductStore::where('id', $productStore->pivot->id)->first();
                $productstore->product_exit = $data['product_quantity'];
                $productstore->product_quantity = $data['product_quantity'];
                $productstore->save();
            }*/
            //}
            return response()->json(['msg' => 'Asignación actualizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error interno del sistema'], 500);
        }
    }

    public function destroy(Request $request)
    {
        Log::info("Eliminar asignacion de Producto a un almacén");
        Log::info($request);
        try {
            $data = $request->validate([
                'product_id' => 'required|numeric',
                'store_id' => 'required|numeric'//,
                //'branch_id' => 'nullable',
                //'enrollment_id' => 'nullable'
            ]);
            $product = Product::find($data['product_id']);
            $store = Store::find($data['store_id']);
            $productstore = $store->products()->wherePivot('product_id', $product->id)->first();
            if($productstore){
                //return $productstore->pivot;
                //$productstore->product_exit += $data['product_quantity'];
                //$productstore->product_quantity = $data['product_quantity'];
                //$productstore->save();
                //$existencia = $data['product_quantity'] + $productstore->pivot['product_exit'];
                $store->products()->updateExistingPivot($product->id,['product_quantity'=>0,'product_exit'=>0]);
            }
            /*if($request->has('branch_id') && $data['branch_id'] != null){
                $productStore = $store->products()
                ->wherePivot('product_id', $product->id)
                ->wherePivot('branch_id', $data['branch_id'])
                ->first();
            if ($productStore) {
                Log::info('tiene valor');
                $productstore = ProductStore::where('id', $productStore->pivot->id)->first();
                $productstore->product_exit = 0;
                $productstore->save();
            }
            }
            else{
                $productStore = $store->products()
                ->wherePivot('product_id', $product->id)
                ->wherePivot('enrollment_id', $data['enrollment_id'])
                ->first();
            if ($productStore) {
                Log::info('tiene valor');
                $productstore = ProductStore::where('id', $productStore->pivot->id)->first();
                $productstore->product_exit = 0;
                $productstore->save();
            }
            }*/
            
            return response()->json(['msg' => 'Operación realizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }
    public function move_product_store(Request $request)
    {
        Log::info("Mover productos de un almacén o otro");
        Log::info($request);
        try {
            $data = $request->validate([
                //'branch_id' => 'required|numeric',
                'product_id' => 'required|numeric',
                'store_id' => 'required|numeric',
                'store_idM' => 'required|numeric',
                //'branch_idM' => 'required|numeric',
                'product_quantity' => 'required|numeric'
            ]);
            //descontar
            $product = Product::find($data['product_id']);
            $store = Store::find($data['store_id']);
            $productstore = $store->products()->wherePivot('product_id', $product->id)->first();
            if($productstore){
                $existencia = $productstore->pivot['product_exit'] - $data['product_quantity'];
                $product->stores()->updateExistingPivot($store->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$existencia]);
            }
            //aumentar
            $storeM = Store::find($data['store_idM']);
            $productstoreM = $storeM->products()->wherePivot('product_id', $product->id)->first();
            if($productstoreM){
                //$existencia = $data['product_quantity'] + $productstoreM->pivot['product_exit'];
                $existencia = $productstoreM->pivot['product_exit'] + $data['product_quantity'];
                $storeM->products()->updateExistingPivot($product->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$existencia]);
            }
            else
            {
                $storeM->products()->attach($product->id, ['product_quantity' => $data['product_quantity'], 'product_exit' => $data['product_quantity']]);
            }
        /*$productstoreE = new ProductStore();
            $productstoreM = new ProductStore();            
            $movementprodct = new MovementProduct();
            $productexist = Product::find($data['product_id']);
            $storeArebajar = Store::find($data['store_id']);
            $storeASumar = Store::find($data['store_idM']);
            $productStoreArebajar = $storeArebajar->products()
                ->wherePivot('product_id', $productexist->id)
                ->wherePivot('branch_id', $data['branch_id'])
                ->first();
                Log::info('productStoreArebajar');
                Log::info($productStoreArebajar);
            if ($productStoreArebajar) {
                Log::info('tiene valor productStoreArebajar');
                $productstoreE = ProductStore::where('id', $productStoreArebajar->pivot->id)->first();
                Log::info('$productstoreE productStoreArebajar');
                Log::info($productstoreE);
                $productstoreE->product_exit = $productstoreE->product_exit - $data['product_quantity'];
                $productstoreE->product_quantity = $data['product_quantity'];
                $productstoreE->save();
            }
            //sumar al nuevo store
            $productStorestoreASumar = $storeASumar->products()
                ->wherePivot('product_id', $productexist->id)
                ->wherePivot('branch_id', $data['branch_idM'])
                ->first();
                Log::info('Producto A sumar productStorestoreASumar');
                Log::info($productStorestoreASumar);
            if ($productStorestoreASumar) {
                Log::info('tiene valor productStorestoreASumar');
                $productstoreM = ProductStore::where('id', $productStorestoreASumar->pivot->id)->first();
                Log::info('$productstoreM productStorestoreASumar');
                Log::info($productstoreM);
                $productstoreM->product_exit = $productstoreM->product_exit + $data['product_quantity'];
                $productstoreM->product_quantity = $data['product_quantity'];
                $productstoreM->save();
            } else {
                Log::info('no existe ese producto en el almacen donde se va a recibir crear la relacion');
                $storeASumar->products()->attach($productexist->id, ['product_quantity' => $data['product_quantity'], 'product_exit' => $data['product_quantity'], 'branch_id' => $data['branch_idM']]);
                Log::info('$productStorestoreASumar creado nuevo producto en el almacen');
                $productStorestoreASumar = $storeASumar->products()
                ->wherePivot('product_id', $productexist->id)
                ->wherePivot('branch_id', $data['branch_idM'])
                ->first();
                Log::info($productStorestoreASumar);
            }*/
            //registro de movimiento de productos
            
            $movementprodct = new MovementProduct();
            $movementprodct->data = Carbon::now();
            $movementprodct->product_id = $data['product_id'];
            //$movementprodct->branch_out_id = $data['branch_id'];
            $movementprodct->store_out_id = $data['store_id'];
            //$movementprodct->branch_int_id = $data['branch_idM'];
            $movementprodct->store_int_id = $data['store_idM'];
            $movementprodct->store_out_exit = $productstore->pivot['product_exit']-$data['product_quantity'];
            $movementprodct->store_int_exit = $productstoreM->pivot['product_exit']+$data['product_quantity'];  
            $movementprodct->cant = $data['product_quantity'];
            $movementprodct->save();
            //todo pendiente para revisar importante
            // $this->actualizarProductExit($productexist->id, $storeexist->id);
            return response()->json(['msg' => 'Producto movido correctamente al almacén'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al mover el producto a este almacén'], 500);
        }
    }

    public function movement_products(Request $request){
        try {
            $data = $request->validate([
                //'branch_id' => 'required|numeric',
                'year' => 'required'
            ]);
            if($request->mounth){
                $movement = MovementProduct::whereYear('data', $data['year'])->whereMonth('data', $request->mounth)->get()->map(function ($query){
                    //$branchOut = Branch::where('id', $query->branch_out_id)->first();
                    $storeOut = Store::where('id', $query->store_out_id)->first();
                    //$branchInt = Branch::where('id', $query->branch_int_id)->first();
                    $storeInt = Store::where('id', $query->store_int_id)->first();
                    $product = Product::where('id', $query->product_id)->first();
                    return [
                        //'branchOut' => $branchOut->name,
                        'storeOut' => $storeOut->address,
                        //'branchInt' => $branchInt->name,
                        'storeInt' => $storeInt->address,
                        'cant' => $query->cant,
                        'data' => $query->data,
                        'nameProduct' => $product->name
                    ];
                })->sortByDesc('data')->values();
            }
            else{
            $movement = MovementProduct::whereYear('data', $data['year'])/*->where(function ($query) use($data){
                $query->orWhere('branch_out_id', $data['branch_id'])->orWhere('branch_int_id', $data['branch_id']);
            })*/->get()->map(function ($query){
                //$branchOut = Branch::where('id', $query->branch_out_id)->first();
                //$branchInt = Branch::where('id', $query->branch_int_id)->first();
                $storeInt = Store::where('id', $query->store_int_id)->first();
                $storeOut = Store::where('id', $query->store_out_id)->first();                
                $product = Product::where('id', $query->product_id)->first();
                return [
                    //'branchOut' => $branchOut->name,
                    'storeOut' => $storeOut->address,
                    //'branchInt' => $branchInt->name,
                    'storeInt' => $storeInt->address,
                    'cant' => $query->cant,
                    'data' => $query->data,
                    'nameProduct' => $product->name
                ];
            })->sortByDesc('data')->values();
        }
            return response()->json(['movimientos' => $movement], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al mover el producto a este almacén'], 500);
        }
    }
}
