<?php

namespace App\Http\Controllers;

use App\Models\Finance;
use App\Models\ProductSale;
use App\Models\ProductStore;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ProductSaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

   /**
 * Registra la venta de un producto a un estudiante en un curso académico.
 *
 * Reduce el stock en `ProductStore` y crea o actualiza una entrada en `ProductSale`.
 * También genera un registro en `Finance` como ingreso para la academia.
 *
 * @authenticated
 * @bodyParam enrollment_id integer required ID de la academia. Example: 4
 * @bodyParam id integer required ID del registro en `product_store` (stock específico). Example: 25
 * @bodyParam student_id integer required ID del estudiante. Example: 12
 * @bodyParam course_id integer required ID del curso. Example: 6
 * @bodyParam cant number required Cantidad del producto vendida. Example: 2
 *
 * @response 200 {"msg": "Producto asigando correctamente"}
 * @response 500 {"msg": "[error]Error interno del sistema"}
 */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'enrollment_id' => 'required|numeric',
                'id' => 'required|numeric',
                'student_id' => 'required|numeric',
                'cant' => 'required|numeric',
                'course_id' => 'required|numeric'

            ]);
            $productstore = ProductStore::find($data['id']);
            $productstore->product_quantity = $data['cant'];
            $productstore->product_exit = $productstore->product_exit - $data['cant'];
            $productstore->save();

            $price = $productstore->product->value('sale_price');
            $productSale = ProductSale::where('enrollment_id', $data['enrollment_id'])->where('product_store_id', $data['id'])->where('course_id', $data['course_id'])->where('student_id', $data['student_id'])->whereDate('data', Carbon::now())->first();
            
            if ($productSale) {
                $productSale->cant = $productSale->cant + $data['cant'];
                $productSale->price = $productSale->price + $price*$data['cant'];
                $productSale->save();
            }
            else{
                $productSale = new ProductSale();
                $productSale->product_store_id = $data['id'];
                $productSale->student_id = $data['student_id'];
                $productSale->enrollment_id = $data['enrollment_id'];
                $productSale->course_id = $data['course_id'];
                $productSale->cant = $data['cant'];
                $productSale->price = $price*$data['cant'];
                $productSale->data = Carbon::now();
                $productSale->save();
            }
                $finance = Finance::orderBy('control', 'desc')->first();
                if($finance)
                    {
                        $control = $finance->control+1;
                    }
                    else {
                        $control = 1;
                    }
                $finance = new Finance();
                $finance->control = $control;
                $finance->operation = 'Ingreso';
                $finance->amount = $price*$data['cant'];
                $finance->comment = 'Venta de Productos';
                $finance->enrollment_id = $data['enrollment_id'];
                $finance->type = 'Academia';
                $finance->revenue_id = 4;
                $finance->data = Carbon::now();                
                $finance->file = '';
                $finance->save();
            //}
            
             return response()->json(['msg' =>'Producto asigando correctamente',], 200);
        } catch (\Throwable $th) {
        return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    /**
 * Lista las ventas de productos realizadas a un estudiante en un curso específico.
 *
 * Incluye nombre del producto, precio, cantidad, imagen y fecha de venta.
 *
 * @authenticated
 * @queryParam course_id integer required ID del curso. Example: 6
 * @queryParam enrollment_id integer required ID de la academia. Example: 4
 * @queryParam student_id integer required ID del estudiante. Example: 12
 *
 * @response 200 {
 *   "productsales": [
 *     {
 *       "id": 8,
 *       "product_id": 30,
 *       "store_id": 5,
 *       "nameProduct": "Shampoo Profesional",
 *       "price": 10000,
 *       "cant": 2,
 *       "image_product": "products/30.jpg",
 *       "student_id": 12,
 *       "data": "2025-11-21"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "[error]Error al mostrar los productos"}
 */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'course_id' => 'required|numeric',
                'enrollment_id' => 'required|numeric',
                'student_id' => 'required|numeric'
            ]);
            $productStudent = ProductSale::where('course_id', $data['course_id'])->where('enrollment_id', $data['enrollment_id'])->where('student_id', $data['student_id'])->get()->map(function ($query) {
                return [
                    'id' => $query->id,
                    'product_id' => $query->productstore->product_id,
                    'store_id' => $query->productstore->store_id,
                    'nameProduct' => $query->productstore->product->name,
                    'price' => $query->price,
                    'cant' => $query->cant,
                    'image_product' => $query->productstore->product->image_product,
                    //'nameStudent' => $query->student->name.' '.$query->student->surname.' '.$query->student->second_surname,
                    'student_id' => $query->student_id,
                    'data' => $query->data
                ];
            })->sortByDesc('data')->values();
            return response()->json(['productsales' => $productStudent], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los productos"], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductSale $productSale)
    {
        //
    }

    /**
 * Elimina una venta de producto a un estudiante.
 *
 * Restaura la cantidad vendida al stock (`product_exit`) y ajusta o elimina el registro financiero asociado.
 *
 * @authenticated
 * @bodyParam id integer required ID de la venta (`product_sale`). Example: 8
 *
 * @response 200 {"msg": "Producto desasigando correctamente"}
 * @response 500 {"msg": "[error]Error interno del sistema"}
 */
    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric',

            ]);
            $productSale = ProductSale::find($data['id']);
            $productstore = ProductStore::where('id', $productSale->product_store_id)->first();
            $productstore->product_quantity = $productSale->cant;
            $productstore->product_exit = $productstore->product_exit + $productSale->cant;
            $productstore->save();
            $finance = Finance::where('enrollment_id', $productSale->enrollment_id)->whereDate('data', $productSale->data)->orderByDesc('control')->first();
            if($finance){
                $temp = $finance->amount - $productSale->price;
                if($temp <= 0){
                    $finance->delete();
                }else{
                    $finance->amount = $temp;
                    $finance->save();
                }
                
            }
            $productSale->delete();
            
             return response()->json(['msg' =>'Producto desasigando correctamente',], 200);
        } catch (\Throwable $th) {
        return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }
}
