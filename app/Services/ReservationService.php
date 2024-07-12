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

class ReservationService
{

    public function store($data, $servs, $client_id)
    {
        Log::info("Guardar Reservacion");
        DB::beginTransaction();
        $clientprofessional = ClientProfessional::where('client_professional.client_id', $client_id)->where('client_professional.professional_id', $data['professional_id'])->first();
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
        $car->amount = 0;
        $car->pay = false;
        $car->active = 1;
        $car->select_professional = $data['select_professional'];
        $car->tip = 0;
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
            $percent = $branchServiceProfessional->percent ? $branchServiceProfessional->percent : 1;
            Log::info('$percent');
            Log::info($percent);
            $branch_service_professional_id = $branchServiceProfessional->id;
            $order = new Order();
            $order->car_id = $car->id;
            $order->product_store_id = null;
            $order->branch_service_professional_id = $branch_service_professional_id;
            $order->data = $data['data'];
            $order->is_product = false;
            //logica de porciento de ganancia
            $order->percent_win = $service->price_service * $percent/100;
            $order->price = $service->price_service;
            $order->request_delete = false;
            $order->save();
            Log::info('$service->price_service Precio del servicio');
            Log::info($service->price_service);
            $total_amount = $total_amount + $service->price_service;
            $total_time = $total_time + $service->duration_service;
        } //end foreach


