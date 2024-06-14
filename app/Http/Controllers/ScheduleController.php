<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            Log::info("mostrar Schedules");
            return response()->json(['Schedules' => Schedule::with(['branch'])->selectRaw("branch_id,id, day, DATE_FORMAT(start_time, '%h:%i:%p') as start_time, DATE_FORMAT(closing_time, '%h:%i:%p') as closing_time")->orderByRaw("FIELD(day, 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo')")->get()], 200, [], JSON_NUMERIC_CHECK);
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
            $schules = Schedule::where('branch_id', $SchedSchedule_data['branch_id'])->selectRaw("id, day, start_time,  closing_time")->orderByRaw("FIELD(day, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')")->get()->map(function ($query) use (&$i) {
                return [
                    'id' => $i++,
                    'day' => $query->day,
                    'start_time' => $query->start_time,
                    'closing_time' => $query->closing_time
                ];
            });
            $branch = Branch::find($SchedSchedule_data['branch_id']);
            $services = $branch->services->map(function ($service){
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price_service' => $service->price_service,
                    'type_service' => $service->type_service,
                    'profit_percentaje' => $service->profit_percentaje,                    
                    'duration_service' => $service->duration_service,
                    'image_service' => $service->image_service,
                    'service_comment' => $service->service_comment,
                    'ponderation' => $service->pivot->ponderation
                ];
            })->sortBy('name')->sortBy('ponderation')->values();
            return response()->json(['Schedules' => $schules, 'services' => $services], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar Horario"], 500);
        }
    }

    public function show_front(Request $request)
    {
        try {
            $SchedSchedule_data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $i = 0;
            $schules = Schedule::where('branch_id', $SchedSchedule_data['branch_id'])->selectRaw("id, day, start_time,  closing_time, branch_id")->orderByRaw("FIELD(day, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')")->get()->map(function ($query) use (&$i) {
                return [
                    'id' => $query->id,
                    'day' => $query->day,
                    'start_time' => $query->start_time,
                    'closing_time' => $query->closing_time,
                    'branch_id' => $query->branch_id
                ];
            });
            return response()->json(['Schedules' => $schules], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar Horario"], 500);
        }
    }

    public function show_schedule_branch(Request $request) //buscar por id branch
    {
        try {
            $SchedSchedule_data = $request->validate([
                'branch_id' => 'required|numeric' //este es el id de la branch
            ]);
            $schules = Schedule::where('branch_id', $SchedSchedule_data['branch_id'])
                ->selectRaw("id, day, start_time, closing_time, branch_id")
                ->orderByRaw("FIELD(day, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo')")
                ->get();

            // Crear un array para almacenar los días de la semana presentes en los horarios
            $presentDays = [];

            // Iterar sobre los horarios para encontrar los días presentes
            foreach ($schules as $schedule) {
                $presentDays[$schedule->day] = true;
            }

            // Crear un array de días de la semana
            $daysOfWeek = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

            // Inicializar un array para almacenar los horarios completos con los valores predeterminados
            $completeSchedule = [];

            // Iterar sobre los días de la semana y agregar los horarios completos
            foreach ($daysOfWeek as $day) {
                if (isset($presentDays[$day])) {
                    // El día está presente en los horarios, se agrega el horario existente
                    $schedule = $schules->firstWhere('day', $day);
                    $completeSchedule[] = [
                        'id' => $schedule->id,
                        'day' => $day,
                        'start_time' => $schedule->start_time,
                        'closing_time' => $schedule->closing_time
                    ];
                } else {
                    // El día no está presente en los horarios, se establece el tiempo en 00:00:00
                    $completeSchedule[] = [
                        'id' => null,
                        'day' => $day,
                        'start_time' => null,
                        'closing_time' => null
                    ];
                }
            }
            return response()->json(['Schedules' => $completeSchedule], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar Horario"], 500);
        }
    }


    public function store(Request $request)
    {
        Log::info("store");
        Log::info($request);
        try {
            /*$SchedSchedule_data = $request->validate([
                'day' => 'required|max:50|unique:schedules,day,NULL,id,branch_id,' . $request->input('branch_id'),   
                'start_time' => 'nullable',
                'closing_time' => 'nullable',
                'branch_id' => 'required|numeric',
            ]);*/

            $validator = Validator::make($request->all(), [
                'day' => 'required|max:50|unique:schedules,day,NULL,id,branch_id,' . $request->input('branch_id'),
                'start_time' => 'nullable',
                'closing_time' => 'nullable',
                'branch_id' => 'required|numeric',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 400);
            }

            $SchedSchedule = new Schedule();
            $SchedSchedule->day = $request->day;
            $SchedSchedule->start_time = $request->start_time;
            $SchedSchedule->closing_time = $request->closing_time;
            $SchedSchedule->branch_id = $request->branch_id;
            $SchedSchedule->save();

            return response()->json(['msg' => 'Horario insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al insertar Horario'], 500);
        }
    }

    public function update(Request $request)
    {
        try {

            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'schedule' => 'nullable'
            ]);
            
            Log::info("data");
            Log::info($data);
            $branch = Branch::find($data['branch_id']);

            // Iteramos sobre los horarios proporcionados
            foreach ($data['schedule'] as $scheduleData) {
                $day = $scheduleData['day'];
                $startTime = $scheduleData['start_time'];
                $closingTime = $scheduleData['closing_time'];

                // Actualizamos o creamos el registro
                $branch->schedule()->updateOrCreate(
                    ['day' => $day],
                    ['start_time' => $startTime, 'closing_time' => $closingTime]
                );
            }
            /*$SchedSchedule_data = $request->validate([
                'id' => 'required|numeric',
                'day' => 'required|max:50|',
                'start_time' => 'nullable',
                'closing_time' => 'nullable',
                'branch_id' => 'required|numeric',
            ]);
            

            $SchedSchedule = Schedule::find($SchedSchedule_data['id']);
            $SchedSchedule->day = $SchedSchedule_data['day'];

            $SchedSchedule->start_time = $SchedSchedule_data['start_time'];
            $SchedSchedule->closing_time = $SchedSchedule_data['closing_time'];
            $SchedSchedule->branch_id = $SchedSchedule_data['branch_id'];
            $SchedSchedule->save();*/

            return response()->json(['msg' => 'Horario actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => $th->getMessage() . 'Error interno del sistema'], 500);
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
