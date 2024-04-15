<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchService;
use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\Client;
use App\Models\ClientProfessional;
use App\Models\Comment;
use App\Models\Order;
use App\Models\Product;
use App\Models\Professional;
use App\Models\Reservation;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservationService {
   
    public function store($data, $servs,$client_id)
    {
        Log::info("Guardar Reservacion");
        DB::beginTransaction();
        $clientprofessional = ClientProfessional::where('client_professional.client_id',$client_id)->where('client_professional.professional_id',$data['professional_id'])->first();
            if (!$clientprofessional) {
                $clientprofessional = new ClientProfessional();
                $clientprofessional->client_id = $client_id;
                $clientprofessional->professional_id = $data['professional_id'];
                $clientprofessional->save();
            }
        $client_professional_id = $clientprofessional->id;
            Log::info('Crear el carro');
            $car = new Car();
            $car->client_professional_id = $client_professional_id;
            $car->amount = 0.0;
            $car->pay = false;
            $car->active = 1;
            $car->select_professional = $data['select_professional'];
            $car->tip = 0.0;
            $car->save();
            $total_amount = 0;
            $total_time = 0;
            //foreach del arreglo de services
            foreach ($servs as $serv) {
                $service_id = $serv;
                $service = Service::find($service_id);
                $branchservice = BranchService::where('branch_id', $data['branch_id'])->where('service_id', $service_id)->first();
                if (!$branchservice) {
                    $branchservice = new BranchService();
                    $branchservice->branch_id = $data['branch_id'];
                    $branchservice->service_id = $service_id;
                    $branchservice->save();
                }
                $branch_service_id = $branchservice->id;

                $branchServiceProfessional = BranchServiceProfessional::where('branch_service_id', $branch_service_id)->where('professional_id', $data['professional_id'])->first();
                //debe ser un servicio que realiza el professional
                /*if (!$branchServiceProfessional) {
                    $branchServiceProfessional = new BranchServiceProfessional();
                    $branchServiceProfessional->branch_service_id = $branch_service_id;
                    $branchServiceProfessional->professional_id = $data['professional_id'];
                    $branchServiceProfessional->save();
                }*/
                Log::info("Crear ordenes");
                Log::info('$branchServiceProfessional->percent');
                Log::info($branchServiceProfessional->percent);
                $porcent = $service->price_service*$branchServiceProfessional->percent/100;
                Log::info('$porcent');
                Log::info($porcent);
                $branch_service_professional_id = $branchServiceProfessional->id;
                $order = new Order();
                $order->car_id = $car->id;
                $order->product_store_id = null;
                $order->branch_service_professional_id = $branch_service_professional_id;
                $order->data = $data['data'];
                $order->is_product = false;
                //logica de porciento de ganancia
                $order->percent_win = $service->price_service*$branchServiceProfessional->percent/100;
                $order->price = $service->price_service;   
                $order->request_delete = false;
                $order->save();
                $total_amount = $total_amount + $service->price_service;
                $total_time = $total_time + $service->duration_service;                
            } //end foreach

            
                //$car = Car::find($car->id);
                $car->amount = $total_amount;
                $car->save();
                $reservation = new Reservation();
                $reservation->start_time = Carbon::parse($data['start_time'])->toTimeString();
                $reservation->final_hour = Carbon::parse($data['start_time'])->addMinutes($total_time)->toTimeString();
                $reservation->total_time = sprintf('%02d:%02d:%02d', floor($total_time/60),$total_time%60,0);
                $reservation->data = $data['data'];
                $reservation->from_home = $data['from_home'];
                $reservation->branch_id = $data['branch_id'];
                $reservation->car_id = $car->id;
                $reservation->save();
            Log::info('Crea la reservación');
            DB::commit();
            Log::info($reservation);
        return $reservation;
    }

    public function client_history($data){
        $fiel = null;
        $frecuencia =null;
        $cantMaxService = 0;
        $client = Client::find($data['client_id']);
        if(!$client){
            Log::info("client_history 1");
            return  $result = [
                 'clientName' => null,
                 'professionalName' => null,
                 'branchName' => '',
                 'image_data' => '',
                 'image_url' => '',
                 'imageLook' => '',             
                 'cantVisit' => 0,
                 'endLook' => '',
                 'lastDate' => '',
                 'frecuencia' => 0,
                 'services' =>  [],
                 'products' => []
                 ];   
         }
         Log::info("client_history 2");
       /*$reservations = Reservation::where('branch_id', $data['branch_id'])->whereHas('car.clientProfessional', function ($query) use ($data){
            $query->where('client_id', $data['client_id']);
        })->get();*/
        $reservations = Reservation::whereHas('car.clientProfessional', function ($query) use ($data){
            $query->where('client_id', $data['client_id']);
        })->orderByDesc('data')->get();
        if(!$reservations){
            return  $result = [
                'clientName' => '', 
                'professionalName' => '',
                'branchName' => '',
                'image_data' => '',
                'imageLook' => '',
                'image_url' => '',             
                'cantVisit' => 0,
                'endLook' => '',
                'lastDate' => '',
                'frecuencia' => 0,
                'services' =>  [],
                'products' => []
                ];   
        }
        $tempBranch = $reservations->first();
        $branch_id = $tempBranch->branch_id;
        if ($branch_id) {           
        $branchName = Branch::where('id', $branch_id)->first()->value('name');
        }
        else{
            $branchName = ''; 
        }
        Log::info("client_history 3");
        if(!$reservations){
            Log::info("client_history 4");
           return  $result = [
                'clientName' => '', 
                'professionalName' => '',
                'branchName' => '',
                'image_data' => '',
                'imageLook' => '',
                'image_url' => '',             
                'cantVisit' => 0,
                'endLook' => '',
                'lastDate' => '',
                'frecuencia' => 0,
                'services' =>  [],
                'products' => []
                ];   
        }
        if ($reservations->count()>=12) {
            /*$fiel = Reservation::where('branch_id', $data['branch_id'])->whereHas('car.clientProfessional', function ($query) use ($data){
                $query->where('client_id', $data['client_id']);
            })->whereYear('data', Carbon::now()->year)->count();*/
            $fiel = Reservation::whereHas('car.clientProfessional', function ($query) use ($data){
                $query->where('client_id', $data['client_id']);
            })->whereYear('data', Carbon::now()->year)->count();
        }
        elseif($reservations->count()>= 3){
            $frecuencia = "Frecuente";
        }
        else{
            $frecuencia = "No Frecuente";
        }
        //$client = Client::find($data['client_id']);
        Log::info("client_history 5");
    /*$reservationids = Reservation::where('branch_id', $data['branch_id'])->whereHas('car.clientProfessional', function ($query) use ($data){
        $query->where('client_id', $data['client_id']);
    })->orderByDesc('data')->take(3)->get()->pluck('car_id');*/
       $reservationids = Reservation::whereHas('car.clientProfessional', function ($query) use ($data){
        $query->where('client_id', $data['client_id']);
    })->orderByDesc('data')->take(3)->get()->pluck('car_id');
        Log::info("client_history 6");
        $services = Service::withCount(['orders'=> function ($query) use ($data, $reservationids){
            $query->whereHas('car.clientProfessional', function ($query) use ($data){
                $query->where('client_id', $data['client_id']);
            })->whereIn('car_id', $reservationids)->where('is_product', 0);
        }])->orderByDesc('orders_count')->get()->where('orders_count', '>', 0);
        $products = Product::withCount(['orders' => function ($query) use ($data, $reservationids){
            $query->whereHas('car.clientProfessional', function ($query) use ($data){
                $query->where('client_id', $data['client_id']);
            })->whereIn('car_id', $reservationids)->where('is_product', 1);
        }])->orderByDesc('orders_count')->get()->where('orders_count', '>', 0);
        $comment = Comment::whereHas('clientProfessional', function ($query) use ($client){
            $query->where('client_id', $client->id);
        })->orderByDesc('data')->orderByDesc('updated_at')->first();
        
        if ($comment && $comment->clientProfessional && $reservations) {
            $result = [
                'clientName' => $client->name." ".$client->surname, 
                'professionalName' => $comment->clientProfessional->professional->name.' '.$comment->clientProfessional->professional->surname,
                'branchName' => $branchName,
                'image_data' => $tempBranch->image_data ? $tempBranch->image_data : 'branches/default.jpg',
                'image_url' => $comment->clientProfessional->professional->image_url ? $comment->clientProfessional->professional->image_url : 'professionals/default_profile.jpg',
                'imageLook' => $comment->client_look ? $comment->client_look : 'comments/default_profile.jpg',             
                'cantVisit' => $reservations->count(),
                'endLook' => $comment ? $comment->look : null,
                'lastDate' => $tempBranch->data,
                'frecuencia' => $fiel ? $fiel : $frecuencia,
                'services' => $services->map(function ($service) use ($cantMaxService){
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'simultaneou' => $service->simultaneou,
                        'price_service' => $service->price_service,
                        'type_service' => $service->type_service,
                        'profit_percentaje' => $service->profit_percentaje,
                        'duration_service' => $service->duration_service,
                        'image_service' => $service->image_service,
                        'service_comment' => $service->service_comment,
                        'cant' => $service->orders_count
                    ];
                }),
                'products' => $products->map(function ($product){
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->code,
                        'description' => $product->description,
                        'product_exit' => 0,//solo para utilizar el modelo en apk bien,
                        'status_product' => $product->status_product,
                        'purchase_price' => $product->purchase_price,
                        'sale_price' => $product->sale_price,
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at,
                        'cant' => $product->orders_count
                    ];
                }),
                'cantMaxService' => $services->max('orders_count')
              ];
        } else {
            // Manejar la situación cuando el objeto es nulo
            /*$clientProfessional = ClientProfessional::where('client_id', $data['client_id'])->first();

if ($clientProfessional) {
    $professional = $clientProfessional->professional;   
}
            $result = [
                'clientName' => $client->name." ".$client->surname, 
               'professionalName' => 'No ha sido atendido',
                'image_url' => 'professionals/default_profile.jpg',
                'imageLook' => 'comments/default_profile.jpg',             
                'cantVisit' => $reservations->count(),
                'endLook' => $comment ? $comment->look : null,
                'frecuencia' => $fiel ? $fiel : $frecuencia,
                'services' => $services->map(function ($service) use ($cantMaxService){
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'simultaneou' => $service->simultaneou,
                        'price_service' => $service->price_service,
                        'type_service' => $service->type_service,
                        'profit_percentaje' => $service->profit_percentaje,
                        'duration_service' => $service->duration_service,
                        'image_service' => $service->image_service,
                        'service_comment' => $service->service_comment,
                        'cant' => $service->orders_count
                    ];
                }),
                'products' => $products->map(function ($product){
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->code,
                        'description' => $product->description,
                        'product_exit' => 0,//solo para utilizar el modelo en apk bien,
                        'status_product' => $product->status_product,
                        'purchase_price' => $product->purchase_price,
                        'sale_price' => $product->sale_price,
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at,
                        'cant' => $product->orders_count
                    ];
                }),
                'cantMaxService' => $services->max('orders_count')
              ];*/
              return  $result = [
                'clientName' => '', 
                'professionalName' => '',
                'branchName' => '',
                'imageLook' => 'comments/default_profile.jpg',
                'image_url' => 'professionals/default_profile.jpg',             
                'cantVisit' => 0,
                'endLook' => '',
                'lastDate' => '',
                'frecuencia' => 0,
                'services' =>  [],
                'products' => []
                ];   
        }
        
          Log::info("client_history 7");
           return $result;

    }

}