        //$car = Car::find($car->id);
        $car->amount = $total_amount;
        $car->save();
        $fechaCarbon = Carbon::createFromFormat('Y-m-d', $data['data']);
        if ($fechaCarbon->isToday() && $data['from_home'] == 0) {
            //$reservationsDay = Reservation::whereHas()->whereDate('data', Carbon::now())->where('from_home', 0)->where('branch_id', $data['branch_id'])->get();
            if($data['select_professional'] == 1){           
            $code = 'SELECT'/*.str_pad($reservationsDay->count() + 1, 2, '0', STR_PAD_LEFT)*/;
            }
            else{
                $reservationsDay = Reservation::whereHas('car', function ($query){
                    $query->where('select_professional', 0);
                })->whereDate('data', Carbon::now())->where('from_home', 0)->where('branch_id', $data['branch_id'])->get();   
            $code = 'TA'.str_pad($reservationsDay->count() + 1, 2, '0', STR_PAD_LEFT);
            //$code = substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
            }
        }
        else{
            $code = 'RE'.substr(str_shuffle("ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 4);
            //$code = 'RESERVA';
        }
        $confirmation = 0;
        if ($fechaCarbon->isToday()) {
            if ($data['from_home'] == 0) {
                $confirmation = 4;
            } elseif ($data['from_home'] == 1) {
                $confirmation = 1;
            }
        } else {
            $confirmation = 0;
        }
        $reservation = new Reservation();    
        $start_time = Carbon::parse($data['start_time'])->toTimeString();
        $reservation->start_time = Carbon::parse($start_time)->toTimeString();
        $reservation->final_hour = Carbon::parse($start_time)->addMinutes($total_time)->toTimeString();
        $reservation->total_time = sprintf('%02d:%02d:%02d', floor($total_time / 60), $total_time % 60, 0);
        $reservation->data = $data['data'];
        $reservation->from_home = $data['from_home'];
        $reservation->branch_id = $data['branch_id'];
        $reservation->car_id = $car->id;
        $reservation->confirmation = $confirmation;
        $reservation->code = $code;
        $reservation->save();
        Log::info('Crea la reservación');
        DB::commit();
        Log::info($reservation);
        return $reservation;
    }

    public function client_history($data)
    {
        $fiel = null;
        $frecuencia = null;
        $cantMaxService = 0;        
        $client = Client::find($data['client_id']);
        $result = [
            'clientName' => $client->name,
            'professionalName' => "Ninguno",
            'branchName' => '',
            'image_data' => '',
            'imageLook' => 'clients/default_profile.jpg',
            'image_url' => '',
            'cantVisit' => 0,
            'endLook' => '',
            'lastDate' => '',
            'frecuencia' => "No Frecuente",
            'services' =>  [],
            'products' => []
        ];
        
        /*if (!$client) {
            Log::info("client_history 1");
            return  $result;
        }*/
        Log::info("client_history 2");
        $reservations = Reservation::whereHas('car', function ($query) use ($data) {
            $query->whereHas('clientProfessional', function ($query) use ($data){
                $query->where('client_id', $data['client_id']);
            });
        })->orderByDesc('data')->limit(12)->get();
        if ($reservations->isEmpty()) {
            return $result;
        }

        $countReservations = $reservations->count();
        if ($countReservations >= 12) {
            $currentYear = Carbon::now()->year;

            $fiel = $reservations->filter(function ($reservation) use ($currentYear) {
                return Carbon::parse($reservation->data)->year == $currentYear;
            })->count();
            if ($fiel >= 12) {
                $frecuencia = "Fiel";
            }
        } elseif ($countReservations >= 3) {
            $frecuencia = "Frecuente";
        } else {
            $frecuencia = "No Frecuente";
        }
        Log::info("client_history 5");

        $reservationids = $reservations->pluck('car_id')->take(3);
        Log::info("client_history 6");
        $services = Service::withCount(['orders' => function ($query) use ($data, $reservationids) {
            $query->whereIn('car_id', $reservationids)->where('is_product', 0);
        }])->orderByDesc('orders_count')->get()->where('orders_count', '>', 0);
        $reservation2 = $reservations->filter(function ($query){
            return $query->car->where('pay', 1);
        });
       $reservationids2 = $reservation2->pluck('car_id')->take(3);
        $products = Product::with(['orders' => function ($query) use ($data, $reservationids2) {
            $query->selectRaw('SUM(cant) as total_sale_price')
                ->groupBy('product_id')
                ->whereIn('car_id', $reservationids2)
                ->where('is_product', 1);
        }])
        ->get()->filter(function ($product) {
            return !$product->orders->isEmpty();
        });
        $comment = Comment::whereHas('clientProfessional', function ($query) use ($data) {
            $query->where('client_id', $data['client_id']);
        })->orderByDesc('data')->orderByDesc('updated_at')->first();
        //if ($reservations !== null && !$reservations->isEmpty()) {
            Log::info('Tiene Reserva');
            $reservation = $reservation2->first();
            $branch = $reservation->branch;
            $professional = $reservation->car->clientProfessional->professional;

            $result = [
                'clientName' => $client->name,
                'professionalName' => $professional->name,
                'branchName' => $branch->name,
                'image_data' => $branch->image_data ? $branch->image_data : 'branches/default.jpg',
                'image_url' => $professional->image_url ? $professional->image_url : 'professionals/default_profile.jpg',
                'imageLook' => $client->client_image ? $client->client_image.'?$'. Carbon::now() : 'clients/default_profile.jpg'.'?$'. Carbon::now(),
                'cantVisit' => $reservation2->count(),
                'endLook' => $comment ? $comment->look : null,
                'lastDate' => $reservation->data,
                'frecuencia' => $frecuencia,
                'services' => $services->map(function ($service) use ($cantMaxService) {
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
                'products' => $products->map(function ($product) {
                    $total_sale_price = $product->orders->isEmpty() ? 0 : $product->orders->first()->total_sale_price;
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->code,
                        'description' => $product->description,
                        'product_exit' => 0, //solo para utilizar el modelo en apk bien,
                        'status_product' => $product->status_product,
                        'purchase_price' => $product->purchase_price,
                        'sale_price' => $product->sale_price,
                        'image_product' => $product->image_product,
                        'created_at' => $product->created_at,
                        'updated_at' => $product->updated_at,
                        'cant' => $total_sale_price
                    ];
                })->values(),
                'cantMaxService' => $services->max('orders_count')
            ];
        /*} else {
            return  $result;
        }*/

        Log::info("client_history 7");
        return $result;
    }
}
