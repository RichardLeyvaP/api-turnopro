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

class ReservationController extends Controller
{
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

    public function send_email()
    {
        try {             
            Log::info( "Entra a send_email");
            Mail::to('richardleyvap1991@gmail.com')->send(new Send_mail);
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
