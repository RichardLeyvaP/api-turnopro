<?php

namespace App\Http\Controllers;

use App\Models\Tail;
use App\Models\Reservation;
use App\Services\TailService;
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

    public function tail_up(Request $request)
    {        

        try { 
            
            Log::info( "entra a availability");
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'data' => 'required'
            ]);
            $idP = $data['professional_id'];
            $id_branch =1;
            Log::info( $idP);


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

            Log::info( $reservations);
            Log::info( 'sisiisisis este es el resultado');

            $differences = [];
        Log::info( "entra a a calcular la diferencia:");
        // Iterar sobre las reservas
        for ($i = 0; $i < count($reservations); $i++) {
            $currentReservation = $reservations[$i];

            // Convertir cadenas de tiempo en minutos
            $startTime = strtotime($currentReservation->start_time);
            $finalHour = strtotime($currentReservation->final_hour);

            // Calcular la diferencia en minutos
            $timeDifferenceMinutes = round(($finalHour - $startTime) / 60);//round es para que devuelva en entero, aproxima por exeso

            // Almacenar el par de registros y la diferencia en minutos en el array
            
            $differences[] = [
                'time_available_start' => $currentReservation->start_time ,
                'time_available_final' =>$currentReservation->final_hour ,
                'service_time_vailable' => $timeDifferenceMinutes,
            ];
        }
        Log::info( "esta es desde la funtion :");
        Log::info( $differences);
        return response()->json(['Reservation' => $differences], 200);

            
                } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las tail_up"], 500);
        }
    }
    
    public function availability(Request $request)
    {
        try { 
            
            Log::info( "entra a availability");
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'data' => 'required'
            ]);

            // Obtener todas las colas (tails) ordenadas por su ID_reservacion
            $tails = Reservation::with(['car.clientProfessional.professional'=> function ($query,$data) {
                $query->where('id', $data['professional_id']);
            }])->whereDate('data', $data['data'])->get();

            Log::info( $tails);
            Log::info( 'sisiisisis');

            
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
            
            Log::info( "Mostarr la cola del dia de una branch");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            
            return response()->json(['tail' => $this->tailService->cola_branch_data($data['branch_id'])], 200);
                } catch (\Throwable $th) {  
                    Log::error($th);
                    return response()->json(['msg' => $th->getMessage()."Error al mostrar las Tail"], 500);
                } 
    }

    public function cola_branch_capilar(Request $request)
    {
        try { 
            
            Log::info( "Mostarr la cola de servicio capilar del dia de una branch");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            
            return response()->json(['tail' => $this->tailService->cola_branch_capilar($data['branch_id'])], 200);
                } catch (\Throwable $th) {  
                    Log::error($th);
                    return response()->json(['msg' => $th->getMessage()], 500);
                } 
    }

    public function cola_branch_delete(Request $request)
    {
        try { 
            
            Log::info( "Mostarr la cola del dia de una branch");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
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
            
            Log::info( "Mostarr la cola del dia de una branch");
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);

            
            return response()->json(['tail' => $this->tailService->cola_branch_professional($data['branch_id'], $data['professional_id'])], 200);
                } catch (\Throwable $th) {  
                    Log::error($th);
                    return response()->json(['msg' =>"Error al mostrar las Tail"], 500);
                } 
    }

    public function type_of_service(Request $request)
    {
        try { 
            
            Log::info( "Mostarr la cola del dia de una branch");
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
            
            Log::info( "Modificar estado de la Cola");
            $data = $request->validate([
                'reservation_id' => 'required|numeric',
                'attended' => 'required|numeric'
            ]);
            $this->tailService->tail_attended($data['reservation_id'], $data['attended']);
            
            return response()->json(['msg' => "Cola modificado correctamente"], 200);
            } catch (\Throwable $th) {  
                Log::error($th);
                    return response()->json(['msg' => $th->getMessage()."Error al mostrar las Cola"], 500);
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

            return response()->json($attended, 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error al mostrar el estado de la reservacion'], 500);
        }
    }

    public function cola_truncate()
    {
        try { 
            
            Log::info( "Mandar a eliminar la cola");
            Tail::truncate();
            return response()->json(['msg' => "Cola eliminada correctamente"], 200);
                } catch (\Throwable $th) {  
                    Log::error($th);
                    return response()->json(['msg' => "Error al eliminar la Tail"], 500);
                } 
    }

    public function set_clock(Request $request)
    {
        try {

            Log::info("Modificar estado del relock");
            Log::info($request);
            $data = $request->validate([
                'id' => 'required|numeric',
                'clock' => 'required|numeric'
            ]);

            $tail = Tail::find($data['id']);
            $tail->clock = $data['clock'];
            $tail->save();
            return response()->json(['msg' => 'Estado del reloj modificado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => 'Error al modificar el estado del reloj'], 500);
        }
    }

    public function get_clock(Request $request)
    {
        try {

            Log::info("Modificar estado del relock");
            Log::info($request);
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);

            return response()->json(Tail::where('id',$data['id'])->value('clock'), 200);
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => 'Error al modificar el estado del reloj'], 500);
        }
    }

}
