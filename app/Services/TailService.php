<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Car;
use App\Models\Client;
use App\Models\Order;
use App\Models\Professional;
use App\Models\Reservation;
use App\Models\Tail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TailService {

    public function cola_branch_data($branch_id){
        $branch = Branch::whereHas('tails', function ($query){
            $query->whereIn('attended', [0,3]);
        })->where('id', $branch_id)->with('tails')->get();
        $tails = $branch->flatMap(function ($branch) {
            return $branch->tails->map(function ($tail) {
                $reservation = $tail->reservation;
                $professional = $reservation->car->clientProfessional->professional;
                $client = $reservation->car->clientProfessional->client;
                $workplace = $professional->workplaces()
                    ->whereDate('data', $reservation->data)
                    ->first();
                    return [
                        'reservation_id' => $reservation->id,
                        'car_id' => $reservation->car_id,
                        'from_home' => $reservation->from_home,
                        'start_time' => Carbon::parse($reservation->start_time)->format('H:i:s'),
                        'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i:s'),
                        'total_time' => $reservation->total_time,
                        'client_name' => $client->name." ".$client->surname." ".$client->second_surname,
                        'professional_name' => $professional->name." ".$professional->surname." ".$professional->second_surname,
                        'client_id' => $reservation->car->clientProfessional->client_id,
                        'professional_id' => $reservation->car->clientProfessional->professional_id,
                        'professional_state' => $professional->state,
                        'attended' => $tail->attended,
                        'puesto' => $workplace ? $workplace->name : null,
                    ];
                })->sortByDesc('professional_state')->sortBy('start_time')->values();
        });

        return $tails;
    }

    public function tail_branch_attended($branch_id){
        return $branch = Branch::where('id', $branch_id)->whereHas('tails', function ($query){
            $query->whereIn('attended', [1,5,11,111,4]);
        })->get()->flatMap(function ($branch) {
            return $branch->tails->map(function ($tail) {
                $reservation = $tail->reservation;
                $professional = $reservation->car->clientProfessional->professional;
                $client = $reservation->car->clientProfessional->client;
                $workplace = $professional->workplaces()
                    ->whereDate('data', $reservation->data)
                    ->first();
            return [
                'reservation_id' => $tail->reservation->id,
                'car_id' => $tail->reservation->car_id,
                'from_home' => $tail->reservation->from_home,
                'start_time' => Carbon::parse($reservation->start_time)->format('H:i:s'),
                'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i:s'),
                'total_time' => $reservation->total_time,
                'client_name' => $client->name." ".$client->surname." ".$client->second_surname,
                'professional_name' => $professional->name." ".$professional->surname." ".$professional->second_surname,
                'client_id' => $reservation->car->clientProfessional->client_id,
                'professional_id' => $reservation->car->clientProfessional->professional_id,
                'professional_state' => $professional->state,
                'attended' => $tail->attended,
                'puesto' => $workplace ? $workplace->name : null,
            ];
        })->sortBy('start_time')->values();
    });
        return $branch;
    }

    public function cola_branch_delete($branch_id){
        $tails = Tail::whereHas('reservation.car.clientProfessional.professional.branchServices', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        })->delete();
    }

    public function cola_branch_professional($branch_id, $professional_id){
        $tails = Tail::whereHas('reservation.car.clientProfessional.professional.branches', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        })->whereHas('reservation.car.clientProfessional', function ($query) use($professional_id){
            $query->where('professional_id', $professional_id);
        })->whereHas('reservation.car.orders', function ($query){
            $query->where('is_product', false);
        })->whereNot('attended', [2])->get();
        $branchTails = $tails->map(function ($tail){
           // Log::info($tail->updated_at->format('Y-m-d H:i:s'));
            return [
                'reservation_id' => $tail->reservation->id,
                'car_id' => $tail->reservation->car_id,
                'start_time' => Carbon::parse($tail->reservation->start_time)->format('H:i:s'),
                'final_hour' => Carbon::parse($tail->reservation->final_hour)->format('H:i:s'),
                'total_time' => $tail->reservation->total_time,
                'client_name' => $tail->reservation->car->clientProfessional->client->name." ".$tail->reservation->car->clientProfessional->client->surname." ".$tail->reservation->car->clientProfessional->client->second_surname,
                'professional_name' => $tail->reservation->car->clientProfessional->professional->name." ".$tail->reservation->car->clientProfessional->professional->surname." ".$tail->reservation->car->clientProfessional->professional->second_surname,
                'client_id' => $tail->reservation->car->clientProfessional->client_id,
                'professional_id' => $tail->reservation->car->clientProfessional->professional_id,
                'attended' => $tail->attended, 
                'updated_at' => $tail->updated_at->format('Y-m-d H:i:s'),
                'clock' => $tail->clock, 
                'timeClock' => $tail->timeClock, 
                'detached' => $tail->detached, 
                'total_services' => Order::whereHas('car.reservations')->whereRelation('car', 'id', '=', $tail->reservation->car_id)->where('is_product', false)->count()/*$tail->reservation->car->orders->map(function ($orderData){
                    $orderData->where('is_product', false);
                      })*/
                //'total_services' => count($tail->reservation->car->orders)
            ];
        })->sortBy('start_time')->values();

        return $branchTails;
    }

    public function tail_attended($reservation_id, $attended){
        $tail = Tail::where('reservation_id', $reservation_id)->first();
        $tail->attended = $attended;
        $tail->save();
        if ($attended == 5) {
            $car = Car::whereHas('reservations', function ($query) use ($reservation_id){
                $query->where('id', $reservation_id);
            })->first();
            $car->technical_assistance = $car->technical_assistance + 1;
            $car->save();
            
            Log::info($car);
        }
    }

    public function type_of_service($branch_id, $professional_id){
        $tails = Tail::with(['reservation.car.clientProfessional.professional.branchServices' => function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        }])->whereHas('reservation.car.clientProfessional', function ($query) use($professional_id){
            $query->where('professional_id', $professional_id);
        })->whereHas('reservation.car.orders', function ($query){
            $query->where('is_product', false);
        })->where('attended', 1)->get();
        if (count($tails) > 3) {
            return false;
        }elseif (count($tails) < 1) {
            return true;
        }
        else {
            foreach ($tails as $tail) {            
            foreach ($tail->reservation->car->orders as $orderData) {
                if ($orderData->branchServiceProfessional->branchService->service->simultaneou == 1) {
                    return true;
                }
            }
        }
        }
        return false;
    }

    public function cola_branch_capilar($branch_id){
        $tails = Tail::with(['reservation.car.clientProfessional.professional.branches' => function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        }])->orderBy('updated_at')->whereIn('attended', [4,5])->get();
        $branchTails = $tails->map(function ($tail){
            return [
                'reservation_id' => $tail->reservation->id,
                'car_id' => $tail->reservation->car_id,
                'start_time' => Carbon::parse($tail->reservation->start_time)->format('H:i:s'),
                'final_hour' => Carbon::parse($tail->reservation->final_hour)->format('H:i:s'),
                'total_time' => $tail->reservation->total_time,
                'client_name' => $tail->reservation->car->clientProfessional->client->name." ".$tail->reservation->car->clientProfessional->client->surname." ".$tail->reservation->car->clientProfessional->client->second_surname,
                'professional_name' => $tail->reservation->car->clientProfessional->professional->name." ".$tail->reservation->car->clientProfessional->professional->surname." ".$tail->reservation->car->clientProfessional->professional->second_surname,
                'client_id' => $tail->reservation->car->clientProfessional->client_id,
                'professional_id' => $tail->reservation->car->clientProfessional->professional_id,
                'professional_state' => $tail->reservation->car->clientProfessional->professional->state,
                'attended' => $tail->attended
            ];
        })->values();

        return $branchTails;
    }

    public function cola_branch_tecnico($branch_id, $professional_id){
        $workplace = Professional::find($professional_id)->workplaces()->wherePivot('data', Carbon::now()->format('Y-m-d'))->withPivot('id', 'places')->get()->map->pivot;
        $places = json_decode($workplace->value('places'), true);
        $professionals = Professional::whereHas('workplaces', function ($query) use ($places){
            $query->whereIn('workplace_id', $places);
        })->get()->value('id');
        $tails = Tail::whereHas('reservation.car.clientProfessional.professional.workplaces', function ($query) use ($branch_id, $places){
            $query->where('branch_id', $branch_id)->whereIn('workplace_id', $places);
        })->orderBy('updated_at')->whereIn('attended', [4])->get();
        $branchTails = $tails->map(function ($tail){
            return [
                'reservation_id' => $tail->reservation->id,
                'car_id' => $tail->reservation->car_id,
                'start_time' => Carbon::parse($tail->reservation->start_time)->format('H:i:s'),
                'final_hour' => Carbon::parse($tail->reservation->final_hour)->format('H:i:s'),
                'total_time' => $tail->reservation->total_time,
                'client_name' => $tail->reservation->car->clientProfessional->client->name." ".$tail->reservation->car->clientProfessional->client->surname." ".$tail->reservation->car->clientProfessional->client->second_surname,
                'professional_name' => $tail->reservation->car->clientProfessional->professional->name." ".$tail->reservation->car->clientProfessional->professional->surname." ".$tail->reservation->car->clientProfessional->professional->second_surname,
                'client_id' => $tail->reservation->car->clientProfessional->client_id,
                'professional_id' => $tail->reservation->car->clientProfessional->professional_id,
                'professional_state' => $tail->reservation->car->clientProfessional->professional->state,
                'attended' => $tail->attended
            ];
        })->values();

        return $branchTails;
    }

    public function reasigned_client($data){
        $client = Client::find($data['client_id']);
        $professional = Professional::find($data['professional_id']);
        $reservation = Reservation::find($data['reservation_id']);
        $horaActual = Carbon::now()->format('H:i:s');
        $timestamp = strtotime($reservation->total_time);
        $tiempo_entero = date('Gis', $timestamp);
        $horas = intval(substr($tiempo_entero, 0, 1));
        $minutos = intval(substr($tiempo_entero, 2, 2));
        $time = $horas * 60 + $minutos;
        $reservation->start_time = $horaActual;
        $reservation->final_hour = Carbon::parse($horaActual)->addMinutes($time)->toTimeString();
        $reservation->save();
        $car = Car::find($reservation->car_id);
        $client_professional_id = $professional->clients()->wherePivot('client_id', $client->id)->withPivot('id')->get()->map->pivot->value('id');
        if($client_professional_id){
            Log::info("no existe");
            $professional->clients()->attach($client->id);
            $client_professional_id = $professional->clients()->wherePivot('client_id', $client->id)->withPivot('id')->get()->map->pivot->value('id');
            Log::info($client_professional_id);
        }
        $car->client_professional_id = $client_professional_id;
        $car->save();
        
    }
}