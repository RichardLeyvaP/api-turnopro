<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\ClientProfessional;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStore;
use App\Models\Professional;
use App\Models\Reservation;
use App\Services\OrderService;
use App\Services\TraceService;
use App\Traits\ProductExitTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    use ProductExitTrait;

    private OrderService $orderService;
    private TraceService $traceService;

    public function __construct(OrderService $orderService, TraceService $traceService)
    {
        $this->orderService = $orderService;
        $this->traceService = $traceService;
    }

    public function index()
    {
        try {             
            Log::info( "Entra a buscar las orders");
            $orders = Order::with(['car.clientProfessional.professional', 'car.clientProfessional.client', 'productStore.product', 'branchServiceProfessional.branchService.service'])->has('productStore.product')->orHas('branchServiceProfessional.branchService.service')->get();
            /*$car = Order::join('cars', 'cars.id', '=', 'orders.car_id')->join('client_professional', 'client_professional.id', '=', 'cars.client_professional_id')->join('clients', 'clients.id', '=', 'client_professional.client_id')->join('professionals', 'professionals.id', '=', 'client_professional.professional_id')->leftjoin('product_store', 'product_store.id', '=', 'orders.product_store_id')->leftjoin('products', 'products.id', '=', 'product_store.product_id')->leftjoin('branch_service_professional', 'branch_service_professional.id', '=', 'orders.branch_service_professional_id')->leftjoin('branch_service', 'branch_service.id', '=', 'branch_service_professional.branch_service_id')->leftjoin('services', 'services.id', '=', 'branch_service.service_id')->get(['cars.*', 'clients.*', 'professionals.*', 'products.*', 'services.*','orders.*']);*/
            return response()->json(['orders' => $orders], 200, [], JSON_NUMERIC_CHECK);
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
                'car_id' => 'required|numeric',
                'product_id' => 'required|numeric',
                'service_id' => 'required|numeric',
                'type' => 'required'

            ]);
            if ($data['service_id'] == 0 && $data['type'] == 'product') {
                $order = $this->orderService->product_order_store($data);
             }
            if ($data['product_id'] == 0 && $data['type'] == 'service') {
                $order = $this->orderService->service_order_store($data);             
            }
            DB::commit();
             return response()->json(['msg' =>'Pedido Agregado correctamente','order_id' =>$order->id ], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            DB::rollback();
            Log::info($th);
        return response()->json(['msg' => $th->getMessage().'Error al solicitar un pedido'], 500);
        }
    }

    public function store_web(Request $request)
    {
        Log::info("Compra de Productos y servicio prestado");
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'car_id' => 'required|numeric',
                'product_id' => 'required|numeric',
                'service_id' => 'required|numeric',
                'type' => 'required',
                'cant' => 'required'

            ]);
            $car = Car::find($data['car_id']);            
            $branch = Branch::where('id', $request->branch_id)->first();
            if ($data['service_id'] == 0 && $data['type'] == 'product') {
                $order = $this->orderService->product_order_store($data);
                $trace = [
                    'branch' => $branch->name,
                    'cashier' => $request->nameProfessional,
                    'client' => $car->clientProfessional->client->name.' '.$car->clientProfessional->client->surname.' '.$car->clientProfessional->client->second_surname,
                    'amount' => $order->price,
                    'operation' => 'Agrega orden de Producto a un carro',
                    'details' => $order->productStore->product->name,
                    'description' => ''
                ];
                $this->traceService->store($trace);
                Log::info('$trace Pproduct');
                Log::info($trace);
             }
            if ($data['product_id'] == 0 && $data['type'] == 'service') {
                $order = $this->orderService->service_order_store($data);
                $trace = [
                    'branch' => $branch->name,
                    'cashier' => $request->nameProfessional,
                    'client' => $car->clientProfessional->client->name.' '.$car->clientProfessional->client->surname.' '.$car->clientProfessional->client->second_surname,
                    'amount' => $order->price,
                    'operation' => 'Agrega orden de Servicio a un carro',
                    'details' => $order->branchServiceProfessional->branchService->service->name,
                    'description' => ''
                ];
                $this->traceService->store($trace);
                Log::info('$trace Service');
                Log::info($trace);             
            }
            DB::commit();
             return response()->json(['msg' =>'Pedido Agregado correctamente','order_id' =>$order->id ], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            DB::rollback();
            Log::info($th);
        return response()->json(['msg' => $th->getMessage().'Error al solicitar un pedido'], 500);
        }
    }

    public function sales_periodo_branch(Request $request)
    {
        Log::info("Ventas de Productos y servicios prestados en un periodo");
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'startDate' => 'required|date',
                'endDate' => 'required|date'

            ]);
                $productSales = $this->orderService->sales_periodo_product($data);
                $serviceSales = $this->orderService->sales_periodo_service($data);      
             return response()->json(['ProductSales' =>$productSales, 'ServiceSales' => $serviceSales], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            DB::rollback();
        return response()->json(['msg' => $th->getMessage().'Error al solicitar un pedido'], 500);
        }
    }

    public function show(Request $request)
    {
        try {             
            Log::info( "Entra a buscar los carros");
            $data = $request->validate([
                'car_id' => 'required|numeric'
            ]);
            $car = Car::find($data['car_id']);
            $orders = Order::with(['product', 'service'])->where('car_id', $car->id)->get()->map(function ($order){
                return [
                    'id' => $order->id,
                    'car_id' => $order->car_id,
                    'request_delete' => $order->request_delete,
                    'name' => $order->is_product ? $order->productStore->product->name : $order->branchServiceProfessional->branchService->service->name,
                    'image' => $order->is_product ? $order->productStore->product->image_product : $order->branchServiceProfessional->branchService->service->image_service,
                    'price' => $order->price,
                    'category' => $order->is_product ? $order->productStore->product->productCategory->name : $order->branchServiceProfessional->type_service,
                ];
            });
            return response()->json(['orders' => $orders], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las orders"], 500);
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
            return response()->json(['msg' => 'Estado de la orden modificado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al hacer la solicitud de eliminar la orden'], 500);
        }
    }

    public function update_web(Request $request)
    {
        Log::info("Actualizar orden");
        Log::info($request);
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'request_delete' => 'required|boolean'
            ]);
            $order = Order::find($data['id']);
            $car = Car::find($order->car_id);
            $branch = Branch::where('id', $request->branch_id)->first();
            if ($order->is_product) {                
                    $trace = [
                        'branch' => $branch->name,
                        'cashier' => $request->nameProfessional,
                        'client' => $car->clientProfessional->client->name.' '.$car->clientProfessional->client->surname.' '.$car->clientProfessional->client->second_surname,
                        'amount' => $order->price,
                        'operation' => 'Deniega solicitud de eliminar orden de Producto de un carro',
                        'details' => $order->productStore->product->name,
                        'description' => ''
                    ];
                    $this->traceService->store($trace);
                    Log::info('$trace Pproduct');
                    Log::info($trace);
                }
                elseif (!$order->is_product) {
                    $trace = [
                        'branch' => $branch->name,
                        'cashier' => $request->nameProfessional,
                        'client' => $car->clientProfessional->client->name.' '.$car->clientProfessional->client->surname.' '.$car->clientProfessional->client->second_surname,
                        'amount' => $order->price,
                        'operation' => 'Deniega solicitud de eliminar orden de Servicio de un carro',
                        'details' => $order->branchServiceProfessional->branchService->service->name,
                        'description' => ''
                    ];
                    $this->traceService->store($trace);
                    Log::info('$trace Service');
                    Log::info($trace);      
    
                }
            $order->request_delete = $data['request_delete'];
            $order->save();
            return response()->json(['msg' => 'Estado de la orden modificado correctamente'], 200);
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
            Log::info($order);
            Log::info($car);
            if ($order->is_product) {                
            Log::info("Es producto");
                $productstore = ProductStore::find($order->product_store_id);
                $productstore->product_quantity = 1;
                $productstore->product_exit = $productstore->product_exit + 1;
                $productstore->save();
                //todo pendiente para revisar importante
               // $this->actualizarProductExit($productstore->product_id, $productstore->service_id); 
            }
            elseif (!$order->is_product) {
                Log::info("servicio");
                $branchServiceprofessional = BranchServiceProfessional::find($order->branch_service_professional_id);
                Log::info($branchServiceprofessional);
                $service = $branchServiceprofessional->branchService->service;
                Log::info("card:".$car);
                $reservation = Reservation::where('car_id', $order->car_id)->first();
                Log::info($reservation);
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
            Log::info("Eliminar orden:$th");
            return response()->json(['msg' => 'Error al hacer la solicitud de eliminar la orden'], 500);
        }
    }

    public function destroy_web(Request $request)
    {
        Log::info("Eliminar orden");
        Log::info($request);
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $order = Order::find($data['id']);
            $car = Car::find($order->car_id);
            $branch = Branch::where('id', $request->branch_id)->first();
            Log::info($order);
            Log::info($car);
            if ($order->is_product) {                
            Log::info("Es producto");
            
                $productstore = ProductStore::find($order->product_store_id);
                $cant = $order->price / $productstore->product->sale_price;
                $productstore->product_quantity = $cant;
                $productstore->product_exit = $productstore->product_exit + $cant;
                $productstore->save();
                $trace = [
                    'branch' => $branch->name,
                    'cashier' => $request->nameProfessional,
                    'client' => $car->clientProfessional->client->name.' '.$car->clientProfessional->client->surname.' '.$car->clientProfessional->client->second_surname,
                    'amount' => $order->price,
                    'operation' => 'Elimina orden de Producto de un carro',
                    'details' => $order->productStore->product->name,
                    'description' => ''
                ];
                $this->traceService->store($trace);
                Log::info('$trace Pproduct');
                Log::info($trace);
                //todo pendiente para revisar importante
               // $this->actualizarProductExit($productstore->product_id, $productstore->service_id); 
            }
            elseif (!$order->is_product) {
                Log::info("servicio");
                $branchServiceprofessional = BranchServiceProfessional::find($order->branch_service_professional_id);
                Log::info($branchServiceprofessional);
                $service = $branchServiceprofessional->branchService->service;
                Log::info("card:".$car);
                $reservation = Reservation::where('car_id', $order->car_id)->first();
                Log::info($reservation);
                $reservation->final_hour = Carbon::parse($reservation->final_hour)->subMinutes($service->duration_service)->toTimeString();
                $reservation->total_time = Carbon::parse($reservation->total_time)->subMinutes($service->duration_service)->format('H:i:s');
                $reservation->save();
                $trace = [
                    'branch' => $branch->name,
                    'cashier' => $request->nameProfessional,
                    'client' => $car->clientProfessional->client->name.' '.$car->clientProfessional->client->surname.' '.$car->clientProfessional->client->second_surname,
                    'amount' => $order->price,
                    'operation' => 'Elimina orden de Servicio de un carro',
                    'details' => $order->branchServiceProfessional->branchService->service->name,
                    'description' => ''
                ];
                $this->traceService->store($trace);
                Log::info('$trace Service');
                Log::info($trace);      

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
            Log::info("Eliminar orden:$th");
            return response()->json(['msg' => $th->getMessage().'Error al hacer la solicitud de eliminar la orden'], 500);
        }
    }
}
