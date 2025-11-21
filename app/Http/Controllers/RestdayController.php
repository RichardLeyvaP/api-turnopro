<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\Restday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RestdayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
 * Obtiene los días de descanso de un profesional.
 *
 * Devuelve los 7 días de la semana, indicando si tiene descanso (`state = 1`) o no (`state = 0`).
 *
 * @authenticated
 * @queryParam professional_id integer required ID del profesional. Example: 123
 *
 * @response 200 {
 *   "Schedules": [
 *     {
 *       "id": 1,
 *       "day": "Lunes",
 *       "state": 0
 *     },
 *     {
 *       "id": 2,
 *       "day": "Martes",
 *       "state": 1
 *     },
 *     {
 *       "id": null,
 *       "day": "Miércoles",
 *       "state": 0
 *     },
 *     // ... resto de días
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar Horario"}
 */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric' //este es el id de la branch
            ]);
            $schules = Restday::where('professional_id', $data['professional_id'])
                ->selectRaw("id, day, state, professional_id")
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
                        'state' => $schedule->state,
                    ];
                } else {
                    // El día no está presente en los horarios, se establece el tiempo en 00:00:00
                    $completeSchedule[] = [
                        'id' => null,
                        'day' => $day,
                        'state' => 0
                    ];
                }
            }
            return response()->json(['Schedules' => $completeSchedule], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar Horario"], 500);
        }
    }

    /**
 * Actualiza los días de descanso de un profesional.
 *
 * Recibe un arreglo de días con su estado y sincroniza los registros en la base de datos.
 *
 * @authenticated
 * @bodyParam professional_id integer required ID del profesional. Example: 123
 * @bodyParam schedule array required Lista de días con estado.
 * @bodyParam schedule.*.day string required Nombre del día (Lunes a Domingo). Example: Lunes
 * @bodyParam schedule.*.state integer required 1 = día de descanso, 0 = día laborable. Example: 1
 *
 * @response 200 {"msg": "Diaas de descanso actualizado correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function update(Request $request, Restday $restday)
    {
        try {

            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'schedule' => 'nullable'
            ]);
            
            $professional = Professional::find($data['professional_id']);

            // Iteramos sobre los horarios proporcionados
            foreach ($data['schedule'] as $scheduleData) {
                $day = $scheduleData['day'];
                $state = $scheduleData['state'];

                // Actualizamos o creamos el registro
                $professional->restdays()->updateOrCreate(
                    ['day' => $day],
                    ['state' => $state]
                );
            }

            return response()->json(['msg' => 'Diaas de descanso actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error interno del sistema'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Restday $restday)
    {
        //
    }
}
