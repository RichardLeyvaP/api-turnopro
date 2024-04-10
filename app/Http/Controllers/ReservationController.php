<?php

namespace App\Http\Controllers;


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
                'name_client' => 'required',
                'surname_client' => 'required',
                'second_surname' => 'required',
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
                    $this->reservationService->store($data, $servs, $id_client);
                } else {
                    Log::info("Si no existe registrarlo");

                    $client = new Client();
                    $client->name = $data['name_client'];
                    $client->surname = $data['surname_client'];
                    $client->second_surname = $data['second_surname'];
                    $client->email = $data['email_client'];
                    $client->phone = $data['phone_client'];
                    $client->user_id = $user->id;
                    $client->client_image = 'comments/default.jpg';
                    $client->save();
                    $id_client = $client->id;

                    Log::info("Id que tiene");
                    Log::info($id_client);
                    $this->reservationService->store($data, $servs, $id_client);
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
                $client->surname = $data['surname_client'];
                $client->second_surname = $data['second_surname'];
                $client->email = $data['email_client'];
                $client->phone = $data['phone_client'];
                $client->client_image = 'comments/default.jpg';
                $client->user_id = $user->id;
                $client->save();
                $id_client = $client->id;

                Log::info("Id que obtuvo");
                Log::info($id_client);
                $this->reservationService->store($data, $servs, $id_client);
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

            //optener nombre del professional
            $professional = Professional::find($data['professional_id']);
            $name = $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname;
            //todo *************** llamando al servicio de envio de email *******************
            $this->sendEmailService->confirmReservation($data['data'], $data['start_time'], $id_client, $data['branch_id'], null, $name);

            return response()->json(['msg' => 'Reservación realizada correctamente'], 200);
        } catch (\Throwable $th) {
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
                $reservations = Reservation::where('branch_id', $data['branch_id'])->whereDate('data', now()->toDateString())->count();
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
                $reservations = Reservation::whereDate('data', now()->toDateString())->count();
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
            if ($data['branch_id'] != 0) {
                Log::info('Es una Sucursal');
                $start = now()->startOfWeek(); // Start of the current week, shifted to Monday
                $end = now()->endOfWeek();
                //$business = Business::find($data['business_id']);
                /*$reservations = Branch::where('id', $data['branch_id'])->whereHas('reservations.car.orders.branchServiceProfessional.branchService', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id']);
                })
                    ->with(['reservations' => function ($query) use ($start, $end, $data) {
                        $query->whereBetween('data', [$start, $end]);
                    }])
                    ->get()
                    ->flatMap(function ($branch) use ($data, $start, $end) {
                        return $branch->reservations->where('laravel_through_key', $data['branch_id'])->whereBetween('data', [$start, $end]);
                    });*/
                    $reservations = Reservation::where('branch_id', $data['branch_id'])->whereDate('data', '>=', $start)->whereDate('data', '<=', $end)->get();

                // Inicializar un array para contabilizar las reservaciones por día
                $reservationsByDay = array_fill(0, 7, 0);

                // Contar las reservaciones por día
                //$uniqueReservations = $reservations->unique('id'); // Eliminar duplicados por ID de reserva
                foreach ($reservations as $reservation) {
                    $reservationDate = new DateTime($reservation->data);
                    $dayOfWeek = ($reservationDate->format('N') + 5) % 7; // Ajuste para que el lunes sea el día 0
                    $reservationsByDay[$dayOfWeek]++;
                }

                // Llenar los días faltantes con 0
                $fullWeek = array_replace(array_fill(0, 7, 0), $reservationsByDay);

                $reservations = $fullWeek;
            } else {
                Log::info("Business");
                $start = now()->startOfWeek(); // Start of the current week, shifted to Monday
                $end = now()->endOfWeek();
                $reservations = Reservation::whereDate('data', '>=', $start)->whereDate('data', '<=', $end)->get();
                /*$business = Business::find($data['business_id']);
                $reservations = $business->branches()->with(['reservations' => function ($query) use ($start, $end) {
                    $query->whereBetween('data', [$start, $end]);
                }])
                    ->get()
                    ->flatMap(function ($branch) use ($data) {
                        return $branch->reservations;
                    });
*/
                // Inicializar un array para contabilizar las reservaciones por día
                $reservationsByDay = array_fill(0, 7, 0);

                // Contar las reservaciones por día
                //$uniqueReservations = $reservations->unique('id'); // Eliminar duplicados por ID de reserva
                foreach ($reservations as $reservation) {
                    $reservationDate = new DateTime($reservation->data);
                    $dayOfWeek = ($reservationDate->format('N') + 5) % 7; // Ajuste para que el lunes sea el día 0
                    $reservationsByDay[$dayOfWeek]++;
                }

                // Llenar los días faltantes con 0
                $fullWeek = array_replace(array_fill(0, 7, 0), $reservationsByDay);

                $reservations = $fullWeek;
            }

            $reservationsString = implode(',', $reservations);
            return response($reservationsString, 200, ['Content-Type' => 'application/json']);
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

    public function update_confirmation(Request $request)
    {
        try {
            $data = $request->validate([
                'confirmation' => 'required|numeric',
                'id' => 'required'

            ]);
            $reservacion = Reservation::find($data['id']);
            $reservacion->confirmation = $data['confirmation'];
            $reservacion->save();

            return response()->json(['msg' => 'Reservacion confirmada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al actualizar la reservacion'], 500);
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
            if($tail){
                $tail->delete();
            }
            $reservacion->delete();

            return response()->json(['msg' => 'Reservacion eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al eliminar la reservacion'], 500);
        }
    }

    public function client_history(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
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
