<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductStore;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Entra a buscar productos");
            return response()->json(['products' => Product::with('productcategory')->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los productos"], 500);
        }
    }

    /*public function product_branch(Request $request)
    {
        try {
            $data = $request->validate([
               'branch_id' => 'required|numeric'
           ]);
           $result = Product::join('product_store','product_store.product_id','=','products.id')->join('stores','stores.id','=','product_store.store_id')->where('stores.id',$data['branch_id'])->get(['products.*']);
           return response()->json(['branch_products' => $result], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => "Error al mostrar los productos por almacen"], 500);
       }
    }*/

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
                'sale_price' => 'nullable',
                'image_product' => 'nullable',
                'product_category_id' => 'required|numeric'
            ]);        
                
            $product = new Product();            
            $product->name = $product_data['name'];
            $product->reference = $product_data['reference'];
            $product->code = $product_data['code'];
            $product->description = $product_data['description'];
            $product->status_product = $product_data['status_product'];
            $product->purchase_price = $product_data['purchase_price'];
            $product->sale_price = $product_data['sale_price'];
            $product->product_category_id = $product_data['product_category_id'];
            $product->save();

            $filename = "products/default.jpg";
            if ($request->hasFile('image_product')) {
                $filename = $request->file('image_product')->storeAs('products',$product->id.'.'.$request->file('image_product')->extension(),'public');
            }
            $product->image_product = $filename;
            $product->save();

            return response()->json(['msg' => 'Producto insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => 'Error al insertar el producto'], 500);
        }
    }

    public function product_mostSold_date(Request $request)
    {
        try {
            $data = $request->validate([
                'Date' => 'required|date'
           ]);
           Log::info('Obtener los cars');
           $branches = Branch::all();
           $result = [];
           $i = 0;
           $total_company = 0;
           foreach ($branches as $branch) {
            $product = Product::withCount('orders')->whereHas('productStores.orders', function ($query) use ($data){
                $query->whereDate('data', Carbon::parse($data['Date']));
            })->whereHas('productStores.store.branches', function ($query) use ($branch){
                $query->where('branch_id', $branch->id);
            })->orderByDesc('orders_count')->first();
                $result[$i]['nameBranch'] = $branch->name;
                $result[$i]['nameProduct'] = $product ? $product->name : null;
                $result[$i++]['cantProduct'] = $product ? $product->orders_count : 0;
                //$total_company += round($cars->sum('earnings'),2);
            }//foreach
            $productcompany = Product::withCount('orders')->whereHas('productStores.orders', function ($query) use ($data){
                $query->whereDate('data', Carbon::parse($data['Date']));
            })->orderByDesc('orders_count')->first();
          return response()->json([
            'branches' => $result,
            'Product' => $productcompany->name,
            'cantProduct' => $productcompany->orders_count
          ], 200, [], JSON_NUMERIC_CHECK);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."La branch no obtuvo ganancias en este dia"], 500);
       }
    }

    public function product_mostSold(Request $request)
    {
        try {
            /*$data = $request->validate([
                'branch_id' => 'nullable'
            ]);*/
            $products = Product::with(['orders' => function ($query) {
                $query->selectRaw('SUM(cant) as total_sale_price')
                    ->groupBy('product_store.product_id')->whereDate('data', Carbon::now()); // Agrupar por el ID del producto en la tabla intermedia
            }, 'productSales' => function ($query) {
                $query->selectRaw('SUM(cant) as total_cant')
                    ->groupBy('product_store.product_id'); // Agrupar por el ID del producto en la tabla intermedia
            },'cashiersales' => function ($query) {
                $query->selectRaw('product_id, SUM(cant) as total_cashier')
                    ->groupBy('product_id')
                    ->whereDate('data', Carbon::now());
            }])/*->whereHas('productStores', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
                })*/
            ->get()
            ->map(function ($product) {
                $total_sale_price = $product->orders->isEmpty() ? 0 : $product->orders->first()->total_sale_price;
                $total_cant = $product->productSales->isEmpty() ? 0 : $product->productSales->first()->total_cant;
                $total_cashier = $product->cashiersales->isEmpty() ? 0 : $product->cashiersales->first()->total_cashier;
                
                // Calcular el valor total de ventas y sumarle total_cant
                $total_sales = $total_sale_price + $total_cant + $total_cashier;
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'reference' => $product->reference,
                    'code' => $product->code,
                    'description' => $product->description,
                    'status_product' => $product->status_product,
                    'purchase_price' => $product->purchase_price,
                    'sale_price' => $product->sale_price,
                    'image_product' => $product->image_product,
                    'product_category_id' => $product->product_category_id,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'orders_count' => $total_sales,
                ];
            })->sortByDesc('orders_count')->values();
        
        //return $products;
            ///}
            /*return $products = Product::with(['orders' => function ($query) {
                $query->selectRaw('SUM((price / sale_price) * sale_price) as total_sale_price');
            }])
            ->withSum('productsales', 'cant')
            ->get();*/
        
            /*$products = Product::withCount(['orders', 'productSales'])
            ->get()->map(function ($query){
                return [
                    'id' => $query->id,
                    'name' => $query->name,
                    'reference' => $query->reference,
                    'code' => $query->code,
                    'description' => $query->description,
                    'status_product' => $query->status_product,
                    'purchase_price' => $query->purchase_price,
                    'sale_price' => $query->sale_price,
                    'image_product' => $query->image_product,
                    'product_category_id' => $query->product_category_id,
                    'created_at' => $query->created_at,
                    'updated_at' => $query->updated_at,
                    'orders_count' => $query->orders_count,
                ];
            });*/
           //if ($data['branch_id'] !=0) {
            /*Log::info('Es branch');
            $products = Product::withCount(['orders' => function ($query){
                $query->whereDate('data', Carbon::now());
            }])->whereHas('productStores', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
            })->orderByDesc('orders_count')->get();
           //}
           /*else {
            Log::info('bussines');
            $products = Product::withCount('orders')->orderByDesc('orders_count')->get();
           }*/
        
          return response()->json($products, 200, [], JSON_NUMERIC_CHECK);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Error interno del sistema"], 500);
       }
    }

    public function product_mostSold_periodo(Request $request)
    {
        try {
            $data = $request->validate([
                //'branch_id' => 'nullable',
                'startDate' => 'nullable',
                'endDate' => 'nullable'
            ]);
            $products = Product::with(['orders' => function ($query) use($data){
                $query->selectRaw('SUM(cant) as total_sale_price')
                    ->groupBy('product_store.product_id')->whereDate('data', '>=', $data['startDate'])->whereDate('data', '<=', $data['endDate']); // Agrupar por el ID del producto en la tabla intermedia
            }, 'productSales' => function ($query) {
                $query->selectRaw('SUM(cant) as total_cant')
                    ->groupBy('product_store.product_id'); // Agrupar por el ID del producto en la tabla intermedia
            },'cashiersales' => function ($query)  use($data){
                $query->selectRaw('product_id, SUM(cant) as total_cashier')
                    ->groupBy('product_id')->whereDate('data', '>=', $data['startDate'])->whereDate('data', '<=', $data['endDate']);
            }])/*->whereHas('productStores', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
                })*/
            ->get()
            ->map(function ($product) {
                $total_sale_price = $product->orders->isEmpty() ? 0 : $product->orders->first()->total_sale_price;
                $total_cant = $product->productSales->isEmpty() ? 0 : $product->productSales->first()->total_cant;
                $total_cashier = $product->cashiersales->isEmpty() ? 0 : $product->cashiersales->first()->total_cashier;
                
                // Calcular el valor total de ventas y sumarle total_cant
                $total_sales = $total_sale_price + $total_cant + $total_cashier;
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'reference' => $product->reference,
                    'code' => $product->code,
                    'description' => $product->description,
                    'status_product' => $product->status_product,
                    'purchase_price' => $product->purchase_price,
                    'sale_price' => $product->sale_price,
                    'image_product' => $product->image_product,
                    'product_category_id' => $product->product_category_id,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'orders_count' => $total_sales,
                ];
            })->sortByDesc('orders_count')->values();
           //if ($data['branch_id'] !=0) {
            /*Log::info('Es branch');
            /*$products = Product::withCount(['orders' => function ($query) use ($data){
                $query->whereDate('data', '>=', $data['startDate'])->whereDate('data', '<=', $data['endDate']);
            }])->whereHas('productStores', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
            })->orderByDesc('orders_count')->get();
            /*return $products = Product::whereHas(['orders' => function ($query) use ($data){
                $query->whereDate('data', '>=', $data['startDate'])->whereDate('data', '<=', $data['endDate'])->whereHas('car.reservation', function ($query) use ($data){
                    $query->where('branch_id', $data['branch_id']);
                });
            }])->get();*/
           /*}
           else {
            Log::info('bussines');
            $products = Product::withCount('orders')->orderByDesc('orders_count')->get();
           }*/
        
          return response()->json($products, 200, [], JSON_NUMERIC_CHECK);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Error interno del sistema"], 500);
       }
    }

    public function product_stock(Request $request)
    {
        try {
            /*$data = $request->validate([
                'business_id' => 'required|numeric'
            ]);*/
           Log::info('Obtener los productos');
           /*if ($data['branch_id'] !=0) {
            $products = ProductStore::where('product_exit', '<', 'stock_depletion')->where('branch_id', $data['branch_id'])->with('store', 'product')->get()->map(function ($query){
                return [
                    'name' => $query->product->name,
                    'stock' => $query->product_exit,
                    'reference' =>$query->product->reference,
                    'code' => $query->product->code,
                    'store' => $query->store->address,
                    'nameBranch' => $query->store->branches()->first()->value('name')
                ];
            });
           }
           else {*/
            $products = ProductStore::whereRaw('product_exit < stock_depletion')->with('store', 'product')->get()->map(function ($query){
                return [
                    'name' => $query->product->name,
                    'stock' => $query->product_exit,
                    'stock_depletion' => $query->stock_depletion,
                    'reference' =>$query->product->reference,
                    'code' => $query->product->code,
                    'store' => $query->store->address//,
                    //'nameBranch' => $query->store->branches()->first()->value('name')
                ];
            });
           //}
        
          return response()->json($products, 200, [], JSON_NUMERIC_CHECK);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."La branch no obtuvo ganancias en este dia"], 500);
       }
    }

    public function show(Request $request)
    {
        try {
            $product_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['product' => Product::find($product_data['id'])], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el producto"], 500);
        }
    }

    public function but_product(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $products = Product::withCount('orders')->whereHas('stores.branches', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);})->orderBy('orders_count', 'desc')->take(10)->get();
            return response()->json(['products' => $products], 200, [], JSON_NUMERIC_CHECK);
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
                'sale_price' => 'nullable',
                'image_product' => 'nullable',
                'product_category_id' => 'required|numeric'
            ]);

            $product = Product::find($product_data['id']);
            if ($request->hasFile('image_product')) {
                if($product->image_product != 'products/default.jpg'){
                $destination = public_path("storage\\" . $product->image_product);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
                }                 
                    $product->image_product = $request->file('image_product')->storeAs('products',$product->id.'.'.$request->file('image_product')->extension(),'public');
                }
            $product->name = $product_data['name'];
            $product->reference = $product_data['reference'];
            $product->code = $product_data['code'];
            $product->description = $product_data['description'];
            $product->status_product = $product_data['status_product'];
            $product->purchase_price = $product_data['purchase_price'];
            $product->sale_price = $product_data['sale_price'];
            $product->product_category_id = $product_data['product_category_id'];
            $product->save();

            return response()->json(['msg' => 'Producto actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => $th->getMessage().'Error al actualizar el producto'], 500);
        }
    }
    public function destroy(Request $request)
    {
        try {
            $product_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $product = Product::find($product_data['id']);
            if ($product->image_product != "products/default.jpg") {
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
