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

    /**
 * Lista todos los productos con su categoría y marca de imagen con timestamp.
 *
 * La URL de la imagen incluye un timestamp (`?${now}`) para evitar caché del navegador.
 *
 * @authenticated
 *
 * @response 200 {
 *   "products": [
 *     {
 *       "id": 1,
 *       "name": "Shampoo Reparador",
 *       "reference": "SR-2025",
 *       "image_product": "products/1.jpg?$2025-11-21 16:30:00",
 *       "productcategory": {
 *         "id": 3,
 *         "name": "Cuidado Capilar"
 *       }
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los productos"}
 */
    public function index()
    {
        try {             
            $now = Carbon::now();
            $products = Product::with('productcategory')->get();
            foreach ($products as $product) {
                // Agrega el dato adicional que necesitas al campo image_product
                $product->image_product = $product->image_product.'?$'.$now;
            }
            return response()->json(['products' => $products], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los productos"], 500);
        }
    }

    /**
 * Crea un nuevo producto.
 *
 * Permite adjuntar una imagen opcional. Si no se adjunta, se usa `products/default.jpg`.
 * Los campos `worker_discount` y `commission_rate` son opcionales (por defecto 0).
 *
 * @authenticated
 * @bodyParam name string required Nombre del producto. Min: 3 chars. Example: "Acondicionador"
 * @bodyParam reference string required Referencia interna. Min: 3 chars. Example: "AC-001"
 * @bodyParam code string required Código único. Example: "PRD001"
 * @bodyParam description string optional Descripción del producto. Min: 3 chars. Example: "Hidrata el cabello"
 * @bodyParam status_product string required Estado (ej. "En venta", "Agotado"). Example: "En venta"
 * @bodyParam purchase_price number required Precio de compra. Example: 2500
 * @bodyParam sale_price number optional Precio de venta. Example: 5000
 * @bodyParam product_category_id integer required ID de la categoría. Example: 3
 * @bodyParam worker_discount number optional Descuento para trabajadores (% o monto). Example: 10
 * @bodyParam commission_rate number optional Porcentaje de comisión. Example: 5
 * @bodyParam image_product file optional Imagen del producto (JPG/PNG).
 *
 * @response 200 {"msg": "Producto insertado correctamente"}
 * @response 500 {"msg": "Error al insertar el producto"}
 */
    public function store(Request $request)
    {
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
                'product_category_id' => 'required|numeric',
                'worker_discount' => 'nullable|numeric', // Cambiado de required a nullable
                'commission_rate' => 'nullable|numeric'
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
            $product->worker_discount = $product_data['worker_discount']?? 0; 
            $product->commission_rate =  $product_data['commission_rate']?? 0;
            $product->save();

            $filename = "products/default.jpg";
            if ($request->hasFile('image_product')) {
                $filename = $request->file('image_product')->storeAs('products',$product->id.'.'.$request->file('image_product')->extension(),'public');
            }
            $product->image_product = $filename;
            $product->save();

            return response()->json(['msg' => 'Producto insertado correctamente'], 200);
        } catch (\Throwable $th) {
        return response()->json(['msg' => 'Error al insertar el producto'], 500);
        }
    }

    /**
 * Obtiene el producto más vendido por sucursal en una fecha específica.
 *
 * Incluye el top por sucursal y el top global de la empresa.
 *
 * @authenticated
 * @queryParam Date date required Fecha de consulta (Y-m-d). Example: "2025-11-20"
 *
 * @response 200 {
 *   "branches": [
 *     {
 *       "nameBranch": "Sucursal Centro",
 *       "nameProduct": "Shampoo Reparador",
 *       "cantProduct": 25
 *     }
 *   ],
 *   "Product": "Shampoo Reparador",
 *   "cantProduct": 85
 * }
 * @response 500 {"msg": "[error]La branch no obtuvo ganancias en este dia"}
 */
    public function product_mostSold_date(Request $request)
    {
        try {
            $data = $request->validate([
                'Date' => 'required|date'
           ]);
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

    /**
 * Lista productos ordenados por ventas del día actual.
 *
 * Si se envía `branch_id`, filtra por esa sucursal. Incluye ventas en órdenes y caja.
 *
 * @authenticated
 * @queryParam branch_id integer optional ID de la sucursal. Example: 3
 *
 * @response 200 [
 *   {
 *     "id": 1,
 *     "name": "Shampoo Reparador",
 *     "orders_count": 32,
 *     "image_product": "products/1.jpg"
 *   }
 * ]
 * @response 500 {"msg": "[error]Error interno del sistema"}
 */
    public function product_mostSold(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'nullable'
            ]);
            if ($data['branch_id'] != null) {
                $products = Product::with(['orders' => function ($query) use ($data){
                    $query->selectRaw('SUM(cant) as total_sale_price')
                        ->groupBy('product_store.product_id')->whereDate('data', Carbon::now())->whereHas('productStore.store.branches', function ($query) use ($data){
                            $query->where('branch_id', $data['branch_id']);
                            }); // Agrupar por el ID del producto en la tabla intermedia
                },'cashiersales' => function ($query) use ($data){
                    $query->selectRaw('product_id, SUM(cant) as total_cashier')
                        ->groupBy('product_id')
                        ->whereDate('data', Carbon::now())->where('cashiersales.branch_id', $data['branch_id']);
                }])
                ->get()
                ->map(function ($product) {
                    $total_sale_price = $product->orders->isEmpty() ? 0 : $product->orders->first()->total_sale_price;
                    $total_cashier = $product->cashiersales->isEmpty() ? 0 : $product->cashiersales->first()->total_cashier;
                    
                    // Calcular el valor total de ventas y sumarle total_cant
                    $total_sales = $total_sale_price + $total_cashier;
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
            }else {
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
                }])
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
            }
          return response()->json($products, 200, [], JSON_NUMERIC_CHECK);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Error interno del sistema"], 500);
       }
    }

    /**
 * Lista productos más vendidos en un rango de fechas.
 *
 * Soporta filtrado por sucursal. Incluye ventas de órdenes, caja y ventas directas.
 *
 * @authenticated
 * @queryParam branch_id integer optional ID de la sucursal (0 = todas). Example: 3
 * @queryParam startDate date optional Fecha de inicio (Y-m-d). Example: "2025-11-01"
 * @queryParam endDate date optional Fecha de fin (Y-m-d). Example: "2025-11-30"
 *
 * @response 200 [
 *   {
 *     "id": 1,
 *     "name": "Shampoo Reparador",
 *     "orders_count": 120,
 *     "image_product": "products/1.jpg"
 *   }
 * ]
 * @response 500 {"msg": "[error]Error interno del sistema"}
 */
    public function product_mostSold_periodo(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'nullable',
                'startDate' => 'nullable',
                'endDate' => 'nullable'
            ]);
            if ($data['branch_id'] != 0) {
            $products = Product::with(['orders' => function ($query) use($data){
                $query->selectRaw('SUM(cant) as total_sale_price')
                    ->groupBy('product_store.product_id')->whereDate('data', '>=', $data['startDate'])->whereDate('data', '<=', $data['endDate'])->whereHas('productStore.store.branches', function ($query) use ($data){
                        $query->where('branch_id', $data['branch_id']);
                        }); // Agrupar por el ID del producto en la tabla intermedia
            },'cashiersales' => function ($query)  use($data){
                $query->selectRaw('product_id, SUM(cant) as total_cashier')
                    ->groupBy('product_id')->whereDate('data', '>=', $data['startDate'])->whereDate('data', '<=', $data['endDate'])->where('cashiersales.branch_id', $data['branch_id']);
            }])
            ->get()
            ->map(function ($product) {
                $total_sale_price = $product->orders->isEmpty() ? 0 : $product->orders->first()->total_sale_price;
                $total_cashier = $product->cashiersales->isEmpty() ? 0 : $product->cashiersales->first()->total_cashier;
                
                // Calcular el valor total de ventas y sumarle total_cant
                $total_sales = $total_sale_price + $total_cashier;
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
            }else {
            $products = Product::with(['orders' => function ($query) use($data){
                $query->selectRaw('SUM(cant) as total_sale_price')
                    ->groupBy('product_store.product_id')->whereDate('data', '>=', $data['startDate'])->whereDate('data', '<=', $data['endDate']); // Agrupar por el ID del producto en la tabla intermedia
            }, 'productSales' => function ($query) {
                $query->selectRaw('SUM(cant) as total_cant')
                    ->groupBy('product_store.product_id'); // Agrupar por el ID del producto en la tabla intermedia
            },'cashiersales' => function ($query)  use($data){
                $query->selectRaw('product_id, SUM(cant) as total_cashier')
                    ->groupBy('product_id')->whereDate('data', '>=', $data['startDate'])->whereDate('data', '<=', $data['endDate']);
            }])
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
            }
           
          return response()->json($products, 200, [], JSON_NUMERIC_CHECK);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Error interno del sistema"], 500);
       }
    }

    /**
 * Lista productos con bajo stock (stock ≤ nivel de alerta).
 *
 * Si se envía `branch_id`, filtra por esa sucursal.
 *
 * @authenticated
 * @queryParam branch_id integer optional ID de la sucursal (0 = todas). Example: 3
 *
 * @response 200 [
 *   {
 *     "name": "Acondicionador",
 *     "product_exit": 5,
 *     "stock_depletion": 10,
 *     "reference": "AC-001",
 *     "code": "PRD002",
 *     "store": "Av. Siempre Viva 123"
 *   }
 * ]
 * @response 500 {"msg": "[error]La branch no obtuvo ganancias en este dia"}
 */
    public function product_stock(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'nullable|numeric'
            ]);
           if ($data['branch_id'] !=0) {
            $products = ProductStore::whereColumn('product_exit', '<=', 'stock_depletion')->whereHas('store.branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->get()->map(function ($query){
                return [
                    'name' => $query->product->name,
                    'product_exit' => $query->product_exit,
                    'stock_depletion' => $query->stock_depletion,
                    'reference' =>$query->product->reference,
                    'code' => $query->product->code,
                    'store' => $query->store->address
                ];
            });
           }
           else {
            $products = ProductStore::whereColumn('product_exit', '<=', 'stock_depletion')->with('store', 'product')->get()->map(function ($query){
                return [
                    'name' => $query->product->name,
                    'product_exit' => $query->product_exit,
                    'stock_depletion' => $query->stock_depletion,
                    'reference' =>$query->product->reference,
                    'code' => $query->product->code,
                    'store' => $query->store->address
                ];
            });
           }
        
          return response()->json($products, 200, [], JSON_NUMERIC_CHECK);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."La branch no obtuvo ganancias en este dia"], 500);
       }
    }

    /**
 * Muestra los detalles de un producto específico.
 *
 * @authenticated
 * @queryParam id integer required ID del producto. Example: 1
 *
 * @response 200 {
 *   "product": {
 *     "id": 1,
 *     "name": "Shampoo Reparador",
 *     "reference": "SR-2025",
 *     "sale_price": 5000,
 *     "product_category_id": 3
 *   }
 * }
 * @response 500 {"msg": "Error al mostrar el producto"}
 */
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

    /**
 * Obtiene los 10 productos más vendidos en una sucursal.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 *
 * @response 200 {
 *   "products": [
 *     { "id": 1, "name": "Shampoo", "orders_count": 45 },
 *     { "id": 2, "name": "Acondicionador", "orders_count": 38 }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar el producto"}
 */
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

    /**
 * Actualiza un producto existente.
 *
 * Permite reemplazar la imagen. Si el archivo anterior no es el predeterminado, se elimina del almacenamiento.
 * Maneja `commission_rate` como `null` si se envía el string `"null"`.
 *
 * @authenticated
 * @bodyParam id integer required ID del producto. Example: 1
 * @bodyParam name string required Nombre. Min: 3. Example: "Shampoo Profesional"
 * @bodyParam reference string required Referencia. Min: 3. Example: "SP-2025"
 * @bodyParam code string required Código. Example: "PRD001"
 * @bodyParam description string optional Descripción. Example: "Para todo tipo de cabello"
 * @bodyParam status_product string required Estado. Example: "En venta"
 * @bodyParam purchase_price number required Precio de compra. Example: 3000
 * @bodyParam sale_price number optional Precio de venta. Example: 6000
 * @bodyParam product_category_id integer required Categoría. Example: 3
 * @bodyParam worker_discount number required Descuento trabajador. Example: 15
 * @bodyParam commission_rate number optional Porcentaje de comisión. Example: 7
 * @bodyParam image_product file optional Nueva imagen.
 *
 * @response 200 {"msg": "Producto actualizado correctamente"}
 * @response 500 {"msg": "[error]Error al actualizar el producto"}
 */
    public function update(Request $request)
    {
        try {
            $request->merge([
                'commission_rate' => $request->commission_rate === 'null' ? null : $request->commission_rate
            ]);
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
                'product_category_id' => 'required|numeric',
                'worker_discount' => 'required|numeric',
                'commission_rate' => 'nullable|numeric',
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
            $product->worker_discount = $product_data['worker_discount'];
            $product->commission_rate =  $product_data['commission_rate']?? 0;
            $product->save();

            return response()->json(['msg' => 'Producto actualizado correctamente'], 200);
        } catch (\Throwable $th) {
        return response()->json(['msg' => $th->getMessage().'Error al actualizar el producto'], 500);
        }
    }

    /**
 * Elimina un producto.
 *
 * Si tiene una imagen personalizada (distinta de `products/default.jpg`), se elimina del almacenamiento.
 *
 * @authenticated
 * @bodyParam id integer required ID del producto a eliminar. Example: 1
 *
 * @response 200 {"msg": "producto eliminado correctamente"}
 * @response 500 {"msg": "Error al eliminar el producto"}
 */
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
