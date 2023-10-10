<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\ClientPerson;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStore;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Entra a buscar los carros");
            $car = Order::join('cars', 'cars.id', '=', 'orders.car_id')->join('client_person', 'client_person.id', '=', 'cars.client_person_id')->join('clients', 'clients.id', '=', 'client_person.client_id')->join('people', 'people.id', '=', 'client_person.person_id')->leftjoin('product_store', 'product_store.id', '=', 'orders.product_store_id')->leftjoin('products', 'products.id', '=', 'product_store.product_id')->leftjoin('branch_service_person', 'branch_service_person.id', '=', 'orders.branch_service_person_id')->leftjoin('branch_service', 'branch_service.id', '=', 'branch_service_person.branch_service_id')->leftjoin('services', 'services.id', '=', 'branch_service.service_id')->get(['cars.*', 'clients.*', 'people.*', 'products.*', 'services.*','orders.*']);
            return response()->json(['cars' => $car], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los carros"], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info("Compra de Productos y servicio prestado");
        Log::info($request);
        try {
            $data = $request->validate([
                'client_id' => 'required|numeric',
                'person_id' => 'required|numeric',
                'product_id' => 'required|numeric',
                'service_id' => 'required|numeric'

            ]);
            $client_person_id = 0;
            $result = ClientPerson::join('clients', 'clients.id', '=', 'client_person.client_id')->join('people', 'people.id', '=', 'client_person.person_id')->where('client_person.client_id',$data['client_id'])->where('client_person.person_id',$data['person_id'])->get('client_person.*');
            if (!count($result)) {
                $clientperson = new ClientPerson();
                $clientperson->client_id = $data['client_id'];
                $clientperson->person_id = $data['person_id'];
                $clientperson->save();
                $client_person_id = $clientperson->id;
            }
            else {                
            $client_person_id = $result[0]['id'];
            }
            $productcar = Car::where('client_person_id', $client_person_id)->whereDate('updated_at', Carbon::today())->first();
            
            if ($data['product_id'] != 0) {
                $product = Product::join('product_store', 'product_store.product_id', '=', 'products.id')->where('product_store.id', $data['product_id'])->get(['products.*']);
                if ($productcar) {
                    $car = Car::find($productcar->id);
                    $car->amount = $productcar->amount + $product[0]['sale_price'];
                }
                else {
                    $car = new Car();
                    $car->client_person_id = $client_person_id;
                    $car->amount = $product[0]['sale_price'];
                    $car->pay = false;
                    $car->active = false;
                }
                $car->save();
                $car_id = $car->id;
                    //rebajar la existencia
                $productstore = ProductStore::find($data['product_id']);
                $productstore->product_quantity = 1;
                $productstore->product_exit = $productstore->product_exit - 1;
                $productstore->save();
                             
                 $order = new Order();
                 $order->car_id = $car_id;
                 $order->product_store_id = $data['product_id'];
                 $order->branch_service_person_id = null;
                 $order->is_product = true;
                 $order->price = $product[0]['sale_price'];               
                 $order->request_delete = false;
                 $order->save();
             }//end if product
             if ($data['service_id'] != 0) {
                $service = Service::join('branch_service', 'branch_service.service_id', '=', 'services.id')->join('branch_service_person', 'branch_service_person.branch_service_id', '=', 'branch_service.id')->where('branch_service_person.id', $data['service_id'])->get('services.*');
                if ($productcar) {
                    $car = Car::find($productcar->id);
                    $car->amount = $productcar->amount + $service[0]['price_service']+$service[0]['profit_percentaje']/100;
                }
                else {
                    $car = new Car();
                    $car->client_person_id = $client_person_id;
                    $car->amount = $service[0]['price_service']+$service[0]['profit_percentaje']/100;
                    $car->pay = false;
                    $car->active = false;
                }
                $car->save();
                $car_id = $car->id;
                 $order = new Order();
                 $order->car_id = $car_id;
                 $order->product_store_id = null;
                 $order->branch_service_person_id = $data['service_id'];
                 $order->is_product = false;
                 $order->price = $service[0]['price_service']+$service[0]['profit_percentaje']/100;   
                 $order->request_delete = false;
                 $order->save();
            }//end if service
             return response()->json(['msg' =>'Empleado asignado correctamente al cliente','order_id' => $order->id], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => 'Error al solicitar un pedido'], 500);
        }
    }

    public function show(Request $request)
    {
        try {             
            Log::info( "Entra a buscar los carros");
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $car = Order::join('cars', 'cars.id', '=', 'orders.car_id')->join('client_person', 'client_person.id', '=', 'cars.client_person_id')->join('clients', 'clients.id', '=', 'client_person.client_id')->join('people', 'people.id', '=', 'client_person.person_id')->leftjoin('product_store', 'product_store.id', '=', 'orders.product_store_id')->leftjoin('products', 'products.id', '=', 'product_store.product_id')->leftjoin('branch_service_person', 'branch_service_person.id', '=', 'orders.branch_service_person_id')->leftjoin('branch_service', 'branch_service.id', '=', 'branch_service_person.branch_service_id')->leftjoin('services', 'services.id', '=', 'branch_service.service_id')->where('orders.id', $data['id'])->get(['cars.*', 'clients.*', 'people.*', 'products.*', 'services.*','orders.*']);
            return response()->json(['cars' => $car], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los carros"], 500);
        }
    }

    public function update(Request $request)
    {
        Log::info("Actualizar orden");
        Log::info($request);
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $order = Order::find($data['id']);
            $order->request_delete = true;
            $order->save();
            return response()->json(['msg' => 'Solicitud de eliminar la orden hecha correctamente'], 500);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al hacer la solicitud de eliminar la orden'], 500);
        }
    }

    public function destroy(Request $request)
    {
        Log::info("Eliminar orden");
        Log::info($request);
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $order = Order::find($data['id']);
            $car = Car::whereDate('updated_at', $order->updated_at)->find($order->car_id);
            if ($order->is_product = true) {
                $productstore = ProductStore::find($order->product_id);
                $productstore->product_quantity = 1;
                $productstore->product_exit = $productstore->product_exit + 1;
                $productstore->save();
            }
            $order->delete();
            if($car->amount = $car->amount - $order->price)
            {
                $car->save();
            }
            else {
                $car->delete();
            }
            
            return response()->json(['msg' =>'Solicitud de eliminar la orden hecha correctamente'], 500);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al hacer la solicitud de eliminar la orden'], 500);
        }
    }
}
