<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\ClientProfessional;
use App\Models\Order;
use App\Models\Product;
use App\Services\CarService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CarController extends Controller
{
    private CarService $carService;
    
    public function __construct(CarService $carService)
    {
         $this->carService = $carService;
    }

    public function index()
    {
        try {             
            Log::info( "Entra a buscar los carros");
            $car = Car::with('clientProfessional.client', 'clientProfessional.professional')->get();
            //$car = Car::join('client_professional', 'client_professional.id', '=', 'cars.client_professional_id')->join('clients', 'clients.id', '=', 'client_professional.client_id')->join('professionals', 'professionals.id', '=', 'client_professional.professional_id')->get(['clients.name as client_name', 'clients.surname as client_surname', 'clients.second_surname as client_second_surname', 'clients.email as client_email', 'clients.phone as client_phone', 'professionals.*', 'cars.*']);
            return response()->json(['cars' => $car], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los carros"], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info("Guardar carro");
        Log::info($request);
        try {
            $data = $request->validate([
                'client_professional_id' => 'required|numeric',
                'amount' => 'nullable|numeric',
                'pay' => 'boolean',
                'active' => 'boolean',
                'tip' => 'nullable'
            ]);
            $car = $this->carService->store($data);

            return response()->json(['msg' => $car], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => 'Error al insertar el carro'], 500);
        }
    }

    public function car_orders(Request $request)
    {
        try {
            $data = $request->validate([
               'id' => 'required|numeric'
           ]);
           $orderProductsDatas = Order::with('car.clientProfessional')->whereRelation('car', 'id', '=', $data['id'])->where('is_product', true)->orderBy('updated_at', 'desc')->get();
            $products = $orderProductsDatas->map(function ($orderData){
                    return [
                        'id' => $orderData->id,                   
                        'name' => $orderData->productStore->product->name,
                        'reference' => $orderData->productStore->product->reference,
                        'code' => $orderData->productStore->product->code,
                        'description' => $orderData->productStore->product->description,
                        'status_product' => $orderData->productStore->product->status_product,
                        'purchase_price' => $orderData->productStore->product->purchase_price,
                        'sale_price' => $orderData->productStore->product->sale_price,
                        'image_product' => $orderData->productStore->product->image_product,
                        'is_product' => $orderData->is_product,
                        'price' => $orderData->price,
                        'request_delete' => $orderData->request_delete
                    ];
               });
           $orderServicesDatas = Order::with('car.clientProfessional')->whereRelation('car', 'id', '=', $data['id'])->where('is_product', false)->orderBy('updated_at', 'desc')->get();
           $services = $orderServicesDatas->map(function ($orderData){
              return [
                    'id' => $orderData->id,
                    'nameService' => $orderData->branchServiceProfessional->branchService->service->name,
                    'simultaneou' => $orderData->branchServiceProfessional->branchService->service->simultaneou,
                    'price_service' => $orderData->branchServiceProfessional->branchService->service->price_service,
                    'type_service' => $orderData->branchServiceProfessional->branchService->service->type_service,
                    'profit_percentaje' => $orderData->branchServiceProfessional->branchService->service->profit_percentaje,
                    'duration_service' => $orderData->branchServiceProfessional->branchService->service->duration_service,
                    'image_service' => $orderData->branchServiceProfessional->branchService->service->image_service,
                    'service_comment' => $orderData->branchServiceProfessional->branchService->service->service_comment,
                    'is_product' => $orderData->is_product,
                    'price' => $orderData->price,
                    'request_delete' => $orderData->request_delete
                    ];
                });
           return response()->json(['productscar' => $products, 'servicescar' => $services], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => "Error al mostrar ls ordenes"], 500);
       }
    }

   /* public function car_order_delete(Request $request)
    {
        try {
            $data = $request->validate([
               'id' => 'required|numeric'
           ]);
           $car = Order::join('cars', 'cars.id', '=', 'orders.car_id')->join('client_professional', 'client_professional.id', '=', 'cars.client_professional_id')->join('clients', 'clients.id', '=', 'client_professional.client_id')->join('people', 'people.id', '=', 'client_professional.professional_id')->leftjoin('product_store', 'product_store.id', '=', 'orders.product_store_id')->leftjoin('products', 'products.id', '=', 'product_store.product_id')->leftjoin('branch_service_professional', 'branch_service_professional.id', '=', 'orders.branch_service_professional_id')->leftjoin('branch_service', 'branch_service.id', '=', 'branch_service_professional.branch_service_id')->leftjoin('services', 'services.id', '=', 'branch_service.service_id')->where('cars.id', $data['id'])->where('request_delete', true)->orderBy('updated_at', 'desc')->get(['clients.name as nombreClients', 'clients.surname', 'clients.second_surname','people.name as nombreProfesional', 'people.surname as surnameProfesional',  'people.second_surname as second_surnameProfesional','products.name as nameProduct', 'services.name as nameService','orders.id','orders.is_product','orders.id','orders.updated_at']);

           return response()->json(['carOrderDelete' => $car], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Error al mostrar ls ordenes"], 500);
       }
    }*/
    public function car_order_delete(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
    
            /*$car = Order::join('cars', 'cars.id', '=', 'orders.car_id')
                ->join('client_professional', 'client_professional.id', '=', 'cars.client_professional_id')
                ->join('clients', 'clients.id', '=', 'client_professional.client_id')
                ->join('people', 'people.id', '=', 'client_professional.professional_id')
                ->leftJoin('product_store', 'product_store.id', '=', 'orders.product_store_id')
                ->leftJoin('products', 'products.id', '=', 'product_store.product_id')
                ->leftJoin('branch_service_professional', 'branch_service_professional.id', '=', 'orders.branch_service_professional_id')
                ->leftJoin('branch_service', 'branch_service.id', '=', 'branch_service_professional.branch_service_id')
                ->leftJoin('services', 'services.id', '=', 'branch_service.service_id')
                ->select([
                    DB::raw('CONCAT(people.name, " ", people.surname) AS nameProfessional'),
                    DB::raw('CONCAT(clients.name, " ", clients.surname) AS nameClient'),
                    DB::raw('DATE_FORMAT(orders.updated_at, "%h:%i:%s %p") as hora'),
                    //DB::raw('TIME(orders.updated_at) as hora'), militar
                    'products.name as nameProduct',
                    'services.name as nameService',
                    'orders.id',
                    'orders.is_product',
                    'orders.updated_at'
                ])
                ->where('cars.id', $data['id'])
                ->where('request_delete', true)
                ->orderBy('updated_at', 'desc')
                ->get();*/

                $orderDatas = Order::with('car.clientProfessional')->whereRelation('car', 'id', '=', $data['id'])->where('request_delete', true)->orderBy('updated_at', 'desc')->get();

           $car = $orderDatas->map(function ($orderData){
            if ($orderData->is_product == true) {
                return [
                    'id' => $orderData->id,
                    'nameProfesional' => $orderData->car->clientProfessional->professional->name.' '.$orderData->car->clientProfessional->professional->surname.' '.$orderData->car->clientProfessional->client->second_surname,
                    'nameClient' => $orderData->car->clientProfessional->client->name.' '.$orderData->car->clientProfessional->client->surname.' '.$orderData->car->clientProfessional->client->second_surname,
                    'hora' => $orderData->updated_at->Format('g:i:s A'),                    
                    'nameProduct' => $orderData->productStore->product->name,
                    'nameService' => null,
                    'is_product' => $orderData->is_product,
                    'updated_at' => $orderData->updated_at->toDateString()
                ];
            }
            else {
                return [
                    'id' => $orderData->id,
                    'nameProfesional' => $orderData->car->clientProfessional->professional->name.' '.$orderData->car->clientProfessional->professional->surname.' '.$orderData->car->clientProfessional->client->second_surname,
                    'nameClient' => $orderData->car->clientProfessional->client->name.' '.$orderData->car->clientProfessional->client->surname.' '.$orderData->car->clientProfessional->client->second_surname,
                    'hora' => $orderData->updated_at->Format('g:i:s A'),
                    'nameProduct' => null,
                    'nameService' => $orderData->branchServiceProfessional->branchService->service->name,
                    'is_product' => (int)$orderData->is_product,
                    'updated_at' => $orderData->updated_at->toDateString()
                ];
            }
           });
    
            return response()->json(['carOrderDelete' => $car], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar las ordenes"], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $car = $this->carService->show($data['id']);
            //$car = Car::with('clientProfessional.client', 'clientProfessional.professional')->find($data['id']);
            //$car = Car::join('client_professional', 'client_professional.id', '=', 'cars.client_professional_id')->join('clients', 'clients.id', '=', 'client_professional.client_id')->join('professionals', 'professionals.id', '=', 'client_professional.professional_id')->where('cars.id', $data['id'])->get(['clients.name as client_name', 'clients.surname as client_surname', 'clients.second_surname as client_second_surname', 'clients.email as client_email', 'clients.phone as client_phone', 'professionals.*', 'cars.*']);
            return response()->json(['car' => $car], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el carrito"], 500);
        }
    }

    public function client_professional_reservation_show($data)
    {
        try {
            Log::info("Busca el carro si no existe lo crea");
                $car = new Car();
                $car->client_professional_id = $data['client_professional_id'];
                $car->amount = 0;
                $car->pay = $data['pay'];
                $car->active = $data['active'];
                $car->save();
            return $car->id;
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al asignar el empleado a este cliente'], 500);
        }
    }
    public function car_clientProfessionalDate(Request $request)
    {
        try {
            $data = $request->validate([
                'client_professional_id' => 'required|numeric',
                'data' => 'required|date'
            ]);
            $car = Car::where('client_professional_id', $data['client_professional_id'])->whereDate('created_at', Carbon::parse($data['data']))->find($data['id']);
            //$car = Car::join('client_professional', 'client_professional.id', '=', 'cars.client_professional_id')->join('clients', 'clients.id', '=', 'client_professional.client_id')->join('professionals', 'professionals.id', '=', 'client_professional.professional_id')->where('cars.id', $data['id'])->get(['clients.name as client_name', 'clients.surname as client_surname', 'clients.second_surname as client_second_surname', 'clients.email as client_email', 'clients.phone as client_phone', 'professionals.*', 'cars.*']);
            return response()->json(['car' => $car], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el carrito"], 500);
        }
    }

    public function reservation_services(Request $request)
    {
        try {             
            Log::info( "Entra a buscar las reservaciones y los servicios de un cliente con un profesional");
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'client_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            
            $client_professional_id = ClientProfessional::where('professional_id', $data['professional_id'])->where('client_id', $data['client_id'])->value('id');
            $orderServicesDatas = Order::whereHas('car.reservations')->whereRelation('car', 'client_professional_id', '=', $client_professional_id)->where('is_product', false)->orderBy('updated_at', 'desc')->get();
            $services = $orderServicesDatas->map(function ($orderData){
                return [
                      'data_reservation' => $orderData->car->reservations->data,
                      'nameService' => $orderData->branchServiceProfessional->branchService->service->name,
                      'simultaneou' => $orderData->branchServiceProfessional->branchService->service->simultaneou,
                      'price_service' => $orderData->branchServiceProfessional->branchService->service->price_service,
                      'type_service' => $orderData->branchServiceProfessional->branchService->service->type_service,
                      'profit_percentaje' => $orderData->branchServiceProfessional->branchService->service->profit_percentaje,
                      'duration_service' => $orderData->branchServiceProfessional->branchService->service->duration_service,
                      'image_service' => $orderData->branchServiceProfessional->branchService->service->image_service
                      ];
                  });
            
            return response()->json(['services' => $services], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las reservaciones"], 500);
			
        }
    }
    public function car_services(Request $request)
    {
        try {             
            Log::info( "Entra a buscar las reservaciones y los servicios de un cliente con un profesional");
            $data = $request->validate([
                'car_id' => 'required|numeric'
            ]);
            
            $orderServicesDatas = Order::whereHas('car.reservations')->whereRelation('car', 'id', '=', $data['car_id'])->where('is_product', false)->get();
            $services = $orderServicesDatas->map(function ($orderData){
                return [
                     'name' => $orderData->branchServiceProfessional->branchService->service->name,
                      'simultaneou' => $orderData->branchServiceProfessional->branchService->service->simultaneou,
                      'price_service' => $orderData->branchServiceProfessional->branchService->service->price_service,
                      'type_service' => $orderData->branchServiceProfessional->branchService->service->type_service,
                      'profit_percentaje' => $orderData->branchServiceProfessional->branchService->service->profit_percentaje,
                      'duration_service' => $orderData->branchServiceProfessional->branchService->service->duration_service,
                      'image_service' => $orderData->branchServiceProfessional->branchService->service->image_service
                      ];
                  });
            
            return response()->json(['services' => $services], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las reservaciones"], 500);
			
        }
    }

   public function update(Request $request)
    {
        try {

            Log::info("Editar");
            Log::info($request);
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);

            $car = Car::find($data['id']);
            $car->pay = true;
            $car->save();
            return response()->json(['msg' => 'Carro actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => 'Error al actualizar el carro'], 500);
        }
    }
    /*public function car_amount_updated($data)
    {
        try {

            Log::info("Editar");
            $car = Car::find($data['id']);
            $car->amount = $car->amount + $data['amount'];
            $car->save();
            return response()->json(['msg' => 'Carro actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => 'Error al actualizar el carro'], 500);
        }
    }*/
   public function give_tips(Request $request)
    {
        try {

            Log::info("Editar");
            Log::info($request);
            $data = $request->validate([
                'id' => 'required|numeric',
                'tip' => 'required'
            ]);

            $car = Car::find($data['id']);
            $car->tip = $data['tip'];
            $car->save();
            return response()->json(['msg' => 'Se le ha dado propina para el profesional correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => 'Error al dar propina para el profesional'], 500);
        }
    }

    public function destroy(Request $request)
    {
        Log::info("Eliminar");
        try{
        $data = $request->validate([
            'id' => 'required|numeric'
        ]);

            $car = Car::find($data['id']);
            $car->delete();
            return response()->json(['msg' => 'Carro eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => 'Error al eliminar el carro'], 500);
        }
    }
}
