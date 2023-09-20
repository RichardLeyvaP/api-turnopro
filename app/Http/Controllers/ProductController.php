<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Entra a buscar productos");
            return response()->json(['products' => Product::with('productcategory')->get()], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los productos"], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info("Guardar Producto");
        Log::info($request);
        try {
            $product_data = $request->validate([
                'name' => 'required|min:3',
                'reference' => 'required|min:3',
                'code' => 'required',
                'description' => 'nullable|min:3',
                'status_product' => 'required',
                'purchase_price' => 'required|numeric',
                'sale_price' => 'required|numeric',
                'image_product' => 'nullable',
                'product_category_id' => 'required|numeric'
            ]);
            if ($request->hasFile('image_product')) {
                $filename = $request->file('image_product')->storeAs('products',$request->code.'.'.$request->file('image_product')->getClientOriginalExtension(),'public');
                $product_data['image_product'] = $filename;
            }
            $product = new Product();
            $product->name = $product_data['name'];
            $product->reference = $product_data['reference'];
            $product->code = $product_data['code'];
            $product->description = $product_data['description'];
            $product->status_product = $product_data['status_product'];
            $product->purchase_price = $product_data['purchase_price'];
            $product->sale_price = $product_data['sale_price'];
            $product->image_product = $product_data['image_product'];
            $product->product_category_id = $product_data['product_category_id'];
            $product->save();

            return response()->json(['msg' => 'Producto insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => 'Error al insertar el producto'], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $product_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['product' => Product::find($product_data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el producto"], 500);
        }
    }

    public function update(Request $request)
    {
        try {

            Log::info("Editar");
            Log::info($request);
            $product_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|min:3',
                'reference' => 'required|min:3',
                'code' => 'required',
                'description' => 'nullable|min:3',
                'status_product' => 'required',
                'purchase_price' => 'required|numeric',
                'sale_price' => 'required|numeric',
                'image_product' => 'nullable',
                'product_category_id' => 'required|numeric'
            ]);

            $product = Product::find($product_data['id']);
            if ($product->image_product) {
            $destination=public_path("storage\\".$product->image_product);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
            }
            if ($request->hasFile('image_product')) {
                $filename = $request->file('image_product')->storeAs('products',$request->code.'.'.$request->file('image_product')->getClientOriginalExtension(),'public');
                $product_data['image_product'] = $filename;
            }
            $product->name = $product_data['name'];
            $product->reference = $product_data['reference'];
            $product->code = $product_data['code'];
            $product->description = $product_data['description'];
            $product->status_product = $product_data['status_product'];
            $product->purchase_price = $product_data['purchase_price'];
            $product->sale_price = $product_data['sale_price'];
            $product->image_product = $product_data['image_product'];
            $product->product_category_id = $product_data['product_category_id'];
            $product->save();

            return response()->json(['msg' => 'Producto actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => 'Error al actualizar el producto'], 500);
        }
    }
    public function destroy(Request $request)
    {
        try {
            $product_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $product = Product::find($product_data['id']);
            if ($product->image_product) {
            $destination=public_path("storage\\".$product->image_product);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
            }
            $product->delete();

            return response()->json(['msg' => 'producto eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el producto'], 500);
        }
    }
}
