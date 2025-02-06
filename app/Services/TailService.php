<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchProfessional;
use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\Client;
use App\Models\ClientProfessional;
use App\Models\Order;
use App\Models\Professional;
use App\Models\Reservation;
use App\Models\Tail;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\ProfessionalWorkPlace;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TailService
{

  
    
       public function cola_branch_data_ANTERIOR($branch_id)
    {
        /*$tails1 = Tail::whereHas('reservation', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        })->get();*/
        Log::info('Cola de una branch');
        //Log::info($tails1);
        $tails = Tail::whereHas('reservation', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id)->where('confirmation', 4);
        })->whereIn('attended', [0, 3, 33])->get()->map(function ($tail) {
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
            $comment = Comment::whereHas('clientProfessional', function ($query) use ($client) {
                $query->where('client_id', $client->id);
            })->orderByDesc('data')->orderByDesc('updated_at')->first();
            /*if($tail->attended == 0 && $tail->aleatorie == 1){
                    $name = '';
                    $image = "professionals/default_profile.jpg";
                }else{*/
            $name = $professional->name;
            $image = $professional->image_url ? $professional->image_url : "professionals/default_profile.jpg";
            $createdAt = $reservation->from_home == 1 ? $reservation->updated_at : $reservation->created_at;
            //}
            return [
                'reservation_id' => $reservation->id,
                'car_id' => $reservation->car_id,
                'from_home' => intval($reservation->from_home),
                'start_time' => Carbon::parse($reservation->start_time)->format('H:i'),
                'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i'),
                'total_time' => $reservation->total_time,
                'client_name' => $client->name . " " . $client->surname,
                'client_image' => $comment ? $comment->client_look : "comments/default_profile.jpg",
                'professional_name' => $name,
                'client_id' => $client->id,
                'professional_id' => $professional->id,
                'professional_state' => $professional->state,
                'attended' => $tail->attended,
                'puesto' => $workplace ? $workplace->name : null,
                'created_at' => $createdAt,
                'select_professional' => intval($reservation->car->select_professional)
            ];
        })->sortByDesc('professional_state')->sortBy('created_at')->values();

        return $tails;
    }
    
       public function cola_branch_data($branch_id)
    {
        /*$tails1 = Tail::whereHas('reservation', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        })->get();*/
        Log::info('Cola de una branch');
        //Log::info($tails1);
        $tails = Tail::whereHas('reservation', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id)->where('confirmation', 4);
        })->whereIn('attended', [0, 3, 33])->get()->map(function ($tail) {
            Log::info($tail);
            $reservation = $tail->reservation;
            Log::info('reservacion');
            Log::info($reservation);
            Log::info('clientProfessional');
            Log::info($reservation->car->clientProfessional);
            $professional = $reservation->car->clientProfessional->professional()->withTrashed()->first();
            $client = $reservation->car->clientProfessional->client;
            $workplace = $professional->workplaces()
                ->whereDate('data', $reservation->data)
                ->first();
            $comment = Comment::whereHas('clientProfessional', function ($query) use ($client) {
                $query->where('client_id', $client->id);
            })->orderByDesc('data')->orderByDesc('updated_at')->first();
            /*if($tail->attended == 0 && $tail->aleatorie == 1){
                    $name = '';
                    $image = "professionals/default_profile.jpg";
                }else{*/
            $name = $professional->name;
            $image = $professional->image_url ? $professional->image_url : "professionals/default_profile.jpg";
            $createdAt = $reservation->from_home == 1 ? $reservation->updated_at : $reservation->created_at;
            //}
            return [
                'reservation_id' => $reservation->id,
                'car_id' => $reservation->car_id,
                'from_home' => intval($reservation->from_home),
                'start_time' => Carbon::parse($reservation->start_time)->format('H:i'),
                'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i'),
                'total_time' => $reservation->total_time,
                'client_name' => $client->name . " " . $client->surname,
                'client_image' => $comment ? $comment->client_look : "comments/default_profile.jpg",
                'professional_name' => $name,
                'client_id' => $client->id,
                'professional_id' => $professional->id,
                'professional_state' => $professional->state,
                'attended' => $tail->attended,
                'puesto' => $workplace ? $workplace->name : null,
                'created_at' => $createdAt,
                'select_professional' => intval($reservation->car->select_professional)
            ];
        })->sortByDesc('professional_state')->sortBy('created_at')->values();

        return $tails;
    }

    public function cola_branch_data2($branch_id)
    {
        $tails = Tail::whereHas('reservation', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->whereIn('attended', [3, 33])->get()->map(function ($tail) {
            $professional = [];
            $professionaltem = [];
            Log::info($tail);
            $reservation = $tail->reservation;
            Log::info('reservacion');
            Log::info($reservation);
            if ($tail->attended == 33) {
                $car = Car::whereHas('reservation', function ($query) use ($reservation) {
                    $query->where('id', $reservation->id);
                })->first();
                Log::info('$car->id');
                Log::info($car->id);
                $professionaltem = ClientProfessional::whereHas('cars', function ($query) use ($car) {
                    $query->where('id', $car->id);
                })->first();
                Log::info('$professional->id');
                Log::info($professionaltem);
                $workplaceId = ProfessionalWorkPlace::where('professional_id', $professionaltem->professional_id)->whereDate('data', Carbon::now())->whereHas('workplace', function ($query) {
                    $query->where('busy', 1)->where('select', 1);
                })->first();
                if ($workplaceId) {
                    Log::info('$workplace->id');
                    Log::info($workplaceId);
                    $workplacetecnicos = ProfessionalWorkplace::where('data', Carbon::today())->whereHas('professional.charge', function ($query) {
                        $query->where('name', 'Tecnico');
                    })->orderByDesc('data')
                        //->whereJsonContains('places', (int)$workplaceId->workplace_id)
                        ->get();
                    if ($workplacetecnicos) {
                        foreach ($workplacetecnicos as $workplacetecnico) {
                            $places = json_decode($workplacetecnico->places, true);
                            if (in_array($workplaceId->workplace_id, $places)) {
                                $tecnicoId = $workplacetecnico;
                                $professional = $workplacetecnico->professional;
                                break;
                            }
                        }
                    }
                }
                /*else{
                    $tecnicoId =  null;
                }*/

                /*Log::info('$tecnicoId');
                Log::info($tecnicoId);
                    if($tecnicoId != null){
                        Log::info('$tecnicoId->id');
                    Log::info($tecnicoId->professional_id);
                    $professional = Professional::where('id', $tecnicoId->professional_id)->first();
                    Log::info($professional);
                    }*/
            } else {
                // $professional = $reservation->car->clientProfessional->professional;
                $professional = $reservation->car->clientProfessional->professional()->withTrashed()->first();
            }
            $client = $reservation->car->clientProfessional->client;
            $comment = Comment::whereHas('clientProfessional', function ($query) use ($client) {
                $query->where('client_id', $client->id);
            })->orderByDesc('data')->orderByDesc('updated_at')->first();
            $tail = $reservation->tail;
            /*if($tail->attended == 0 && $tail->aleatorie == 1){
                    $name = '';
                    $image = "professionals/default_profile.jpg";
                }else{*/
            $name = $professionaltem ? $professionaltem->professional->name : '';
            $image = $professional->image_url ? $professional->image_url : "professionals/default_profile.jpg";
            //}
            return [
                'reservation_id' => $reservation->id,
                'car_id' => $reservation->car_id,
                'from_home' => intval($reservation->from_home),
                'start_time' => Carbon::parse($reservation->start_time)->format('H:i'),
                'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i'),
                'total_time' => $reservation->total_time,
                'client_image' => $comment ? $comment->client_look : "comments/default_profile.jpg",
                'client_id' => $client->id,
                'idBarber' => $professionaltem ? $professionaltem->professional_id : 0,
                'nameBarber' => $name,
                'professional_id' => $professional ? $professional->id : 0,
                'professional_name' => $professional->name,
                'client_name' => $client->name . " " . $client->surname,
                'charge' => $professional ? $professional->charge->name : ' ',
                'attended' => $tail->attended,
                'time' => Carbon::parse($tail->updated_at)->format('H:i'),
                'select_professional' => intval($reservation->car->select_professional)
            ];
        })->sortBy('time')->values();

        return $tails;
    }

    public function tail_branch_attended($branch_id)
    {
        /*
        $branch = Branch::where('id', $branch_id)->whereHas('tails', function ($query) use ($branch_id){
            $query->whereIn('attended', [1, 5, 11, 111, 4])->whereHas('reservation', function ($query) use ($branch_id){
                $query->where('branch_id', $branch_id);
            });
        })->get()->flatMap(function ($branch) {
            return $branch->tails->filter(function ($tail) {
                // Add the condition to filter tails based on the 'attended' attribute
                return in_array($tail->attended, [1]);//[1, 5, 11, 111, 4]
            })*/
        $branch = Tail::whereHas('reservation', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->whereIn('attended', [1])->get()->map(function ($tail) {
            $reservation = $tail->reservation;
            // $professional = $reservation->car->clientProfessional->professional;
            $professional = $reservation->car->clientProfessional->professional()->withTrashed()->first();
            $client = $reservation->car->clientProfessional->client;
            $workplace = $professional->workplaces()
                ->whereDate('data', $reservation->data)
                ->first();
            $comment = Comment::whereHas('clientProfessional', function ($query) use ($client) {
                $query->where('client_id', $client->id);
            })->orderByDesc('updated_at')->first();

            return [
                'reservation_id' => $reservation->id,
                'car_id' => $reservation->car_id,
                'from_home' => intval($reservation->from_home),
                'start_time' => Carbon::parse($reservation->start_time)->format('H:i'),
                'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i'),
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
                'code' => $reservation->code,
                'select_professional' => intval($reservation->car->select_professional)
            ];
        })->sortBy('start_time')->values();
        //});

        return $branch;
    }

    public function cola_branch_delete($branch_id)
    {
        $tails = Tail::whereHas('reservation', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->delete();
    }

    public function cola_branch_professional($branch_id, $professional_id)
    {
        $professional = Professional::find($professional_id);
        if ($professional->state == 1) {
            $this->verific_aleatorie($branch_id, $professional);
        }
      $tails = Tail::whereHas('reservation', function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id)->whereIn('confirmation', [1, 4]);
            })
            ->whereHas('reservation.car.clientProfessional', function ($query) use ($professional_id) {
                $query->where('professional_id', $professional_id);
            })
            ->whereNot('attended', [2])
            ->where('aleatorie', '!=', 1)
            ->join('reservations', 'tails.reservation_id', '=', 'reservations.id')
            ->orderByRaw('reservations.confirmation = 4 DESC')
            ->orderBy('reservations.from_home', 'desc')
            ->orderBy('reservations.start_time', 'asc')
            ->select('tails.*')  // Selecciona sólo las columnas del modelo Tail
            ->with('reservation') // Carga la relación reservation
            ->get();
        $branchTails = $tails->map(function ($tail) use ($branch_id) {
            $reservation = $tail->reservation;
            $professional = $reservation->car->clientProfessional->professional;
            $client = $reservation->car->clientProfessional->client;
            return [
                'reservation_id' => $reservation->id,
                'car_id' => $reservation->car_id,
                'from_home' => intval($reservation->from_home),
                'start_time' => Carbon::parse($reservation->start_time)->format('H:i'),
                'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i'),
                'total_time' => $reservation->total_time,
                'confirmation' => intval($reservation->confirmation),
                'client_name' => $client->name,
                'client_image' => $client->client_image ? $client->client_image : "comments/default_profile.jpg",
                'professional_name' => $professional->name,
                'client_id' => $client->id,
                'professional_id' => $professional->id,
                'attended' => $tail->attended,
                'updated_at' => $tail->updated_at->format('Y-m-d H:i'),
                'clock' => $tail->clock,
                'timeClock' => $tail->timeClock,
                'detached' => $tail->detached,
                'total_services' => Order::whereHas('car.reservation')->whereRelation('car', 'id', '=', $reservation->car_id)->where('is_product', false)->count(),
                'select_professional' => intval($reservation->car->select_professional)

            ];
        })->values();
        return $branchTails;
    }

    public function tail_branch_professional_ANTERIOR($branch_id, $professional_id)
    {
        $professional = Professional::find($professional_id);
        if ($professional->state == 1) {
            $this->verific_aleatorie($branch_id, $professional);
        }        
        Log::info('Llamando a la cola el profesional: '.$professional->name.' en el servivio TailService(tail_branch_professional)');
            $tails = Tail::whereHas('reservation', function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id)->whereIn('confirmation', [1, 4]);
            })
            ->whereHas('reservation.car.clientProfessional', function ($query) use ($professional_id) {
                $query->where('professional_id', $professional_id);
            })
            ->whereNot('attended', [2])
            ->where('aleatorie', '!=', 1)
            ->join('reservations', 'tails.reservation_id', '=', 'reservations.id')
            ->orderByRaw('reservations.confirmation = 4 DESC')
            ->orderBy('reservations.from_home', 'desc')
            ->orderBy('reservations.start_time', 'asc')
            ->select('tails.*')  // Selecciona sólo las columnas del modelo Tail
            ->with('reservation') // Carga la relación reservation
            ->get();
        $branchTails = $tails->map(function ($tail) use ($branch_id) {
            $reservation = $tail->reservation;
            $professional = $reservation->car->clientProfessional->professional;
            $client = $reservation->car->clientProfessional->client;
            $orderServicesDatas = Order::whereHas('car.reservation')->whereRelation('car', 'id', '=', $reservation->car_id)->where('is_product', 0)->get();
            $services = $orderServicesDatas->map(function ($orderData) {
                $service = $orderData->branchServiceProfessional->branchService->service;
                return [
                    'name' => $service->name,
                    'simultaneou' => $service->simultaneou,
                    'price_service' => $service->price_service,
                    'type_service' => $service->type_service,
                    'profit_percentaje' => $service->profit_percentaje,
                    'duration_service' => $service->duration_service,
                    'image_service' => $service->image_service,
                    'description' => $service->service_comment
                ];
            });
            return [
                'reservation_id' => $reservation->id,
                'car_id' => $reservation->car_id,
                'from_home' => intval($reservation->from_home),
                'start_time' => Carbon::parse($reservation->start_time)->format('H:i'),
                'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i'),
                'total_time' => $reservation->total_time,
                'confirmation' => intval($reservation->confirmation),
                'client_name' => $client->name,
                'client_image' => $client->client_image ? $client->client_image : "comments/default_profile.jpg",
                'professional_name' => $professional->name,
                'client_id' => $client->id,
                'professional_id' => $professional->id,
                'attended' => $tail->attended,
                'updated_at' => $tail->updated_at->format('Y-m-d H:i'),
                'clock' => $tail->clock,
                'timeClock' => $tail->timeClock,
                'detached' => $tail->detached,
                'total_services' => $services->count(),
                'select_professional' => intval($reservation->car->select_professional),
                'telefone_client' => $client->phone ? strval($client->phone) : '',
                'services' => $services

            ];
        })->values();
        return $branchTails;
    }
    
      public function tail_branch_professional($branch_id, $professional_id)
    {
        $professional = Professional::find($professional_id);

        if ($professional->state == 1) {
            $this->verific_aleatorie($branch_id, $professional);
        }

        Log::info('Llamando a la cola el profesional: ' . $professional->name . ' en el servicio TailService(tail_branch_professional)');
        $today = Carbon::now()->format('Y-m-d');
        // Eager loading para evitar consultas N+1
        $tails = Tail::with([
            'reservation.car.clientProfessional.client',
            'reservation.car.clientProfessional.professional',
            'reservation.car.orders.branchServiceProfessional.branchService.service'
        ])
        ->whereHas('reservation', function ($query) use ($branch_id, $today) {
            $query->where('branch_id', $branch_id)
            ->whereDate('data', $today)
                ->whereIn('confirmation', [1, 4]);
        })
        ->whereHas('reservation.car.clientProfessional', function ($query) use ($professional_id) {
            $query->where('professional_id', $professional_id);
        })
        ->whereNot('attended', 2)
        ->where('aleatorie', '!=', 1)
        ->join('reservations', 'tails.reservation_id', '=', 'reservations.id')
        ->orderByRaw('reservations.confirmation = 4 DESC')
        ->orderBy('reservations.from_home', 'desc')
        ->orderByRaw('CASE WHEN reservations.from_home = 0 THEN reservations.created_at ELSE reservations.updated_at END ASC')
        ->select('tails.*')  // Selecciona sólo las columnas del modelo Tail
        ->with('reservation') // Carga la relación reservation
        ->get()->map(function ($tail) {
            $reservation = $tail->reservation;
            $car = $reservation->car;
            $clientProfessional = $car->clientProfessional;
            $professional = $clientProfessional->professional;
            $client = $clientProfessional->client;

            // Eager loaded services
            $services = $car->orders->filter(function ($orderServiceProfessional) {
                return $orderServiceProfessional->is_product == 0;
            })->map(function ($orderServiceProfessional) {
                $service = $orderServiceProfessional->branchServiceProfessional->branchService->service;
                return [
                    'name' => $service->name,
                    'simultaneou' => $service->simultaneou,
                    'price_service' => $service->price_service,
                    'type_service' => $service->type_service,
                    'profit_percentaje' => $service->profit_percentaje,
                    'duration_service' => $service->duration_service,
                    'image_service' => $service->image_service,
                    'description' => $service->service_comment
                ];
            })->values();

            return [
                'reservation_id' => $reservation->id,
                'car_id' => $reservation->car_id,
                'from_home' => intval($reservation->from_home),
                'start_time' => Carbon::parse($reservation->start_time)->format('H:i'),
                'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i'),
                'total_time' => $reservation->total_time,
                'confirmation' => intval($reservation->confirmation),
                'client_name' => $client->name,
                'client_image' => $client->client_image ? $client->client_image : "comments/default_profile.jpg",
                'professional_name' => $professional->name,
                'client_id' => $client->id,
                'professional_id' => $professional->id,
                'attended' => $tail->attended,
                    'updated_at' => $tail->updated_at->format('Y-m-d H:i'),
                    'clock' => $tail->clock,
                    'timeClock' => $tail->timeClock,
                    'detached' => $tail->detached,
                    'total_services' => $services->count(),
                    'select_professional' => intval($reservation->car->select_professional),
                    'telefone_client' => $client->phone ? strval($client->phone) : '',
                    'services' => $services

                ];
            })->values();
            return $tails;
    }
    
     public function tail_branch_professional_ANTERIOR_OPTIMIZADO($branch_id, $professional_id)
    {
        $professional = Professional::find($professional_id);

        if ($professional->state == 1) {
            $this->verific_aleatorie($branch_id, $professional);
        }

        Log::info('Llamando a la cola el profesional: ' . $professional->name . ' en el servicio TailService(tail_branch_professional)');

        // Eager loading para evitar consultas N+1
        $tails = Tail::with([
            'reservation' => function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id)
                ->whereDate('data', Carbon::now())
                    ->whereIn('confirmation', [1, 4]);
            },
            'reservation.car.clientProfessional.client',
            'reservation.car.clientProfessional.professional',
            'reservation.car.orders.branchServiceProfessional.branchService.service'
        ])
        ->whereHas('reservation', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id)
            ->whereDate('data', Carbon::now())
                ->whereIn('confirmation', [1, 4]);
        })
        ->whereHas('reservation.car.clientProfessional', function ($query) use ($professional_id) {
            $query->where('professional_id', $professional_id);
        })
        ->whereNotIn('attended', [2])
        ->where('aleatorie', '!=', 1)
        ->join('reservations', 'tails.reservation_id', '=', 'reservations.id')
        ->orderByRaw('reservations.confirmation = 4 DESC')
        ->orderBy('reservations.from_home', 'desc')
        ->orderBy('reservations.start_time', 'asc')
        ->select('tails.*')
        ->get();

        // Procesamiento de resultados
        $branchTails = $tails->map(function ($tail) use ($branch_id) {
            $reservation = $tail->reservation;
            $professional = $reservation->car->clientProfessional->professional;
            $client = $reservation->car->clientProfessional->client;

            // Eager loaded services
            $services = $reservation->car->orders->map(function ($orderServiceProfessional) {
                $service = $orderServiceProfessional->branchServiceProfessional->branchService->service;
                return [
                    'name' => $service->name,
                    'simultaneou' => $service->simultaneou,
                    'price_service' => $service->price_service,
                    'type_service' => $service->type_service,
                    'profit_percentaje' => $service->profit_percentaje,
                    'duration_service' => $service->duration_service,
                    'image_service' => $service->image_service,
                    'description' => $service->service_comment
                ];
            });

            return [
                'reservation_id' => $reservation->id,
                'car_id' => $reservation->car_id,
                'from_home' => intval($reservation->from_home),
                'start_time' => Carbon::parse($reservation->start_time)->format('H:i'),
                'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i'),
                'total_time' => $reservation->total_time,
                'confirmation' => intval($reservation->confirmation),
                'client_name' => $client->name,
                'client_image' => $client->client_image ? $client->client_image : "comments/default_profile.jpg",
                'professional_name' => $professional->name,
                'client_id' => $client->id,
                'professional_id' => $professional->id,
                'attended' => $tail->attended,
                    'updated_at' => $tail->updated_at->format('Y-m-d H:i'),
                    'clock' => $tail->clock,
                    'timeClock' => $tail->timeClock,
                    'detached' => $tail->detached,
                    'total_services' => $services->count(),
                    'select_professional' => intval($reservation->car->select_professional),
                    'telefone_client' => $client->phone ? strval($client->phone) : '',
                    'services' => $services

                ];
            })->values();
            return $branchTails;
        }
        
        

    public function cola_branch_professional_new($branch_id, $professional_id)
    {
        $professional = Professional::find($professional_id);
        $this->verific_aleatorie($branch_id, $professional);
        $tails = Tail::whereHas('reservation', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id)->whereIn('confirmation', [1, 4]);
        })
        ->whereHas('reservation.car.clientProfessional', function ($query) use ($professional_id) {
            $query->where('professional_id', $professional_id);
        })
        ->whereNot('attended', [2])
        ->where('aleatorie', '!=', 1)
        ->join('reservations', 'tails.reservation_id', '=', 'reservations.id')
        ->orderByRaw('reservations.confirmation = 4 DESC')
        ->orderBy('reservations.from_home', 'desc')
        ->orderBy('reservations.start_time', 'asc')
        ->select('tails.*')  // Selecciona sólo las columnas del modelo Tail
        ->with('reservation') // Carga la relación reservation
        ->get();
        $branchTails = $tails->map(function ($tail) use ($branch_id) {
            $reservation = $tail->reservation;
            $professional = $reservation->car->clientProfessional->professional;
            $client = $reservation->car->clientProfessional->client;
            $services = Order::whereHas('car.reservation')->whereRelation('car', 'id', '=', $reservation->car_id)->where('is_product', false)->get()->map(function ($orderData) {
                $service = $orderData->branchServiceProfessional->branchService->service;
                return [
                    'name' => $service->name,
                    'simultaneou' => $service->simultaneou,
                    'price_service' => $service->price_service,
                    'type_service' => $service->type_service,
                    'profit_percentaje' => $service->profit_percentaje,
                    'duration_service' => $service->duration_service,
                    'image_service' => $service->image_service,
                    'description' => $service->service_comment
                ];
            });
            return [
                'reservation_id' => $reservation->id,
                'car_id' => $reservation->car_id,
                'start_time' => Carbon::parse($reservation->start_time)->format('H:i'),
                'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i'),
                'total_time' => $reservation->total_time,
                'client_name' => $client->name,
                'from_home' => intval($reservation->from_home),
                'client_image' => $client->client_image ? $client->client_image : "comments/default_profile.jpg",
                'professional_name' => $professional->name,
                'client_id' => $client->id,
                'professional_id' => $professional->id,
                'attended' => $tail->attended,
                'updated_at' => $tail->updated_at->format('Y-m-d H:i'),
                'clock' => $tail->clock,
                'timeClock' => $tail->timeClock,
                'detached' => $tail->detached,
                'total_services' => $services->count(),
                'services' => $services,
                'select_professional' => intval($reservation->car->select_professional)

            ];
        })->values();
        return $branchTails;
    }

    public function tail_attended($reservation_id, $attended)
    {
        DB::beginTransaction();
        try{
        $tecnicoId = 0;
        $reservationNoti = Reservation::where('id', $reservation_id)->first();
        $tail = Tail::where('reservation_id', $reservation_id)->first();
        if ($attended == 1) {
            $current_date = Carbon::now()->format('H:i:s');
            $tail->aleatorie = 0;
            $reservation = Reservation::findOrFail($reservation_id);
            $car = $reservation->car;
            if ($car->select_professional == 0) {
                $branchProfessional = BranchProfessional::where('professional_id', $car->clientProfessional->professional_id)
                                                ->where('branch_id', $reservation->branch_id)
                                                ->first();
                                                
                    if ($branchProfessional) {
                        $branchProfessional->numberRandom += 1;
                        $branchProfessional->save();
                    }
            }
            $reservation->start_time = $current_date;  
            $total_time = $reservation->total_time; // Ejemplo: '00:10:00'

            // Convertimos $current_date y $total_time a instancias de Carbon
            $current_time = Carbon::createFromFormat('H:i:s', $current_date);
            $total_time_carbon = Carbon::createFromFormat('H:i:s', $total_time);

            // Sumamos el tiempo total a la hora actual
            $reservation->final_hour = $current_time->addHours($total_time_carbon->hour)
                                                ->addMinutes($total_time_carbon->minute)
                                                ->addSeconds($total_time_carbon->second)
                                                ->format('H:i:s');
          
            //$reservation->final_hour = date('H:i:s', strtotime($current_date) + strtotime($reservation->total_time));            
            $reservation->started_at = now();
            $reservation->save();
        }//if 1
        if ($attended == 2) {
            $reservation = Reservation::findOrFail($reservation_id);
            $reservation->finished_at = now();
            $reservation->confirmation = 2;
            $reservation->save();
        }//if 2
        if ($attended == 5) {
            $car = Car::whereHas('reservation', function ($query) use ($reservation_id) {
                $query->where('id', $reservation_id);
            })->first();
            Log::info('$car->id');
            Log::info($car->id);
            $professional = ClientProfessional::whereHas('cars', function ($query) use ($car) {
                $query->where('id', $car->id);
            })->first()->professional_id;
            Log::info('$professional->id');
            Log::info($professional);
            $workplaceId = ProfessionalWorkPlace::where('professional_id', $professional)->whereDate('data', Carbon::now())->whereHas('workplace', function ($query) {
                $query->where('busy', 1)->where('select', 1);
            })->first();
            $workplacetecnicos = ProfessionalWorkplace::where('data', Carbon::today())->whereHas('professional.charge', function ($query) {
                $query->where('name', 'Tecnico');
            })->orderByDesc('data')
                //->whereJsonContains('places', (int)$workplaceId->workplace_id)
                ->get();
            if ($workplacetecnicos) {
                foreach ($workplacetecnicos as $workplacetecnico) {
                    $places = json_decode($workplacetecnico->places, true);
                    if (in_array($workplaceId->workplace_id, $places)) {
                        $tecnicoId = $workplacetecnico->professional_id;
                        //$professional = $workplacetecnico->professional;
                        break;
                    }
                }
                $car->technical_assistance = $car->technical_assistance + 1;
                $car->tecnico_id = $tecnicoId;
                $car->save();
            }
        }//if 5
        if ($attended == 3) {
            $reservation = Reservation::findOrFail($reservation_id);
            $car = $reservation->car;
            $clientProfessional = $car->clientProfessional;
            $professional = $clientProfessional->professional;
            $client = $clientProfessional->client;
            $branch = Branch::find($reservation->branch_id);
                $professionals = BranchProfessional::with(['professional' => function($query) {
                    $query->select('id', 'charge_id'); // Especifica los campos necesarios
                }, 'professional.charge' => function($query) {
                    $query->select('id', 'name'); // Especifica los campos necesarios
                }])
                ->where('branch_id', $branch->id)
                ->whereHas('professional.charge', function ($query) {
                    $query->whereIn('name', ['Coordinador', 'Encargado', 'Barbero y Encargado']);
                })
                ->get(['id', 'professional_id', 'branch_id']); // Especifica los campos necesarios de BranchProfessional
                // Agrupa los profesionales por su cargo
                $groupedProfessionals = $professionals->groupBy('professional.charge.name');

                // Extrae los IDs de los profesionales para cada cargo
                $encargados = $groupedProfessionals->has('Encargado') ? $groupedProfessionals->get('Encargado')->pluck('professional_id') : collect();
                $coordinadors = $groupedProfessionals->has('Coordinador') ? $groupedProfessionals->get('Coordinador')->pluck('professional_id') : collect();
                $barberoEncargados = $groupedProfessionals->has('Barbero y Encargado') ? $groupedProfessionals->get('Barbero y Encargado')->pluck('professional_id') : collect();
                $charge = $professional->charge->name;
                $charge = $charge == 'Tecnico' ? 'Técnico' : $charge;               
                    $tittle = 'Solicitud de rechazo';
                    $description = 'EL profesional'.' '.$professional->name.' '.'está rechazando a'.' '.$client->name;
                if (!$encargados->isEmpty()) {
                    foreach ($encargados as $encargado) {
                        $notification = new Notification();
                        $notification->professional_id = $encargado;
                        $notification->tittle = $tittle;
                        $notification->description = $description;
                        $notification->type = 'Encargado';
                        $notification->stateApk = 'reservacion'.$reservation_id;
                        $branch->notifications()->save($notification);
                    }
                }
                if (!$coordinadors->isEmpty()) {
                    foreach ($coordinadors as $coordinador) {
                        $notification = new Notification();
                        $notification->professional_id = $coordinador;
                        $notification->tittle = $tittle;
                        $notification->description = $description;
                        $notification->type = 'Coordinador';
                        $notification->stateApk = 'reservacion'.$reservation_id;
                        $branch->notifications()->save($notification);
                    }
                }
                if (!$barberoEncargados->isEmpty()) {
                    foreach ($barberoEncargados as $barberoEncargado) {
                        $notification = new Notification();
                        $notification->professional_id = $barberoEncargado;
                        $notification->tittle = $tittle;
                        $notification->description = $description;
                        $notification->type = 'Encargado';
                        $notification->stateApk = 'reservacion'.$reservation_id;
                        $branch->notifications()->save($notification);
                    }
                }
        }//if 3
        if ($attended == 4) {
            $tail->timeThecnical = now();
            Notification::where('branch_id', $reservationNoti->branch_id)->where('state', 0)->where('stateApk', 'reservacion'.$reservation_id)->update(['state' => 1]);
        }
        if ($attended == 0 || $attended == 11) {
        Notification::where('branch_id', $reservationNoti->branch_id)->where('state', 0)->where('stateApk', 'reservacion'.$reservation_id)->update(['state' => 1]);
        }
        $tail->attended = $attended;
        $tail->save();
        DB::commit();
    } catch (Exception $e) {
        DB::rollback();
        // Manejo de la excepción en el servicio, puedes lanzar una excepción personalizada
        throw new \RuntimeException("Error al ejecutar el ProfessionalService(branch_professionals_service): " . $e->getMessage());
    }

    }

    public function tail_attended_client($reservation_id, $attended, $data)
    {
        DB::beginTransaction();
        try{
        $tecnicoId = 0;
        $reservationNoti = Reservation::where('id', $reservation_id)->first();
        $tail = Tail::where('reservation_id', $reservation_id)->first();
        if ($attended == 1) {
            $current_date = Carbon::now()->format('H:i:s');
            $tail->aleatorie = 0;
            $reservation = Reservation::findOrFail($reservation_id);
            $car = $reservation->car;
            if ($car->select_professional == 0) {
                $branchProfessional = BranchProfessional::where('professional_id', $car->clientProfessional->professional_id)
                                                ->where('branch_id', $reservation->branch_id)
                                                ->first();
                                                
                    if ($branchProfessional) {
                        $branchProfessional->numberRandom += 1;
                        $branchProfessional->save();
                    }
            }
            $reservation->start_time = $current_date;  
            $total_time = $reservation->total_time; // Ejemplo: '00:10:00'

            // Convertimos $current_date y $total_time a instancias de Carbon
            $current_time = Carbon::createFromFormat('H:i:s', $current_date);
            $total_time_carbon = Carbon::createFromFormat('H:i:s', $total_time);

            // Sumamos el tiempo total a la hora actual
            $reservation->final_hour = $current_time->addHours($total_time_carbon->hour)
                                                ->addMinutes($total_time_carbon->minute)
                                                ->addSeconds($total_time_carbon->second)
                                                ->format('H:i:s');
          
            //$reservation->final_hour = date('H:i:s', strtotime($current_date) + strtotime($reservation->total_time));            
            $reservation->started_at = now();
            $reservation->save();
            if ($data['timeClock'] !== null) {
                if ($tail) {
                    $tail->timeClock = $data['timeClock'];
                    $tail->detached = $data['detached'];
                    $tail->clock = $data['clock'];
                }
            }
        }//if 1
        if ($attended == 2) {
            $reservation = Reservation::findOrFail($reservation_id);
            $reservation->finished_at = now();
            $reservation->confirmation = 2;
            $reservation->save();
        }//if 2
        if ($attended == 5) {
            $car = Car::whereHas('reservation', function ($query) use ($reservation_id) {
                $query->where('id', $reservation_id);
            })->first();
            Log::info('$car->id');
            Log::info($car->id);
            $professional = ClientProfessional::whereHas('cars', function ($query) use ($car) {
                $query->where('id', $car->id);
            })->first()->professional_id;
            Log::info('$professional->id');
            Log::info($professional);
            $workplaceId = ProfessionalWorkPlace::where('professional_id', $professional)->whereDate('data', Carbon::now())->whereHas('workplace', function ($query) {
                $query->where('busy', 1)->where('select', 1);
            })->first();
            $workplacetecnicos = ProfessionalWorkplace::where('data', Carbon::today())->whereHas('professional.charge', function ($query) {
                $query->where('name', 'Tecnico');
            })->orderByDesc('data')
                //->whereJsonContains('places', (int)$workplaceId->workplace_id)
                ->get();
            if ($workplacetecnicos) {
                foreach ($workplacetecnicos as $workplacetecnico) {
                    $places = json_decode($workplacetecnico->places, true);
                    if (in_array($workplaceId->workplace_id, $places)) {
                        $tecnicoId = $workplacetecnico->professional_id;
                        //$professional = $workplacetecnico->professional;
                        break;
                    }
                }
                $car->technical_assistance = $car->technical_assistance + 1;
                $car->tecnico_id = $tecnicoId;
                $car->save();
            }
        }//if 5
        if ($attended == 3) {
            $reservation = Reservation::findOrFail($reservation_id);
            $car = $reservation->car;
            $clientProfessional = $car->clientProfessional;
            $professional = $clientProfessional->professional;
            $client = $clientProfessional->client;
            $branch = Branch::find($reservation->branch_id);
                $professionals = BranchProfessional::with(['professional' => function($query) {
                    $query->select('id', 'charge_id'); // Especifica los campos necesarios
                }, 'professional.charge' => function($query) {
                    $query->select('id', 'name'); // Especifica los campos necesarios
                }])
                ->where('branch_id', $branch->id)
                ->whereHas('professional.charge', function ($query) {
                    $query->whereIn('name', ['Coordinador', 'Encargado', 'Barbero y Encargado']);
                })
                ->get(['id', 'professional_id', 'branch_id']); // Especifica los campos necesarios de BranchProfessional
                // Agrupa los profesionales por su cargo
                $groupedProfessionals = $professionals->groupBy('professional.charge.name');

                // Extrae los IDs de los profesionales para cada cargo
                $encargados = $groupedProfessionals->has('Encargado') ? $groupedProfessionals->get('Encargado')->pluck('professional_id') : collect();
                $coordinadors = $groupedProfessionals->has('Coordinador') ? $groupedProfessionals->get('Coordinador')->pluck('professional_id') : collect();
                $barberoEncargados = $groupedProfessionals->has('Barbero y Encargado') ? $groupedProfessionals->get('Barbero y Encargado')->pluck('professional_id') : collect();
                $charge = $professional->charge->name;
                $charge = $charge == 'Tecnico' ? 'Técnico' : $charge;               
                    $tittle = 'Solicitud de rechazo';
                    $description = 'EL profesional'.' '.$professional->name.' '.'está rechazando a'.' '.$client->name;
                if (!$encargados->isEmpty()) {
                    foreach ($encargados as $encargado) {
                        $notification = new Notification();
                        $notification->professional_id = $encargado;
                        $notification->tittle = $tittle;
                        $notification->description = $description;
                        $notification->type = 'Encargado';
                        $notification->stateApk = 'reservacion'.$reservation_id;
                        $branch->notifications()->save($notification);
                    }
                }
                if (!$coordinadors->isEmpty()) {
                    foreach ($coordinadors as $coordinador) {
                        $notification = new Notification();
                        $notification->professional_id = $coordinador;
                        $notification->tittle = $tittle;
                        $notification->description = $description;
                        $notification->type = 'Coordinador';
                        $notification->stateApk = 'reservacion'.$reservation_id;
                        $branch->notifications()->save($notification);
                    }
                }
                if (!$barberoEncargados->isEmpty()) {
                    foreach ($barberoEncargados as $barberoEncargado) {
                        $notification = new Notification();
                        $notification->professional_id = $barberoEncargado;
                        $notification->tittle = $tittle;
                        $notification->description = $description;
                        $notification->type = 'Encargado';
                        $notification->stateApk = 'reservacion'.$reservation_id;
                        $branch->notifications()->save($notification);
                    }
                }
        }//if 3
        if ($attended == 4) {
            $tail->timeThecnical = now();
            Notification::where('branch_id', $reservationNoti->branch_id)->where('state', 0)->where('stateApk', 'reservacion'.$reservation_id)->update(['state' => 1]);
        }
        if ($attended == 0 || $attended == 11) {
        Notification::where('branch_id', $reservationNoti->branch_id)->where('state', 0)->where('stateApk', 'reservacion'.$reservation_id)->update(['state' => 1]);
        }
        $tail->attended = $attended;
        $tail->save();
        DB::commit();
    } catch (Exception $e) {
        DB::rollback();
        // Manejo de la excepción en el servicio, puedes lanzar una excepción personalizada
        throw new \RuntimeException("Error al ejecutar el ProfessionalService(branch_professionals_service): " . $e->getMessage());
    }

    }
    

    public function type_of_service_ANTERIOR($branch_id, $professional_id)
    {
        try {
            Log::info('Entrando a type_of_service');
        $tails = Tail::with(['reservation' => function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        }])->whereHas('reservation.car.clientProfessional', function ($query) use ($professional_id) {
            $query->where('professional_id', $professional_id);
        })->whereIn('attended', [1, 11, 111, 33, 4, 5])->get();

        //primera condicion es ver si es vacio
        if ($tails->isEmpty()) {
            Log::info('type_of_service - > Vacia');
            return true;
        } else
        if (count($tails) > 3) { //Solo se pueden atender maximo 4 clientes
            Log::info('type_of_service - > $tails>3');
            return false;
        } else {
            Log::info('type_of_service - > No está Vacia y no hay mas de 4 atendiendose');
            foreach ($tails as $tail) {
                // Verifica si car no es null
                if ($tail->reservation->car !== null) {
                    $cantServ = 0;
                    foreach ($tail->reservation->car->orders->where('is_product', 0) as $orderData) {
                        $cantServ++;
                        if ($orderData->branchServiceProfessional->branchService->service->simultaneou == 1) {
                            Log::info('type_of_service - > foreach -> hay un servicio simultaneo : ' . $orderData->branchServiceProfessional->branchService->service->name);
                            return true;
                        }
                    }
                    Log::info('type_of_service - > foreach -> cat servicios = ' . $cantServ);
                }
            }
            //sini llega aca es que no hay servicios simultaneos
            return false;
        }
        } catch (Exception $e) {
            // Manejo de la excepción en el servicio, puedes lanzar una excepción personalizada
            throw new \RuntimeException("Error al ejecutar el servicio: " . $e->getMessage());
        }

    }
    
     public function type_of_service($branch_id, $professional_id)
        {
            try {
                Log::info('Entrando a type_of_service');
                // Filtramos las colas por la fecha actual y el estado
                $today = Carbon::today();
                
                $tails = Tail::with(['reservation.car.orders.branchServiceProfessional.branchService'])
                    ->whereHas('reservation', function ($query) use ($branch_id, $today) {
                        $query->where('branch_id', $branch_id)
                            ->whereDate('data', $today);  // Filtrar por la fecha actual
                    })
                    ->whereHas('reservation.car.clientProfessional', function ($query) use ($professional_id) {
                        $query->where('professional_id', $professional_id);
                    })
                    ->whereIn('attended', [1, 11, 111, 33, 4, 5])
                    ->take(4)  // Solo necesitamos verificar hasta 4 colas
                    ->get();

                    // Primera condición: si está vacío
                    if ($tails->isEmpty()) {
                        Log::info('type_of_service - > Vacía');
                        return true;
                    }

                    // Si hay más de 3, no se pueden atender más de 4 clientes
                    if ($tails->count() > 3) {
                        Log::info('type_of_service - > Más de 3 colas activas');
                        return false;
                    }

            /* //primera condicion es ver si es vacio
                if ($tails->isEmpty()) {
                    Log::info('type_of_service - > Vacia');
                    return true;
                } else
                if (count($tails) > 3) { //Solo se pueden atender maximo 4 clientes
                    Log::info('type_of_service - > $tails>3');
                    return false;
                } else {*/
                    Log::info('type_of_service - > No está Vacia y no hay mas de 4 atendiendose');
                    /*foreach ($tails as $tail) {
                        // Verifica si car no es null
                        if ($tail->reservation->car !== null) {
                            $cantServ = 0;
                            foreach ($tail->reservation->car->orders->where('is_product', 0) as $orderData) {
                                $cantServ++;
                                if ($orderData->branchServiceProfessional->branchService->service->simultaneou == 1) {
                                    Log::info('type_of_service - > foreach -> hay un servicio simultaneo : ' . $orderData->branchServiceProfessional->branchService->service->name);
                                    return true;
                                }
                            }
                            Log::info('type_of_service - > foreach -> cat servicios = ' . $cantServ);
                        }
                    }
                    //sini llega aca es que no hay servicios simultaneos
                    return false;*/
                    foreach ($tails as $tail) {
                        $car = $tail->reservation->car;
            
                        // Verificamos si el carro tiene servicios
                        if ($car !== null && $car->orders->isNotEmpty()) {
                            foreach ($car->orders->where('is_product', 0) as $orderData) {
                                $service = $orderData->branchServiceProfessional->branchService->service;
            
                                // Si hay un servicio simultáneo, devolvemos true
                                if ($service->simultaneou == 1) {
                                    Log::info('type_of_service - > Servicio simultáneo detectado');
                                    return true;
                                }
                            }
                        }
                    }
            
                    // Si no hay servicios simultáneos
                    Log::info('type_of_service - > No hay servicios simultáneos');
                    return false;
                //}
                } catch (Exception $e) {
                    // Manejo de la excepción en el servicio, puedes lanzar una excepción personalizada
                    throw new \RuntimeException("Error al ejecutar el servicio: " . $e->getMessage());
                }

        }

    public function cola_branch_capilar($branch_id)
    {
        $tails = Tail::with(['reservation' => function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        }])->orderBy('updated_at')->whereIn('attended', [4, 5])->get();
        $branchTails = $tails->map(function ($tail) {
            $client = $tail->reservation->car->clientProfessional->client;
            $professional = $tail->reservation->car->clientProfessional->professional;
            $reservation = $tail->reservation;
            return [
                'reservation_id' => $reservation->id,
                'car_id' => $reservation->car_id,
                'start_time' => Carbon::parse($reservation->start_time)->format('H:i'),
                'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i'),
                'total_time' => $reservation->total_time,
                'client_name' => $client->name . " " . $client->surname,
                'professional_name' => $professional->name . " " . $professional->surname,
                'client_id' => $client->id,
                'professional_id' => $professional->id,
                'professional_state' => $professional->state,
                'attended' => $tail->attended,
                'from_home' => intval($reservation->from_home),
                'select_professional' => intval($reservation->car->select_professional)
            ];
        })->values();

        return $branchTails;
    }

    public function cola_branch_tecnico($branch_id, $professional_id)
    {
        $workplace = ProfessionalWorkPlace::where('professional_id', $professional_id)->whereDate('data', Carbon::now())->where('state', 1)->orderByDesc('created_at')->first();

        if ($workplace != null) {
            $places = json_decode($workplace->places, true);
            $professionals = ProfessionalWorkPlace::whereHas('workplace', function ($query) use ($places) {
                $query->whereIn('id', $places)->where('select', 1);
            })->where('state', 1)->whereDate('data', Carbon::now())->orderByDesc('created_at')->get()->pluck('professional_id');
            //$professionals = ProfessionalWorkPlace::whereIn('workplace_id', $places)->whereDate('data', Carbon::now())->orderByDesc('created_at')->first();
            $tails = Tail::whereHas('reservation', function ($query) use ($branch_id, $professionals) {
                $query->where('branch_id', $branch_id)->whereHas('car.clientProfessional', function ($query) use ($professionals) {
                    $query->whereIn('professional_id', $professionals);
                });
            })->orderBy('timeThecnical')->whereIn('attended', [4, 5, 33])->get()->map(function ($tail) {
                $client = $tail->reservation->car->clientProfessional->client;
                $professional = $tail->reservation->car->clientProfessional->professional;
                $reservation = $tail->reservation;
                return [
                    'reservation_id' => $reservation->id,
                    'car_id' => $reservation->car_id,
                    'start_time' => Carbon::parse($reservation->start_time)->format('H:i'),
                    'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i'),
                    'total_time' => $reservation->total_time,
                    'client_name' => $client->name . " " . $client->surname,
                    'professional_name' => $professional->name . " " . $professional->surname,
                    'client_id' => $client->id,
                    'client_image' => $client->client_image ? $client->client_image : "comments/default_profile.jpg",
                    'professional_id' => $professional->id,
                    'professional_state' => $professional->state,
                    'attended' => $tail->attended,
                    'from_home' => intval($reservation->from_home),
                    'select_professional' => intval($reservation->car->select_professional)
                ];
            })->values();

            return $tails;
        } else {
            // Manejar caso donde no se encuentra el lugar de trabajo
            return $tail = [];
        }
    }

    /*public function reasigned_client($data){
        $client = Client::find($data['client_id']);
        Log::info($client);
        $professional = Professional::find($data['professional_id']);
        Log::info($professional);
        $reservation = Reservation::find($data['reservation_id']);
        Log::info($reservation);
        $horaActual = Carbon::now();
            $tiempoReserva = $reservation->total_time;
            $reservations = $professional->reservations()
            ->where('branch_id', $reservation->branch_id)
            ->whereIn('confirmation', [1, 4])
            ->whereDate('data', Carbon::now())
            ->orderBy('start_time')
            ->get();
        if ($reservations->isEmpty()) {
            Log::info('No tiene reservas reasigned coordinador');
            list($horasReserva, $minutosReserva, $segundosReserva) = explode(':', $tiempoReserva);

            $reservation->start_time = $horaActual->format('H:i:s');
            // Sumar el tiempo de la reserva a la hora actual
            $nuevaHora = $horaActual->copy()->addHours($horasReserva)->addMinutes($minutosReserva)->addSeconds($segundosReserva);

            $reservation->final_hour = $nuevaHora->format('H:i:s');
            $reservation->save();
        } else {
            Log::info('Tiene reservas reasigned coordinador');
            $encontrado = false;
            $nuevaHoraInicio = $horaActual;
            
            $total_timeMin = $this->convertirHoraAMinutos($tiempoReserva);
            // Recorrer las reservas existentes para encontrar un intervalo de tiempo libre
            foreach ($reservations as $reservation1) {
                Log::info('entra al ciclo de las reservas reasigned coordinador');
                $start_time = Carbon::parse($reservation1->start_time);
                $final_hour = Carbon::parse($reservation1->final_hour);
                //return $reservation1;
                $start_timeMin = $this->convertirHoraAMinutos($reservation1->start_time);
                $final_hourMin = $this->convertirHoraAMinutos($reservation1->final_hour);
                $nuevaHoraInicioMin = $this->convertirHoraAMinutos($nuevaHoraInicio->format('H:i'));
                
                if (($nuevaHoraInicioMin + $total_timeMin) <= $start_timeMin) {
                    Log::info('Entra que es menor que la reserva reasigned coordinador');
                   $encontrado = true;
                   break;
                }

                $nuevaHoraInicio = $final_hour;
            }

            if (!$encontrado) {
                // Si no se encontró un intervalo libre, usar el final de la última reserva
                $nuevaHoraInicio = Carbon::parse($reservations->last()->final_hour);
            }

            list($horasReserva, $minutosReserva, $segundosReserva) = explode(':', $tiempoReserva);

            $reservation->start_time = $nuevaHoraInicio->format('H:i:s');
            // Sumar el tiempo de la reserva a la hora actual
            $nuevaHoraFinal = $nuevaHoraInicio->copy()->addHours($horasReserva)->addMinutes($minutosReserva)->addSeconds($segundosReserva);
            // Guardar la nueva reserva
            $reservation->final_hour = $nuevaHoraFinal->format('H:i:s');
            $reservation->save();
        }
                    $car = Car::find($reservation->car_id);
                    
        Log::info($car);
        $servicesOrders = Order::where('car_id', $car->id)->where('is_product', 0)->get();
        $service_professionals = BranchServiceProfessional::whereHas('branchService', function ($query) use ($reservation){
            $query->where('branch_id', $reservation->branch_id);
        })->where('professional_id', $data['professional_id'])->get();
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
        $tail = $reservation->tail;
        if ($tail) {
            if ($tail->aleatorie != 0) {
                $tail->aleatorie = 1;
                $tail->save();
            }
        }
        $car->client_professional_id = $client_professional_id;
        $car->save();

        foreach ($servicesOrders as $service) {
            foreach ($service_professionals as $service_professional) {
                $serv = $service->branchServiceProfessional->branchService->service;
                if ($service->branchServiceProfessional->branchService->service->id == $service_professional->branchService->service->id) {
                    $percent = $service_professional->percent ? $service_professional->percent : 1;
                    $order = new Order();
                    $order->car_id = $service->car_id;
                    $order->product_store_id = null;
                    $order->branch_service_professional_id = $service_professional->id;
                    $order->data = $service->data;
                    $order->is_product = false;
                    //logica de porciento de ganancia
                    $order->percent_win = $serv->price_service * $percent/100;
                    $order->price = $serv->price_service;
                    $order->request_delete = false;
                    $order->save();
                    $service->delete();
                }
            }
            
        } 
        
    }*/

    public function reasigned_clientOld_ANTERIOR($data)
    {
        try {
            DB::beginTransaction();

            Log::info("Reasignar Cliente");

            $client = Client::findOrFail($data['client_id']);
            Log::info($client);

            $professional = Professional::findOrFail($data['professional_id']);
            Log::info($professional);

            $reservation = Reservation::findOrFail($data['reservation_id']);
            Log::info($reservation);

            $horaActual = Carbon::now();
            $tiempoReserva = $reservation->total_time;

            $reservations = $professional->reservations()
                ->where('branch_id', $reservation->branch_id)
                ->where('confirmation', 4)
                ->whereHas('car.clientProfessional', function ($query) use ($data) {
                    $query->where('professional_id', $data['professional_id']);
                })
                ->whereHas('tail', function ($query) use ($data) {
                    $query->whereNot('aleatorie', 1);
                })
                ->whereDate('data', Carbon::now())
                ->orderByDesc('start_time')
                ->get();

            if ($reservations->isEmpty()) {
                Log::info('No tiene reservas reasigned coordinador');
                $this->actualizarReserva($reservation, $horaActual, $tiempoReserva);
            } else {
                Log::info('Tiene reservas reasigned coordinador');
                $nuevaHoraInicio = $this->encontrarIntervaloLibreOld($reservations, $horaActual, $tiempoReserva, $reservation);
                $this->actualizarReserva($reservation, $nuevaHoraInicio, $tiempoReserva);
            }

            $car = Car::findOrFail($reservation->car_id);
            Log::info($car);

            $servicesOrders = Order::where('car_id', $car->id)->where('is_product', 0)->distinct('id')->get();

            /*$service_professionals = BranchServiceProfessional::whereHas('branchService', function ($query) use ($reservation) {
                $query->where('branch_id', $reservation->branch_id);
            })->where('professional_id', $data['professional_id'])->get();*/
            $service_professionals = BranchServiceProfessional::whereHas('branchService', function ($query) use ($reservation) {
                $query->where('branch_id', $reservation->branch_id);
            })->where('professional_id', $data['professional_id'])
            ->distinct('branch_service_id') // Asegura que los resultados sean únicos
            ->get();

            $client_professional = $professional->clients()->where('client_id', $client->id)->withPivot('id')->first();

            if (!$client_professional) {
                Log::info("Cliente profesional no existe");
                $professional->clients()->attach($client->id);
                $client_professional_id = $professional->clients()->wherePivot('client_id', $client->id)->withPivot('id')->get()->map->pivot->value('id');
            } else {
                $client_professional_id = $client_professional->pivot->id;
            }
            Log::info('Relacion Cliente Professional'.$client_professional_id);

           /* $tail = $reservation->tail;
            if ($tail && $tail->aleatorie != 0) {
                $tail->aleatorie = 2;
                $tail->save();
            }*/

            $car->client_professional_id = $client_professional_id;
            $car->save();
            $reservationsComp = $professional->reservations()
                ->where('branch_id', $reservation->branch_id)
                ->where('confirmation', 4)
                ->whereDate('data', Carbon::now())
                ->whereHas('tail', function ($query) {
                    $query->whereNot('aleatorie', 1);
                })
                ->orderBy('start_time')
                ->first();
            $tail = $reservation->tail;
            if($reservationsComp != NULL && $reservationsComp->id == $reservation->id){
                $reservation->timeClock = now();
                $reservation->save();
            }
           if ($tail && $tail->aleatorie != 0) {
                //if ($reservationsComp != NULL && $reservationsComp->id == $reservation->id) {
                    $tail->aleatorie = 2;
                //}else {
                    //$tail->aleatorie = 1;
                //}
            }
                $tail->attended = 0;
                $tail->save();

            $this->reassignServices($servicesOrders, $service_professionals);
                $notification = new Notification();
                $notification->professional_id = $professional->id;
                $notification->branch_id = $reservation->branch_id;
                $notification->tittle = 'Nuevo cliente en cola';
                $notification->description = 'Tienes un nuevo cliente en cola';
                $notification->type = 'Barbero';
                $notification->save();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => 'Error interno del sistema'], 500);
        }
    }
    
     public function reasigned_clientOld($data)
        {
            try {
                DB::beginTransaction();

                Log::info("Reasignar Cliente Coordinador");

                $client = Client::findOrFail($data['client_id']);
                Log::info($client);

                $professional = Professional::findOrFail($data['professional_id']);
                Log::info($professional);

                $reservation = Reservation::findOrFail($data['reservation_id']);
                Log::info($reservation);

                $horaActual = Carbon::now();
                $tiempoReserva = $reservation->total_time;

                $reservations = $professional->reservations()
                    ->where('branch_id', $reservation->branch_id)
                    ->where('confirmation', 4)
                    ->whereHas('car.clientProfessional', function ($query) use ($data) {
                        $query->where('professional_id', $data['professional_id']);
                    })
                    ->whereHas('tail', function ($query) use ($data) {
                        $query->whereNot('aleatorie', 1);
                    })
                    ->whereDate('data', Carbon::now())
                    ->orderByDesc('start_time')
                    ->get();

                if ($reservations->isEmpty()) {
                    Log::info('No tiene reservas reasigned coordinador');
                    $this->actualizarReserva($reservation, $horaActual, $tiempoReserva);
                } else {
                    Log::info('Tiene reservas reasigned coordinador');
                    $nuevaHoraInicio = $this->encontrarIntervaloLibreOld($reservations, $horaActual, $tiempoReserva, $reservation);
                    $this->actualizarReserva($reservation, $nuevaHoraInicio, $tiempoReserva);
                }

                $car = Car::findOrFail($reservation->car_id);
                Log::info($car);

                $servicesOrders = Order::where('car_id', $car->id)->where('is_product', 0)->distinct('id')->get();

                /*$service_professionals = BranchServiceProfessional::whereHas('branchService', function ($query) use ($reservation) {
                    $query->where('branch_id', $reservation->branch_id);
                })->where('professional_id', $data['professional_id'])->get();*/
                $service_professionals = BranchServiceProfessional::whereHas('branchService', function ($query) use ($reservation) {
                    $query->where('branch_id', $reservation->branch_id);
                })->where('professional_id', $data['professional_id'])
                ->distinct('branch_service_id') // Asegura que los resultados sean únicos
                ->get();

                $client_professional = $professional->clients()->where('client_id', $client->id)->withPivot('id')->first();

                if (!$client_professional) {
                    Log::info("Cliente profesional no existe");
                    $professional->clients()->attach($client->id);
                    $client_professional_id = $professional->clients()->wherePivot('client_id', $client->id)->withPivot('id')->get()->map->pivot->value('id');
                } else {
                    $client_professional_id = $client_professional->pivot->id;
                }
                Log::info('Relacion Cliente Professional'.$client_professional_id);

            /* $tail = $reservation->tail;
                if ($tail && $tail->aleatorie != 0) {
                    $tail->aleatorie = 2;
                    $tail->save();
                }*/

                $car->client_professional_id = $client_professional_id;
                $car->save();
                $reservationsComp = $professional->reservations()
                    ->where('branch_id', $reservation->branch_id)
                    ->where('confirmation', 4)
                    ->whereDate('data', Carbon::now())
                    ->whereHas('tail', function ($query) {
                        $query->whereNot('aleatorie', 1);
                    })
                    ->orderBy('start_time')
                    ->first();
                $tail = $reservation->tail;
                if($reservationsComp != NULL && $reservationsComp->id == $reservation->id){
                    $reservation->timeClock = now();
                    $reservation->save();
                }else{
                    $reservation->timeClock = NULL;
                    $reservation->save(); 
                }
                if ($tail && $tail->aleatorie != 0) {
                    //if ($reservationsComp != NULL && $reservationsComp->id == $reservation->id) {
                        $tail->aleatorie = 2;
                    //}else {
                        //$tail->aleatorie = 1;
                    //}
                }
                    $tail->attended = 0;
                    $tail->save();

                $this->reassignServices($servicesOrders, $service_professionals);
                    $notification = new Notification();
                    $notification->professional_id = $professional->id;
                    $notification->branch_id = $reservation->branch_id;
                    $notification->tittle = 'Nuevo cliente en cola';
                    $notification->description = 'Tienes un nuevo cliente en cola';
                    $notification->type = 'Barbero';
                    $notification->save();
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error($e->getMessage());
                return response()->json(['error' => 'Error interno del sistema'], 500);
            }
        }


    public function reasigned_client($data)
    {
        try {
            DB::beginTransaction();

            Log::info("Reasignar Cliente-444");

            $client = Client::findOrFail($data['client_id']);
            Log::info($client);

            $professional = Professional::findOrFail($data['professional_id']);
            Log::info($professional);

            $reservation = Reservation::findOrFail($data['reservation_id']);
            Log::info($reservation);

            $horaActual = Carbon::now();
            $tiempoReserva = $reservation->total_time;

            $reservations = $professional->reservations()
                ->where('branch_id', $reservation->branch_id)
                ->where('confirmation', 4)
                ->whereDate('data', Carbon::now())
                ->orderBy('start_time')
                ->get();

            if ($reservations->isEmpty()) {
                Log::info('No tiene reservas reasigned coordinador');
                $this->actualizarReserva($reservation, $horaActual, $tiempoReserva);
            } else {
                Log::info('Tiene reservas reasigned coordinador');
                $nuevaHoraInicio = $this->encontrarIntervaloLibre($reservations, $horaActual, $tiempoReserva);
                $this->actualizarReserva($reservation, $nuevaHoraInicio, $tiempoReserva);
            }

            $car = Car::findOrFail($reservation->car_id);
            Log::info($car);

            $servicesOrders = Order::where('car_id', $car->id)->where('is_product', 0)->distinct('id')->get();

            $service_professionals = BranchServiceProfessional::whereHas('branchService', function ($query) use ($reservation) {
                $query->where('branch_id', $reservation->branch_id);
            })->where('professional_id', $data['professional_id'])
            ->distinct('branch_service_id') // Asegura que los resultados sean únicos
            ->get();

            $client_professional = $professional->clients()->where('client_id', $client->id)->withPivot('id')->first();

            if (!$client_professional) {
                Log::info("Cliente profesional no existe");
                $professional->clients()->attach($client->id);
                $client_professional_id = $professional->clients()->wherePivot('client_id', $client->id)->withPivot('id')->get()->map->pivot->value('id');
            } else {
                $client_professional_id = $client_professional->pivot->id;
            }
            Log::info($client_professional_id);

            $tail = $reservation->tail;
            
            
            if ($tail->attended == 3) {
                $ProfessOld = $car->clientProfessional->professional_id;
                $tempProfessional = Professional::where('id', $ProfessOld)->first();                
                Log::info('Cliente Reasignado por el sistema al estar en solicitud de rechazo del professional');
                Log::info($tempProfessional->name);
                $notification = new Notification();
                $notification->professional_id = $tempProfessional->id;
                $notification->branch_id = $reservation->branch_id;
                $notification->tittle = 'Aceptada Eliminación de Cliente';
                $notification->description = 'El cliente fue reasignado por el sistema';
                $notification->type = 'Barbero';
                $notification->state = 3;
                $notification->save();
            }
            
            
            if ($tail && $tail->aleatorie != 0) {
                $tail->aleatorie = 2;
            }
            $tail->attended = 0;
            $tail->save();

            $car->client_professional_id = $client_professional_id;
            $car->save();

            $this->reassignServices($servicesOrders, $service_professionals);
                $notification = new Notification();
                $notification->professional_id = $professional->id;
                $notification->branch_id = $reservation->branch_id;
                $notification->tittle = 'Nuevo cliente en cola';
                $notification->description = 'Tienes un nuevo cliente en cola';
                $notification->type = 'Barbero';
                $notification->save();
                
                
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return response()->json(['error' => 'Error interno del sistema'], 500);
        }
    }

    private function actualizarReserva($reservation, $horaInicio, $tiempoReserva)
    {
        list($horasReserva, $minutosReserva, $segundosReserva) = explode(':', $tiempoReserva);
        $nuevaHoraFinal = $horaInicio->copy()->addHours($horasReserva)->addMinutes($minutosReserva)->addSeconds($segundosReserva);

        $reservation->start_time = $horaInicio->format('H:i:s');
        $reservation->final_hour = $nuevaHoraFinal->format('H:i:s');
        $reservation->save();
    }

    private function encontrarIntervaloLibreOld($reservations, $horaActual, $tiempoReserva, $reservation)
    {
        Log::info('Revisando reservas coordinador');
        $encontrado = false;
        $nuevaHoraInicio = $horaActual;
        $total_timeMin = $this->convertirHoraAMinutos($tiempoReserva);

        foreach ($reservations as $index => $reservation1) {
            $car = $reservation1->car;
            if ($reservation1->from_home == 1 && $reservation1->confirmation == 4) {
                $nuevaHoraInicio = Carbon::parse($reservation1->final_hour);
                $encontrado = true;
                break;
            }
            elseif ($reservation1->from_home == 1 && $reservation1->confirmation == 1) {
                $start_timeMin = $this->convertirHoraAMinutos($reservation1->start_time);
                $nuevaHoraInicioMin = $this->convertirHoraAMinutos($nuevaHoraInicio->format('H:i'));
                if (isset($reservations[$index + 1])) {
                    $nextReservation = $reservations[$index + 1];
                    $final_hour_next = Carbon::parse($nextReservation->final_hour);
                    $final_hour_nextMin = $this->convertirHoraAMinutos($final_hour_next->format('H:i'));
    
                    if (($final_hour_nextMin + $total_timeMin) <= $start_timeMin) {
                        Log::info('Se coloca antes del reservadobh no ha confirmado');
                        $nuevaHoraInicio = $final_hour_next;
                        $encontrado = true;
                        break;
                    }else {
                        //if (($nuevaHoraInicioMin + $total_timeMin) <= $start_timeMin) {
                            Log::info('Ne se coloca antes del reservadobh no ha confirmado porque no tiene tiempo');
                            $nuevaHoraInicio = Carbon::parse($reservation1->final_hour);
                            $encontrado = true;
                            break;
                        //}
                    }
                }else {
                    if (($nuevaHoraInicioMin + $total_timeMin) <= $start_timeMin) {
                        Log::info('No tiene mas reservas y cabe antes de la bh no comfirmada hora de inicio hora actual');
                        $encontrado = true;
                        break;
                    }else {
                        Log::info('Ne se coloca antes del reservadobh no ha confirmado porque no tiene tiempo hora de inicio finalizacion de la bh');
                        $nuevaHoraInicio = Carbon::parse($reservation1->final_hour);
                            $encontrado = true;
                            break;
                    }
                } 
            }elseif ($reservation1->from_home == 0 && $car->select_professional == 1) {
                $nuevaHoraInicio = Carbon::parse($reservation1->final_hour);
                $encontrado = true;
                break;
            }elseif ($car->select_professional == 0) {
                if ($reservation1->created_at < $reservation->created_at) {
                    $nuevaHoraInicio = Carbon::parse($reservation1->final_hour);
                    $encontrado = true;
                    break;
                }
            }
        }

        if (!$encontrado) {
            $nuevaHoraInicio = Carbon::parse($reservations->last()->final_hour);
        }

        return $nuevaHoraInicio;
    }

    private function encontrarIntervaloLibre($reservations, $horaActual, $tiempoReserva)
    {
        $encontrado = false;
        $nuevaHoraInicio = $horaActual;
        $total_timeMin = $this->convertirHoraAMinutos($tiempoReserva);

        foreach ($reservations as $reservation1) {
            Log::info('Revisando reservas coordinador');
            $start_timeMin = $this->convertirHoraAMinutos($reservation1->start_time);
            $final_hourMin = $this->convertirHoraAMinutos($reservation1->final_hour);
            $nuevaHoraInicioMin = $this->convertirHoraAMinutos($nuevaHoraInicio->format('H:i'));

            if (($nuevaHoraInicioMin + $total_timeMin) <= $start_timeMin) {
                $encontrado = true;
                break;
            }
            $nuevaHoraInicio = Carbon::parse($reservation1->final_hour);
        }

        if (!$encontrado) {
            $nuevaHoraInicio = Carbon::parse($reservations->last()->final_hour);
        }

        return $nuevaHoraInicio;
    }

    private function verific_aleatorie_ANTERIOR($branch_id, $professional)
    {
        try{
        //ver si hay aleatorios antes de algun cliente seleccionado
        //$professional = Professional::find($professional_id);
        $currentDateTime = Carbon::now()->format('H:i:s');
        $currentDate = Carbon::now();
        $reservations = $professional->reservations()
            ->where('branch_id', $branch_id)
            ->where('confirmation', 4)
            ->whereDate('data', $currentDate)
            ->where(function ($query) use ($currentDateTime) {
                $query->whereHas('tail', function ($subquery) {//Está atendiendo cliente
                    $subquery->where('aleatorie', '!=', 1)
                            ->whereIn('attended', [1, 11, 111, 4, 5, 33]);
                })/*
                ->orWhere(function ($query) use ($currentDateTime) {//No se esta atendiendo pero tiene una reserva menor que la hora actual
                    $query->where('start_time', '<', $currentDateTime)
                          ->whereHas('tail', function ($subquery) {
                            $subquery->where('aleatorie', '!=', 1)
                              ->whereNotIn('attended', [1, 11, 111, 4, 5, 33, 2]);
                          });
                })*/;
            })
            ->get();
            Log::info('$reservations de que esta ocupado');
            Log::info($reservations);
            $now = Carbon::now();
            $currentTime = $now->format('H:i:s');
        if ($reservations->isEmpty()) {//esta libre
            $reservationsTail = $professional->reservations()
            ->where('branch_id', $branch_id)
            ->where('confirmation', 4)
            ->whereDate('data', $now)
            ->whereHas('tail', function ($subquery) {
                $subquery->where('aleatorie', '!=', 1);
            })
            ->where(function ($query) use ($currentTime) {
                $query->where('confirmation', '!=', 2)
                    ->orWhere(function ($subquery) use ($currentTime) {
                        $subquery->where('confirmation', 1)
                                ->whereRaw('ADDTIME(start_time, "00:20:00") > ?', [$currentTime]);
                    });
            })
            ->orderBy('confirmation', 'desc') // Ordenar por confirmation en orden descendente (4 primero, luego 1)
            ->orderByDesc('from_home') // Luego ordenar por from_home, 1 primero
            ->orderBy('created_at') // Finalmente, ordenar por created_at
            ->first();
            /*$reservationsTail = $professional->reservations()
                ->where('branch_id', $branch_id)
                ->whereIn('confirmation', [1, 4])
                ->whereDate('data', Carbon::now())
                ->whereHas('tail', function ($subquery) {
                    $subquery->where('aleatorie', '!=', 1);
                })->where(function ($query) {
                    $query->where('confirmation', '!=', 1)
                    ->where('confirmation', '!=', 2) // Excluir explicitamente confirmation 2
                          ->orWhere(function ($subquery) {
                              $subquery->where('confirmation', 1)
                                       ->whereRaw('ADDTIME(start_time, "00:20:00") > ?', [Carbon::now()->format('H:i:s')]);
                          });
                })
                  // Ordenar por confirmation, 4 primero
                ->orderByDesc('from_home') // Ordenar por from_home, 1 primero
                ->orderBy('created_at') // Luego ordenar por start_time
                ->first();*/
                Log::info('$reservationsTail orden de las reservaciones');
                Log::info($reservationsTail);

                if($reservationsTail == NULL){//sino tiene a nadie en cola llama a los aleatorios para tomar al primero que llego
                    $tails = Tail::whereHas('reservation', function ($query) use ($branch_id) {
                        $query->where('branch_id', $branch_id)->orderBy('created_at');
                    })->where('aleatorie', 1)->get();
                    if ($tails->isNotEmpty()) {
                        $this->verific_services($tails, $branch_id, $professional);
                    }
                }
                elseif ($reservationsTail && $reservationsTail->from_home == 1 && $reservationsTail->confirmation == 1) {
                    $tails = Tail::whereHas('reservation', function ($query) use ($branch_id) {
                        $query->where('branch_id', $branch_id)->orderBy('created_at');
                    })->where('aleatorie', 1)->get();
                    $this->verific_services_bh($tails, $branch_id, $professional, $reservationsTail->start_time);
                }

            //$current_date = Carbon::now()->format('H:i:s');
            elseif($reservationsTail && $reservationsTail->from_home == 0 && $reservationsTail->car->select_professional == 1) {
                $tails = Tail::whereHas('reservation', function ($query) use ($branch_id, $reservationsTail) {
                    $query->where('branch_id', $branch_id)->where('created_at', '<', $reservationsTail->created_at)->orderBy('created_at');
                })->where('aleatorie', 1)->get();
                if ($tails->isNotEmpty()) {
                    $this->verific_services($tails, $branch_id, $professional);
                }
            }
        }
        //end ver si hay aleatorios antes de algun cliente seleccionado
    } catch (\Throwable $th) {
        throw new \RuntimeException("Error al ejecutar el TailService(verific_aleatorie): " . $th->getMessage());
    }
    }
    
      private function verific_aleatorie($branch_id, $professional)
        {
            try {
                $now = Carbon::now();
                $currentTime = $now->format('H:i:s');

                // Usar eager loading para evitar consultas redundantes
                $reservations = $professional->reservations()
                    ->with(['tail', 'car'])
                    ->where('branch_id', $branch_id)
                    ->where('confirmation', 4)
                    ->whereDate('data', $now)
                    ->where(function ($query) use ($currentTime) {
                        $query->whereHas('tail', function ($subquery) {
                            $subquery->where('aleatorie', '!=', 1)
                                    ->whereIn('attended', [1, 11, 111, 4, 5, 33]);
                        });
                    })
                    ->get();

              
                if ($reservations->isEmpty()) {
                    Log::info('$reservations vacias:');
                    // Optimizar esta consulta usando eager loading y reducir operaciones redundantes
                    $reservationsTail = $professional->reservations()
                        ->where('branch_id', $branch_id)
                        ->where('confirmation', 4)
                        ->whereDate('data', $now)
                        ->whereHas('tail', function ($subquery) {
                            $subquery->where('aleatorie', '!=', 1);
                        })
                        ->where(function ($query) use ($currentTime) {
                            $query->where('confirmation', '!=', 2)
                                ->orWhere(function ($subquery) use ($currentTime) {
                                    $subquery->where('confirmation', 1)
                                            ->whereRaw('ADDTIME(start_time, "00:20:00") > ?', [$currentTime]);
                                });
                        })
                        ->orderBy('confirmation', 'desc')
                        ->orderByDesc('from_home')
                        ->orderBy('created_at')
                        ->first();

                    Log::info('$reservationsTail orden de las reservaciones: ');
                    Log::info($reservationsTail);

                    if (!$reservationsTail) {
                        // Ejecutar consulta aleatoria si no hay reserva disponible
                        $this->handleAleatorieTails($branch_id, $professional);
                    } elseif ($reservationsTail->from_home == 1 && $reservationsTail->confirmation == 1) {
                        // Caso especial cuando la reserva es desde casa
                        $this->handleFromHomeTails($branch_id, $professional, $reservationsTail->start_time);
                    } elseif ($reservationsTail->from_home == 0 && $reservationsTail->car->select_professional == 1) {
                        // Caso para clientes que seleccionan un profesional
                        $this->handleSelectedProfessionalTails($branch_id, $reservationsTail, $professional);
                    }
                }
            } catch (\Throwable $th) {
                throw new \RuntimeException("Error al ejecutar el TailService(verific_aleatorie): " . $th->getMessage());
            }
        }
        
         private function handleAleatorieTails($branch_id, $professional)
        {
            // Optimización: consultar solo una vez y ordenar
            $tails = Tail::whereHas('reservation', function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id)
                ->whereDate('data', Carbon::now())
                    ->orderBy('created_at');
            })->where('aleatorie', 1)->get();

            if ($tails->isNotEmpty()) {
                $this->verific_services($tails, $branch_id, $professional);
            }
        }

        private function handleFromHomeTails($branch_id, $professional, $start_time)
        {
            // Optimización: evitar consultas innecesarias
            $tails = Tail::whereHas('reservation', function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id)
                ->whereDate('data', Carbon::now())
                    ->orderBy('created_at');
            })->where('aleatorie', 1)->get();

            $this->verific_services_bh($tails, $branch_id, $professional, $start_time);
        }

        private function handleSelectedProfessionalTails($branch_id, $reservationsTail, $professional)
        {
            $tails = Tail::whereHas('reservation', function ($query) use ($branch_id, $reservationsTail) {
                $query->where('branch_id', $branch_id)
                ->whereDate('data', Carbon::now())
                    ->where('created_at', '<', $reservationsTail->created_at)
                    ->orderBy('created_at');
            })->where('aleatorie', 1)->get();

            if ($tails->isNotEmpty()) {
                $this->verific_services($tails, $branch_id, $professional);
            }
        }

    private function verific_services($tails, $branch_id, $professional)
    {
       try{
        foreach ($tails as $tail) {
            $reservation = $tail->reservation;
            $tiempoReserva = $reservation->total_time;
            $car = $reservation->car;

            $servicesOrders = Order::where('car_id', $car->id)
                ->where('is_product', 0)
                ->with(['branchServiceProfessional.branchService.service'])
                ->get();

            $services_id = $servicesOrders->pluck('branchServiceProfessional.branchService.service.id')->toArray();

            $service_professionals = BranchServiceProfessional::whereHas('branchService', function ($query) use ($branch_id, $professional) {
                $query->where('branch_id', $branch_id);
            })
                ->where('professional_id', $professional->id)
                ->with('branchService.service')
                ->get();
            $service_professional_id = $service_professionals->pluck('branchService.service.id')->toArray();

            $services_id_collection = collect($services_id);
            $service_professional_id_collection = collect($service_professional_id);
            $diff = $services_id_collection->diff($service_professional_id_collection);
            if ($diff->isEmpty()) {
                Log::info('Realiza todos los servicios');

                $client = $car->clientProfessional->client;
                //$professional = Professional::find($professional_id);

                $nuevaHoraInicio = Carbon::now();
                list($horasReserva, $minutosReserva, $segundosReserva) = explode(':', $tiempoReserva);
                $reservation->start_time = $nuevaHoraInicio->format('H:i:s');
                $reservation->final_hour = $nuevaHoraInicio->copy()->addHours($horasReserva)->addMinutes($minutosReserva)->addSeconds($segundosReserva)->format('H:i:s');
                $reservation->save();

                $client_professional = $professional->clients()->where('client_id', $client->id)->withPivot('id')->first();
                if (!$client_professional) {
                    Log::info("No existe relación cliente-profesional");
                    $professional->clients()->attach($client->id);
                    $client_professional_id = $professional->clients()->wherePivot('client_id', $client->id)->withPivot('id')->get()->map->pivot->value('id');
                    Log::info($client_professional_id);
                } else {
                    $client_professional_id = $client_professional->pivot->id;
                }

                $car->client_professional_id = $client_professional_id;
                $car->save();

                $tail->aleatorie = 2;
                $tail->save();
                $this->reassignServices($servicesOrders, $service_professionals);
                // Retorna true indicando que se ha procesado una 'tail'
            return true;
            } //if diferencia de si realiza los servicios

        }//for aleatorie
         // Retorna false indicando que no se ha procesado ninguna 'tail'
        return false;
    } catch (\Throwable $th) {
        throw new \RuntimeException("Error al ejecutar el TailService(verific_services): " . $th->getMessage());
    }
    }

    private function verific_services_bh($tails, $branch_id, $professional, $start_time)
    {
        try {
            Log::info('Verificar aleatorios en Tailservices');
        foreach ($tails as $tail) {
            $reservation = $tail->reservation;
            $tiempoReserva = $reservation->total_time;
            $car = $reservation->car;

            $servicesOrders = Order::where('car_id', $car->id)
                ->where('is_product', 0)
                ->with(['branchServiceProfessional.branchService.service'])
                ->get();

            $services_id = $servicesOrders->pluck('branchServiceProfessional.branchService.service.id')->toArray();

            $service_professionals = BranchServiceProfessional::whereHas('branchService', function ($query) use ($branch_id, $professional) {
                $query->where('branch_id', $branch_id);
            })
                ->where('professional_id', $professional->id)
                ->with('branchService.service')
                ->get();
            $service_professional_id = $service_professionals->pluck('branchService.service.id')->toArray();

            $services_id_collection = collect($services_id);
            $service_professional_id_collection = collect($service_professional_id);
            $diff = $services_id_collection->diff($service_professional_id_collection);
            if ($diff->isEmpty()) {
                Log::info('Realiza todos los servicios');
                // Hora actual
                $horaActual = Carbon::now();
                // Sumar el tiempo de reserva a la hora actual
                $horaActualConReserva = $horaActual->addSeconds(Carbon::parse($tiempoReserva)->secondsSinceMidnight());
                $startTime = Carbon::parse($start_time); // Suponiendo que `start_time` es un campo en tu modelo
                $startTimeMas20Min = $startTime->addMinutes(20);
                Log::info('Hora actual mas tiempo de reserava aleatoria(Tailservices)'.$horaActualConReserva);
                Log::info('Hora de inicio de la reserva bh no confirmada(Tailservices)'.$startTimeMas20Min);
                if ($horaActualConReserva->lessThan($startTimeMas20Min)) {
                    $client = $car->clientProfessional->client;
                //$professional = Professional::find($professional_id);

                $nuevaHoraInicio = Carbon::now();
                list($horasReserva, $minutosReserva, $segundosReserva) = explode(':', $tiempoReserva);
                $reservation->start_time = $nuevaHoraInicio->format('H:i:s');
                $reservation->final_hour = $nuevaHoraInicio->copy()->addHours($horasReserva)->addMinutes($minutosReserva)->addSeconds($segundosReserva)->format('H:i:s');
                $reservation->save();

                $client_professional = $professional->clients()->where('client_id', $client->id)->withPivot('id')->first();
                if (!$client_professional) {
                    Log::info("No existe relación cliente-profesional");
                    $professional->clients()->attach($client->id);
                    $client_professional_id = $professional->clients()->wherePivot('client_id', $client->id)->withPivot('id')->get()->map->pivot->value('id');
                    Log::info($client_professional_id);
                } else {
                    $client_professional_id = $client_professional->pivot->id;
                }

                $car->client_professional_id = $client_professional_id;
                $car->save();

                $tail->aleatorie = 2;
                $tail->save();
                $this->reassignServices($servicesOrders, $service_professionals);
                // Retorna true indicando que se ha procesado una 'tail'
                return true;
                }                
            } //if diferencia de si realiza los servicios

        }//for aleatorie
         // Retorna false indicando que no se ha procesado ninguna 'tail'
        return false;
        } catch (\Throwable $th) {
            throw new \RuntimeException("Error al ejecutar el TailService(verific_services_bh): " . $th->getMessage());
        }
    }





    private function reassignServices_ANTERIOR($servicesOrders, $service_professionals)
    {
        try{
         $serviceProfessionalMap = $service_professionals->unique('branch_service_id')->keyBy(function ($item) {
            return $item->branchService->service->id;
        });

        foreach ($servicesOrders as $service) {
            $serv = $service->branchServiceProfessional->branchService->service;
            $serviceProfessional = $serviceProfessionalMap->get($serv->id);

            if ($serviceProfessional) {
                $percent = $serviceProfessional->percent ?? 0;
                $order = new Order();
                $order->car_id = $service->car_id;
                $order->product_store_id = null;
                $order->branch_service_professional_id = $serviceProfessional->id;
                $order->data = $service->data;
                $order->is_product = false;
                $order->percent_win = $percent ? $serv->price_service * $percent / 100 : $serv->price_service;
                $order->price = $serv->price_service;
                $order->request_delete = false;
                $order->save();
                $service->delete();
            }
        }
    } catch (\Throwable $th) {
        throw new \RuntimeException("Error al ejecutar el TailService(reassignServices): " . $th->getMessage());
    }
    }
    
     private function reassignServices($servicesOrders, $service_professionals)
    {
        Log::info('Entra a reasignar Servicios al profesional');
        Log::info('Entra a reasignar Servicios al profesional');
        try{
         $serviceProfessionalMap = $service_professionals->unique('branch_service_id')->keyBy(function ($item) {
            return $item->branchService->service->id;
        });

        foreach ($servicesOrders as $service) {
            $serv = $service->branchServiceProfessional->branchService->service;
            $serviceProfessional = $serviceProfessionalMap->get($serv->id);
            Log::info('Carro de las ordenes');
            Log::info($serv->name);
            if ($serviceProfessional) {
                Log::info('Servicio a Reasignar');
                Log::info($service->car_id);
                $percent = $serviceProfessional->percent ?? 0;
                // Verifica si ya existe una orden para evitar duplicados
                $existingOrder = Order::where('car_id', $service->car_id)
                    ->where('branch_service_professional_id', $serviceProfessional->id)
                    ->first();
                
                if (!$existingOrder) {
                    $order = new Order();
                    $order->car_id = $service->car_id;
                    $order->product_store_id = null;
                    $order->branch_service_professional_id = $serviceProfessional->id;
                    $order->data = $service->data;
                    $order->is_product = false;
                    $order->percent_win = $percent ? $serv->price_service * $percent / 100 : $serv->price_service;
                    $order->price = $serv->price_service;
                    $order->request_delete = false;
                    $order->save();

                    // Elimina el servicio de la orden solo después de guardar correctamente
                    $service->delete();
                }
            }
        }
        } catch (\Throwable $th) {
            throw new \RuntimeException("Error al ejecutar el TailService(reassignServices): " . $th->getMessage());
        }
    }


    private function convertirHoraAMinutos($hora)
    {
        list($horas, $minutos) = explode(':', $hora);
        return ($horas * 60) + $minutos;
    }
}
