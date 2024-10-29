<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchServiceProfessional;
use App\Models\Reservation;
use App\Models\Order;
use App\Models\Professional;
use App\Models\Tail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AssistantController extends Controller
{
    public function professional_branch_notif_queque_ANTERIOR_OPTIMIZADO(Request $request)
    {
        Log::info('Dada una sucursal y un professional devuelve las notificaciones');
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
            ]);
            $notifications = [];
            $branch = Branch::find($data['branch_id']);
            $professional = Professional::find($data['professional_id']);
            $notifications = $branch->notifications()
                ->where('professional_id', $professional->id)
                ->whereDate('created_at', Carbon::now())
                ->get()
                ->map(function ($query) {
                    return [
                        'id' => $query->id,
                        'professional_id' => intval($query->professional_id),
                        'branch_id' => intval($query->branch_id),
                        'tittle' => $query->tittle,
                        'description' => $query->description,
                        'state' => intval($query->state),
                        'type' => $query->type,
                        'created_at' => Carbon::parse($query->created_at)->format('Y-m-d h:i A'),
                        'updated_at' => Carbon::parse($query->updated_at)->format('Y-m-d h:i A')
                    ];
                })
                ->sortByDesc(function ($notification) {
                    return $notification['created_at'];
                })
                ->values();
            //cola
            $branch_id = $branch->id;
            $professional_id = $professional->id;
            if ($professional->state == 1) {        
                Log::info('Estado del Professional Llama a la cola de los aleatorios');        
                $this->verific_aleatorie($branch_id, $professional);
            }
            Log::info('Llamando a la cola el profesional: '.$professional->name.' en el metodo(professional_branch_notif_queque)');
            Reservation::softDeleteExpiredReservations($data['branch_id'], $data['professional_id']);
            $tails = Tail::whereHas('reservation', function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id)->whereIn('confirmation', [1,4]);
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
            $branchTails = $tails->map(function ($tail) use ($data) {
                $reservation =  $tail->reservation;
                $client = $reservation->car->clientProfessional->client;
                $professional = $reservation->car->clientProfessional->professional;
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
                    'car_id' => intval($reservation->car_id),
                    'start_time' => Carbon::parse($reservation->start_time)->format('H:i'),
                    'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i'),
                    'total_time' => $reservation->total_time,
                    'confirmation' => intval($reservation->confirmation),
                    'client_name' => $client->name,
                    'telefone_client' => $client->phone ? strval($client->phone) : '',
                    'client_image' => $client->client_image ? $client->client_image : "comments/default_profile.jpg",
                    'professional_name' => $professional->name,
                    'client_id' => intval($client->id),
                    'professional_id' => intval($data['professional_id']),
                    'attended' => intval($tail->attended),
                    'updated_at' => $tail->updated_at->format('Y-m-d H:i'),
                    'clock' => intval($tail->clock),
                    'timeClock' => intval($tail->timeClock),
                    'detached' => intval($tail->detached),
                    'total_services' => intval($services->count()),
                    'from_home' => intval($reservation->from_home),
                    'select_professional' => intval($reservation->car->select_professional),
                    'services' => $services

                ];
            })->values();
            return response()->json(['notifications' => $notifications, 'tail' => $branchTails], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las notifocaciones"], 500);
        }
    }
    
     public function professional_branch_notif_queque_ANTERIOR_ULTIMO(Request $request)
    {
        //Log::info('professional_branch_notif_queque');
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
            ]);
            $notifications = [];
            $now = Carbon::now();
            $branch = Branch::find($data['branch_id']);
            $professional = Professional::find($data['professional_id']);
            $notifications = $branch->notifications()
                ->where('professional_id', $professional->id)
                ->where('state', '!=', 1)
                ->whereDate('created_at', $now)
                ->get()
                ->map(function ($query) {
                    return [
                        'id' => $query->id,
                        'professional_id' => intval($query->professional_id),
                        'branch_id' => intval($query->branch_id),
                        'tittle' => $query->tittle,
                        'description' => $query->description,
                        'state' => intval($query->state),
                        'type' => $query->type,
                        'created_at' => Carbon::parse($query->created_at)->format('Y-m-d h:i A'),
                        'updated_at' => Carbon::parse($query->updated_at)->format('Y-m-d h:i A')
                    ];
                })
                ->sortByDesc(function ($notification) {
                    return $notification['created_at'];
                })
                ->values();
            //cola
            $branch_id = $branch->id;
            $professional_id = $professional->id;
            if ($professional->state == 1) {        
               // Log::info('Estado del Professional Llama a la cola de los aleatorios');        
                $this->verific_aleatorie($branch_id, $professional);
            }
            Log::info('Llamando a la cola el profesional: '.$professional->name.' en el metodo(professional_branch_notif_queque)');
            $now = Carbon::now();
            $tails = Tail::whereHas('reservation', function ($query) use ($branch_id,$now)  {
                $query->where('branch_id', $branch_id)->whereIn('confirmation', [1,4])->whereDate('data', $now);
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
            $branchTails = $tails->map(function ($tail) use ($data) {
                $reservation =  $tail->reservation;
                $client = $reservation->car->clientProfessional->client;
                $professional = $reservation->car->clientProfessional->professional;
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
                    'car_id' => intval($reservation->car_id),
                    'start_time' => Carbon::parse($reservation->start_time)->format('H:i'),
                    'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i'),
                    'total_time' => $reservation->total_time,
                    'confirmation' => intval($reservation->confirmation),
                    'client_name' => $client->name,
                    'telefone_client' => $client->phone ? strval($client->phone) : '',
                    'client_image' => $client->client_image ? $client->client_image : "comments/default_profile.jpg",
                    'professional_name' => $professional->name,
                    'client_id' => intval($client->id),
                    'professional_id' => intval($data['professional_id']),
                    'attended' => intval($tail->attended),
                    'updated_at' => $tail->updated_at->format('Y-m-d H:i'),
                    'clock' => intval($tail->clock),
                    'timeClock' => intval($tail->timeClock),
                    'detached' => intval($tail->detached),
                    'total_services' => intval($services->count()),
                    'from_home' => intval($reservation->from_home),
                    'select_professional' => intval($reservation->car->select_professional),
                    'services' => $services

                ];
            })->values();
            return response()->json(['notifications' => $notifications, 'tail' => $branchTails], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las notifocaciones"], 500);
        }
    }
    
     public function professional_branch_notif_queque(Request $request)
    {
        Log::info('Dada una sucursal y un professional devuelve las notificaciones');
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
            ]);
            $notifications = [];            
            $now = Carbon::now();
            $branch = Branch::find($data['branch_id']);
            $professional = Professional::find($data['professional_id']);
            $notifications = $branch->notifications()
                ->where('professional_id', $professional->id)
                ->whereDate('created_at', $now)
                ->where('state', '!=', 1)
                ->get()
                ->map(function ($query) {
                    return [
                        'id' => $query->id,
                        'professional_id' => intval($query->professional_id),
                        'branch_id' => intval($query->branch_id),
                        'tittle' => $query->tittle,
                        'description' => $query->description,
                        'state' => intval($query->state),
                        'type' => $query->type,
                        'created_at' => Carbon::parse($query->created_at)->format('Y-m-d h:i A'),
                        'updated_at' => Carbon::parse($query->updated_at)->format('Y-m-d h:i A')
                    ];
                })
                ->sortByDesc(function ($notification) {
                    return $notification['created_at'];
                })
                ->values();
            //cola
            $branch_id = $branch->id;
            $professional_id = $professional->id;
            if ($professional->state == 1) {        
                //Log::info('Estado del Professional Llama a la cola de los aleatorios');        
                $this->verific_aleatorie($branch_id, $professional);
            }
            Log::info('Llamando a la cola el profesional: '.$professional->name.' en el metodo(professional_branch_notif_queque)');
            $tails = Tail::whereHas('reservation', function ($query) use ($branch_id, $now) {
                $query->where('branch_id', $branch_id)->whereIn('confirmation', [1,4])->whereDate('data', $now);
            })
            ->whereHas('reservation.car.clientProfessional', function ($query) use ($professional_id) {
                $query->where('professional_id', $professional_id);
            })
            ->whereNot('attended', [2])
            ->where('aleatorie', '!=', 1)
            ->join('reservations', 'tails.reservation_id', '=', 'reservations.id')
            ->orderByRaw('reservations.confirmation = 4 DESC')
            ->orderBy('reservations.from_home', 'desc')
            ->orderByRaw('CASE WHEN reservations.from_home = 0 THEN reservations.created_at ELSE reservations.updated_at END ASC')
            ->select('tails.*')  // Selecciona sólo las columnas del modelo Tail
            ->with('reservation') // Carga la relación reservation
            ->get();
            $branchTails = $tails->map(function ($tail) use ($data) {
                $reservation =  $tail->reservation;
                $car = $reservation->car;
                $client = $reservation->car->clientProfessional->client;
                $professional = $reservation->car->clientProfessional->professional;

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
            });
                return [
                    'reservation_id' => $reservation->id,
                    'car_id' => intval($reservation->car_id),
                    'start_time' => Carbon::parse($reservation->start_time)->format('H:i'),
                    'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i'),
                    'total_time' => $reservation->total_time,
                    'confirmation' => intval($reservation->confirmation),
                    'client_name' => $client->name,
                    'telefone_client' => $client->phone ? strval($client->phone) : '',
                    'client_image' => $client->client_image ? $client->client_image : "comments/default_profile.jpg",
                    'professional_name' => $professional->name,
                    'client_id' => intval($client->id),
                    'professional_id' => intval($data['professional_id']),
                    'attended' => intval($tail->attended),
                    'updated_at' => $tail->updated_at->format('Y-m-d H:i'),
                    'clock' => intval($tail->clock),
                    'timeClock' => intval($tail->timeClock),
                    'detached' => intval($tail->detached),
                    'total_services' => intval($services->count()),
                    'from_home' => intval($reservation->from_home),
                    'select_professional' => intval($reservation->car->select_professional),
                    'services' => $services

                ];
            })->values();
            return response()->json(['notifications' => $notifications, 'tail' => $branchTails], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las notifocaciones"], 500);
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

               // Log::info('$reservations ocupadas Notificaciones:');
                //Log::info($reservations->toArray());

                if ($reservations->isEmpty()) {
                   // Log::info('$reservations vacias Notificaciones:');
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

                 //   Log::info('$reservationsTail orden de las reservaciones Notificaciones: ');
                    //Log::info($reservationsTail);

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
               
                 Log::error($th->getMessage());
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
    }

    private function verific_services_bh($tails, $branch_id, $professional, $start_time)
    {
        Log::info('Verificar aleatorios en aistanController');
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
                Log::info('Hora actual mas tiempo de reserava aleatoria(aistanController)'.$horaActualConReserva);
                Log::info('Hora de inicio de la reserva bh no confirmada(aistanController)'.$startTimeMas20Min);
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
    }

    private function reassignServices($servicesOrders, $service_professionals)
    {
        // Construir un mapa de profesionales de servicio por ID de servicio
        $serviceProfessionalMap = $service_professionals->keyBy(function ($item) {
            return $item->branchService->service->id;
        });

        // Añadir logging para depuración
        Log::info('Mapa de profesionales de servicio:', $serviceProfessionalMap->toArray());

        foreach ($servicesOrders as $service) {
            $serv = $service->branchServiceProfessional->branchService->service;
            Log::info('Revisando servicio:', ['id' => $serv->id, 'nombre' => $serv->name]);

            // Buscar el profesional de servicio correspondiente en el mapa
            $serviceProfessional = $serviceProfessionalMap->get($serv->id);
            Log::info('Profesional de servicio encontrado:', $serviceProfessional ? $serviceProfessional->toArray() : 'No encontrado');

            if ($serviceProfessional) {
                $percent = $serviceProfessional->percent ?? 1;

                $order = new Order();
                $order->car_id = $service->car_id;
                $order->product_store_id = null;
                $order->branch_service_professional_id = $serviceProfessional->id;
                $order->data = $service->data;
                $order->is_product = false;
                $order->percent_win = $serv->price_service * $percent / 100;
                $order->price = $serv->price_service;
                $order->request_delete = false;

                // Añadir logging para la creación de la orden
                Log::info('Creando nueva orden:', $order->toArray());

                $order->save();

                // Eliminar el servicio original después de reasignar
                $service->delete();
                Log::info('Servicio original eliminado:', ['id' => $service->id]);
            }
        }
    }
    
     private function reassignServices_POR_ACTUALIZAR($servicesOrders, $service_professionals)
    {
            Log::info('professional_branch_notif_queque-reassignServices de Aleatorios');
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
                        ->lockForUpdate()
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
                throw new \RuntimeException("Error al ejecutar el AssistanController(reassignServices): " . $th->getMessage());
            }
    }
}
