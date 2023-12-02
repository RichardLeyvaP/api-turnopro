<?php

namespace App\Http\Controllers;

use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\ClientProfessional;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStore;
use App\Models\Reservation;
use App\Models\Service;
use App\Services\ClientProfessionalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{

    public function index()
    {
        try {             
            Log::info( "Entra a buscar las orders");
            $orders = Order::with(['car.clientProfessional.professional', 'car.clientProfessional.client', 'productStore.product', 'branchServiceProfessional.branchService.service'])->has('productStore.product')->orHas('branchServiceProfessional.branchService.service')->get();
            /*$car = Order::join('cars', 'cars.id', '=', 'orders.car_id')->join('client_professional', 'client_professional.id', '=', 'cars.client_professional_id')->join('clients', 'clients.id', '=', 'client_professional.client_id')->join('professionals', 'professionals.id', '=', 'client_professional.professional_id')->leftjoin('product_store', 'product_store.id', '=', 'orders.product_store_id')->leftjoin('products', 'products.id', '=', 'product_store.product_id')->leftjoin('branch_service_professional', 'branch_service_professional.id', '=', 'orders.branch_service_professional_id')->leftjoin('branch_service', 'branch_service.id', '=', 'branch_service_professional.branch_service_id')->leftjoin('services', 'services.id', '=', 'branch_service.service_id')->get(['cars.*', 'clients.*', 'professionals.*', 'products.*', 'services.*','orders.*']);*/
            return response()->json(['orders' => $orders], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los carros"], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info("Compra de Productos y servicio prestado");
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'client_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'product_id' => 'required|numeric',
                'service_id' => 'required|numeric',
                'type' => 'required'

            ]);
            $client_professional_id = ClientProfessional::where('professional_id', $data['professional_id'])->where('client_id', $data['client_id'])->value('id');/*0;
            $result = ClientProfessional::join('clients', 'clients.id', '=', 'client_professional.client_id')->join('rofessional', 'rofessional.id', '=', 'client_professional.professional_id')->where('client_professional.client_id',$data['client_id'])->where('client_rofessional.professional_id',$data['professional_id'])->get('client_professional.*');*/
            /*if (!$client_professional_id) {
                $clientprofessional = new ClientProfessional();
                $clientprofessional->client_id = $data['client_id'];
                $clientprofessional->professional_id = $data['professional_id'];
                $clientprofessional->save();
                $client_professional_id = $clientprofessional->id;
            }*/
            /*else {                
            $client_professional_id = $result[0]['id'];
            }*/
            $productcar = Car::where('client_professional_id', $client_professional_id)->whereDate('updated_at', Carbon::today())->first();
            
            if ($data['service_id'] == 0 && $data['type'] == 'product') {
                //$product = Product::join('product_store', 'product_store.product_id', '=', 'products.id')->where('product_store.id', $data['product_id'])->get(['products.*']);
                $productStore = ProductStore::with('product')->where('id', $data['product_id'])->first();
                $sale_price = $productStore->product()->first()->sale_price;
                if ($productcar) {
                    $car = Car::find($productcar->id);
                    $car->amount = $productcar->amount + $sale_price;
                }
                else {
                    $car = new Car();
                    $car->client_professional_id = $client_professional_id;
                    $car->amount = $sale_price;
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
                 $order->branch_service_professional_id = null;
                 $order->is_product = true;
                 $order->price = $sale_price;               
                 $order->request_delete = false;
                 $order->save();
             }//end if product

            if ($data['product_id'] == 0 && $data['type'] == 'service') {
                $branchServiceprofessional = BranchServiceProfessional::with('branchService.service')->where('id', $data['service_id'])->first();
                $service = $branchServiceprofessional->branchService->service;
                if ($productcar) {
                    $car = Car::find($productcar->id);
                    $car->amount = $productcar->amount + $service->price_service+$service->profit_percentaje/100;
                }
                else {
                    $car = new Car();
                    $car->client_professional_id = $client_professional_id;
                    $car->amount = $service->price_service+$service->profit_percentaje/100;
                    $car->pay = false;
                    $car->active = false;
                }
                $car->save();
                $car_id = $car->id;
                 $order = new Order();
                 $order->car_id = $car_id;
                 $order->product_store_id = null;
                 $order->branch_service_professional_id = $data['service_id'];
                 $order->is_product = false;
                 $order->price = $service->price_service+$service->profit_percentaje/100;   
                 $order->request_delete = false;
                 $order->save();

                 DB::commit();
            }//end if service
             return response()->json(['msg' =>'Pedido Agregado correctamente','order_id' =>$order->id ], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            DB::rollback();
        return response()->json(['msg' => 'Error al solicitar un pedido'], 500);
        }
    }

    /*public function order_service_store($data)
    {
        Log::info("reservacion de servicio prestado");
        try {
                 $order = new Order();
                 $order->car_id = $data['car_id'];
                 $order->product_store_id = null;
                 $order->branch_service_professional_id = $data['branch_service_professional_id'];
                 $order->is_product = false;
                 $order->price = $data['price'];   
                 $order->request_delete = false;
                 $order->save();
             return $order;
        } catch (\Throwable $th) {
            Log::error($th);
            DB::rollback();
        return response()->json(['msg' => 'Error al solicitar un pedido'], 500);
        }
    }*/

    public function show(Request $request)
    {
        try {             
            Log::info( "Entra a buscar los carros");
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            /*$car = Order::join('cars', 'cars.id', '=', 'orders.car_id')->join('client_professional', 'client_professional.id', '=', 'cars.client_professional_id')->join('clients', 'clients.id', '=', 'client_professional.client_id')->join('professionals', 'professionals.id', '=', 'client_professional.professional_id')->leftjoin('product_store', 'product_store.id', '=', 'orders.product_store_id')->leftjoin('products', 'products.id', '=', 'product_store.product_id')->leftjoin('branch_service_professional', 'branch_service_professional.id', '=', 'orders.branch_service_professional_id')->leftjoin('branch_service', 'branch_service.id', '=', 'branch_service_professional.branch_service_id')->leftjoin('services', 'services.id', '=', 'branch_service.service_id')->where('orders.id', $data['id'])->get(['cars.*', 'clients.*', 'professionals.*', 'products.*', 'services.*','orders.*']);*/
            $order = Order::with(['car.clientProfessional.professional', 'car.clientProfessional.client', 'productStore.product', 'branchServiceProfessional.branchService.service'])->has('productStore.product')->orHas('branchServiceProfessional.branchService.service')->get();
            return response()->json(['order' => $order], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las orders"], 500);
        }
    }

    public function update(Request $request)
    {
        Log::info("Actualizar orden");
        Log::info($request);
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'request_delete' => 'required|boolean'
            ]);
            $order = Order::find($data['id']);
            $order->request_delete = $data['request_delete'];
            $order->save();
            return response()->json(['msg' => 'Solicitud de eliminar la orden hecha correctamente'], 200);
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
            $car = Car::find($order->car_id);
            if ($order->is_product) {
                $productstore = ProductStore::find($order->product_store_id);
                $productstore->product_quantity = 1;
                $productstore->product_exit = $productstore->product_exit + 1;
                $productstore->save();
            }
            elseif (!$order->is_product) {
                $branchServiceprofessional = BranchServiceProfessional::find($order->branch_service_professional_id);
                $service = $branchServiceprofessional->branchService->service;
                $reservation = Reservation::where('car_id', $order->car_id)->first();
                $reservation->final_hour = Carbon::parse($reservation->final_hour)->subMinutes($service->duration_service)->toTimeString();
                $reservation->total_time = Carbon::parse($reservation->total_time)->subMinutes($service->duration_service)->format('H:i:s');
                $reservation->save();
            }
            $order->delete();
            if($car->amount = $car->amount - $order->price)
            {
                $car->save();
            }
            else {
                $car->delete();
            }
            
            return response()->json(['msg' =>'Solicitud de eliminar la orden hecha correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al hacer la solicitud de eliminar la orden'], 500);
        }
    }
}
