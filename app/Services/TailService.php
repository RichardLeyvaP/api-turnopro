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
use App\Models\Service;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Cache;

class TailService
{

    private $clientHistoryCache = [];
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
    
    public function cola_branch_data($branch_id)
    {
        $tails = Tail::whereHas('reservation', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id)->where('confirmation', 4);
        })->whereIn('attended', [0, 3, 33])->get()->map(function ($tail) {
            $reservation = $tail->reservation;
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
            $reservation = $tail->reservation;
            if ($tail->attended == 33) {
                $car = Car::whereHas('reservation', function ($query) use ($reservation) {
                    $query->where('id', $reservation->id);
                })->first();
                $professionaltem = ClientProfessional::whereHas('cars', function ($query) use ($car) {
                    $query->where('id', $car->id);
                })->first();
                $workplaceId = ProfessionalWorkPlace::where('professional_id', $professionaltem->professional_id)->whereDate('data', Carbon::now())->whereHas('workplace', function ($query) {
                    $query->where('busy', 1)->where('select', 1);
                })->first();
                if ($workplaceId) {
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
               
            } else {
                // $professional = $reservation->car->clientProfessional->professional;
                $professional = $reservation->car->clientProfessional->professional()->withTrashed()->first();
            }
            $client = $reservation->car->clientProfessional->client;
            $comment = Comment::whereHas('clientProfessional', function ($query) use ($client) {
                $query->where('client_id', $client->id);
            })->orderByDesc('data')->orderByDesc('updated_at')->first();
            $tail = $reservation->tail;
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
    
    public function tail_branch_professional($branch_id, $professional_id)
    {
        $professional = Professional::find($professional_id);

        if ($professional->state == 1) {
            $this->verific_aleatorie($branch_id, $professional);
        }

        Log::info('Llamando a la cola el profesional: ' . $professional->name . ' en el servicio TailService(tail_branch_professional)');
        $today = Carbon::now()->format('Y-m-d');

        // NUEVO: Clave para seguimiento de clientes activos ===
        $activeClientsKey = "tail_active_{$branch_id}_{$professional_id}_" . $today;
        $previousActiveClients = Cache::get($activeClientsKey, []);

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
        ->orderByRaw('CASE WHEN reservations.from_home = 0 THEN reservations.created_at ELSE reservations.announced END ASC')
        ->select('tails.*')
        ->with('reservation')
        ->get()
        ->map(function ($tail) use ($branch_id, $professional_id, $today) {
            $reservation = $tail->reservation;
            $car = $reservation->car;
            $clientProfessional = $car->clientProfessional;
            $professional = $clientProfessional->professional;
            $client = $clientProfessional->client;

            // Servicios
            $services = $car->orders->filter(fn($os) => $os->is_product == 0)
                ->map(function ($orderServiceProfessional) {
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

            $client_id = $client->id;

            // CACHÉ DEL HISTORIAL: SI YA SE CALCULÓ, USAR CACHÉ ===
            $cacheKey = "tail_history_{$branch_id}_{$professional_id}_{$client_id}_{$today}";

            if (!isset($this->clientHistoryCache[$client_id])) {
                $this->clientHistoryCache[$client_id] = Cache::remember($cacheKey, now()->addHours(24), function () use ($client_id) {
                    return $this->client_history(['client_id' => $client_id]);
                });
            }

            $history = $this->clientHistoryCache[$client_id];

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
                'notification' => intval($tail->notification),
                'updated_at' => $tail->updated_at->format('Y-m-d H:i'),
                'clock' => $tail->clock,
                'timeClock' => $tail->timeClock,
                'detached' => $tail->detached,
                'total_services' => $services->count(),
                'select_professional' => intval($reservation->car->select_professional),
                'telefone_client' => $client->phone ? strval($client->phone) : '',
                'services' => $services,
                'url_image_barber' => $history['image_url'] ?: "comments/default_profile.jpg",
                'frecuencia' => $history['frecuencia'] ?: "No Frecuente",
                'cant_visit' => $history['cantVisit'] ?: 0,
                'professional_name' => $history['professionalName'] ?: "Desconocido",
                'history_service' => $history['services'],
            ];
        })->values();

        // NUEVO: Detectar quiénes salieron de la cola y limpiar caché ===
        $currentClientIds = $tails->pluck('client_id')->toArray();
        $clientsThatLeft = array_diff($previousActiveClients, $currentClientIds);

        foreach ($clientsThatLeft as $client_id) {
            $cacheKey = "tail_history_{$branch_id}_{$professional_id}_{$client_id}_{$today}";
            Cache::forget($cacheKey);
        }

        // Actualizar lista de activos
        Cache::put($activeClientsKey, $currentClientIds, now()->addHours(24));

        // === WhatsApp (sin cambios) ===
        if ($tails->isNotEmpty() && $tails->first()['attended'] == 0) {
            $firstReservation = $tails->first();
            if ($firstReservation['notification'] == 0) {
                $client_name = $firstReservation['client_name'];
                $telefone_client = $firstReservation['telefone_client'];
                $reservation_id = $firstReservation['reservation_id'];

                $send = $this->notificationService->sendWhatsApp($telefone_client, $client_name);
               
                Tail::where('reservation_id', $reservation_id)->update(['notification' => 1]);
            }
        }

        return $tails;
    }
    
    private function client_history($data)
    {
        $fiel = null;
        $frecuencia = null;
        $cantMaxService = 0;
        $client = Client::withTrashed()->find($data['client_id']);
        $result = [
            'clientName' => $client?->name ?? 'Sin nombre',
            'professionalName' => "Ninguno",
            'branchName' => '',
            'image_data' => '',
            'imageLook' => $client?->client_image ? $client->client_image . '?$' . Carbon::now()->format('Y-m-d') : 'clients/default_profile.jpg' . '?$' . Carbon::now(),
            'image_url' => '',
            'cantVisit' => 0,
            'endLook' => '',
            'lastDate' => '',
            'frecuencia' => "No Frecuente",
            'services' =>  [],
        ];

        $reservations = Reservation::whereHas('car', function ($query) use ($data) {
            $query->where('pay', 1)->whereHas('clientProfessional', function ($query) use ($data) {
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

        $reservationids = $reservations->pluck('car_id')->take(3);
        $services = Service::withCount(['orders' => function ($query) use ($data, $reservationids) {
            $query->whereIn('car_id', $reservationids)->where('is_product', 0);
        }])->orderByDesc('orders_count')->get()->where('orders_count', '>', 0);
        $reservationids2 = $reservations->pluck('car_id')->take(3);
   
        $comment = Comment::whereHas('clientProfessional', function ($query) use ($data) {
            $query->where('client_id', $data['client_id']);
        })->orderByDesc('data')->orderByDesc('updated_at')->first();
        //if ($reservations !== null && !$reservations->isEmpty()) {
        if ($reservations->isEmpty()) {
            $branch = [];
            $professional = [];
            $reservation = [];
        } else {
            $reservation = $reservations->first();
            $branch = $reservation->branch;
            $professional = $reservation->car->clientProfessional->professional()->withTrashed()->first();
        }
        $result = [
            'clientName' => $client->name,
            'professionalName' => $professional ? $professional->name : '',
            'branchName' => $branch ? $branch->name : '',
            'image_data' => $branch ? $branch->image_data : 'branches/default.jpg',
            'image_url' => $professional ? $professional->image_url : 'professionals/default_profile.jpg',
            'imageLook' => $client->client_image ? $client->client_image . '?$' . Carbon::now()->format('Y-m-d') : 'clients/default_profile.jpg' . '?$' . Carbon::now(),
            'cantVisit' => $reservations->count(),
            'endLook' => $comment ? $comment->look : null,
            'lastDate' => $reservation ? $reservation->data : '',
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
            'cantMaxService' => $services->max('orders_count')
        ];
        return $result;
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
            $professional = ClientProfessional::whereHas('cars', function ($query) use ($car) {
                $query->where('id', $car->id);
            })->first()->professional_id;
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
            $professional = ClientProfessional::whereHas('cars', function ($query) use ($car) {
                $query->where('id', $car->id);
            })->first()->professional_id;
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
        
    public function type_of_service($branch_id, $professional_id)
    {
            try {
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
                        return true;
                    }

                    // Si hay más de 3, no se pueden atender más de 4 clientes
                    if ($tails->count() > 3) {
                        return false;
                    }
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
                    $this->actualizarReserva($reservation, $horaActual, $tiempoReserva);
                } else {
                    $nuevaHoraInicio = $this->encontrarIntervaloLibreOld($reservations, $horaActual, $tiempoReserva, $reservation);
                    $this->actualizarReserva($reservation, $nuevaHoraInicio, $tiempoReserva);
                }

                $car = Car::findOrFail($reservation->car_id);

                $servicesOrders = Order::where('car_id', $car->id)->where('is_product', 0)->distinct('id')->get();
                $service_professionals = BranchServiceProfessional::whereHas('branchService', function ($query) use ($reservation) {
                    $query->where('branch_id', $reservation->branch_id);
                })->where('professional_id', $data['professional_id'])
                ->distinct('branch_service_id') // Asegura que los resultados sean únicos
                ->get();

                $client_professional = $professional->clients()->where('client_id', $client->id)->withPivot('id')->first();

                if (!$client_professional) {
                    $professional->clients()->attach($client->id);
                    $client_professional_id = $professional->clients()->wherePivot('client_id', $client->id)->withPivot('id')->get()->map->pivot->value('id');
                } else {
                    $client_professional_id = $client_professional->pivot->id;
                }

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

            Log::info("Reasignar Cliente tailService.reasigned_client");

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
                $this->actualizarReserva($reservation, $horaActual, $tiempoReserva);
            } else {
                $nuevaHoraInicio = $this->encontrarIntervaloLibre($reservations, $horaActual, $tiempoReserva);
                $this->actualizarReserva($reservation, $nuevaHoraInicio, $tiempoReserva);
            }

            $car = Car::findOrFail($reservation->car_id);

            $servicesOrders = Order::where('car_id', $car->id)->where('is_product', 0)->distinct('id')->get();

            $service_professionals = BranchServiceProfessional::whereHas('branchService', function ($query) use ($reservation) {
                $query->where('branch_id', $reservation->branch_id);
            })->where('professional_id', $data['professional_id'])
            ->distinct('branch_service_id') // Asegura que los resultados sean únicos
            ->get();

            $client_professional = $professional->clients()->where('client_id', $client->id)->withPivot('id')->first();

            if (!$client_professional) {
                $professional->clients()->attach($client->id);
                $client_professional_id = $professional->clients()->wherePivot('client_id', $client->id)->withPivot('id')->get()->map->pivot->value('id');
            } else {
                $client_professional_id = $client_professional->pivot->id;
            }

            $tail = $reservation->tail;
            
            
            if ($tail->attended == 3) {
                $ProfessOld = $car->clientProfessional->professional_id;
                $tempProfessional = Professional::where('id', $ProfessOld)->first();                
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
                        $nuevaHoraInicio = $final_hour_next;
                        $encontrado = true;
                        break;
                    }else {
                            $nuevaHoraInicio = Carbon::parse($reservation1->final_hour);
                            $encontrado = true;
                            break;
                        //}
                    }
                }else {
                    if (($nuevaHoraInicioMin + $total_timeMin) <= $start_timeMin) {
                        $encontrado = true;
                        break;
                    }else {
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
                // Hora actual
                $horaActual = Carbon::now();
                // Sumar el tiempo de reserva a la hora actual
                $horaActualConReserva = $horaActual->addSeconds(Carbon::parse($tiempoReserva)->secondsSinceMidnight());
                $startTime = Carbon::parse($start_time); // Suponiendo que `start_time` es un campo en tu modelo
                $startTimeMas20Min = $startTime->addMinutes(20);
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
                    $professional->clients()->attach($client->id);
                    $client_professional_id = $professional->clients()->wherePivot('client_id', $client->id)->withPivot('id')->get()->map->pivot->value('id');
                    
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
    
    private function reassignServices($servicesOrders, $service_professionals)
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
