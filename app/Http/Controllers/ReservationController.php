<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Entra a buscar las reservaciones");
            $reservations = Reservation::with('client', 'branchServiceProfessional.professional')->get();
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
                'final_hour' => 'required',
                'client_id' => 'required|numeric',
                'branch_service_professional_id' => 'required|numeric'
            ]);
            $reservacion = new Reservation();
            $reservacion->start_time = $data['start_time'];
            $reservacion->final_hour = $data['final_hour'];
            $reservacion->client_id = $data['client_id'];
            $reservacion->branch_service_professional_id = $data['branch_service_professional_id'];
            $reservacion->save();

            return response()->json(['msg' => 'Reservacion realizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error al hacer la reservacion'], 500);
        }
    }


    public function show(Request $request)
    {
        try {             
            Log::info( "Entra a buscar una reservaciones");
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $reservations = Reservation::with('client', 'branchServiceProfessional.professional')->where('id', $data['id'])->get();
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
                'client_id' => 'required|numeric',
                'branch_service_professional_id' => 'required|numeric',
                'id' => 'required'
            ]);
            $reservacion = Reservation::find($data['id']);
            $reservacion->start_time = $data['start_time'];
            $reservacion->final_hour = $data['final_hour'];
            $reservacion->client_id = $data['client_id'];
            $reservacion->branch_service_professional_id = $data['branch_service_professional_id'];
            $reservacion->save();

            return response()->json(['msg' => 'Reservacion actualizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error al actualizar la reservacion'], 500);
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
