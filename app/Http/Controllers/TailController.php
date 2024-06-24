<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\BoxClose;
use App\Models\Branch;
use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\CashierSale;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Finance;
use App\Models\Notification;
use App\Models\OperationTip;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Professional;
use App\Models\ProfessionalPayment;
use App\Models\ProfessionalWorkPlace;
use App\Models\Record;
use App\Models\Tail;
use App\Models\Reservation;
use App\Models\Retention;
use App\Models\Trace;
use App\Models\User;
use App\Services\TailService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TailController extends Controller
{
    private TailService $tailService;

    public function __construct(TailService $tailService)
    {
        $this->tailService = $tailService;
    }

    public function index()
    {
        try {

            Log::info("entra a buscar Tail");
            $tails = Tail::with(['reservation' => function ($query) {
                $query->orderBy('start_time');
            }])->get();
            return response()->json(['tails' => $tails], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las Tail"], 500);
        }
    }

    public function tail_up(Request $request)
    {

        try {

            Log::info("entra a availability");
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'data' => 'required'
            ]);
            $idP = $data['professional_id'];
            $id_branch = 1;
            Log::info($idP);


            //todo funcionandooooooo Obtener todas las colas (tails) ordenadas por su ID_reservacion 
            //             $reservations = Reservation::whereHas('car.clientProfessional', function ($query) use ($idP) {
            //     $query->where('professional_id', $idP);
            // })->whereDate('data', $data['data'])->get();

            $reservations = Reservation::whereHas('car.clientProfessional', function ($query) use ($idP, $id_branch) {
                $query->whereHas('professional', function ($query) use ($idP) {
                    $query->where('id', $idP);
                })->whereHas('professional.branchServices', function ($query) use ($id_branch) {
                    $query->where('branch_id', $id_branch);
                });
            })->whereDate('data', $data['data'])->get();

            Log::info($reservations);
            Log::info('muestra  el resultado');

            $differences = [];
            Log::info("entra a a calcular la diferencia:");
            // Iterar sobre las reservas
            for ($i = 0; $i < count($reservations); $i++) {
                $currentReservation = $reservations[$i];

                // Convertir cadenas de tiempo en minutos
                $startTime = strtotime($currentReservation->start_time);
                $finalHour = strtotime($currentReservation->final_hour);

                // Calcular la diferencia en minutos
                $timeDifferenceMinutes = round(($finalHour - $startTime) / 60); //round es para que devuelva en entero, aproxima por exeso

                // Almacenar el par de registros y la diferencia en minutos en el array

                $differences[] = [
                    'time_available_start' => $currentReservation->start_time,
                    'time_available_final' => $currentReservation->final_hour,
                    'service_time_vailable' => $timeDifferenceMinutes,
                ];
            }
            Log::info("esta es desde la funtion :");
            Log::info($differences);
            return response()->json(['Reservation' => $differences], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las tail_up"], 500);
        }
    }

    public function availability(Request $request)
    {
        try {

            Log::info("entra a availability");
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'data' => 'required'
            ]);

            // Obtener todas las colas (tails) ordenadas por su ID_reservacion
            $tails = Reservation::with(['car.clientProfessional.professional' => function ($query, $data) {
                $query->where('id', $data['professional_id']);
            }])->whereDate('data', $data['data'])->get();

            Log::info($tails);
            Log::info('muestra la cola');
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las Tail"], 500);
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

            $tail = Tail::find($data['id']);
            $tail->attended = true;
            $tail->save();
            return response()->json(['msg' => 'Cliente atendido'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al pasar el cliente a atendido'], 500);
        }
    }

    public function cola_branch_data(Request $request)
    {
        try {

            Log::info("Mostarr la cola del dia de una branch");
            $data = $request->validate([
                'branch_id' => 'required'
            ]);
            $data['branch_id'] = intval($data['branch_id']);
            return response()->json(['tail' => $this->tailService->cola_branch_data($data['branch_id'])], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las Tail"], 500);
        }
    }
    public function cola_branch_data2(Request $request)
    {
        try {

            Log::info("Mostarr la cola del dia de una branch");
            $data = $request->validate([
                'branch_id' => 'required'
            ]);
            $data['branch_id'] = intval($data['branch_id']);
            return response()->json(['tail' => $this->tailService->cola_branch_data2($data['branch_id'])], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las Tail"], 500);
        }
    }
    public function tail_branch_attended(Request $request)
    {
        try {

            $data = $request->validate([
                'branch_id' => 'required'
            ]);
            $data['branch_id'] = intval($data['branch_id']);
            $reservations = Tail::whereHas('reservation', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id'])->where('confirmation', 4);
            })->whereIn('attended', [0, 1, 3, 11, 111, 4, 5, 33])->get()->map(function ($tail) {
                $reservation = $tail->reservation;
                $professional = $reservation->car->clientProfessional->professional;
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
                    'from_home' => $reservation->from_home,
                    'start_time' => Carbon::parse($reservation->start_time)->format('H:i'),
                    'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i'),
                    'total_time' => $reservation->total_time,
                    'client_name' => $client->name,
                    'client_image' => $comment ? $comment->client_look : "comments/default_profile.jpg",
                    'professional_name' => $professional->name,
                    'image_url' => $professional->image_url ? $professional->image_url : "professionals/default_profile.jpg",
                    'client_id' => $client->id,
                    'professional_id' => $professional->id,
                    'professional_state' => $professional->state,
                    'attended' => $tail->attended,
                    'puesto' => $workplace ? $workplace->name : null,
                    'code' => $reservation->code
                ];
            })->sortBy('start_time')->values();

            $attendedReservations = $reservations->whereIn('attended', [1,11,111,4,5,33])->sortByDesc('start_time')->values();
            $unattendedReservations = $reservations->where('attended', '!=', 1)->values();

            return response()->json(['tail' => $unattendedReservations, 'attended' => $attendedReservations], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las Tail"], 500);
        }
    }

    public function cola_branch_capilar(Request $request)
    {
        try {

            Log::info("Mostarr la cola de servicio capilar del dia de una branch");
            $data = $request->validate([
                'branch_id' => 'required'
            ]);
            $data['branch_id'] = intval($data['branch_id']);
            return response()->json(['tail' => $this->tailService->cola_branch_capilar($data['branch_id'])], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()], 500);
        }
    }

    public function cola_branch_tecnico(Request $request)
    {
        try {

            Log::info("Mostarr la cola de servicio capilar del dia de un tecnico");
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
            ]);
            return response()->json(['tail' => $this->tailService->cola_branch_tecnico($data['branch_id'], $data['professional_id'])], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()], 500);
        }
    }

    public function cola_branch_delete(Request $request)
    {
        try {

            Log::info("Mostarr la cola del dia de una branch");
            $data = $request->validate([
                'branch_id' => 'required'
            ]);
            $data['branch_id'] = intval($data['branch_id']);
            $this->tailService->cola_branch_delete($data['branch_id']);
            return response()->json(['tail' => "Tails eliminada correctamente"], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al eiliminra las Tail"], 500);
        }
    }

    public function cola_branch_professional(Request $request)
    {
        try {

            Log::info("Mostarr la cola del dia de una branch");
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);


            return response()->json(['tail' => $this->tailService->cola_branch_professional($data['branch_id'], $data['professional_id'])], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las Tail"], 500);
        }
    }
    public function cola_branch_professional_new(Request $request)
    {
        try {

            Log::info("Mostarr la cola del dia de una branch");
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);


            return response()->json(['tail' => $this->tailService->cola_branch_professional($data['branch_id'], $data['professional_id'])], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las Tail"], 500);
        }
    }


    public function type_of_service(Request $request)
    {
        try {

            Log::info("Mostarr la cola del dia de una branch");
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);


            return response()->json($this->tailService->type_of_service($data['branch_id'], $data['professional_id']), 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las Tail"], 500);
        }
    }

    public function tail_attended(Request $request)
    {
        try {

            Log::info("Modificar estado de la Cola");
            $data = $request->validate([
                'reservation_id' => 'required|numeric',
                'attended' => 'required|numeric'
            ]);
            $this->tailService->tail_attended($data['reservation_id'], $data['attended']);

            return response()->json(['msg' => "Cola modificado correctamente"], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las Cola"], 500);
        }
    }

    public function return_client_status(Request $request)
    {
        try {
            $data = $request->validate([
                'reservation_id' => 'required|numeric'
            ]);
            $attended = Tail::where('reservation_id', $data['reservation_id'])->get()->value('attended');
            if (!$attended) {
                $attended = 0;
            }
            return response()->json($attended, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al mostrar el estado de la reservacion'], 500);
        }
    }

    public function cola_truncate()
    {
        try {

            Log::info("Mandar a eliminar la cola");
            Tail::truncate();
            return response()->json(['msg' => "Cola eliminada correctamente"], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al eliminar la Tail"], 500);
        }
    }

    public function table_test_truncate()
    {
        try {

            Log::info("Vaciar tablas para prueba");
            // Eliminar todos los registros de la tabla box_closes
            BoxClose::query()->delete();

            // Eliminar todos los registros de la tabla boxes
            Box::query()->delete();
            Car::query()->delete(); //car, reservation, tail y orders
            Finance::truncate(); //Finance
            OperationTip::query()->delete(); //pago a cajera de cars
            Payment::query()->delete(); //pago de cars
            ProfessionalPayment::query()->delete(); //pago a professionales
            CashierSale::truncate(); //pago a professionales
            Retention::truncate(); //Retenciones de los professionales
            Trace::truncate(); //Operaciones realizadas en la caja
            Notification::truncate(); //Notificaciones
            Comment::truncate(); //Clientes
            Client::query()->delete(); //Clientes
            ProfessionalWorkPlace::truncate(); //Professionals puestos de trabajo
            Record::truncate(); //Hora de entrada y salida de los professionales
            User::whereDoesntHave('professional')->delete(); //borrar los usuarios que no professionales

            return response()->json(['msg' => "Tablas vaciadas correctamente"], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al eliminar la Tail"], 500);
        }
    }

    public function set_clock(Request $request)
    {
        try {

            Log::info("Modificar estado del relock");
            Log::info($request);
            $data = $request->validate([
                'reservation_id' => 'required|numeric',
                'clock' => 'required|numeric'
            ]);

            $tail = Tail::where('reservation_id', $data['reservation_id'])->first();

            $tail->clock = $data['clock'];
            $tail->save();
            return response()->json(['msg' => 'Estado del reloj modificado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al modificar el estado del reloj'], 500);
        }
    }

    public function set_timeClock(Request $request)
    {
        try {

            Log::info("Modificar estado del relock");
            Log::info($request);
            $data = $request->validate([
                'reservation_id' => 'required|numeric',
                'timeClock' => 'required|numeric',
                'detached' => 'required|numeric',
                'clock' => 'required|numeric'
            ]);
            //esta comparacin esta porque llego en null en una ocasion y da error
            Log::info('Variable que lleg null ($data["timeClock"])');
            Log::info($data['timeClock']);
            if ($data['timeClock'] !== null) {
                $tail = Tail::where('reservation_id', $data['reservation_id'])->first();
                if ($tail) {
                    $tail->timeClock = $data['timeClock'];
                    $tail->detached = $data['detached'];
                    $tail->clock = $data['clock'];
                    $tail->save();
                }
            }
            return response()->json(['msg' => 'Estado del tiempo del reloj y estado modificado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al modificar el tiempo del reloj y el estado'], 500);
        }
    }

    public function get_clock(Request $request)
    {
        try {

            Log::info("Devolver campo clock dado el id reservation");
            Log::info($request);
            $data = $request->validate([
                'reservation_id' => 'required|numeric'
            ]);
            // return response()->json(Tail::where('reservation_id',$data['reservation_id'])->get(), 200); //ESTE ERA EL QUE ESTABA
            $result = Tail::where('reservation_id', $data['reservation_id'])->pluck('clock')->first();
            return response()->json($result, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al modificar el estado del reloj'], 500);
        }
    }

    public function reasigned_client(Request $request)
    {
        try {

            Log::info("Reasignar Cliente a barbero");
            $data = $request->validate([
                'reservation_id' => 'required|numeric',
                'client_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $this->tailService->reasigned_client($data);

            return response()->json(['msg' => "Cliente reasignado correctamente"], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las Cola"], 500);
        }
    }

    /*public function reasigned_client_totem(Request $request)
    {
        try {

            Log::info("Reasignar Cliente a barbero");
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $tails = Tail::whereHas('reservation', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id'])->orderBy('start_time');
            })->where('aleatorie', 1)->get();
            if ($tails->isEmpty()) {
                Log::info('No hay aleatorie');
                return response()->json(0, 200);
            } else {
                foreach ($tails as $tail) {
                    $reservation = $tail->reservation;
                    $tiempoReserva = $reservation->total_time;
                    $car = $reservation->car;
                    $services_id = [];
                    $service_professional_id = [];
                    $servicesOrders = Order::where('car_id', $car->id)
                    ->where('is_product', 0)
                    ->with(['branchServiceProfessional.branchService.service'])
                    ->get();
                    foreach ($servicesOrders as $servicesOrder) {
                        $services_id[] = $servicesOrder->branchServiceProfessional->branchService->service->id;
                    }
                    //$services_id = $servicesOrders->branchService->service->pluck('id');
                    $service_professionals = BranchServiceProfessional::whereHas('branchService', function ($query) use ($data) {
                        $query->where('branch_id', $data['branch_id']);
                    })
                    ->where('professional_id', $data['professional_id'])
                    ->with('branchService.service')
                    ->get();
                    foreach ($service_professionals as $service_professional) {
                        $service_professional_id[] = $service_professional->branchService->service->id;
                    }
                    // Verificar si todos los services_id están en service_professional_ids
                    // Convertir los arrays en colecciones
                    $services_id_collection = collect($services_id);
                    $service_professional_id_collection = collect($service_professional_id);

                    // Calcular la diferencia
                    $diff = $services_id_collection->diff($service_professional_id_collection);
                    Log::info($diff);
                    if ($diff->isEmpty()) {
                        Log::info('Realiza todos los servicios');
                        // Todos los services_id están en service_professional_ids
                        $client = $car->clientProfessional->client;
                        $professional = Professional::find($data['professional_id']);
                        //actualizar horario de la rserva
                        $horaActual = Carbon::now();
                        $reservations = $professional->reservations()
                            ->where('branch_id', $data['branch_id'])
                            ->whereIn('confirmation', [1, 4])
                            ->whereDate('data', Carbon::now())
                            ->orderBy('start_time')
                            ->get();
                        Log::info('$reservations');
                        if ($reservations->isEmpty()) {
                            Log::info('No tiene reservas');
                            list($horasReserva, $minutosReserva, $segundosReserva) = explode(':', $tiempoReserva);


                            $reservation->start_time = $horaActual->format('H:i:s');
                            // Sumar el tiempo de la reserva a la hora actual
                            $nuevaHora = $horaActual->copy()->addHours($horasReserva)->addMinutes($minutosReserva)->addSeconds($segundosReserva);
                            $reservation->final_hour = $nuevaHora->format('H:i:s');
                            $reservation->save();
                        } else {
                            Log::info('Tiene reservas reasigned aleatorie');
                            $encontrado = false;
                            $nuevaHoraInicio = $horaActual;
                            
                            $total_timeMin = $this->convertirHoraAMinutos($tiempoReserva);
                            // Recorrer las reservas existentes para encontrar un intervalo de tiempo libre
                            foreach ($reservations as $reservation1) {
                                Log::info('entra al ciclo de las reservas reasigned aleatorie');
                                $start_time = Carbon::parse($reservation1->start_time);
                                $final_hour = Carbon::parse($reservation1->final_hour);
                                //return $reservation1;
                                $start_timeMin = $this->convertirHoraAMinutos($reservation1->start_time);
                                $final_hourMin = $this->convertirHoraAMinutos($reservation1->final_hour);
                                $nuevaHoraInicioMin = $this->convertirHoraAMinutos($nuevaHoraInicio->format('H:i'));
                                
                                if (($nuevaHoraInicioMin + $total_timeMin) <= $start_timeMin) {
                                    Log::info('Entra que es menor que la reserva reasigned aleatorie');
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
                        //end actualizar horario de la reserva
                        $client_professional = $professional->clients()->where('client_id', $client->id)->withPivot('id')->first();
                        if (!$client_professional) {
                            Log::info("no existe");
                            $professional->clients()->attach($client->id);
                            $client_professional_id = $professional->clients()->wherePivot('client_id', $client->id)->withPivot('id')->get()->map->pivot->value('id');
                            Log::info($client_professional_id);
                        } else {
                            $client_professional_id = $client_professional->pivot->id;
                            Log::info($client_professional_id);
                        }
                        $car->client_professional_id = $client_professional_id;
                        $car->save();
                        $tails->aleatorie = 2;
                        $tails->save();
                        foreach ($servicesOrders as $service) {
                            foreach ($service_professionals as $service_professional) {
                                $serv = $service->branchServiceProfessional->branchService->service;
                                if ($serv->id == $service_professional->branchService->service->id) {
                                    $percent = $service_professional->percent ? $service_professional->percent : 1;
                                    $order = new Order();
                                    $order->car_id = $service->car_id;
                                    $order->product_store_id = null;
                                    $order->branch_service_professional_id = $service_professional->id;
                                    $order->data = $service->data;
                                    $order->is_product = false;
                                    //logica de porciento de ganancia
                                    $order->percent_win = $serv->price_service * $percent / 100;
                                    $order->price = $serv->price_service;
                                    $order->request_delete = false;
                                    $order->save();
                                    $service->delete();
                                }
                            }
                        }
                        return response()->json(1, 200);
                    } //if diferencia
                } //foreach
                return response()->json(0, 200);
            }
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
        }
    }*/
    public function reasigned_client_totem(Request $request)
{
    try {
        Log::info("Reasignar Cliente a barbero");
        $data = $request->validate([
            'branch_id' => 'required|numeric',
            'professional_id' => 'required|numeric'
        ]);

        $tails = Tail::whereHas('reservation', function ($query) use ($data) {
            $query->where('branch_id', $data['branch_id'])->orderBy('start_time');
        })->where('aleatorie', 1)->get();

        if ($tails->isEmpty()) {
            Log::info('No hay aleatorie');
            return response()->json(0, 200);
        }

        DB::beginTransaction();

        foreach ($tails as $tail) {
            $reservation = $tail->reservation;
            $tiempoReserva = $reservation->total_time;
            $car = $reservation->car;

            $servicesOrders = Order::where('car_id', $car->id)
                ->where('is_product', 0)
                ->with(['branchServiceProfessional.branchService.service'])
                ->get();

            $services_id = $servicesOrders->pluck('branchServiceProfessional.branchService.service.id')->toArray();

            $service_professionals = BranchServiceProfessional::whereHas('branchService', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id']);
                })
                ->where('professional_id', $data['professional_id'])
                ->with('branchService.service')
                ->get();

            $service_professional_id = $service_professionals->pluck('branchService.service.id')->toArray();

            $services_id_collection = collect($services_id);
            $service_professional_id_collection = collect($service_professional_id);
            $diff = $services_id_collection->diff($service_professional_id_collection);

            Log::info($diff);
            if ($diff->isEmpty()) {
                Log::info('Realiza todos los servicios');

                $client = $car->clientProfessional->client;
                $professional = Professional::find($data['professional_id']);

                $this->updateReservationTimes($reservation, $professional, $data['branch_id'], $tiempoReserva);

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

                DB::commit();
                return response()->json(1, 200);
            }
        }
        
        DB::commit();
        return response()->json(0, 200);
    } catch (\Throwable $th) {
        DB::rollBack();
        Log::error($th);
        return response()->json(['msg' => $th->getMessage() . " Error interno del sistema"], 500);
    }
}

private function updateReservationTimes($reservation, $professional, $branch_id, $tiempoReserva)
{
    $horaActual = Carbon::now();
    $reservations = $professional->reservations()
        ->where('branch_id', $branch_id)
        ->whereIn('confirmation', [1, 4])
        ->whereDate('data', Carbon::now())
        ->orderBy('start_time')
        ->get();

    Log::info('$reservations');
    if ($reservations->isEmpty()) {
        Log::info('No tiene reservas');
        $this->setReservationTimes($reservation, $horaActual, $tiempoReserva);
    } else {
        Log::info('Tiene reservas reasigned aleatorie');
        $nuevaHoraInicio = $this->findAvailableTimeSlot($reservations, $horaActual, $tiempoReserva);
        $this->setReservationTimes($reservation, $nuevaHoraInicio, $tiempoReserva);
    }
}

private function setReservationTimes($reservation, $start_time, $tiempoReserva)
{
    list($horasReserva, $minutosReserva, $segundosReserva) = explode(':', $tiempoReserva);
    $reservation->start_time = $start_time->format('H:i:s');
    $reservation->final_hour = $start_time->copy()->addHours($horasReserva)->addMinutes($minutosReserva)->addSeconds($segundosReserva)->format('H:i:s');
    $reservation->save();
}

private function findAvailableTimeSlot($reservations, $horaActual, $tiempoReserva)
{
    $encontrado = false;
    $nuevaHoraInicio = $horaActual;
    $total_timeMin = $this->convertirHoraAMinutos($tiempoReserva);

    foreach ($reservations as $reservation1) {
        Log::info('Revisando reservas Aleatorio');
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

private function reassignServices($servicesOrders, $service_professionals)
{
    // Construir un mapa de profesionales de servicio por ID de servicio
    $serviceProfessionalMap = $service_professionals->keyBy(function($item) {
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
    private function convertirHoraAMinutos($hora)
    {
        list($horas, $minutos) = explode(':', $hora);
        return ($horas * 60) + $minutos;
    }

}
