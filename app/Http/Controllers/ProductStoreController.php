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
            return response()->json(['msg' => "Error al mostrar los productos"], 500);
        }
    }

    public function showStoresProductsold(Request $request)
    {
        try {
            $stores = Store::all('id', 'address', 'reference');
            $products = Product::all('id', 'name', 'image_product');
            return response()->json([
                'stores' => $stores,
                'products' => $products
            ], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los stores y productos"], 500);
        }
    }

    public function showStoresProducts(Request $request)
    {
        try {
            $data = $request->validate([
                'business_id' => 'required|numeric',
                'branch_id' => 'nullable|numeric'
            ]);
            if ($data['branch_id'] != 0) {
                $stores = Store::whereHas('branches', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id']);
                })->get()->select('id', 'address', 'reference');
            } else {
                $stores = Store::all('id', 'address', 'reference');
            }
            $products = Product::all('id', 'name', 'image_product', 'purchase_price', 'sale_price');
            $branches = Branch::where('business_id', $data['business_id'])->select('id', 'name', 'image_data', 'address')->get();
            return response()->json([
                'stores' => $stores,
                'products' => $products,
                'branches' => $branches
            ], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los stores y productos"], 500);
        }
    }

    public function store(Request $request)
    {        
        try {
            $data = $request->validate([
                'product_id' => 'required|numeric',
                'store_id' => 'required|numeric',
                'product_quantity' => 'required|numeric',
                'stock_depletion' => 'nullable|numeric',
            ]);
            $data['stock_depletion'] = $data['stock_depletion'] ?? 0;
            $product = Product::find($data['product_id']);
            $store = Store::find($data['store_id']);
             // Buscar relación incluyendo eliminados lógicamente
             $existingRelation = ProductStore::withTrashed()
             ->where('product_id', $data['product_id'])
             ->where('store_id', $data['store_id'])
             ->first();

            if ($existingRelation) {                
                if ($existingRelation->trashed()) {                    
                    // Actualizar todos los campos incluyendo deleted_at en una sola operación
                    $existingRelation->restore();
                    $existingRelation->product_quantity = $data['product_quantity'];
                    $existingRelation->product_exit = $data['product_quantity'];
                    $existingRelation->stock_depletion = $data['stock_depletion'];
                    $existingRelation->save();
                } else {                    
                    // Sumar a la existencia actual
                    $existingRelation->product_quantity = $data['product_quantity'];
                    $existingRelation->product_exit += $data['product_quantity'];
                    $existingRelation->stock_depletion = $data['stock_depletion'];
                    $existingRelation->save();
                }
            } else {
                $newRelation = new ProductStore();
                $newRelation->product_id = $data['product_id'];
                $newRelation->store_id = $data['store_id'];
                $newRelation->product_quantity = $data['product_quantity'];
                $newRelation->product_exit = $data['product_quantity'];
                $newRelation->stock_depletion = $data['stock_depletion'];
                $newRelation->save();
            }
            return response()->json(['msg' => 'Producto asignado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error interno del sistema'], 500);
        }
    }

    public function show(Request $request)
    {
        try {
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
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los productos"], 500);
        }
    }

    public function show_branch(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'nullable|numeric'
            ]);
            if ($data['branch_id'] != 0) {
                $productStore = ProductStore::whereHas('store.branches', function ($query) use ($data) {
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
            } else {
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
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los productos"], 500);
        }
    }

    public function show_branch_state(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'nullable|numeric'
            ]);
            if ($data['branch_id'] != 0) {
                $productStore = ProductStore::where(function($query) use ($data) {
                    // 1. Almacenes relacionados con la sucursal especificada en $data
                    $query->whereHas('store.branches', function ($q) use ($data) {
                        $q->where('branch_id', $data['branch_id']);
                    });
                    
                    // 2. O almacenes que solo tienen relación con la sucursal 20
                    $query->orWhereHas('store.branches', function ($q) {
                        $q->where('branch_id', 20);
                    }, '=', 1); // '= 1' asegura que solo tenga esta relación
                    
                    // 3. O almacenes que no tienen relación con ninguna sucursal
                    $query->orWhereDoesntHave('store.branches');
                })
                ->where('product_exit', '>', 0)
                ->where(function($query) use ($data) {
                    // Aplicar el filtro de status_product solo a:
                    // 1. Almacenes relacionados con la sucursal especificada
                    $query->whereHas('store.branches', function ($q) use ($data) {
                        $q->where('branch_id', $data['branch_id']);
                    })->whereHas('product', function ($q) {
                        $q->where('status_product', 'No en venta');
                    });
                    
                    // 2. O a almacenes que solo tienen relación con sucursal 20 (sin filtro de status)
                    $query->orWhereHas('store.branches', function ($q) {
                        $q->where('branch_id', 20);
                    }, '=', 1);
                    
                    // 3. O a almacenes sin relación con ninguna sucursal (con filtro de status)
                    $query->orWhereDoesntHave('store.branches')->whereHas('product', function ($q) {
                        $q->where('status_product', 'No en venta');
                    });
                })
                ->with(['product', 'store', 'store.branches' => function($q) use ($data) {
                    $q->where('branch_id', $data['branch_id']);
                }])
                ->get()
                ->map(function ($item) use ($data) {
                    // Determinar el branch_id
                    $branchId = 0;
                    if ($item->store->branches->isNotEmpty()) {
                        $branchId = $data['branch_id'];
                    }
                    
                    return [
                        'id' => $item->id,
                        'product_exit' => $item->product_exit,
                        'product_id' => $item->product_id,
                        'store_id' => $item->store_id,
                        'stock_depletion' => $item->stock_depletion,
                        'name' => $item->product->name,
                        'reference' => $item->product->reference,
                        'code' => $item->product->code,
                        'status_product' => $item->product->status_product,
                        'sale_price' => $item->product->sale_price,
                        'purchase_price' => $item->product->purchase_price,
                        'image_product' => $item->product->image_product,
                        'direccionStore' => $item->store->address,
                        'storetReference' => $item->store->reference,
                        'quantity' => 0,
                        'branch_id' => $branchId
                    ];
                });
            } else {
                $productStore = ProductStore::with('product', 'store')->where('product_exit', '>', 0)->whereHas('product', function ($query) {
                    $query->where('status_product', 'No en venta'); // Filtro para status_product
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
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los productos"], 500);
        }
    }

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
            $productStore = ProductStore::whereHas('store.enrollments', function ($query) use ($data) {
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
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los productos"], 500);
        }
    }

    public function products_academy_show(Request $request)
    {
        try {
            $data = $request->validate([
                'enrollment_id' => 'required|numeric'
            ]);
            $productStore = ProductStore::whereHas('store.enrollments', function ($query) use ($data) {
                $query->where('enrollments.id', $data['enrollment_id']);
            })->where('product_exit', '>', 0)->whereHas('product', function ($query) {
                $query->where('status_product', 'En venta');
            })->with('product', 'store')->get()->map(function ($query) {
                return [
                    'id' => $query->id,
                    'product_exit' => $query->product_exit,
                    'name' => $query->product->name . ' (' . 'Almacén:' . $query->store->address . ')',
                    'image_product' => $query->product->image_product,
                ];
            });
            return response()->json(['products' => $productStore], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los productos"], 500);
        }
    }

    public function product_show_web(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $productStores = ProductStore::whereHas('product', function ($query) use ($data) {
                $query->where('status_product', 'En venta');
            })->whereHas('store.branches', function ($query) use ($data) {
                $query->where('branches.id', $data['branch_id']);
            })->where('product_exit', '>', 0)->get()->map(function ($productStore) {
                $product = $productStore->product;
                return [
                    'id' => $productStore->id,
                    'product_exit' => $productStore->product_exit,
                    'name' => $product->name . ' (' . 'Almacén:' . $productStore->store->address . ')',
                    'image_product' => $product->image_product,
                    'price' => $product->sale_price
                ];
            });
            return response()->json(['products' => $productStores], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los productos"], 500);
        }
    }

    public function product_show_worker(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $productStores = ProductStore::whereHas('product', function ($query) use ($data) {
                $query->where('status_product', 'En venta');
            })->whereHas('store.branches', function ($query) use ($data) {
                $query->where('branches.id', $data['branch_id']);
            })
                ->where('product_exit', '>', 0)
                ->with(['product', 'store'])
                ->get()
                ->map(function ($productStore) {
                    $product = $productStore->product;
                    return [
                        'id' => $productStore->id,
                        'product_exit' => $productStore->product_exit,
                        'name' => $product->name . ' (' . 'Almacén: ' . $productStore->store->address . ')',
                        'image_product' => $product->image_product,
                        'price' => $product->sale_price ?? 0,
                        'worker_discount' => $product->worker_discount ?? 10, // Valor por defecto 10% si no está definido
                        'worker_price' => $product->sale_price * (1 - ($product->worker_discount ?? 10) / 100) // Precio con descuento aplicado
                    ];
                });

            return response()->json([
                'products' => $productStores
            ], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json([
                'msg' => "Error al mostrar los productos",
                'error' => $th->getMessage()
            ], 500);
        }
    }


    public function product_show_academy_web(Request $request)
    {
        try {
            $data = $request->validate([
                'enrollment_id' => 'required|numeric'
            ]);
            $productStores = ProductStore::whereHas('product', function ($query) use ($data) {
                $query->where('status_product', 'En venta');
            })->whereHas('store.enrollments', function ($query) use ($data) {
                $query->where('enrollments.id', $data['enrollment_id']);
            })->where('product_exit', '>', 0)->get()->map(function ($productStore) {
                return [
                    'id' => $productStore->id,
                    'name' => $productStore->product->name . ' (' . 'Almacén:' . $productStore->store->address . ')'
                ];
            });
            return response()->json(['products' => $productStores], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
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
                ->whereHas('store.branches', function ($query) use ($data) {
                    $query->where('branches.id', '=', $data['branch_id']);
                })
                ->where('product_exit', '>', 0)
                ->select(['id', 'product_exit', 'product_id', 'store_id'])
                ->get();

            $productsArray = $productStores->map(function ($productStore) {
                $product = $productStore->product;
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
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar la categoría de producto"], 500);
        }
    }

    public function update(Request $request)
    {
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
            if ($productstore) {
                $product->stores()->updateExistingPivot($store->id, ['product_quantity' => $data['product_quantity'], 'product_exit' => $data['product_quantity'], 'stock_depletion' => $data['stock_depletion']]);
            } else {
                $store->products()->attach($product->id, ['product_quantity' => $data['product_quantity'], 'product_exit' => $data['product_quantity'], 'stock_depletion' => $data['stock_depletion']]);
            }
            return response()->json(['msg' => 'Asignación actualizada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error interno del sistema'], 500);
        }
    }

    public function destroy(Request $request)
    {
        
        try {
            $data = $request->validate([
                'product_id' => 'required|numeric',
                'store_id' => 'required|numeric' //,
                //'branch_id' => 'nullable',
                //'enrollment_id' => 'nullable'
            ]);
            $product = Product::find($data['product_id']);
            $store = Store::find($data['store_id']);
            $productstore = $store->products()->wherePivot('product_id', $product->id)->first();
            if ($productstore) {
                $store->products()->updateExistingPivot($product->id, ['product_quantity' => 0, 'product_exit' => 0]);
            }

            return response()->json(['msg' => 'Operación realizada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error interno del sistema'], 500);
        }
    }
    public function move_product_store(Request $request)
    {
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
            if ($productstore != null) {
                $existencia = $productstore->product_exit - $data['product_quantity'];
                $product->stores()->updateExistingPivot($store->id, ['product_quantity' => $data['product_quantity'], 'product_exit' => $existencia]);
                $product = $store->products()->wherePivot('product_id', $product->id)->first();
                $productstore = $product->pivot;
            }
            //aumentar
            $storeM = Store::find($data['store_idM']);
            $storeDestino = Store::findOrFail($data['store_idM']);
            $existingRelation = ProductStore::withTrashed()
                ->where('product_id', $product->id)
                ->where('store_id', $storeDestino->id)
                ->first();

            if ($existingRelation) {                
                if ($existingRelation->trashed()) {                    
                    // Restaurar y actualizar valores
                    $existingRelation->restore();
                    $existingRelation->product_quantity = $data['product_quantity'];
                    $existingRelation->product_exit = $data['product_quantity'];
                    $existingRelation->save();
                } else {                    
                    // Sumar a la existencia actual
                    $existingRelation->product_exit += $data['product_quantity'];
                    $existingRelation->save();
                }
            } else {                
                // Crear nueva relación
                $newRelation = new ProductStore();
                $newRelation->product_id = $product->id;
                $newRelation->store_id = $storeDestino->id;
                $newRelation->product_quantity = $data['product_quantity'];
                $newRelation->product_exit = $data['product_quantity'];
                $newRelation->save();
            }

            $movementprodct = new MovementProduct();
            $movementprodct->data = Carbon::now();
            $movementprodct->product_id = $data['product_id'];
            //$movementprodct->branch_out_id = $data['branch_id'];
            $movementprodct->store_out_id = $data['store_id'];
            $movementprodct->branch_int_id = $data['professional_id'];
            $movementprodct->store_int_id = $data['store_idM'];
            $movementprodct->store_out_exit = $productstore->product_exit - $data['product_quantity'];
            $movementprodct->store_int_exit = $existingRelation ? $existingRelation->product_exit : $data['product_quantity'];;
            $movementprodct->cant = $data['product_quantity'];
            $movementprodct->save();
            if ($request->has('branch_id')) {
                $this->actualizarProductExit($productstore, $data['branch_id']);
            } else {
                $this->actualizarProductExit($productstore, 0);
            }
            //todo pendiente para revisar importante
            return response()->json(['msg' => 'Producto movido correctamente al almacén'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al mover el producto a este almacén'], 500);
        }
    }

    public function movement_products(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'nullable|numeric',
                'year' => 'required'
            ]);
            $movement = [];
            if ($request->mounth) {
                if ($data['branch_id'] != 0) {
                    $storeIds = Store::whereHas('branches', function ($query) use ($data) {
                        $query->where('branch_id', $data['branch_id']);
                    })->pluck('id');
                    $movements = MovementProduct::whereYear('data', $data['year'])->whereMonth('data', $request->mounth)->where(function ($query) use ($storeIds) {
                        $query->whereIn('store_out_id', $storeIds)
                            ->orWhereIn('store_int_id', $storeIds);
                    })->get();
                    foreach ($movements as $query) {
                        $storeInt = Store::where('id', $query->store_int_id)->first();
                        $storeOut = Store::where('id', $query->store_out_id)->first();
                        $product = Product::where('id', $query->product_id)->first();
                        $professional = Professional::where('id', $query->branch_int_id)->first();
                        if ($storeInt && $storeOut && $product && $professional) {
                            $movement[] = [
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
                } else {
                    $movements = MovementProduct::whereYear('data', $data['year'])->whereMonth('data', $request->mounth)->get();
                    foreach ($movements as $query) {
                        $storeInt = Store::where('id', $query->store_int_id)->first();
                        $storeOut = Store::where('id', $query->store_out_id)->first();
                        $product = Product::where('id', $query->product_id)->first();
                        $professional = Professional::where('id', $query->branch_int_id)->first();
                        if ($storeInt && $storeOut && $product && $professional) {
                            $movement[] = [
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
            } else {
                if ($data['branch_id'] != 0) {
                    $storeIds = Store::whereHas('branches', function ($query) use ($data) {
                        $query->where('branch_id', $data['branch_id']);
                    })->pluck('id');
                    $movements = MovementProduct::whereYear('data', $data['year'])->where(function ($query) use ($storeIds) {
                        $query->whereIn('store_out_id', $storeIds)
                            ->orWhereIn('store_int_id', $storeIds);
                    })->get();
                    foreach ($movements as $query) {
                        //$branchOut = Branch::where('id', $query->branch_out_id)->first();
                        //$branchInt = Branch::where('id', $query->branch_int_id)->first();
                        $storeInt = Store::where('id', $query->store_int_id)->first();
                        $storeOut = Store::where('id', $query->store_out_id)->first();
                        $product = Product::where('id', $query->product_id)->first();
                        $professional = Professional::where('id', $query->branch_int_id)->first();
                        if ($storeInt && $storeOut && $product && $professional) {
                            $movement[] = [
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
                } else {
                    $movements = MovementProduct::whereYear('data', $data['year'])->get();
                    foreach ($movements as $query) {
                        $storeInt = Store::where('id', $query->store_int_id)->first();
                        $storeOut = Store::where('id', $query->store_out_id)->first();
                        $product = Product::where('id', $query->product_id)->first();
                        $professional = Professional::where('id', $query->branch_int_id)->first();
                        if ($storeInt && $storeOut && $product && $professional) {
                            $movement[] = [
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
            return response()->json(['msg' => $th->getMessage() . 'Error al mover el producto a este almacén'], 500);
        }
    }
}
