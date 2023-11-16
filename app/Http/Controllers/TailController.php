<?php

namespace App\Http\Controllers;

use App\Models\Tail;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TailController extends Controller
{
    public function index()
    {
        try { 
            
            Log::info( "entra a buscar Tail");
            $tails = Tail::with(['reservation'=> function ($query) {
                $query->orderBy('start_time');
            }])->get();
            return response()->json(['tails' => $tails], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las Tail"], 500);
        }
    }

    public function tail_up()
    {        

        try { 
            
            Log::info( "entra a buscar tail_up");
            return $this->availability();
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las tail_up"], 500);
        }
    }
    
    public function availability()
    {
        try { 
            
            Log::info( "entra a availability");

            // Obtener todas las colas (tails) ordenadas por su ID
        $tails = Tail::orderBy('id')->get();

        // Obtener los IDs de reservaciones de las colas
        $reservationIds = $tails->pluck('reservation_id');
        Log::info( " reservationIds : $reservationIds");

// Obtener todas las reservas ordenadas por id
$reservations = Reservation::whereIn('id', $reservationIds)
->orderBy('start_time')
->get();

// Inicializar un array para almacenar las diferencias y los pares de registros
$differences = [];
Log::info( "entra a buscar reservations:");
// Iterar sobre las reservas
for ($i = 1; $i < count($reservations); $i++) {
    $currentReservation = $reservations[$i];
    $previousReservation = $reservations[$i - 1];

    // Convertir cadenas de tiempo en minutos
    $startTime = strtotime($currentReservation->start_time);
    $finalHour = strtotime($previousReservation->final_hour);

    // Calcular la diferencia en minutos
    $timeDifferenceMinutes = round(($startTime - $finalHour) / 60);//round es para que devuelva en entero, aproxima por exeso

    // Almacenar el par de registros y la diferencia en minutos en el array
    $differences[] = [
        'id_registro_anterior' => $previousReservation->id,
        'id_registro_actual' => $currentReservation->id,
        'diferencia' => $timeDifferenceMinutes,
    ];
}
Log::info( "esta es desde la funtion :");
Log::info( $differences);

return response()->json(['differences' => $differences], 200);
       
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las Tail"], 500);
        }
    }


}
