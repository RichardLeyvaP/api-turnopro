<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\MovementProduct;
use App\Models\Product;
use App\Models\ProductStore;
use App\Models\Professional;
use App\Models\Store;
use App\Traits\ProductExitTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    public function showStoresProductsold(Request $request)
    {
    try {
        Log::info("Entra a buscar los stores y productos");
        $stores = Store::all('id', 'address', 'reference');
        $products = Product::all('id', 'name', 'image_product');
        return response()->json([
            'stores' => $stores,
            'products' => $products
        ], 200, [], JSON_NUMERIC_CHECK);
    } catch (\Throwable $th) {
        Log::error($th);
        return response()->json(['msg' => "Error al mostrar los stores y productos"], 500);
    }
    }

    public function showStoresProducts(Request $request)
    {
    try {
        Log::info("Entra a buscar los stores y productos");
        $data = $request->validate([
            'business_id' => 'required|numeric',
            'branch_id' => 'nullable|numeric'
        ]);
        if ($data['branch_id'] != 0) {
            $stores = Store::whereHas('branches', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            })->get()->select('id', 'address', 'reference');
        }else {
            $stores = Store::all('id', 'address', 'reference');
        }
        $products = Product::all('id', 'name', 'image_product');
        $branches = Branch::where('business_id', $data['business_id'])->select('id', 'name', 'image_data', 'address')->get();
        return response()->json([
            'stores' => $stores,
            'products' => $products,
            'branches' => $branches
        ], 200, [], JSON_NUMERIC_CHECK);
    } catch (\Throwable $th) {
        Log::error($th);
        return response()->json(['msg' => "Error al mostrar los stores y productos"], 500);
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
                'branch_id' => 'nullable|numeric'
            ]);
            Log::info("Entra a buscar los almacenes con los productos pertenecientes en el de una branch");
            if ($data['branch_id'] != 0) {
                Log::info("No es Administrador");
                $productStore = ProductStore::whereHas('store.branches', function ($query) use ($data){
                    $query->where('branch_id', $data['branch_id']);
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
            }else {
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
            }
            
            return response()->json(['products' => $productStore], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los productos"], 500);
        }
    }

    public function show_branch_state(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'nullable|numeric'
            ]);
            Log::info("Entra a buscar los almacenes con los productos pertenecientes en el de una branch");
            if ($data['branch_id'] != 0) {
                Log::info("No es Administrador");
                $productStore = ProductStore::whereHas('store.branches', function ($query) use ($data){
                    $query->where('branch_id', $data['branch_id']);
                })->where('product_exit', '>', 0)->whereHas('product', function ($query) {
                    $query->where('status_product', 'no en venta'); // Filtro para status_product
                })->with('product', 'store')->get()->map(function ($query) {
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
                        'quantity' => 0
                    ];
                });
            }else {
                $productStore = ProductStore::with('product', 'store')->where('product_exit', '>', 0)->whereHas('product', function ($query) {
                    $query->where('status_product', 'no en venta'); // Filtro para status_product
                })->get()->map(function ($query) {
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
                        'quantity' => 0                   
                    ];
                });
            }
            
            return response()->json(['products' => $productStore], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los productos"], 500);
        }
    }

    /*public function subtractProductExit(Request $request)
    {
        // Validar la solicitud
        $request->validate([
            'id' => 'required|integer|exists:product_store,id',
            'quantity' => 'required|integer|min:1', // La cantidad debe ser un entero positivo
        ]);

        // Obtener la cantidad a restar
        $quantityToSubtract = $request->input('quantity');
        $id = $request->input('id');

        // Iniciar una transacción de base de datos
        DB::beginTransaction();

        try {
            // Obtener el registro de ProductStore por su ID
            $productStore = ProductStore::findOrFail($id);

            // Calcular el nuevo valor de product_exit
            $newProductExit = max($productStore->product_exit - $quantityToSubtract, 0);

            // Actualizar el campo product_exit
            $productStore->product_exit = $newProductExit;

            // Guardar los cambios en la base de datos
            $productStore->save();

            // Confirmar la transacción
            DB::commit();

            // Respuesta exitosa
            return response()->json([
                'success' => true,
                'message' => "Se restaron $quantityToSubtract unidades correctamente.",
                'new_product_exit' => $newProductExit,
            ]);
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();

            // Log del error
            Log::error('Error al restar unidades de product_exit: ' . $e->getMessage());

            // Respuesta de error
            return response()->json([
                'success' => false,
                'message' => 'Hubo un error al restar las unidades.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }*/
    public function subtractProductExit(Request $request)
    {
        // Validar la solicitud
        $request->validate([
            'changes' => 'required|array',
            'changes.*.id' => 'required|integer|exists:product_store,id',
            'changes.*.quantity' => 'required|integer|min:1',
            'branch_id' => 'required|integer|exists:branches,id',
        ]);

        // Obtener los cambios
        $changes = $request->input('changes');
        $branch_id = $request->input('branch_id');
        // Iniciar una transacción de base de datos
        DB::beginTransaction();

        try {
            foreach ($changes as $change) {
                try {
                    // Obtener el registro de ProductStore por su ID
                    $productStore = ProductStore::findOrFail($change['id']);
            
                    // Calcular el nuevo valor de product_exit
                    $newProductExit = max($productStore->product_exit - $change['quantity'], 0);
            
                    // Actualizar el campo product_exit
                    $productStore->product_exit = $newProductExit;
            
                    // Guardar los cambios en la base de datos
                    $productStore->save();
            
                    // Llamar a la función para actualizar y notificar
                    $this->actualizarProductExit($productStore, $branch_id);
                } catch (\Exception $e) {
                    // Capturar cualquier error que ocurra durante el proceso
                    Log::error('Error al procesar el cambio: ' . $e->getMessage());
                }
            }

            // Confirmar la transacción
            DB::commit();

            // Respuesta exitosa
            return response()->json([
                'success' => true,
                'message' => 'Cantidades actualizadas correctamente.',
            ]);
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();

            // Log del error
            Log::error('Error al restar unidades de product_exit: ' . $e->getMessage());

            // Respuesta de error
            return response()->json([
                'success' => false,
                'message' => 'Hubo un error al actualizar las cantidades.',
                'error' => $e->getMessage(),
            ], 500);
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
                    'name' => $query->product->name.' ('.'Almacén:'.$query->store->address.')',
                    'image_product' => $query->product->image_product,
                    ];
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
            $product = $productStore->product;
                return [
                    'id' => $productStore->id,
                    'product_exit' => $productStore->product_exit,
                    'name' => $product->name.' ('.'Almacén:'.$productStore->store->address.')',
                    'image_product' => $product->image_product,
                    'price' => $product->sale_price
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

        // Eager loading con filtrado más específico
        $productStores = ProductStore::with(['product' => function ($query) use ($data) {
            $query->select(['id', 'name', 'reference', 'code', 'description', 'status_product', 'purchase_price', 'sale_price', 'image_product'])
                  ->where('status_product', '=', 'En venta');
        }])
        ->whereHas('product', function ($query) use ($data) {
            $query->where('product_category_id', '=', $data['id'])->where('status_product', '=', 'En venta');
        })
        ->whereHas('store.branches', function ($query) use ($data){
            $query->where('branches.id', '=', $data['branch_id']);
        })
        ->where('product_exit', '>', 0)
        ->select(['id', 'product_exit', 'product_id', 'store_id'])
        ->get();

        $productsArray = $productStores->map(function ($productStore) {
            $product = $productStore->product;
            Log::info('Producto'.$product);
            if ($product) {
                return [
                    'id' => $productStore->id,
                    'product_exit' => $productStore->product_exit,
                    'product_id' => $productStore->product_id,
                    'name' => $product->name,
                    'reference' => $product->reference,
                    'code' => $product->code,
                    'description' => $product->description,
                    'status_product' => $product->status_product,
                    'purchase_price' => $product->purchase_price,
                    'sale_price' => $product->sale_price,
                    'image_product' => $product->image_product
                ];
            }
        });

        return response()->json(['category_products' => $productsArray], 200, [], JSON_NUMERIC_CHECK);
    } catch (\Throwable $th) {
        Log::error($th);
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
                'branch_id' => 'nullable|numeric',
                'professional_id' => 'nullable|numeric',
                'product_id' => 'required|numeric',
                'store_id' => 'required|numeric',
                'store_idM' => 'required|numeric',
                //'branch_idM' => 'required|numeric',
                'product_quantity' => 'required|numeric'
            ]);
            //descontar
            $product = Product::find($data['product_id']);
            $store = Store::find($data['store_id']);
            $product = $store->products()->wherePivot('product_id', $product->id)->first();
            $productstore = $product->pivot;
            Log::info('Relacion producto almacen:'.$productstore);
            if($productstore != null){
                $existencia = $productstore->product_exit - $data['product_quantity'];
                $product->stores()->updateExistingPivot($store->id,['product_quantity'=>$data['product_quantity'],'product_exit'=>$existencia]);
                $product = $store->products()->wherePivot('product_id', $product->id)->first();
                $productstore = $product->pivot;
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
                $productstoreM = $storeM->products()->wherePivot('product_id', $product->id)->first();
            }
    
            //registro de movimiento de productos
            
            $movementprodct = new MovementProduct();
            $movementprodct->data = Carbon::now();
            $movementprodct->product_id = $data['product_id'];
            //$movementprodct->branch_out_id = $data['branch_id'];
            $movementprodct->store_out_id = $data['store_id'];
            $movementprodct->branch_int_id = $data['professional_id'];
            $movementprodct->store_int_id = $data['store_idM'];
            $movementprodct->store_out_exit = $productstore->product_exit-$data['product_quantity'];
            $movementprodct->store_int_exit = $productstoreM->pivot['product_exit']+$data['product_quantity'];  
            $movementprodct->cant = $data['product_quantity'];
            $movementprodct->save();
            if($request->has('branch_id')) {              
                $this->actualizarProductExit($productstore, $data['branch_id']);
            }else {
                $this->actualizarProductExit($productstore, 0);
            }
            //todo pendiente para revisar importante
            return response()->json(['msg' => 'Producto movido correctamente al almacén'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al mover el producto a este almacén'], 500);
        }
    }

    public function movement_products(Request $request){
        try {
            $data = $request->validate([
                'branch_id' => 'nullable|numeric',
                'year' => 'required'
            ]);
            $movement = [];
            if($request->mounth){
                if ($data['branch_id'] != 0) {
                    $storeIds = Store::whereHas('branches', function ($query) use ($data) {
                        $query->where('branch_id', $data['branch_id']);
                    })->pluck('id');
                    $movements = MovementProduct::whereYear('data', $data['year'])->whereMonth('data', $request->mounth)->where(function ($query) use ($storeIds) {
                        $query->whereIn('store_out_id', $storeIds)
                              ->orWhereIn('store_int_id', $storeIds);
                    })->get();
                    foreach ( $movements as $query) {
                        //$branchOut = Branch::where('id', $query->branch_out_id)->first();
                        //$branchInt = Branch::where('id', $query->branch_int_id)->first();
                        $storeInt = Store::where('id', $query->store_int_id)->first();
                        $storeOut = Store::where('id', $query->store_out_id)->first();                
                        $product = Product::where('id', $query->product_id)->first();
                        $professional = Professional::where('id', $query->branch_int_id)->first();
                        if ($storeInt && $storeOut && $product && $professional) {
                            $movement [] = [
                                //'branchOut' => $branchOut->name,
                                'storeOut' => $storeOut->address,
                                //'branchInt' => $branchInt->name,
                                'storeInt' => $storeInt->address,
                                'cant' => $query->cant,
                                'data' => $query->data,
                                'nameProduct' => $product->name,
                                'nameProfessional' => $professional->name,
                                'image_url' => $professional->image_url
                            ];
                        }
                }
                }else {
                    $movements = MovementProduct::whereYear('data', $data['year'])->whereMonth('data', $request->mounth)->get();
                    foreach ( $movements as $query) {
                        //$branchOut = Branch::where('id', $query->branch_out_id)->first();
                        //$branchInt = Branch::where('id', $query->branch_int_id)->first();
                        $storeInt = Store::where('id', $query->store_int_id)->first();
                        $storeOut = Store::where('id', $query->store_out_id)->first();                
                        $product = Product::where('id', $query->product_id)->first();
                        $professional = Professional::where('id', $query->branch_int_id)->first();
                        if ($storeInt && $storeOut && $product && $professional) {
                            $movement [] = [
                                //'branchOut' => $branchOut->name,
                                'storeOut' => $storeOut->address,
                                //'branchInt' => $branchInt->name,
                                'storeInt' => $storeInt->address,
                                'cant' => $query->cant,
                                'data' => $query->data,
                                'nameProduct' => $product->name,
                                'nameProfessional' => $professional->name,
                                'image_url' => $professional->image_url
                            ];
                        }
                }
                }
            }
            else{
                if ($data['branch_id'] != 0){
                    $storeIds = Store::whereHas('branches', function ($query) use ($data) {
                        $query->where('branch_id', $data['branch_id']);
                    })->pluck('id');
                    $movements = MovementProduct::whereYear('data', $data['year'])->where(function ($query) use ($storeIds) {
                        $query->whereIn('store_out_id', $storeIds)
                              ->orWhereIn('store_int_id', $storeIds);
                    })->get();
                    foreach ( $movements as $query) {
                        //$branchOut = Branch::where('id', $query->branch_out_id)->first();
                        //$branchInt = Branch::where('id', $query->branch_int_id)->first();
                        $storeInt = Store::where('id', $query->store_int_id)->first();
                        $storeOut = Store::where('id', $query->store_out_id)->first();                
                        $product = Product::where('id', $query->product_id)->first();
                        $professional = Professional::where('id', $query->branch_int_id)->first();
                        if ($storeInt && $storeOut && $product && $professional) {
                            $movement [] = [
                                //'branchOut' => $branchOut->name,
                                'storeOut' => $storeOut->address,
                                //'branchInt' => $branchInt->name,
                                'storeInt' => $storeInt->address,
                                'cant' => $query->cant,
                                'data' => $query->data,
                                'nameProduct' => $product->name,
                                'nameProfessional' => $professional->name,
                                'image_url' => $professional->image_url
                            ];
                        }
                }
                }else {
                    $movements = MovementProduct::whereYear('data', $data['year'])/*->where(function ($query) use($data){
                        $query->orWhere('branch_out_id', $data['branch_id'])->orWhere('branch_int_id', $data['branch_id']);
                    })*/->get();
                    foreach ( $movements as $query) {
                            //$branchOut = Branch::where('id', $query->branch_out_id)->first();
                            //$branchInt = Branch::where('id', $query->branch_int_id)->first();
                            $storeInt = Store::where('id', $query->store_int_id)->first();
                            $storeOut = Store::where('id', $query->store_out_id)->first();                
                            $product = Product::where('id', $query->product_id)->first();
                            $professional = Professional::where('id', $query->branch_int_id)->first();
                            if ($storeInt && $storeOut && $product && $professional) {
                                $movement [] = [
                                    //'branchOut' => $branchOut->name,
                                    'storeOut' => $storeOut->address,
                                    //'branchInt' => $branchInt->name,
                                    'storeInt' => $storeInt->address,
                                    'cant' => $query->cant,
                                    'data' => $query->data,
                                    'nameProduct' => $product->name,
                                    'nameProfessional' => $professional->name,
                                    'image_url' => $professional->image_url
                                ];
                            }
                    }
                }
        }
            return response()->json(['movimientos' => $movement], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al mover el producto a este almacén'], 500);
        }
    }
}
