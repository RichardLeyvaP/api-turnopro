<?php
namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchServiceProfessional;
use App\Models\Order;
use App\Models\ProductCategory;
use App\Models\ProductStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductCategoryController extends Controller
{
    public function index()
    {
        try { 
            
            Log::info( "entra a buscar categorias de productos");
            return response()->json(['productcategories' => ProductCategory::all()], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las categorias de productos"], 500);
        }
    }
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
           /*$branch = Branch::find($data['branch_id']);
           $Categories = collect();
           foreach($branch->stores as $store){
            foreach ($store->products as $product) {
                $Categories[] = $product->productCategory;
            }
           }*/
           return response()->json(['category_products' => $Categories], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => "Error al mostrar la categoría de producto"], 500);
       }
    }

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
        })->with(['products' => function ($query) use ($branchId, $statusProduct) {
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
            ->whereHas('product', function ($query) use ($category) {
                $query->where('product_category_id', '=', $category->id);
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
                })
            ];
        });

        $orderServicesDatas = Order::whereHas('car.reservation')
            ->whereRelation('car', 'id', '=', $data['car_id'])
            ->where('is_product', 0)
            ->pluck('branch_service_professional_id');

        $BSProfessional = BranchServiceProfessional::whereHas('branchService', function ($query) use ($data) {
            $query->where('branch_id', $data['branch_id']);
        })->where('professional_id', $data['professional_id'])
          ->with(['branchService.service'])
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
        });

        return response()->json(['category_products' => $formattedCategories, 'professional_services' => $serviceModels], 200, [], JSON_NUMERIC_CHECK);
    } catch (\Throwable $th) {
        return response()->json(['msg' => $th->getMessage()." Error interno del sistema"], 500);
    }
}

    public function store(Request $request)
    {

        Log::info("crear categoría de producto");
        Log::info($request);
        try {
             $product_category_data = $request->validate([
                'name' => 'required|max:50',
                'description' => 'required|max:220',
               
              
            ]);

            $product_category = new ProductCategory();
            $product_category->name =  $product_category_data['name'];
            $product_category->description =  $product_category_data['description'];
       
       
            $product_category->save();

            return response()->json(['msg' => 'Regla insertada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al insertar la Categoria de Producto'], 500);
        }
    }

    public function update(Request $request)
    {
        try {

            Log::info("entra a actualizar");
             $product_category_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'description' => 'required|max:220',
              
              
            ]);
            Log::info($request);
            $product_category = ProductCategory::find( $product_category_data['id']);
            $product_category->name =  $product_category_data['name'];
            $product_category->description =  $product_category_data['description'];
          
            $product_category->save();

            return response()->json(['msg' => 'Categoria de Producto actualizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al actualizar la Categoría de Producto'], 500);
        }
    }

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