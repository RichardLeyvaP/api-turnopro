<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\Tail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\Send_mail;
use App\Models\Branch;
use App\Models\Business;
use App\Models\Client;
use App\Models\Professional;
use App\Models\User;
use App\Services\ReservationService;
use App\Services\SendEmailService;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Mailer\Exception\TransportException;

class ReservationController extends Controller
{

    private ReservationService $reservationService;
    private SendEmailService $sendEmailService;

    public function __construct(ReservationService $reservationService, SendEmailService $sendEmailService)
    {
        $this->reservationService = $reservationService;
        $this->sendEmailService = $sendEmailService;
    }

    public function index()
    {
        try {
            Log::info("Entra a buscar las reservaciones");
            $reservations = Reservation::with('car.clientProfessional.professional', 'car.clientProfessional.client')->get();
            return response()->json(['reservaciones' => $reservations], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las reservaciones"], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info("Guardar Reservacion");
        try {
            $data = $request->validate([
                'start_time' => 'required',
                //'final_hour' => 'required',
                //'total_time' => 'required',
                'data' => 'required|date',
                'from_home' => 'required',
                'car_id' => 'required'
            ]);

            $orderServicesDatas = Order::with('car')->whereRelation('car', 'id', '=', $data['car_id'])->where('is_product', false)->get();
            $sumaDuracion = $orderServicesDatas->sum(function ($orderServicesData) {
                return $orderServicesData->branchServiceProfessional->branchService->service->duration_service;
            });
            $reservacion = new Reservation();
            $reservacion->start_time = Carbon::parse($data['start_time'])->toTimeString();
            $reservacion->final_hour = Carbon::parse($data['start_time'])->addMinutes($sumaDuracion)->toTimeString();
            $reservacion->total_time = sprintf('%02d:%02d:%02d', floor($sumaDuracion / 60), $sumaDuracion % 60, 0);
            $reservacion->data = $data['data'];
            $reservacion->from_home = $data['from_home'];
            $reservacion->car_id = $data['car_id'];
            $reservacion->save();

            return response()->json(['msg' => 'Reservacion realizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al hacer la reservacion'], 500);
        }
    }

    public function branch_reservations(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            Log::info("Entra a buscar las reservaciones");
            $branch = Branch::find($data['branch_id']);
            $reservations = $branch->reservations()->with(['car.clientProfessional.client', 'car.clientProfessional.professional'])->whereDate('data', Carbon::now())->orderByDesc('data')->get()->map(function ($reservation) {
                $client = $reservation->car->clientProfessional->client;
                $professional = $reservation->car->clientProfessional->professional;
                $services = $reservation->car->orders->where('is_product', 0)->count();
                return [
                    'id' => $reservation->id,
                    'car_id' => $reservation->car_id,
                    'client_professional_id' => $reservation->car->client_professional_id,
                    'clientName' => $client->name . ' ' . $client->surname . ' ' . $client->second_surname,
                    'professionalName' => $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname,
                    'client_image' => $client->client_image,
                    'image_url' => $professional->image_url,
                    'data' => $reservation->data,
                    'start_time' => $reservation->start_time,
                    'end_time' => $reservation->final_hour,
                    'total_time' => $reservation->total_time

                ];
            });
            /*$car = Car::whereHas('branch', function ($query) use ($data){
                $query->where('id', $data['branch_id']);
            })->with('clientProfessional.client', 'clientProfessional.professional')->get();*/
            //$car = Car::with('clientProfessional.client', 'clientProfessional.professional')->get();
            return response()->json(['reservations' => $reservations], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los carros"], 500);
        }
    }

    public function reservation_store(Request $request)
    {
        Log::info("Guardar Reservacion");
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'start_time' => 'required',
                'data' => 'required|date',
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'email_client' => 'required',
                'phone_client' => 'required',
                'name_client' => 'required'//,
                //'surname_client' => 'required',
                //'second_surname' => 'required',
            ]);
            if ($request->has('select_professional')) {
                $data['select_professional'] = $request->select_professional;
            } else {
                $data['select_professional'] = 1;
            }
            if ($request->has('from_home')) {
                $data['from_home'] = $request->from_home;
            } else {
                $data['from_home'] = 1;
            }
            $servs = $request->input('services');
            $id_client = 0;
            $code = '';
            //1-Verificar que el usuario no este registrado
            $user = User::where('email', $data['email_client'])->first();
            // Verificar si se encontró un usuario
            if ($user) {
                Log::info("Encontro el ususario");
                Log::info($user);
                // Buscar el cliente
                $client = Client::where('email', $data['email_client'])->first();
                if ($client) {
                    Log::info("Buscar el cliente");
                    $id_client = $client->id;
                    $reservation = $this->reservationService->store($data, $servs, $id_client);
                } else {
                    Log::info("Si no existe registrarlo");

                    $client = new Client();
                    $client->name = $data['name_client'];
                    //$client->surname = $data['surname_client'];
                    //$client->second_surname = $data['second_surname'];
                    $client->email = $data['email_client'];
                    $client->phone = $data['phone_client'];
                    $client->user_id = $user->id;
                    $client->client_image = 'comments/default.jpg';
                    $client->save();
                    $id_client = $client->id;

                    Log::info("Id que tiene");
                    Log::info($id_client);
                    $reservation = $this->reservationService->store($data, $servs, $id_client);
                }
            } else {
                Log::info("Crear Usuario");
                // Crear Usuario
                $user = User::create([
                    'name' => $data['name_client'],
                    'email' => $data['email_client'],
                    'password' => Hash::make($data['email_client'])
                ]);
                Log::info("Crear client");

                $client = new Client();
                $client->name = $data['name_client'];
                //$client->surname = $data['surname_client'];
                //$client->second_surname = $data['second_surname'];
                $client->email = $data['email_client'];
                $client->phone = $data['phone_client'];
                $client->client_image = 'comments/default.jpg';
                $client->user_id = $user->id;
                $client->save();
                $id_client = $client->id;

                Log::info("Id que obtuvo");
                Log::info($id_client);
                $reservation = $this->reservationService->store($data, $servs, $id_client);
            }

            DB::commit();

            // SI la fecha con la que se registró es igual a la fecha de hoy llamar actualizar la cola del dia de hoy
            Log::info("5.comparando fechas");


            $fechaHoy = Carbon::today();
            // Obtener la fecha formateada como 'YYYY-MM-DD'
            $fechaFormateada = $fechaHoy->toDateString();
            Log::info($data['data']);
            Log::info($fechaFormateada);

            if (($data['data'] == $fechaFormateada)) {
                Log::info("5.las fechas son iguales");
                $this->reservation_tail();
                Log::info("5.actualice la cola");
            }
            if($data['from_home'] == 1){
                $code = $reservation->code;
                //optener nombre del professional
            $professional = Professional::find($data['professional_id']);
            $name = $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname;
            //todo *************** llamando al servicio de envio de email *******************
            //$this->sendEmailService->confirmReservation($data['data'], $data['start_time'], $id_client, $data['branch_id'], null, $name);
            //SendEmailJob::dispatch()->confirmReservation($data['data'], $data['start_time'], $id_client, $data['branch_id'], null, $name);
            if($reservation != null){
                $id = $reservation->id;
            }
            else{
                $id = 0; 
            }
            Log::info('Id de la reservacion');
            Log::info($id);
            $data = [
                'confirm_reservation' => true, // Indica que es una confirmación de reserva
                'data_reservation' => $data['data'], // Datos de la reserva
                'start_time' => $data['start_time'], // Hora de inicio
                'client_id' => $id_client, // ID del cliente
                'branch_id' => $data['branch_id'], // ID de la sucursal
                'type' => null, // Tipo (en este caso, se deja como null)
                'name_professional' => $name, // Nombre del profesional
                'recipient' => null, // Destinatario (en este caso, se deja como null),                
                'id_reservation' => $id, // Destinatario (en este caso, se deja como null)
                'code_reserva' => $code                
            ];
            
            SendEmailJob::dispatch($data);
            }
            return response()->json(['msg' => 'Reservación realizada correctamente'], 200);
        } catch (TransportException $e) {
    
            return response()->json(['msg' => 'Reservación realizada correctamente.Error al enviar el correo electrónico '], 200);
        }
          catch (\Throwable $th) {
              Log::error($th);
            
              DB::rollback();
              return response()->json(['msg' => $th->getMessage() . 'Error al hacer la reservacion'], 500);
        }
    }

    public function professional_reservations(Request $request)
    {
        try {
            Log::info("Entra a buscar las reservaciones de un professionals en una fecha dada");
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'data' => 'required|date'
            ]);
            $reservations = Reservation::WhereHas('car.clientProfessional', function ($query) use ($data) {
                $query->where('professional_id', $data['professional_id']);
            })->where('branch_id', $data['branch_id'])->whereBetween('data', [$data['data'], Carbon::parse($data['data'])->addDays(7)])->orderBy('data')->orderBy('start_time')->get();
            return response()->json(['reservaciones' => $reservations], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las reservaciones"], 500);
        }
    }

    public function professional_reservations_periodo(Request $request)
    {
        try {
            Log::info("Entra a buscar las reservaciones de un professionals en una fecha dada");
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'startDate' => 'required|date',
                'endDate' => 'required|date'
            ]);
            $dates = [];
            $reservations = Reservation::WhereHas('car.clientProfessional', function ($query) use ($data) {
                $query->where('professional_id', $data['professional_id']);
            })->where('branch_id', $data['branch_id'])->whereDate('data', '>=',$data['startDate'])->whereDate('data', '<=',$data['endDate'])->orderBy('data')->get();
            foreach ($reservations as $reservation) {   
                $client = $reservation['car']['clientProfessional']['client'];             
            $dates[] = [
                'startDate' => Carbon::parse($reservation['data'].' '.$reservation['start_time'])->toDateTimeString(),
                'endDate' => Carbon::parse($reservation['data'].' '.$reservation['final_hour'])->toDateTimeString(),
                'clientName' => $client['name']
            ];
            }
            $sortedDates = collect($dates)->sortBy('startDate')->values()->all();
            return response()->json(['reservaciones' => $sortedDates], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las reservaciones"], 500);
        }
    }
    
    public function branch_reservations_periodo(Request $request)
    {
        try {
            Log::info("Entra a buscar las reservaciones de una branch en una fecha dada");
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'startDate' => 'required|date',
                'endDate' => 'required|date'
            ]);
            $dates = [];
            $professionalDates = [];
            $reservations = Reservation::where('branch_id', $data['branch_id'])->whereDate('data', '>=',$data['startDate'])->whereDate('data', '<=',$data['endDate'])->orderBy('data')->get();
            foreach ($reservations as $reservation) {   
                $client = $reservation['car']['clientProfessional']['client'];             
            $dates[] = [
                'startDate' => Carbon::parse($reservation['data'].' '.$reservation['start_time'])->toDateTimeString(),
                'endDate' => Carbon::parse($reservation['data'].' '.$reservation['final_hour'])->toDateTimeString(),
                'clientName' => $client['name']
            ];
            }
            $sortedDates = collect($dates)->sortBy('startDate')->values()->all();
            $professionals = Professional::with('charge')->whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->whereHas('charge', function ($query) {
                $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
            })->get();
            foreach($professionals as $professional){
                $professionalDates[] = [
                    'id' => $professional['id'],
                    'name' => $professional['name'],
                    'image_url' => $professional['image_url'],
                    'charge' => $professional['charge']['name']
                ];
            }
            return response()->json(['reservaciones' => $sortedDates, 'professionals' => $professionalDates], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las reservaciones"], 500);
        }
    }

    public function reservations_count(Request $request)
    {
        try {
            Log::info("Entra a buscar una las reservations del dia");
            $data = $request->validate([
                'business_id' => 'required|numeric',
                'branch_id' => 'nullable'
            ]);
            Log::info('dataaaaaaaaaa');
            Log::info($data);
            if ($data['branch_id'] != 0) {
                Log::info("Branch");
                $reservations = Reservation::where('branch_id', $data['branch_id'])->whereDate('data', now()->toDateString())->where('from_home', 1)->count();
                //$branch = Branch::find($data['branch_id']);
                //Log::info('Es una branch');
                //Log::info($branch);
                /*$reservations =  Branch::where('id', $data['branch_id'])->whereHas('reservations.car.clientProfessional.professional.branches', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id']);
                })->whereHas('reservations', function ($query) {
                    $query->whereDate('data', now()->toDateString());
                })
                    ->get()
                    ->flatMap(function ($branch) use ($data) {
                        return $branch->reservations->where('laravel_through_key', $data['branch_id'])->where('data', now()->toDateString());
                    })->count();*/
            } else {
                Log::info("Business");
                $reservations = Reservation::whereDate('data', now()->toDateString())->where('from_home', 1)->count();
            }

            Log::info('$reservations');
            Log::info($reservations);
            return response()->json($reservations, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las reservaciones"], 500);
        }
    }

    public function reservations_count_week(Request $request)
    {
        try {
            Log::info("Entra a buscar una las reservations de la semana");
            $data = $request->validate([
                'business_id' => 'required|numeric',
                'branch_id' => 'nullable'
            ]);

            $array = [];
            $start = now()->startOfWeek(); // Start of the current week, shifted to Monday
            $end = now()->endOfWeek();   // End of the current week, shifted to Sunday
            $dates = [];
            $reservationsData = [];
            //return [$start, $end];
            $i = 0;
            $day = 0; //en $day = 1 es Lunes,$day=2 es Martes...$day=7 es Domingo, esto e spara el front
            if ($data['branch_id'] != 0) {
                Log::info('Es una Sucursal');
                // Consulta para obtener las reservas de la semana actual
                $reservations = Reservation::whereDate('data', '>=', $start)->whereDate('data', '<=', $end)->where('from_home', 1)->with(['car.clientProfessional.client', 'car.clientProfessional.professional'])->where('branch_id', $data['branch_id'])->orderBy('data')->get();

                $reservationsData = $reservations->map(function ($reservation) {
                    $client = $reservation->car->clientProfessional->client;
                    $professional = $reservation->car->clientProfessional->professional;
                    return [
                        'id' => $reservation->id,
                        'car_id' => $reservation->car_id,
                        'client_professional_id' => $reservation->car->client_professional_id,
                        'clientName' => $client->name . ' ' . $client->surname,
                        'professionalName' => $professional->name . ' ' . $professional->surname,
                        'client_image' => $client->client_image,
                        'image_url' => $professional->image_url,
                        'data' => $reservation->data,
                        'start_time' => $reservation->start_time,
                        'end_time' => $reservation->final_hour,
                        'total_time' => $reservation->total_time
    
                    ];
                }); 

                for ($date = $start, $i = 0; $date->lte($end); $date->addDay(), $i++) {
                    $machingResult = $reservations->where('data', $date->toDateString())->count();
                    //$dates['amount'][$i] = $machingResult ? $machingResult: 0;
                    $dates[$i] = $machingResult ? $machingResult : 0;
                }
                $dates;

                $reservations = $dates;
            } else {
                Log::info("Business");
                $reservations = Reservation::whereDate('data', '>=', $start)->whereDate('data', '<=', $end)->where('from_home', 1)->get();
                $reservationsData = $reservations->map(function ($reservation) {
                    $client = $reservation->car->clientProfessional->client;
                    $professional = $reservation->car->clientProfessional->professional;
                    return [
                        'id' => $reservation->id,
                        'car_id' => $reservation->car_id,
                        'client_professional_id' => $reservation->car->client_professional_id,
                        'clientName' => $client->name . ' ' . $client->surname,
                        'professionalName' => $professional->name . ' ' . $professional->surname,
                        'client_image' => $client->client_image,
                        'image_url' => $professional->image_url,
                        'data' => $reservation->data,
                        'start_time' => $reservation->start_time,
                        'end_time' => $reservation->final_hour,
                        'total_time' => $reservation->total_time
    
                    ];
                }); 
                for ($date = $start, $i = 0; $date->lte($end); $date->addDay(), $i++) {
                    $machingResult = $reservations->where('data', $date->toDateString())->count();
                    //$dates['amount'][$i] = $machingResult ? $machingResult: 0;
                    $dates[$i] = $machingResult ? $machingResult : 0;
                }
                $dates;

                $reservations = $dates;
            }

            //$reservationsString = implode(',', $reservations);
            return response(['cantReservations' => $reservations, 'reservations' => $reservationsData], 200, ['Content-Type' => 'application/json']);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las reservaciones"], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            Log::info("Entra a buscar una reservaciones");
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $reservations = Reservation::with('car.clientProfessional.professional', 'car.clientProfessional.client')->where('id', $data['id'])->get();
            return response()->json(['reservaciones' => $reservations], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las reservaciones"], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'start_time' => 'required',
                'final_hour' => 'required',
                'total_time' => 'required',
                'data' => 'required|date',
                'from_home' => 'required',
                'car_id' => 'required',
                'id' => 'required'

            ]);
            $reservacion = Reservation::find($data['id']);
            $reservacion->start_time = $data['start_time'];
            $reservacion->final_hour = $data['final_hour'];
            $reservacion->total_time = $data['total_time'];
            $reservacion->data = $data['data'];
            $reservacion->from_home = $data['from_home'];
            $reservacion->car_id = $data['car_id'];
            $reservacion->save();

            return response()->json(['msg' => 'Reservacion actualizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al actualizar la reservacion'], 500);
        }
    }

    public function reservation_send_mail()
    {
        try {
            $twoDaysLater = Carbon::now()->addDays(1)->toDateString();

            // Consultar reservas con fecha igual a dos días en adelante
            $reservations = Reservation::with(['branch', 'car.clientProfessional'])->whereDate('data', $twoDaysLater)->get();
            foreach($reservations as $reservation){
                $branchId = $reservation['branch']['id'];
                $id_client = $reservation['car']['clientProfessional']['client']['id'];
                $professional = $reservation['car']['clientProfessional']['professional'];
                $professionalName = $professional['name'].' '.$professional['surname'];
                $data = $reservation['data'];
                $startTime = $reservation['start_time'];
                $reservationId = $reservation['id'];
                $code_reserva = $reservation['code'];
                $data = [
                    'remember_reservation' => true, // Indica que es una confirmación de reserva
                    'data_reservation' => $data, // Datos de la reserva
                    'start_time' => $startTime, // Hora de inicio
                    'client_id' => $id_client, // ID del cliente
                    'branch_id' => $branchId, // ID de la sucursal
                    'type' => null, // Tipo (en este caso, se deja como null)
                    'name_professional' => $professionalName, // Nombre del profesional
                    'recipient' => null, // Destinatario (en este caso, se deja como null),                
                    'id_reservation' => $reservationId, // Destinatario (en este caso, se deja como null),
                    'code_reserva' => $code_reserva                
                ];
                
                SendEmailJob::dispatch($data);
            }

            return response()->json(['reservaciones' => 'Correos enviados'], 200, [], JSON_NUMERIC_CHECK);
        } catch (TransportException $e) {
    
            return response()->json(['msg' => 'Correos de recordar reserva enviados'], 200);
        }
          catch (\Throwable $th) {
              Log::error($th);
            
              DB::rollback();
              return response()->json(['msg' => $th->getMessage() . 'Error al hacer la reservacion'], 500);
        }
    }

    public function update_confirmation(Request $request)
    {
        try {
            $data = $request->validate([
                'confirmation' => 'required|numeric',
                'id' => 'required'

            ]);
            $msg = '';
            $reservacion = Reservation::find($data['id']);
            if($reservacion){
            if (!Carbon::parse($reservacion->data)->isToday()  && Carbon::parse($reservacion->data)->isFuture())
            {
                $reservacion->confirmation = $data['confirmation'];
                $reservacion->save();
                if($data['confirmation'] == 1 ){
                    return redirect('https://administracion.simplifies.cl/reserv/confirmation');
                    //return redirect('http://localhost:3000/reserv/confirmation');
                }
                else if($data['confirmation'] == 3 ){
                    return redirect('https://administracion.simplifies.cl/reserv/cancelation');
                    //return redirect('http://localhost:3000/reserv/cancelation');
                }
            }else{
                return redirect('https://administracion.simplifies.cl/reserv/denied');
                //return redirect('http://localhost:3000/reserv/denied');
            }
        }else{
            return redirect('https://administracion.simplifies.cl/reserv/denied');
            //return redirect('http://localhost:3000/reserv/denied');
        }
            

            //return response()->json(['msg' => $msg], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getmessage().'Error al actualizar la reservacion'], 500);
        }
    }

    public function update_confirmation_code(Request $request)
    {
        try {
            $data = $request->validate([
                'code' => 'required',                
                'branch_id' => 'required|numeric',                

            ]);
            $reservacion = Reservation::where('code', $data['code'])->where('branch_id', $data['branch_id'])->first();
            if($reservacion!=null){
                if($reservacion->confirmation == 3){
                    return response()->json(3, 200, [], JSON_NUMERIC_CHECK);
                }else{
                    $reservacion->confirmation = 4;
                    $reservacion->save();
                    return response()->json(4, 200, [], JSON_NUMERIC_CHECK);
                }
        }else{
            return response()->json(5, 200, [], JSON_NUMERIC_CHECK);
            //return redirect('http://localhost:3000/reserv/denied');
        }
            

            //return response()->json(['msg' => $msg], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getmessage().'Error al actualizar la reservacion'], 500);
        }
    }


    public function reservation_tail()
    {
        log::info('registrar las reservaciones del dia en la cola');
        try {
            $reservations = Reservation::whereDate('data', Carbon::today())
                ->whereDoesntHave('tail')
                ->orderBy('start_time')->get();
            foreach ($reservations as $reservation) {
                $cola = $reservation->tail()->create();
            }
            return response()->json(['msg' => 'Cola creada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al crear la cola'], 500);
        }
    }

    public function professional_reservationDate(Request $request)
    {
        log::info('Reservaciones de un professional en una branch y una fecha determinada');
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'data' => 'required|date'
            ]);
            $reservations = Reservation::where('branch_id', $data['branch_id'])->WhereHas('car.clientProfessional', function ($query) use ($data) {
                $query->where('professional_id', $data['professional_id']);
            })->orderBy('start_time')->whereDate('data', Carbon::parse($data['data']))->get();
            return response()->json(['reservaciones' => $reservations], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al mostrar las reservaciones en esa fecha'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'cause' => 'required|string|max:255'
            ]);
            $reservacion = Reservation::find($data['id']);
            $reservacion->cause = $request->cause;
            $reservacion->save();
            $tail = Tail::where('reservation_id', $reservacion->id);
            if ($tail) {
                $tail->delete();
            }
            $reservacion->delete();

            return response()->json(['msg' => 'Reservacion eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al eliminar la reservacion'], 500);
        }
    }

    public function reserve_noconfirm(Request $request)
    {
        try {
            /*$data = $request->validate([
                'id' => 'required|numeric'
            ]);*/
            $fechaCarbon = Carbon::now();
            $reservacionIds = Reservation::whereDate('data', $fechaCarbon)->where('confirmation', 0)->pluck('id');
            foreach ($reservacionIds as $reservacionId) {
                $reservation = Reservation::find($reservacionId);
            $reservation->cause = 'No confimada';
            $reservation->save();
            $tail = Tail::where('reservation_id', $reservation->id);
            if ($tail) {
                $tail->delete();
            }
            $reservation->delete();
            }

            return response()->json(['msg' => 'Reservacion eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al eliminar la reservacion'], 500);
        }
    }

    public function client_history(Request $request)
    {
        try {
            $data = $request->validate([
                //'branch_id' => 'required|numeric',
                'client_id' => 'required|numeric'
            ]);
            $history = $this->reservationService->client_history($data);
            return response()->json(['clientHistory' => $history], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al mostrar la history'], 500);
        }
    }
    //metodos privados
    public function send_email($data_reservation, $start_time, $client_id, $branch_id, $template, $logoUrl, $name_professional)
    {
        try {

            Log::info("Entra a send_email");
            //todo una ves que reserva envia email
            $client = Client::where('id', $client_id)->first();
            $branch = Branch::where('id', $branch_id)->first();

            if ($client) {
                $client_email = $client->email;
                $client_name = $client->name . ' ' . $client->surname;
            } else {
                // El cliente con id 5 no fue encontrado
                $client_email = null; // o manejar de acuerdo a tus necesidades
            }
            if ($branch) {
                $branch_name = $branch->name;
            } else {
                // El cliente con id 5 no fue encontrado
                $branch_name = null; // o manejar de acuerdo a tus necesidades
            }
            Log::info($client_email);
            // Puedes agregar más datos según sea necesario

            if ($client_email) {
                // Envía el correo con los datos
                $mail = new Send_mail($logoUrl, $client_name, $name_professional, $data_reservation, $template, $start_time, $branch_name, null); //falta mandar dinamicamente la sucursal
                Mail::to($client_email)
                    ->send($mail->from('reservas@simplifies.cl', 'simplifies')
                        ->subject('Confirmación de Reserva en simplifies'));

                Log::info("Enviado send_email");
            } else {
                Log::info("ERROR:El Correo es null por eso no envio el correo");
            }
            //todo *********Cerrando lógica de envio de correo**********************
            return response()->json(['Response' => "Email enviado correctamente"], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al enviar el Email"], 500);
        }
    }
}
