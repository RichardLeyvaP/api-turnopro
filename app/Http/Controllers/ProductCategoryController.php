<?php
namespace App\Http\Controllers;

use App\Models\ProductCategory;
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
           $result = ProductCategory::join('products', 'products.product_category_id','=','product_categories.id')->join('product_store','product_store.product_id','=','products.id')->join('stores','stores.id','=','product_store.store_id')->where('stores.branch_id',$data['branch_id'])->get(['product_categories.*']);
           return response()->json(['category_products' => $result], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => "Error al mostrar la categoría de producto"], 500);
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