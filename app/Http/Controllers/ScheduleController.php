<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            Log::info("mostrar Schedules");
            return response()->json(['Schedules' =>Schedule::with(['branch'])->selectRaw("branch_id,id, day, DATE_FORMAT(start_time, '%h:%i:%p') as start_time, DATE_FORMAT(closing_time, '%h:%i:%p') as closing_time")->orderByRaw("FIELD(day, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo')")->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los Locales de Trabajo"], 500);
        }
    }



    public function show(Request $request)
    {
        try {
            $SchedSchedule_data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $i = 0;
             $schules = Schedule::where('branch_id', $SchedSchedule_data['branch_id'])->selectRaw("id, day, start_time,  closing_time")->orderByRaw("FIELD(day, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')")->get()->map(function ($query) use (&$i){
                return [
                    'id' => $i++,
                    'day' => $query->day,
                    'start_time' => $query->start_time,
                    'closing_time' => $query->closing_time
                ];
            });
            return response()->json(['Schedules' =>$schules], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()."Error al mostrar Horario"], 500);
        }
    }

    public function show_front(Request $request)
    {
        try {
            $SchedSchedule_data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $i = 0;
             $schules = Schedule::where('branch_id', $SchedSchedule_data['branch_id'])->selectRaw("id, day, start_time,  closing_time, branch_id")->orderByRaw("FIELD(day, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')")->get()->map(function ($query) use (&$i){
                return [
                    'id' => $query->id,
                    'day' => $query->day,
                    'start_time' => $query->start_time,
                    'closing_time' => $query->closing_time,
                    'branch_id' => $query->branch_id
                ];
            });
            return response()->json(['Schedules' =>$schules], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()."Error al mostrar Horario"], 500);
        }
    }

    public function show_schedule_branch(Request $request)//buscar por id branch
    {
         try {
             $SchedSchedule_data = $request->validate([
                 'branch_id' => 'required|numeric'//este es el id de la branch
             ]);
             $schules = Schedule::where('branch_id', $SchedSchedule_data['branch_id'])->selectRaw("id, day, start_time,  closing_time, branch_id")->orderByRaw("FIELD(day, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')")->get();
             return response()->json(['Schedules' => $schules], 200, [], JSON_NUMERIC_CHECK);
         } catch (\Throwable $th) {
             return response()->json(['msg' => "Error al mostrar Horario"], 500);
         }
    }


    public function store(Request $request)
    {
        Log::info("store");
        Log::info($request);
        try {
            $SchedSchedule_data = $request->validate([
                'day' => 'required|max:50|unique:schedules,day,NULL,id,branch_id,' . $request->input('branch_id'),   
                'start_time' => 'nullable',
                'closing_time' => 'nullable',
                'branch_id' => 'required|numeric',
            ]);

            $SchedSchedule = new Schedule();
            $SchedSchedule->day = $SchedSchedule_data['day'];            
            $SchedSchedule->start_time = $SchedSchedule_data['start_time'];
            $SchedSchedule->closing_time = $SchedSchedule_data['closing_time'];
            $SchedSchedule->branch_id = $SchedSchedule_data['branch_id'];
            $SchedSchedule->save();

            return response()->json(['msg' => 'Horario insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage().'Error al insertar Horario'], 500);
        }
    }

    public function update(Request $request)
    {
        try {

            Log::info("Editar");
            Log::info($request);
            $SchedSchedule_data = $request->validate([
                'id' => 'required|numeric',
                'day' => 'required|max:50|', 
                'start_time' => 'nullable',
                'closing_time' => 'nullable',
                'branch_id' => 'required|numeric',
            ]);

            $SchedSchedule =Schedule::find($SchedSchedule_data['id']);
            $SchedSchedule->day = $SchedSchedule_data['day'];
            
            $SchedSchedule->start_time = $SchedSchedule_data['start_time'];
            $SchedSchedule->closing_time = $SchedSchedule_data['closing_time'];
            $SchedSchedule->branch_id = $SchedSchedule_data['branch_id'];
            $SchedSchedule->save();

            return response()->json(['msg' => 'Horario actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $SchedSchedule_data = $request->validate([
                'id' => 'required|numeric'
            ]);
           Schedule::destroy($SchedSchedule_data['id']);

            return response()->json(['msg' => 'Horario eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar Horario'], 500);
        }
    }



}
