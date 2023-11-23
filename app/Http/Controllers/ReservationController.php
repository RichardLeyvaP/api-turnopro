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
use App\Models\Client;
use App\Services\ServiceService;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{

    private ServiceService $serviceService;
    protected $clientProfessionalController;
    protected $branchServiceController;
    protected $branchServiceProfessionalController;
    protected $carController;
    protected $serviceController;
    protected $orderController;

    public function __construct(ClientProfessionalController $clientProfessionalController, BranchServiceController $branchServiceController, BranchServiceProfessionalController $branchServiceProfessionalController, CarController $carController, ServiceController $serviceController, OrderController $orderController, ServiceService $serviceService)
    {
        $this->clientProfessionalController = $clientProfessionalController;
        $this->branchServiceController = $branchServiceController;
        $this->branchServiceProfessionalController = $branchServiceProfessionalController;
        $this->carController = $carController;
        $this->serviceController = $serviceController;
        $this->orderController = $orderController;
        $this->serviceService = $serviceService;
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

    public function send_email(Request $request)
    {
        try {    
            $data = $request->validate([
                'email' => 'required',
            ]);         
            Log::info( "Entra a send_email");
            $client = Client::where('id', 5)->select('email')->first();

            if ($client) {
                $client_email = $client->email;
            } else {
                // El cliente con id 5 no fue encontrado
                $client_email = null; // o manejar de acuerdo a tus necesidades
            }
                  Log::info($client_email);
            $logoUrl = 'https://image.freepik.com/vector-gratis/plantilla-logotipo-barberia-vintage_441059-26.jpg'; // Reemplaza esto con la lógica para obtener la URL dinámicamente
            $icon = 'nada'; // Puedes agregar más datos según sea necesario
            $template = 'send_mail_reservation'; // Puedes agregar más datos según sea necesario
    
            // Envía el correo con los datos
            $mail = new Send_mail($logoUrl, $icon,$template);

Mail::to($data['email'])
    ->send($mail->from('correo@tuempresa.com', 'Simplify')->subject('Bienvenidos!!!'));

          
            Log::info( "Enviado send_email");
            return response()->json(['Response' => "Email enviado correctamente"], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al enviar el Email"], 500);
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
                'client_id' => 'required|numeric'
            ]);
            $servs = $request->input('services');
            $client_professional_id = $this->clientProfessionalController->client_professional($data);
           $dataCarData = [
                'data' => $data['data'],
                'client_professional_id' => $client_professional_id,
                'pay' => false,
                'active' => 1
            ];
            $car_id = $this->carController->client_professional_reservation_show($dataCarData);
            //foreach
            foreach ($servs as $serv) {
                $service_id = $serv;
                /*$dataservice = [
                    'id' => $service_id
                ];*/
                $service = $this->serviceService->show($service_id);
                $dataBranchService = [
                    'service_id' => $service_id,
                    'branch_id' => $data['branch_id']
                ];
            $branch_service_id = $this->branchServiceController->branch_service_show($dataBranchService);
            $dataBranchServiceProfessional = [
                'professional_id' => $data['professional_id'],
                'branch_service_id' => $branch_service_id
            ];
            $branch_service_professional_id = $this->branchServiceProfessionalController->branch_service_professional($dataBranchServiceProfessional);
            
            $dataOrderService = [
                'car_id' =>$car_id,
                'branch_service_professional_id' => $branch_service_professional_id,
                'product_store_id' => 0,
                'price' => $service->price_service+$service->profit_percentaje/100
            ];
            $order = $this->orderController->order_service_store($dataOrderService);
            $dataCarAmount = [
                'id' =>$car_id,
                'amount' => $order->price
            ];
            $this->carController->car_amount_updated($dataCarAmount);
            $reservation = Reservation::where('car_id', $car_id)->whereDate('data', $data['data'])->first();
            if (!$reservation) {
                $reservacion = new Reservation();
                $reservacion->start_time = Carbon::parse($data['start_time'])->toTimeString();
                $reservacion->final_hour = Carbon::parse($data['start_time'])->addMinutes($service->duration_service)->toTimeString();
                $reservacion->total_time = sprintf('%02d:%02d:%02d', floor($service->duration_service/60),$service->duration_service%60,0);
                $reservacion->data = $data['data'];
                $reservacion->from_home = 1;
                $reservacion->car_id = $car_id;
                $reservacion->save();
                }else{
                  $reservation->final_hour = Carbon::parse($reservation->final_hour)->addMinutes($service->duration_service)->toTimeString();
                  $reservation->total_time = Carbon::parse($reservation->total_time)->addMinutes($service->duration_service)->format('H:i:s');
                  $reservation->save();
                 //todo una ves que reserva envia email
                 $client = Client::where('id', $data['client_id'])->select('email')->first();

                 if ($client) {
                     $client_email = $client->email;
                 } else {
                     // El cliente con id 5 no fue encontrado
                     $client_email = null; // o manejar de acuerdo a tus necesidades
                 }
                       Log::info($client_email);
                 $logoUrl = 'https://image.freepik.com/vector-gratis/plantilla-logotipo-barberia-vintage_441059-26.jpg'; // Reemplaza esto con la lógica para obtener la URL dinámicamente
                 $icon = 'por ahora nada'; // Puedes agregar más datos según sea necesario
                 $template = 'send_mail_reservation'; // Puedes agregar más datos según sea necesario
         
                 // Envía el correo con los datos
                 $mail = new Send_mail($logoUrl, $icon,$template);
     
                Mail::to($client_email)//$data['email']
                ->send($mail->from('simplify@tuempresa.com', 'Simplify')->subject('Bienvenidos!!!'));
     
               
                 Log::info( "Enviado send_email");
                 //todo *******************************
                  



                }
            } //end foreach
            DB::commit();
            return response()->json(['msg' => 'Reservacion realizada correctamente'], 200);
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
}
