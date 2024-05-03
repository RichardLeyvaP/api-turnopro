<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Car;
use App\Models\Client;
use App\Models\ClientProfessional;
use App\Models\Order;
use App\Models\Professional;
use App\Models\Reservation;
use App\Models\Tail;
use App\Models\Comment;
use App\Models\ProfessionalWorkPlace;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TailService {

    public function cola_branch_data($branch_id){
        /*$tails1 = Tail::whereHas('reservation', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        })->get();*/
        Log::info('Cola de una branch');
        //Log::info($tails1);
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
                'reservation_id' => $reservation->id,
                'car_id' => $reservation->car_id,
                'from_home' => $reservation->from_home,
                'start_time' => Carbon::parse($reservation->start_time)->format('H:i:s'),
                'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i:s'),
                'total_time' => $reservation->total_time,
                'client_name' => $client->name." ".$client->surname,
                'client_image' => $comment ? $comment->client_look : "comments/default_profile.jpg",
                'professional_name' => $professional->name." ".$professional->surname,
                'client_id' => $client->id,
                'professional_id' => $professional->id,
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
        })->whereIn('attended', [3,33])->get()->map(function ($tail){
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
                'reservation_id' => $reservation->id,
                'car_id' => $reservation->car_id,
                'start_time' => Carbon::parse($reservation->start_time)->format('H:i:s'),
                'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i:s'),
                'total_time' => $reservation->total_time,
                'client_image' => $comment ? $comment->client_look : "comments/default_profile.jpg",
                'client_id' => $client->id,
                'professional_id' => $professional->id,
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
                    'reservation_id' => $reservation->id,
                    'car_id' => $tail->reservation->car_id,
                    'from_home' => $tail->reservation->from_home,
                    'start_time' => Carbon::parse($reservation->start_time)->format('H:i:s'),
                    'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i:s'),
                    'total_time' => $reservation->total_time,
                    'client_name' => $client->name . " " . $client->surname,
                    'client_image' => $comment ? $comment->client_look : "comments/default_profile.jpg",
                    'professional_name' => $professional->name . " " . $professional->surname  . " " . $professional->second_surname,
                    'image_url' => $professional->image_url ? $professional->image_url : "professionals/default_profile.jpg",
                    'client_id' => $client->id,
                    'professional_id' => $professional->id,
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
        })->whereNot('attended', [2])->get();
        $branchTails = $tails->map(function ($tail) use ($branch_id){  
            $reservation = $tail->reservation;
            $professional = $reservation->car->clientProfessional->professional;
            $client = $reservation->car->clientProfessional->client;      
            return [
                'reservation_id' => $reservation->id,
                'car_id' => $reservation->car_id,
                'start_time' => Carbon::parse($reservation->start_time)->format('H:i:s'),
                'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i:s'),
                'total_time' => $reservation->total_time,
                'client_name' => $client->name." ".$client->surname,
                'client_image' => $client->client_image ? $client->client_image : "comments/default_profile.jpg",
                'professional_name' => $professional->name." ".$professional->surname,
                'client_id' => $client->id,
                'professional_id' => $professional->id,
                'attended' => $tail->attended, 
                'updated_at' => $tail->updated_at->format('Y-m-d H:i:s'),
                'clock' => $tail->clock, 
                'timeClock' => $tail->timeClock, 
                'detached' => $tail->detached, 
                'total_services' => Order::whereHas('car.reservation')->whereRelation('car', 'id', '=', $reservation->car_id)->where('is_product', false)->count()
               
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
            Log::info('$car->id');
            Log::info($car->id);
            $professional = ClientProfessional::whereHas('cars', function ($query) use ($car){
                $query->where('id', $car->id);
            })->first()->professional_id;
            Log::info('$professional->id');
            Log::info($professional);
            $workplaceId = ProfessionalWorkPlace::where('professional_id', $professional)->whereDate('data', Carbon::now())->whereHas('workplace', function($query){
                $query->where('busy', 1)->where('select', 1);
            })->first()->workplace_id;
            Log::info('$workplace->id');
            Log::info($workplaceId);
            $tecnicoId = ProfessionalWorkplace::where('data', Carbon::today())
    ->whereJsonContains('places', $workplaceId)
    ->value('professional_id');
            Log::info('$tecnicoId->id');
            Log::info($tecnicoId);
            
            $car->technical_assistance = $car->technical_assistance + 1;
            $car->tecnico_id = $tecnicoId;
            $car->save();
            
            Log::info($car);
        }
    }

    public function type_of_service($branch_id, $professional_id){
        $tails = Tail::with(['reservation' => function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        }])->whereHas('reservation.car.clientProfessional', function ($query) use($professional_id){
            $query->where('professional_id', $professional_id);
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
                // Verifica si car no es null
                if ($tail->reservation->car !== null) {
                    foreach ($tail->reservation->car->orders->where('is_product', false) as $orderData) {
                        if ($orderData->branchServiceProfessional->branchService->service->simultaneous == 1) {
                            return true;
                        }
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
        /*$workplace = Professional::find($professional_id)->workplaces()->wherePivot('data', Carbon::now()->format('Y-m-d'))->withPivot('id', 'places')->get()->map->pivot;
        $places = json_decode($workplace->value('places'), true);
        $professionals = Professional::whereHas('workplaces', function ($query) use ($places){
            $query->whereIn('workplace_id', $places);
        })->get()->value('id');*/
        $workplace = Professional::find($professional_id)
            ->workplaces()
            ->wherePivot('data', Carbon::now()->format('Y-m-d'))
            ->withPivot('places')
            ->first();

        if ($workplace) {
            $places = json_decode($workplace->pivot->places, true);

            $professionals = Professional::whereHas('workplaces', function ($query) use ($places) {
                $query->whereIn('workplace_id', $places)->whereDate('data', Carbon::now()->format('Y-m-d'));
            })->pluck('id');
            } else {
                // Manejar caso donde no se encuentra el lugar de trabajo
                $professionals = [];
            }
        $tails = Tail::whereHas('reservation', function ($query) use ($branch_id, $professionals){
            $query->where('branch_id', $branch_id)->whereHas('car.clientProfessional', function ($query) use ($professionals){
                $query->whereIn('professional_id', $professionals);
            });
        })->orderBy('updated_at')->whereIn('attended', [4])->get()->map(function ($tail){
            $client = $tail->reservation->car->clientProfessional->client;
            $professional = $tail->reservation->car->clientProfessional->professional;
            $reservation = $tail->reservation;
            return [
                'reservation_id' => $reservation->id,
                'car_id' => $reservation->car_id,
                'start_time' => Carbon::parse($reservation->start_time)->format('H:i:s'),
                'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i:s'),
                'total_time' => $reservation->total_time,
                'client_name' => $client->name." ".$client->surname,
                'professional_name' => $professional->name." ".$professional->surname,
                'client_id' => $client->id,
                'professional_id' => $professional->id,
                'professional_state' => $professional->state,
                'attended' => $tail->attended
            ];
        })->values();
        
        return $tails;
    }

    public function reasigned_client($data){
        $client = Client::find($data['client_id']);
        Log::info($client);
        $professional = Professional::find($data['professional_id']);
        Log::info($professional);
        $reservation = Reservation::find($data['reservation_id']);
        Log::info($reservation);
        $horaActual = Carbon::now();
        /*$horaActual = Carbon::now()->format('H:i:s');
        $timestamp = strtotime($reservation->total_time);
        $tiempo_entero = date('Gis', $timestamp);
        $horas = intval(substr($tiempo_entero, 0, 1));
        $minutos = intval(substr($tiempo_entero, 2, 2));
        return $time = $horas * 60 + $minutos;*/
        // Obtener el tiempo total de la reserva en formato "00:40:00"
            $tiempoReserva = $reservation->total_time;

            // Parsear el tiempo de la reserva para obtener horas, minutos y segundos
            list($horasReserva, $minutosReserva, $segundosReserva) = explode(':', $tiempoReserva);

            // Sumar el tiempo de la reserva a la hora actual
            $nuevaHora = $horaActual->copy()->addHours($horasReserva)->addMinutes($minutosReserva)->addSeconds($segundosReserva);

            // Formatear la nueva hora en el formato deseado (H:i:s)
            $nuevaHoraFormateada = $nuevaHora->format('H:i:s');
                    $reservation->start_time = $horaActual;
                    $reservation->final_hour = Carbon::parse($nuevaHoraFormateada)->toTimeString();
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