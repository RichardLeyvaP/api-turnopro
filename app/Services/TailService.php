<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Car;
use App\Models\Client;
use App\Models\Order;
use App\Models\Professional;
use App\Models\Reservation;
use App\Models\Tail;
use App\Models\Comment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TailService {

    public function cola_branch_data($branch_id){
        $tails1 = Tail::whereHas('reservation', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        })->get();
        Log::info('Cola de una branch');
        Log::info($tails1);
        $tails = Tail::whereHas('reservation', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        })->whereIn('attended', [0,3,33])->get()->map(function ($tail){
            Log::info($tail);
                $reservation = $tail->reservation;
                Log::info('reservacion');
                Log::info($reservation);
                Log::info('clientProfessional');
                Log::info($reservation->car->clientProfessional);
                $professional = $reservation->car->clientProfessional->professional;
                $client = $reservation->car->clientProfessional->client;
            $workplace = $professional->workplaces()
                ->whereDate('data', $reservation->data)
                ->first();
                $comment = Comment::whereHas('clientProfessional', function ($query) use ($client){
                    $query->where('client_id', $client->id);
                })->orderByDesc('data')->orderByDesc('updated_at')->first();
            return [
                'reservation_id' => $tail->reservation->id,
                'car_id' => $tail->reservation->car_id,
                'from_home' => $tail->reservation->from_home,
                'start_time' => Carbon::parse($reservation->start_time)->format('H:i:s'),
                'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i:s'),
                'total_time' => $reservation->total_time,
                'client_name' => $client->name." ".$client->surname,
                'client_image' => $comment ? $comment->client_look : "comments/default_profile.jpg",
                'professional_name' => $professional->name." ".$professional->surname,
                'client_id' => $reservation->car->clientProfessional->client_id,
                'professional_id' => $reservation->car->clientProfessional->professional_id,
                'professional_state' => $professional->state,
                'attended' => $tail->attended,
                'puesto' => $workplace ? $workplace->name : null,
            ];
        })->sortByDesc('professional_state')->sortBy('start_time')->values();

        return $tails;

    }

    public function cola_branch_data2($branch_id){
        $tails = Tail::whereHas('reservation', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        })->where('attended', [3,33])->get()->map(function ($tail){
            Log::info($tail);
                $reservation = $tail->reservation;
                Log::info('reservacion');
                Log::info($reservation);
                $professional = $reservation->car->clientProfessional->professional;
                $client = $reservation->car->clientProfessional->client;
                $comment = Comment::whereHas('clientProfessional', function ($query) use ($client){
                    $query->where('client_id', $client->id);
                })->orderByDesc('data')->orderByDesc('updated_at')->first();
            return [
                'reservation_id' => $tail->reservation->id,
                'car_id' => $tail->reservation->car_id,
                'start_time' => Carbon::parse($reservation->start_time)->format('H:i:s'),
                'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i:s'),
                'total_time' => $reservation->total_time,
                'client_image' => $comment ? $comment->client_look : "comments/default_profile.jpg",
                'client_id' => $reservation->car->clientProfessional->client_id,
                'professional_id' => $reservation->car->clientProfessional->professional_id,
                'professional_name' => $professional->name." ".$professional->surname,
                'client_name' => $client->name." ".$client->surname, 
                'attended' => $tail->attended,
                'time' => Carbon::parse($tail->updated_at)->format('H:i:s')
            ];
        })->sortBy('time')->values();

        return $tails;

    }

    public function tail_branch_attended($branch_id){
        $branch = Branch::where('id', $branch_id)->whereHas('tails', function ($query) use ($branch_id){
            $query->whereIn('attended', [1, 5, 11, 111, 4])->whereHas('reservation', function ($query) use ($branch_id){
                $query->where('branch_id', $branch_id);
            });
        })->get()->flatMap(function ($branch) {
            return $branch->tails->filter(function ($tail) {
                // Add the condition to filter tails based on the 'attended' attribute
                return in_array($tail->attended, [1]);//[1, 5, 11, 111, 4]
            })->map(function ($tail) {
                $reservation = $tail->reservation;
                $professional = $reservation->car->clientProfessional->professional;
                $client = $reservation->car->clientProfessional->client;
                $workplace = $professional->workplaces()
                    ->whereDate('data', $reservation->data)
                    ->first();
                    $comment = Comment::whereHas('clientProfessional', function ($query) use ($client){
                        $query->where('client_id', $client->id);
                    })->orderByDesc('updated_at')->first();
    
                return [
                    'reservation_id' => $tail->reservation->id,
                    'car_id' => $tail->reservation->car_id,
                    'from_home' => $tail->reservation->from_home,
                    'start_time' => Carbon::parse($reservation->start_time)->format('H:i:s'),
                    'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i:s'),
                    'total_time' => $reservation->total_time,
                    'client_name' => $client->name . " " . $client->surname,
                    'client_image' => $comment ? $comment->client_look : "comments/default_profile.jpg",
                    'professional_name' => $professional->name . " " . $professional->surname  . " " . $professional->second_surname,
                    'image_url' => $professional->image_url ? $professional->image_url : "professionals/default_profile.jpg",
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
        $tails = Tail::whereHas('reservation', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        })->delete();
    }

    public function cola_branch_professional($branch_id, $professional_id){
        $tails = Tail::whereHas('reservation', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        })->whereHas('reservation.car.clientProfessional', function ($query) use($professional_id){
            $query->where('professional_id', $professional_id);
        })->whereHas('reservation.car.orders', function ($query){
            $query->where('is_product', false);
        })->whereNot('attended', [2])->get();
        $branchTails = $tails->map(function ($tail) use ($branch_id){        
            return [
                'reservation_id' => $tail->reservation->id,
                'car_id' => $tail->reservation->car_id,
                'start_time' => Carbon::parse($tail->reservation->start_time)->format('H:i:s'),
                'final_hour' => Carbon::parse($tail->reservation->final_hour)->format('H:i:s'),
                'total_time' => $tail->reservation->total_time,
                'client_name' => $tail->reservation->car->clientProfessional->client->name." ".$tail->reservation->car->clientProfessional->client->surname,
                'client_image' => $tail->reservation->car->clientProfessional->client->client_image ? $tail->reservation->car->clientProfessional->client->client_image : "comments/default_profile.jpg",
                'professional_name' => $tail->reservation->car->clientProfessional->professional->name." ".$tail->reservation->car->clientProfessional->professional->surname,
                'client_id' => $tail->reservation->car->clientProfessional->client_id,
                'professional_id' => $tail->reservation->car->clientProfessional->professional_id,
                'attended' => $tail->attended, 
                'updated_at' => $tail->updated_at->format('Y-m-d H:i:s'),
                'clock' => $tail->clock, 
                'timeClock' => $tail->timeClock, 
                'detached' => $tail->detached, 
                'total_services' => Order::whereHas('car.reservation')->whereRelation('car', 'id', '=', $tail->reservation->car_id)->where('is_product', false)->count()
               
            ];
        })->sortBy('start_time')->values();
        return $branchTails;
    }

    public function tail_attended($reservation_id, $attended){
        $tail = Tail::where('reservation_id', $reservation_id)->first();
        $tail->attended = $attended;
        $tail->save();
        if ($attended == 5) {
            $car = Car::whereHas('reservation', function ($query) use ($reservation_id){
                $query->where('id', $reservation_id);
            })->first();
            $car->technical_assistance = $car->technical_assistance + 1;
            $car->save();
            
            Log::info($car);
        }
    }

    public function type_of_service($branch_id, $professional_id){
        $tails = Tail::with(['reservation' => function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        }])->whereHas('reservation.car.clientProfessional', function ($query) use($professional_id){
            $query->where('professional_id', $professional_id);
        })->whereHas('reservation.car.orders', function ($query){
            $query->where('is_product', false);
        })->where('attended', 1)->get();
        if (count($tails) > 3) {
            Log::info('$tails>3');
            return false;
        }elseif (count($tails) < 1) {
            Log::info('$tails<1');
            return true;
        }
        else {
            Log::info('else');
            foreach ($tails as $tail) {            
            foreach ($tail->reservation->car->orders->where('is_product', false) as $orderData) { 
                if ($orderData->branchServiceProfessional->branchService->service->simultaneou == 1) {
                    return true;
                }
            }
        }
        }
        return false;
    }

    public function cola_branch_capilar($branch_id){
        $tails = Tail::with(['reservation' => function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        }])->orderBy('updated_at')->whereIn('attended', [4,5])->get();
        $branchTails = $tails->map(function ($tail){
            return [
                'reservation_id' => $tail->reservation->id,
                'car_id' => $tail->reservation->car_id,
                'start_time' => Carbon::parse($tail->reservation->start_time)->format('H:i:s'),
                'final_hour' => Carbon::parse($tail->reservation->final_hour)->format('H:i:s'),
                'total_time' => $tail->reservation->total_time,
                'client_name' => $tail->reservation->car->clientProfessional->client->name." ".$tail->reservation->car->clientProfessional->client->surname,
                'professional_name' => $tail->reservation->car->clientProfessional->professional->name." ".$tail->reservation->car->clientProfessional->professional->surname,
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
        $tails = Tail::whereHas('reservation.car.orders.branchServiceProfessional.professional.workplaces', function ($query) use ($branch_id, $places){
            $query->where('branch_id', $branch_id)->whereIn('workplace_id', $places);
        })->orderBy('updated_at')->whereIn('attended', [4])->get();
        
        $branchTails = $tails->map(function ($tail){
            return [
                'reservation_id' => $tail->reservation->id,
                'car_id' => $tail->reservation->car_id,
                'start_time' => Carbon::parse($tail->reservation->start_time)->format('H:i:s'),
                'final_hour' => Carbon::parse($tail->reservation->final_hour)->format('H:i:s'),
                'total_time' => $tail->reservation->total_time,
                'client_name' => $tail->reservation->car->clientProfessional->client->name." ".$tail->reservation->car->clientProfessional->client->surname,
                'professional_name' => $tail->reservation->car->clientProfessional->professional->name." ".$tail->reservation->car->clientProfessional->professional->surname,
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
        Log::info($client);
        $professional = Professional::find($data['professional_id']);
        Log::info($professional);
        $reservation = Reservation::find($data['reservation_id']);
        Log::info($reservation);
        $horaActual = Carbon::now()->format('H:i:s');
        $timestamp = strtotime($reservation->total_time);
        $tiempo_entero = date('Gis', $timestamp);
        $horas = intval(substr($tiempo_entero, 0, 1));
        $minutos = intval(substr($tiempo_entero, 2, 2));
        $time = $horas * 60 + $minutos;
        Log::info($horaActual);
        Log::info(Carbon::parse($horaActual)->addMinutes($time)->toTimeString());
        $reservation->start_time = $horaActual;
        $reservation->final_hour = Carbon::parse($horaActual)->addMinutes($time)->toTimeString();
        $reservation->save();
        $car = Car::find($reservation->car_id);
        
        Log::info($car);
        //$relation = Client::find($data['client_id'])->professionals()->where('professional_id', $data['professional_id'])->first();
        //Log::info($relation);
        //$client_professional_id = $relation->pivot->id;
        //$client_professional_id = $professional->clients()->wherePivot('client_id', $client->id)->withPivot('id')->get()/*->map->pivot->value('id')*/;
        $client_professional = $professional->clients()->where('client_id', $client->id)->withPivot('id')->first();
        
       
        if(!$client_professional){
            Log::info("no existe");
            $professional->clients()->attach($client->id);
            $client_professional_id = $professional->clients()->wherePivot('client_id', $client->id)->withPivot('id')->get()->map->pivot->value('id');
            Log::info($client_professional_id);
        }
        else{
            $client_professional_id = $client_professional->pivot->id;
            Log::info($client_professional_id);
        }
        $car->client_professional_id = $client_professional_id;
        $car->save();
        
    }
}