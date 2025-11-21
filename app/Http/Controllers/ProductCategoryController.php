<?php
namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\Order;
use App\Models\ProductCategory;
use App\Models\ProductStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductCategoryController extends Controller
{

    /**
 * Lista todas las categorías de productos del sistema.
 *
 * @authenticated
 *
 * @response 200 {
 *   "productcategories": [
 *     {
 *       "id": 1,
 *       "name": "Cuidado Capilar",
 *       "description": "Productos para el cabello",
 *       "gives_commission": 1
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar las categorias de productos"}
 */
    public function index()
    {
        try { 
            
            return response()->json(['productcategories' => ProductCategory::all()], 200);
        } catch (\Throwable $th) {  
            return response()->json(['msg' => "Error al mostrar las categorias de productos"], 500);
        }
    }

    /**
 * Muestra los detalles de una categoría de producto específica.
 *
 * @authenticated
 * @queryParam id integer required ID de la categoría. Example: 1
 *
 * @response 200 {
 *   "productcategory": {
 *     "id": 1,
 *     "name": "Cuidado Capilar",
 *     "description": "Productos para el cabello",
 *     "gives_commission": 1
 *   }
 * }
 * @response 500 {"msg": "Error al mostrar la categoría de producto"}
 */
    public function show(Request $request)
    {
        try {
             $product_category_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['productcategory' => ProductCategory::find( $product_category_data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar la categoría de producto"], 500);
        }
    }

    /**
 * Obtiene categorías de productos disponibles en una sucursal.
 *
 * Solo incluye categorías que tienen productos con existencia > 0 y estado "En venta".
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 *
 * @response 200 {
 *   "category_products": [
 *     {
 *       "id": 1,
 *       "name": "Cuidado Capilar",
 *       "description": "Productos para el cabello"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar la categoría de producto"}
 */
    public function category_branch(Request $request)
    {
        try {
            $data = $request->validate([
               'branch_id' => 'required|numeric'
           ]);
           /*$Categories = ProductCategory::whereHas('products.stores', function ($query) use ($data) {
            $query->where('branch_id', $data['branch_id']);
           })->whereHas('products.stores', function ($query) {
            $query->where('product_exit', '>', 0);
           })->get();*/
           $Categories = ProductCategory::whereHas('products.stores.branches', function ($query) use ($data) {
            $query->where('branch_id', $data['branch_id']);
           })->whereHas('products.stores', function ($query) {
            $query->where('product_exit', '>', 0)->where('status_product', 'En venta');
           })->get();
           return response()->json(['category_products' => $Categories], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => "Error al mostrar la categoría de producto"], 500);
       }
    }

    /**
 * Obtiene categorías y productos disponibles en una sucursal para un carro y profesional específicos.
 *
 * Además, devuelve los servicios asignados al profesional en esa sucursal y el conteo de productos/servicios ya seleccionados en el carro.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 * @queryParam professional_id integer required ID del profesional. Example: 10
 * @queryParam car_id integer required ID del carro (car). Example: 101
 *
 * @response 200 {
 *   "category_products": [
 *     {
 *       "id": 1,
 *       "name": "Cuidado Capilar",
 *       "description": "...",
 *       "products": [
 *         {
 *           "id": 501,
 *           "product_exit": 15,
 *           "product_id": 25,
 *           "name": "Shampoo Reparador",
 *           "sale_price": 8500,
 *           "image_product": "products/25.jpg"
 *         }
 *       ]
 *     }
 *   ],
 *   "professional_services": [
 *     {
 *       "id": 45,
 *       "name": "Corte de Cabello",
 *       "price_service": 12000,
 *       "cliente": true
 *     }
 *   ],
 *   "product_select": 2,
 *   "service_select": 1
 * }
 * @response 500 {"msg": "[error] Error interno del sistema"}
 */
    public function category_products_branch(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'car_id' => 'required|numeric',
            ]);

            $branchId = $data['branch_id'];
            $statusProduct = 'En venta';

            // Obtener categorías con productos filtrados y sus relaciones necesarias
            $categories = ProductCategory::whereHas('products.stores.branches', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })->where('name', '!=', 'Bebidas') // Filtra las categorías cuyo nombre sea distinto de "Bebidas"
            ->with(['products' => function ($query) use ($branchId, $statusProduct) {
                $query->whereHas('stores.branches', function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })->where('status_product', $statusProduct)
                ->select(['id', 'name', 'reference', 'code', 'description', 'status_product', 'purchase_price', 'sale_price', 'image_product']);
            }])->get(['id', 'name', 'description']);

            // Formatear la respuesta
            $formattedCategories = $categories->map(function ($category) use ($branchId, $statusProduct) {
                $productStores = ProductStore::with(['product' => function ($query) use ($statusProduct) {
                    $query->select(['id', 'name', 'reference', 'code', 'description', 'status_product', 'purchase_price', 'sale_price', 'image_product'])
                        ->where('status_product', '=', $statusProduct);
                }])
                ->whereHas('product', function ($query) use ($category, $statusProduct) {
                    $query->where('product_category_id', '=', $category->id)->where('status_product', '=', $statusProduct);
                })
                ->whereHas('store.branches', function ($query) use ($branchId) {
                    $query->where('branches.id', '=', $branchId);
                })
                ->where('product_exit', '>', 0)
                ->select(['id', 'product_exit', 'product_id', 'store_id'])
                ->get();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'products' => $productStores->map(function ($productStore) {
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
                        
                    })
                ];
            });

            $orderServicesDatas = Order::whereHas('car.reservation')
                ->whereRelation('car', 'id', '=', $data['car_id'])
                ->where('is_product', 0)
                ->pluck('branch_service_professional_id');

            $BSProfessional = BranchServiceProfessional::with([
                'branchService' => function ($query) {
                    $query->whereNull('branch_service.deleted_at'); // Evita branch_services eliminados
                },
                'branchService.service' => function ($query) {
                    $query->whereNull('services.deleted_at'); // Evita services eliminados
                }
            ])
            ->where('professional_id', $data['professional_id'])
            ->whereNull('branch_service_professional.deleted_at') // Evita B.S.P. eliminados
            ->whereHas('branchService', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id'])
                    ->whereNull('branch_service.deleted_at') // B.S. activo
                    ->whereHas('service', function ($q) {
                        $q->whereNull('services.deleted_at'); // S. activo
                    });
            })
            ->get();

            $serviceModels = $BSProfessional->map(function ($branchServiceProfessional) use ($orderServicesDatas) {
                $service = $branchServiceProfessional->branchService->service;
                return [
                    "id" => $branchServiceProfessional->id,
                    "name" => $service->name,
                    "simultaneou" => $service->simultaneou,
                    "price_service" => $service->price_service,
                    "type_service" => $service->type_service,
                    "profit_percentaje" => $service->profit_percentaje,
                    "duration_service" => $service->duration_service,
                    "image_service" => $service->image_service,
                    "service_comment" => $service->service_comment,
                    "cliente" => $orderServicesDatas->contains($branchServiceProfessional->id)
                ];
            })->sortByDesc('cliente')->values();

            $car = Car::find($data['car_id']);
            if ($car != null) {
                $services = $car->orders->where('is_product', 0)->count();
                $products = $car->orders->where('is_product', 1)->sum('cant');
            }
            else{
                $services = 0;
                $products = 0;
            }

            return response()->json(['category_products' => $formattedCategories, 'professional_services' => $serviceModels, 'product_select' => intval($products), 'service_select' => intval($services)], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()." Error interno del sistema"], 500);
        }
    }

    /**
 * Crea una nueva categoría de producto.
 *
 * @authenticated
 * @bodyParam name string required Nombre de la categoría. Max: 50 caracteres. Example: "Bebidas"
 * @bodyParam description string required Descripción. Max: 220 caracteres. Example: "Bebidas para clientes"
 * @bodyParam gives_commission integer required 1 = da comisión, 0 = no da comisión. Example: 0
 *
 * @response 200 {"msg": "Regla insertada correctamente"}
 * @response 500 {"msg": "Error al insertar la Categoria de Producto"}
 */
    public function store(Request $request)
    {
        try {
             $product_category_data = $request->validate([
                'name' => 'required|max:50',
                'description' => 'required|max:220',
                'gives_commission' => 'required|numeric',
                //'commission_rate' => 'nullable|numeric',
            ]);

            $product_category = new ProductCategory();
            $product_category->name =  $product_category_data['name'];
            $product_category->description =  $product_category_data['description'];
            $product_category->gives_commission =  $product_category_data['gives_commission'];
            //$product_category->commission_rate =  $product_category_data['commission_rate'];
       
       
            $product_category->save();

            return response()->json(['msg' => 'Regla insertada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al insertar la Categoria de Producto'], 500);
        }
    }

    /**
 * Actualiza una categoría de producto existente.
 *
 * @authenticated
 * @bodyParam id integer required ID de la categoría. Example: 5
 * @bodyParam name string required Nuevo nombre. Max: 50. Example: "Bebidas Frescas"
 * @bodyParam description string required Nueva descripción. Max: 220. Example: "Agua, jugos y refrescos"
 * @bodyParam gives_commission integer required 1/0. Example: 0
 *
 * @response 200 {"msg": "Categoria de Producto actualizada correctamente"}
 * @response 500 {"msg": "Error al actualizar la Categoría de Producto"}
 */
    public function update(Request $request)
    {
        try {
             $product_category_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'description' => 'required|max:220',
                'gives_commission' => 'required|numeric',
                //'commission_rate' => 'nullable|numeric',
              
            ]);
            $product_category = ProductCategory::find( $product_category_data['id']);
            $product_category->name =  $product_category_data['name'];
            $product_category->description =  $product_category_data['description'];
            $product_category->gives_commission =  $product_category_data['gives_commission'];
            //$product_category->commission_rate =  $product_category_data['commission_rate'];
          
            $product_category->save();

            return response()->json(['msg' => 'Categoria de Producto actualizada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar la Categoría de Producto'], 500);
        }
    }

    /**
 * Elimina una categoría de producto.
 *
 * ⚠️ **Advertencia**: Esta acción es irreversible y puede afectar productos asociados.
 *
 * @authenticated
 * @bodyParam id integer required ID de la categoría a eliminar. Example: 5
 *
 * @response 200 {"msg": "Regla eliminada correctamente"}
 * @response 500 {"msg": "Error al eliminar la Regla"}
 */
    public function destroy(Request $request)
    {
        try {
            
             $product_category_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            ProductCategory::destroy( $product_category_data['id']);

            return response()->json(['msg' => 'Regla eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar la Regla'], 500);
        }
    }
}