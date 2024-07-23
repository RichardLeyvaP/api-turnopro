<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchProfessional;
use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\ClientProfessional;
use App\Models\Notification;
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
            $orders = Order::with(['car.clientProfessional.professional', 'car.clientProfessional.client', 'productStore.product', 'branchServiceProfessional.branchService.service'])->get();

            return response()->json(['orders' => $orders], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los carros"], 500);
        }
    }

    public function order_delete_show(Request $request)
    {
        try {             
            Log::info( "Entra a buscar las orders");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $orders = Order::with(['car.clientProfessional.professional', 'car.clientProfessional.client', 'productStore.product', 'branchServiceProfessional.branchService.service'])->whereHas('car.reservation', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now());
            })->where('request_delete', 3)->get();
            $orderData = [];
            foreach ($orders as $order) {
                $professional = $order['car']['clientProfessional']['professional'];
                $client = $order['car']['clientProfessional']['client'];
                $product = $order['is_product'] ? $order['productStore']['product'] : null;
                $service = !$order['is_product'] ? $order['branchServiceProfessional'] : null;
                $orderData [] = [
                    'id' => $order['id'],
                    'car_id' => $order['car_id'],
                    'price' => $order['price'],
                    'nameProfessional' => $professional['name'],
                    'image_url' => $professional['image_url'],
                    'nameClient' => $client['name'],
                    'client_image' => $client['client_image'],
                    'category' => $order['is_product'] ? $product['productCategory']['name'] : $service['type_service'],
                    'name' => $order['is_product'] ? $product['name'] : $service['branchService']['service']['name'],
                    'image' => $order['is_product'] ? $product['image_product'] : $service['branchService']['service']['image_service']
                ];
            }
            return response()->json(['orders' => $orderData], 200, [], JSON_NUMERIC_CHECK);
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
            $data['cant'] = 1;
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

    public function store_products(Request $request)
    {
        Log::info("Compra de Productos y servicio prestado");
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'car_id' => 'required|numeric',
                'product_id' => 'required|numeric',
                'category_id' => 'required|numeric',
                'branch_id' => 'required|numeric'

            ]);
            //$productsArray = [];
            $data['cant'] = 1;
            $order = $this->orderService->product_order_store($data);
                $productStores = ProductStore::with(['product' => function ($query) use ($data) {
                    $query->select(['id', 'name', 'reference', 'code', 'description', 'status_product', 'purchase_price', 'sale_price', 'image_product'])
                          ->where('status_product', '=', 'En venta');
                }])
                ->whereHas('product', function ($query) use ($data) {
                    $query->where('product_category_id', $data['category_id']);
                })
                ->whereHas('store.branches', function ($query) use ($data){
                    $query->where('branches.id', $data['branch_id']);
                })
                ->where('product_exit', '>', 0)
                ->select(['id', 'product_exit', 'product_id', 'store_id'])
                ->get();
        
                $productsArray = $productStores->map(function ($productStore) {
                    $product = $productStore->product; // Almacenar producto en una variable local
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
                });
            DB::commit();
             return response()->json(['category_products' => $productsArray], 200, [], JSON_NUMERIC_CHECK);
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
            Log::info('$data');
        Log::info($data);
            $car = Car::find($data['car_id']);            
            $branch = Branch::where('id', $request->branch_id)->first();
            $clientName = $car->clientProfessional->client->name;
            $professionalName = $car->clientProfessional->professional->name;
            if ($data['service_id'] == 0 && $data['type'] == 'product') {
                $order = $this->orderService->product_order_store($data);
                $trace = [
                    'branch' => $branch->name,
                    'cashier' => $request->nameProfessional,
                    'client' => $clientName,
                    'amount' => $order->price,
                    'operation' => 'Agrega Producto al carro: '.$car->id,
                    'details' => $order->productStore->product->name,
                    'description' => $professionalName,
                ];
                $this->traceService->store($trace);
                Log::info('$trace Pproduct');
                Log::info($trace);
             }
            if ($data['product_id'] == 0 && $data['type'] == 'service') {
                $order = $this->orderService->service_order_store1($data);
                $trace = [
                    'branch' => $branch->name,
                    'cashier' => $request->nameProfessional,
                    'client' => $clientName,
                    'amount' => $order->price,
                    'operation' => 'Agrega Servicio  al carro: '.$car->id,
                    'details' => $order->branchServiceProfessional->branchService->service->name,
                    'description' => $professionalName,
                ];
                $this->traceService->store($trace);
                Log::info('$trace Service');
                //Log::info($trace);             
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
                $product = $order->is_product ? $order->productStore->product : null;
                $service = !$order->is_product ? $order->branchServiceProfessional->branchService->service : null;
                return [
                    'id' => $order->id,
                    'car_id' => $order->car_id,
                    'request_delete' => $order->request_delete,
                    'name' => $order->is_product ? $product->name : $service->name,
                    'image' => $order->is_product ? $product->image_product : $service->image_service,
                    'price' => $order->price,
                    'category' => $order->is_product ? $product->productCategory->name : $order->branchServiceProfessional->type_service,
                ];
            });
            if($car->technical_assistance){
                // Agregar una nueva fila al arreglo
                $newRow = [
                    'id' => null, // o el valor que corresponda
                    'car_id' => $car->id,
                    'request_delete' => false, // o el valor que corresponda
                    'name' => 'Técnico', // nombre predeterminado o dinámico
                    'image' => '', // imagen predeterminada o dinámica
                    'price' => 5000*$car->technical_assistance, // precio predeterminado o dinámico
                    'category' => '' // categoría predeterminada o dinámica
                ];

                $orders[] = $newRow; // Agregar la nueva fila al arreglo

                // Si necesitas convertirlo de nuevo a una colección
                $orders = collect($orders);
            }
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
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'request_delete' => 'required|boolean'
            ]);
            $order = Order::find($data['id']);
            $order->request_delete = $data['request_delete'];
            $order->save();
            if ($data['request_delete'] == 0) {
                $car = Car::find($order->car_id);
            $reservation = $car->reservation;
            $client = $car->clientProfessional->client;
             if ($order->is_product == 1) {
                $productstore = ProductStore::find($order->product_store_id);
                $product = $productstore->product;

                $notification = new Notification();
                $notification->professional_id = $car->clientProfessional->professional_id;
                $notification->branch_id = $reservation->branch_id;
                $notification->tittle = 'Solicitud de Eliminación Rechazada';
                $notification->description = 'Atención El producto'.' '.$product->name.' '. 'del ciente'.' '.$client->name.' '.'no fue aprobado para su eliminación';
                $notification->type = 'Barbero';
                $notification->save();
             }else {
                Log::info("servicio");
                $branchServiceprofessional = BranchServiceProfessional::find($order->branch_service_professional_id);
                Log::info($branchServiceprofessional);
                $service = $branchServiceprofessional->branchService->service;
                Log::info("card:".$car);

                $notification = new Notification();
                $notification->professional_id = $car->clientProfessional->professional_id;
                $notification->branch_id = $reservation->branch_id;
                $notification->tittle = 'Solicitud de Eliminación Rechazada';
                $notification->description = 'Atención.. El ervicio'.' '.$service->name.' '. 'del ciente'.' '.$client->name.' '.'no fue aprobado para su eliminación';
                $notification->type = 'Barbero';
                $notification->save();
             }
            }
             DB::commit();
            return response()->json(['msg' => 'Estado de la orden modificado correctamente'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return response()->json(['msg' => 'Error al hacer la solicitud de eliminar la orden'], 500);
        }
    }

    public function order_denegar(Request $request)
    {
        Log::info("Actualizar orden");
        Log::info($request);
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $order = Order::find($data['id']);
            $branch = Branch::where('id', $order->car->reservation->branch_id)->first();
            /*$cajeros = BranchProfessional::where('branch_id', $branch->id)->whereHas('professional.charge', function ($query){
            $query->where('name', 'Cajero (a)');
        })->get('professional_id');*/
        $type = $order->is_producy ? 'producto' : 'servicio';
        /*if(!$cajeros->isEmpty()){
            foreach ($cajeros as $cajero) {    */                
            $notification = new Notification();
            $notification->professional_id = $data['professional_id'];
            $notification->tittle = 'Denegada';
            $notification->description = 'Solicitud de eliminación de orden de '.$type.' del carro: '.$order->car_id.' denegada';
            $notification->type = 'Caja';
            $branch->notifications()->save($notification);
            //}
        //}
            $order->request_delete = 2;
            $order->save();
            return response()->json(['msg' => 'Estado de la orden modificado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al hacer la solicitud de eliminar la orden'], 500);
        }
    }

    public function update2(Request $request)
    {
        Log::info("Actualizar orden");
        Log::info($request);
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'request_delete' => 'required|boolean',
                 'id_branch' => 'required|numeric'
            ]);
            
            
            $order = Order::find($data['id']);
            $order->request_delete = $data['request_delete'];
            $order->save();
            
             $orderDatas = Order::whereHas('car.reservation', function ($query) use ($data){
                    $query->where('branch_id', $data['branch_id']);
                })->where('request_delete', true)->whereDate('data', Carbon::now()->toDateString())->orderBy('updated_at', 'desc')->get();

           $car = $orderDatas->map(function ($orderData){
            if ($orderData->is_product == true) {
                return [
                    'id' => $orderData->id,
                    'profesional_id' => $orderData->car->clientProfessional->professional->id,
                    'reservation_id' => $orderData->car->reservation->id,
                    'nameProfesional' => $orderData->car->clientProfessional->professional->name,
                    'nameClient' => $orderData->car->clientProfessional->client->name,
                    'hora' => $orderData->updated_at->Format('g:i A'),                    
                    'nameProduct' => $orderData->productStore->product->name,
                    'nameService' => null,
                    'duration_service' => null,
                    'is_product' => $orderData->is_product,
                    'updated_at' => $orderData->updated_at->toDateString()
                ];
            }
            else {
                return [
                    'id' => $orderData->id,
                    'profesional_id' => $orderData->car->clientProfessional->professional->id,
                    'reservation_id' => $orderData->car->reservation->id,
                    'nameProfesional' => $orderData->car->clientProfessional->professional->name,
                    'nameClient' => $orderData->car->clientProfessional->client->name,
                    'hora' => $orderData->updated_at->Format('g:i A'),
                    'nameProduct' => null,
                    'nameService' => $orderData->branchServiceProfessional->branchService->service->name,
                    'duration_service' => $orderData->branchServiceProfessional->branchService->service->duration_service,
                    'is_product' => (int)$orderData->is_product,
                    'updated_at' => $orderData->updated_at->toDateString()
                ];
            }
           });
    
            return response()->json(['carOrderDelete' => $car], 200);
         
        } catch (\Throwable $th) {
            Log::error($th);
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
                        'client' => $car->clientProfessional->client->name,
                        'amount' => $order->price,
                        'operation' => 'Deniega solicitud de eliminar orden de Producto del carro: '.$car->id,
                        'details' => $order->productStore->product->name,
                        'description' => $car->clientProfessional->professional->name,
                    ];
                    $this->traceService->store($trace);
                    Log::info('$trace Pproduct');
                    Log::info($trace);
                }
                elseif (!$order->is_product) {
                    $trace = [
                        'branch' => $branch->name,
                        'cashier' => $request->nameProfessional,
                        'client' => $car->clientProfessional->client->name,
                        'amount' => $order->price,
                        'operation' => 'Deniega solicitud de eliminar orden de Servicio del carro: '.$car->id,
                        'details' => $order->branchServiceProfessional->branchService->service->name,
                        'description' => $car->clientProfessional->professional->name,
                    ];
                    $this->traceService->store($trace);
                    Log::info('$trace Service');
                    Log::info($trace);      
    
                }
            $order->request_delete = $data['request_delete'];
            $order->save();
            return response()->json(['msg' => 'Estado de la orden modificado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al hacer la solicitud de eliminar la orden'], 500);
        }
    }

    public function destroy(Request $request)
    {
        Log::info("Eliminar orden");
        Log::info($request);
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $order = Order::find($data['id']);
            $car = Car::find($order->car_id);
            $reservation = $car->reservation;
            $client = $car->clientProfessional->client;
            //$branch = Branch::where('id', $car->reservation->branch_id)->first();
            Log::info($order);
            Log::info($car);
            if ($order->is_product == 1) {                
            Log::info("Es producto");
                $productstore = ProductStore::find($order->product_store_id);
                $product = $productstore->product;
                $cant = $order->cant;
                $productstore->product_quantity = $cant;
                $productstore->product_exit = $productstore->product_exit + $cant;
                $productstore->save();

                $notification = new Notification();
                $notification->professional_id = $car->clientProfessional->professional_id;
                $notification->branch_id = $reservation->branch_id;
                $notification->tittle = 'Aceptada Eliminación de Producto';
                $notification->description = 'El Producto'.' '.$product->name.' '. 'del ciente'.' '.$client->name.' '.'fue eliminado satisfactoriamente';
                $notification->type = 'Barbero';
                $notification->save();

            }
            if ($order->is_product == 0) {
                Log::info("servicio");
                $branchServiceprofessional = BranchServiceProfessional::find($order->branch_service_professional_id);
                Log::info($branchServiceprofessional);
                $service = $branchServiceprofessional->branchService->service;
                Log::info("card:".$car);
                //$reservation = Reservation::where('car_id', $order->car_id)->first();
                Log::info($reservation);
                $reservation->final_hour = Carbon::parse($reservation->final_hour)->subMinutes($service->duration_service)->toTimeString();
                $reservation->total_time = Carbon::parse($reservation->total_time)->subMinutes($service->duration_service)->format('H:i');
                $reservation->save();
                //reducir tiempo al reloj
                $updated = Carbon::createFromFormat('H:i:s', $order->updated_at);
                $starnow = Carbon::now();
                $diffInSegunds = $updated->diffInSeconds($starnow, false);
                $timeMod =  ($service->duration_service*60)+$diffInSegunds;
                Log::info('diferencia en segundos'.$diffInSegunds);
                $tail = $reservation->tail;
                $timeClock = $tail->timeClock - $timeMod;
                Log::info('diferencia en segundos - reloj actual'.$timeClock);
                $timeClock1 = $timeClock <=0 ? 0 : $timeClock;
                $tail->timeClock = $timeClock1;
                $tail->save();
                $notification = new Notification();
                $notification->professional_id = $car->clientProfessional->professional_id;
                $notification->branch_id = $reservation->branch_id;
                $notification->tittle = 'Aceptada Eliminación de Servicio';
                $notification->description = 'Servicio'.' '. $service->name.' '. 'del ciente'.' '.$client->name.' '.'fue eliminado, su reloj ahora tiene un tiempo de '.''.$timeClock1.' '.'seg'.'.'.$reservation->id;
                $notification->type = 'Barbero';
                $notification->state = 3;
                $notification->save();
            }
            $amountTemp = $car->amount - $order->price;
            $car->amount = $amountTemp;
            $order->delete();
            if($amountTemp)
            {
                $car->save();
            }
            else {
                $car->delete();
            }
            DB::commit();
            return response()->json(['msg' =>'Solicitud de eliminar la orden hecha correctamente'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
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
                'id' => 'required|numeric',
                'professional_id' => 'nullable'
            ]);
            $order = Order::find($data['id']);
            $car = Car::find($order->car_id);
            //$client = $car->clientProfessional->client;
            //$professional = $car->clientProfessional->professional;
            $branch = Branch::where('id', $car->reservation->branch_id)->first();
            /*$cajeros = BranchProfessional::where('branch_id', $branch->id)->whereHas('professional.charge', function ($query){
                $query->where('name', 'Cajero (a)');
            })->get('professional_id');*/
            if ($order->is_product) {                
            Log::info("Es producto");
            
                $productstore = ProductStore::find($order->product_store_id);
                //$product = $productstore->product;
                $cant = $order->cant;
                $productstore->product_quantity = $cant;
                $productstore->product_exit = $productstore->product_exit + $cant;
                $productstore->save();
                /*if(!$cajeros->isEmpty()){
                    foreach ($cajeros as $cajero) {     */               
                    $notification = new Notification();
                    $notification->professional_id = $data['professional_id'];
                    $notification->tittle = 'Aceptada';
                    $notification->description = 'Solicitud de eliminación de orden de producto del carro: '.$car->id.' aceptada';
                    $notification->type = 'Caja';
                    $branch->notifications()->save($notification);
                    //}
                //}
                /*$trace = [
                    'branch' => $branch->name,
                    'cashier' => $request->nameProfessional,
                    'client' => $client->name.' '.$client->surname.' '.$client->second_surname,
                    'amount' => $order->price,
                    'operation' => 'Elimina orden de Producto del carro: '.$car->id,
                    'details' => $product->name,
                    'description' => $professional->name.' '.$professional->surname.' '.$professional->second_surname,
                ];
                $this->traceService->store($trace);
                Log::info('$trace Pproduct');
                Log::info($trace);*/
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
                $reservation->total_time = Carbon::parse($reservation->total_time)->subMinutes($service->duration_service)->format('H:i');
                $reservation->save();
                /*if(!$cajeros->isEmpty()){
                    foreach ($cajeros as $cajero) { */                   
                    $notification = new Notification();
                    $notification->professional_id = $data['professional_id'];
                    $notification->tittle = 'Aceptada';
                    $notification->description = 'Solicitud de eliminación de orden de servicio del carro: '.$car->id.' aceptada';
                    $notification->type = 'Caja';
                    $branch->notifications()->save($notification);
                    //}
                //}
                /*$trace = [
                    'branch' => $branch->name,
                    'cashier' => $request->nameProfessional,
                    'client' => $client->name.' '.$client->surname.' '.$client->second_surname,
                    'amount' => $order->price,
                    'operation' => 'Elimina orden de Servicio del carro: '.$car->id,
                    'details' => $service->name,
                    'description' => $professional->name.' '.$professional->surname.' '.$professional->second_surname,
                ];
                $this->traceService->store($trace);
                Log::info('$trace Service');
                Log::info($trace);      */

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

    public function destroy_solicitud(Request $request)
    {
        Log::info("Eliminar orden");
        Log::info($request);
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'professional_id' => 'nullable'
            ]);
            $order = Order::find($data['id']);
            $car = Car::find($order->car_id);
            $client = $car->clientProfessional->client;
            $professional = $car->clientProfessional->professional;
            $branch = Branch::where('id', $request->branch_id)->first();
            /*$administradores = BranchProfessional::where('branch_id', $branch->id)->whereHas('professional.charge', function ($query){
                $query->where('name', 'Administrador de Sucursal');
            })->get('professional_id');*/
            if ($order->is_product == 1) {                
            Log::info("Es producto");
            
                $productstore = ProductStore::find($order->product_store_id);
                $product = $productstore->product;
                $cant = $order->cant;
                /*$productstore->product_quantity = $cant;
                $productstore->product_exit = $productstore->product_exit + $cant;
                $productstore->save();*/
                $trace = [
                    'branch' => $branch->name,
                    'cashier' => $request->nameProfessional,
                    'client' => $client->name.' '.$client->surname.' '.$client->second_surname,
                    'amount' => $order->price,
                    'operation' => 'Solicitud de eliminación de Producto del carro: '.$car->id,
                    'details' => $product->name,
                    'description' => $professional->name.' '.$professional->surname.' '.$professional->second_surname,
                ];
                $this->traceService->store($trace);
                /*if(!$administradores->isEmpty()){
                    foreach ($administradores as $administrador) { */                   
                    $notification = new Notification();
                    $notification->professional_id = $data['professional_id'];
                    $notification->tittle = 'Solicitud';
                    $notification->description = 'Solicitud de eliminación de la orden de producto del carro: '.$car->id;
                    $notification->type = 'Administrador';
                    $branch->notifications()->save($notification);
                   // }
                //}
                //todo pendiente para revisar importante
               // $this->actualizarProductExit($productstore->product_id, $productstore->service_id); 
            }
            elseif ($order->is_product == 0) {
                Log::info("servicio");
                $branchServiceprofessional = BranchServiceProfessional::find($order->branch_service_professional_id);
                $service = $branchServiceprofessional->branchService->service;
                /*$reservation = Reservation::where('car_id', $order->car_id)->first();
                $reservation->final_hour = Carbon::parse($reservation->final_hour)->subMinutes($service->duration_service)->toTimeString();
                $reservation->total_time = Carbon::parse($reservation->total_time)->subMinutes($service->duration_service)->format('H:i:s');
                $reservation->save();*/
                $trace = [
                    'branch' => $branch->name,
                    'cashier' => $request->nameProfessional,
                    'client' => $client->name.' '.$client->surname.' '.$client->second_surname,
                    'amount' => $order->price,
                    'operation' => 'Solicitud de Eliminacion de orden de Servicio del carro: '.$car->id,
                    'details' => $service->name,
                    'description' => $professional->name.' '.$professional->surname.' '.$professional->second_surname,
                ];
                $this->traceService->store($trace);
                /*if(!$administradores->isEmpty()){
                    foreach ($administradores as $administrador) {  */                  
                    $notification = new Notification();
                    $notification->professional_id = $data['professional_id'];
                    $notification->tittle = 'Solicitud';
                    $notification->description = 'Solicitud de eliminación de la orden de servicio del carro: '.$car->id;
                    $notification->type = 'Administrador';
                    $branch->notifications()->save($notification);
                    //}
               // }   

            }
            $order->request_delete = 3;
            $order->save();
            
            return response()->json(['msg' =>'Solicitud de eliminar la orden hecha correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info("Eliminar orden:$th");
            return response()->json(['msg' => $th->getMessage().'Error al hacer la solicitud de eliminar la orden'], 500);
        }
    }
}
