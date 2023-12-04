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
use App\Models\Client;
use App\Models\User;
use App\Services\ReservationService;
use App\Services\SendEmailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ReservationController extends Controller
{

    private ReservationService $reservationService;
    private SendEmailService $sendEmailService;

    public function __construct(ReservationService $reservationService,SendEmailService $sendEmailService )
    {
        $this->reservationService = $reservationService;
        $this->sendEmailService = $sendEmailService;
    }

    public function index()
    {
        try {             
            Log::info( "Entra a buscar las reservaciones");
            $reservations = Reservation::with('car.clientProfessional.professional', 'car.clientProfessional.client')->get();
            return response()->json(['reservaciones' => $reservations], 200);
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
            $sumaDuracion = $orderServicesDatas->sum(function ($orderServicesData){
                return $orderServicesData->branchServiceProfessional->branchService->service->duration_service;
            });
            $reservacion = new Reservation();
            $reservacion->start_time = Carbon::parse($data['start_time'])->toTimeString();
            $reservacion->final_hour = Carbon::parse($data['start_time'])->addMinutes($sumaDuracion)->toTimeString();
            $reservacion->total_time = sprintf('%02d:%02d:%02d', floor($sumaDuracion/60),$sumaDuracion%60,0);
            $reservacion->data = $data['data'];
            $reservacion->from_home = $data['from_home'];
            $reservacion->car_id = $data['car_id'];
            $reservacion->save();

            return response()->json(['msg' => 'Reservacion realizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error al hacer la reservacion'], 500);
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
            $servs = $request->input('services');  
            $id_client=0;
            //1-Verificar que el usuario no este registrado
            $user = User::where('email', $data['email_client'])->first();
             // Verificar si se encontró un usuario
            if ($user) {
                Log::info( "1");
            // Buscar el cliente
            $client = Client::where('email', $data['email_client'])->first();
            if($client)
            {
                  Log::info( "2");
                $id_client = $client->id;
                $this->reservationService->store($data, $servs,$id_client);
            }
           } 
           else {
                 Log::info( "3");
            // Crear Usuario
            $user = User::create([
                'name' => $data['name_client'],
                'email' => $data['email_client'],
                'password' => Hash::make($data['email_client'])
            ]);
            Log::info( "4");

            $client = new Client();
            $client->name = $data['name_client'];
            $client->surname = $data['surname_client'];
            $client->second_surname = $data['second_surname'];
            $client->email = $data['email_client'];
            $client->phone = $data['phone_client'];
            $client->user_id = $user->id;
            $client->save();
            $id_client = $client->id;

            Log::info( "5");
            Log::info($id_client);
                $this->reservationService->store($data, $servs,$id_client);

            }
                               
            DB::commit(); 

              // SI la fecha con la que se registró es igual a la fecha de hoy llamar actualizar la cola del dia de hoy
              Log::info( "5.comparando fechas");
              

              $fechaHoy = Carbon::today();
            // Obtener la fecha formateada como 'YYYY-MM-DD'
            $fechaFormateada = $fechaHoy->toDateString();
            Log::info( $data['data']);
                        Log::info( $fechaFormateada);

              if(($data['data'] == $fechaFormateada ))
              {
                Log::info( "5.las fechas son iguales");
                $this->reservation_tail();
                Log::info( "5.actualice la cola");
              }
                     
           
            //todo *************** llamando al servicio de envio de email *******************
                $this->sendEmailService->confirmReservation($data['data'],$data['start_time'],$id_client,$data['branch_id']);
                
            return response()->json(['msg' => 'Reservación realizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            DB::rollback();
        return response()->json(['msg' => $th->getMessage().'Error al hacer la reservacion'], 500);
        }
    }

    public function professional_reservations(Request $request){
        try {             
            Log::info( "Entra a buscar las reservaciones de un professionals en una fecha dada");
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'data' => 'required|date'
            ]);
            $reservations = Reservation::WhereHas('car.clientProfessional', function ($query) use ($data){
                $query->where('professional_id', $data['professional_id']);
            })->whereHas('car.clientProfessional.professional.branchServices', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            })->whereBetween('data', [$data['data'], Carbon::parse($data['data'])->addDays(7)])->orderBy('data')->orderBy('start_time')->get();
            return response()->json(['reservaciones' => $reservations], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las reservaciones"], 500);
        }
    }

    public function show(Request $request)
    {
        try {             
            Log::info( "Entra a buscar una reservaciones");
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $reservations = Reservation::with('car.clientProfessional.professional', 'car.clientProfessional.client')->where('id', $data['id'])->get();
            return response()->json(['reservaciones' => $reservations], 200);
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

    public function reservation_tail()
    {
        log::info('registrar las reservaciones del dia en la cola');
        try {
            $reservations = Reservation::whereDate('data', Carbon::today())
            ->whereDoesntHave('tail')
            ->orderBy('start_time')->get();
            foreach($reservations as $reservation){
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
            $reservations = Reservation::WhereHas('car.clientProfessional', function ($query) use ($data){
                $query->where('professional_id', $data['professional_id']);
            })->whereHas('car.clientProfessional.professional.branchServices', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            })->orderBy('start_time')->whereDate('data', Carbon::parse($data['data']))->get();
            return response()->json(['reservaciones' => $reservations], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error al mostrar las reservaciones en esa fecha'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $reservacion = Reservation::find($data['id']);
            $reservacion->delete();

            return response()->json(['msg' => 'Reservacion eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar la reservacion'], 500);
        }
    }

    //metodos privados
    public function send_email($data_reservation,$start_time,$client_id,$branch_id,$template,$logoUrl)
    {
        try {    
                     
            Log::info( "Entra a send_email");
                            //todo una ves que reserva envia email
                            $client = Client::where('id', $client_id)->first();
                            $branch = Branch::where('id', $branch_id)->first();
           
                            if ($client) {
                                $client_email = $client->email;
                                $client_name = $client->name.' '.$client->surname;
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
                            
                            if($client_email){
                               // Envía el correo con los datos
                               $mail = new Send_mail($logoUrl, $client_name,$data_reservation,$template,$start_time,$branch_name);//falta mandar dinamicamente la sucursal
                               Mail::to($client_email)
                               ->send($mail->from('reservas@simplifies.cl', 'simplifies')
                                           ->subject('Confirmación de Reserva en simplifies'));       
                             
                               Log::info( "Enviado send_email");
           
                            }
                            else
                            {
                               Log::info( "ERROR:El Correo es null por eso no envio el correo"); 
                            }
                             //todo *********Cerrando lógica de envio de correo**********************
            return response()->json(['Response' => "Email enviado correctamente"], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al enviar el Email"], 500);
        }
    }
}
