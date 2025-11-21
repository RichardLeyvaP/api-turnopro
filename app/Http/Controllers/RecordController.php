<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchProfessional;
use App\Models\BranchRuleProfessional;
use App\Models\Professional;
use App\Models\Record;
use App\Models\Schedule;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class RecordController extends Controller
{
   
    /**
 * Lista todos los registros de entrada/salida de profesionales por sucursal.
 *
 * @authenticated
 *
 * @response 200 {
 *   "records": [
 *     {
 *       "id": 1,
 *       "professional_id": 123,
 *       "branch_id": 5,
 *       "start_time": "2025-11-21 08:30:00",
 *       "end_time": "2025-11-21 18:00:00",
 *       "professional": { "name": "Yasmany" },
 *       "branch": { "name": "Centro" }
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar el historial de records"}
 */
    public function index()
    {
        try {
            return response()->json(['records' => Record::with(['branch', 'professional'])->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el historial de records"], 500);
        }
    }

/**
 * Registra la entrada de un profesional en una sucursal (marca hora de inicio).
 *
 * Asigna número de llegada y activa estado para coordinadores/encargados.
 *
 * @authenticated
 * @bodyParam professional_id integer required ID del profesional. Example: 123
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {"msg": "Record creado correctamente"}
 * @response 200 {"msg": "Ya registró entrada en el día de hoy"}
 * @response 500 {"msg": "Error al crear un record: ..."}
 */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validación de los datos de entrada
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
            ]);

            // Verificar si ya existe un registro para hoy
            $record = Record::where('branch_id', $data['branch_id'])
                ->where('professional_id', $data['professional_id'])
                ->whereDate('start_time', Carbon::now())
                ->first();

                // Obtener el número máximo de llegada para la sucursal dada
                $maxArrival = BranchProfessional::where('branch_id', $data['branch_id'])->max('arrival');

                // Si no hay valores, inicializar a 0
                if (is_null($maxArrival)) {
                    $maxArrival = 0;
                }

                // Encontrar el registro específico y actualizar el campo arrival
                $branchProfessional = BranchProfessional::where('branch_id', $data['branch_id'])
                                                        ->where('professional_id', $data['professional_id'])
                                                        ->firstOrFail();

                // Asignar el siguiente número de llegada
                $branchProfessional->arrival = $maxArrival + 1;

                // Guardar los cambios
                $branchProfessional->save();

                $professional = Professional::find($data['professional_id']);
                $charge = $professional->charge->name;
                if($charge == 'Barbero' || $charge == 'Tecnico' || $charge == 'Barbero y Encargado'){
                            //return $user->professional->branchRules->where('branch_id', $request->branch_id);
                           $professionalRules = $professional->branchRules()
                            ->where('branch_id', $request->branch_id)
                            ->get()->map->pivot->where('data', Carbon::now()->toDateString());
                            if ($professionalRules->isEmpty()) {
                                $branchRules = Branch::find($request->branch_id);
                                $branchRulesId = $branchRules->rules()->withPivot('id')->get()->map->pivot->pluck('id');
                                $professional->branchRules()->attach($branchRulesId, ['data' => Carbon::now()->toDateString(), 'estado' => 3]);
                            }
                        }//if del cargo

            if (!$record) {
                // Obtener el nombre del día en español
                $nombreDia = ucfirst(strtolower(Carbon::now()->locale('es_ES')->dayName));
                $startTime = Schedule::where('branch_id', $data['branch_id'])
                    ->where('day', $nombreDia)
                    ->value('start_time');
                if ($startTime) {
                    $startTimeCarbon = Carbon::createFromFormat('H:i:s', $startTime);
                    $currentTime = Carbon::now();
                    $branchRuleProfessionals = BranchRuleProfessional::whereHas('branchRule', function ($query) use ($data) {
                        $query->where('branch_id', $data['branch_id'])
                            ->whereHas('rule', function ($query) {
                                $query->where('type', 'Puntualidad')
                                    ->where('automatic', 1);
                            });
                    })->where('professional_id', $data['professional_id'])
                        ->whereDate('data', Carbon::now())
                        ->orderByDesc('data')
                        ->first();
                    // Comparar la hora actual con la hora de inicio
                    if ($currentTime->greaterThan($startTimeCarbon)) {

                        if ($branchRuleProfessionals) {
                            $branchRuleProfessionals->estado = 0;
                            $branchRuleProfessionals->save();
                        }
                    } else {
                        if ($branchRuleProfessionals) {
                            $branchRuleProfessionals->estado = 1;
                            $branchRuleProfessionals->save();
                        }
                    }
                }

                // Crear un nuevo registro
                $record = new Record();
                $record->professional_id = $data['professional_id'];
                $record->branch_id = $data['branch_id'];
                $record->start_time = Carbon::now();
                $record->save();
                $professional = Professional::find($data['professional_id']);
                if ($professional->charge->name == 'Coordinador' || $professional->charge->name == 'Encargado') {
                    $professional->state = 1;
                    $professional->save();
                }
                DB::commit();
                return response()->json(['msg' => 'Record creado correctamente'], 200);
            } else {
                $professional = Professional::find($data['professional_id']);
                if ($professional->charge->name == 'Coordinador' || $professional->charge->name == 'Encargado') {
                    $professional->state = 1;
                    $professional->save();
                }
                DB::commit();
                return response()->json(['msg' => 'Ya registró entrada en el día de hoy'], 200);
            }
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['msg' => 'Error al crear un record: ' . $th->getMessage()], 500);
        }
    }

   /**
 * Obtiene los registros de un profesional en una sucursal (sin filtrar por fecha).
 *
 * ⚠️ Este método actualmente **no devuelve resultados** porque le falta `->get()`.
 * Corrige la línea:
 * ```php
 * return response()->json(['records' => Record::with('professional', 'branch')->where('branch_id', $branch_data['branch_id'])->get()], 200);
 * ```
 *
 * @authenticated
 * @queryParam professional_id integer required ID del profesional. Example: 123
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {"records": [...]}
 * @response 500 {"msg": "Error al mostrar la sucursal"}
 */
    public function show(Request $request)
    {
        try {
            $branch_data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            return response()->json(['records' => Record::with('professional', 'branch')->where('branch_id', $branch_data['branch_id'])], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar la sucursal"], 500);
        }
    }

    public function record_show_professional(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $recordProfessional = Record::where('branch_id', $data['branch_id'])
        ->where('professional_id', $data['professional_id'])
        ->whereDate('start_time', Carbon::today())
        ->first();

            if ($recordProfessional != null) {
                return 1;
            } else {
                return 0;
            }
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getmessage() . "Error interno del sistema"], 500);
        }
    }

    /**
 * Registra la salida de un profesional en una sucursal (marca hora de fin).
 *
 * @authenticated
 * @bodyParam professional_id integer required ID del profesional. Example: 123
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {"msg": "Record creado correctamente"}
 * @response 500 {"msg": "Error al crear un record"}
 */
    public function update(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);

            $record = Record::where('branch_id', $data['branch_id'])->where('professional_id', $data['professional_id'])->whereDate('start_time', Carbon::now())->first();
            if($record != null){                
            $record->end_time = Carbon::now();
            $record->save();
            }
            $professional = Professional::find($data['professional_id']);
                if ($professional->charge->name == 'Coordinador' || $professional->charge->name == 'Encargado') {
                    $professional->state = 0;
                    $professional->save();
                }
                DB::commit();
            return response()->json(['msg' => 'Record creado correctamente'], 200);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['msg' => $th->getMessage() . 'Error al crear un record'], 500);
        }
    }

    /**
 * Elimina un registro de entrada/salida.
 *
 * @authenticated
 * @bodyParam id integer required ID del registro. Example: 1
 *
 * @response 200 {"msg": "Record eliminado correctamente"}
 * @response 500 {"msg": "Error al eliminar el record"}
 */
    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            //$branch = Record::find($data['id']);

            Record::destroy($data['id']);

            return response()->json(['msg' => 'Record eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el record'], 500);
        }
    }

    /**
 * Obtiene los profesionales que llegaron tarde en un **rango de fechas** en una sucursal.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam startDate string required Fecha de inicio (Y-m-d). Example: 2025-11-01
 * @queryParam endDate string required Fecha de fin (Y-m-d). Example: 2025-11-30
 *
 * @response 200 [ ... ]
 * @response 500 {"msg": "Error al eliminar el record"}
 */
    public function arriving_late_branch_periodo(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
            ]);
            $llegadasTardias = Record::withCount('professional')->with('professional')
                ->where('branch_id', $data['branch_id'])
                ->whereDate('start_time', '>=', $request->startDate)->whereDate('start_time', '<=', $request->endDate) //->whereBetween('start_time', [$request->startDate, $request->endDate])
                ->get()
                ->filter(function ($registro) use ($data) {
                    // Obtiene el nombre del día de la semana en español
                    //$diaSemana = $registro->start_time->formatLocalized('%A');
                    $diaSemana = new DateTime($registro->start_time);
                    $nombreDia = $diaSemana->format('l');
                    // Días de la semana en español
                    $diasSemanaEspañol = [
                        'Monday' => 'Lunes',
                        'Tuesday' => 'Martes',
                        'Wednesday' => 'Miércoles',
                        'Thursday' => 'Jueves',
                        'Friday' => 'Viernes',
                        'Saturday' => 'Sábado',
                        'Sunday' => 'Domingo',
                    ];

                    // Reemplazamos el día de la semana en inglés por su equivalente en español
                    $diaSemanaEspañol = $diasSemanaEspañol[$nombreDia];
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                    // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    // Considera llegada tardía si la hora de inicio del registro es después del horario de inicio de la sucursal
                    return Carbon::parse($registro->start_time)->format('H:i') > $schedule->start_time;
                })
                ->groupBy('professional_id')
                ->map(function ($group) {
                    return [
                        'professional_id' => $group->first()->professional_id,
                        'name' => $group->first()->professional->name . ' ' . $group->first()->professional->surname . ' ' . $group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,
                        'cant' => $group->sum('professional_count')
                    ];
                })
                ->sortByDesc('cant')
                ->values();
            
            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al eliminar el record'], 500);
        }
    }

    /**
 * Obtiene los profesionales que llegaron tarde **hoy** en una sucursal.
 *
 * Se compara con el horario de apertura del día.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 [
 *   {
 *     "professional_id": 123,
 *     "name": "Yasmany Sánchez Martínez",
 *     "image_url": "professionals/123.jpg",
 *     "charge": "Barbero",
 *     "cant": 1
 *   }
 * ]
 * @response 500 {"msg": "Error al mostrar las llegadas tardes"}
 */
    public function arriving_late_branch_date(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
            ]);
            $today = Carbon::now(); // Incluye toda la jornada del último día
            $llegadasTardias = Record::withCount('professional')->with('professional')
                ->where('branch_id', $data['branch_id'])
                ->whereDate('start_time', $today)
                ->get()
                ->filter(function ($registro) use ($data) {
                    // Obtiene el nombre del día de la semana en español
                    //$diaSemana = $registro->start_time->formatLocalized('%A');
                    $diaSemana = new DateTime($registro->start_time);
                    $nombreDia = $diaSemana->format('l');
                    // Días de la semana en español
                    $diasSemanaEspañol = [
                        'Monday' => 'Lunes',
                        'Tuesday' => 'Martes',
                        'Wednesday' => 'Miércoles',
                        'Thursday' => 'Jueves',
                        'Friday' => 'Viernes',
                        'Saturday' => 'Sábado',
                        'Sunday' => 'Domingo',
                    ];

                    // Reemplazamos el día de la semana en inglés por su equivalente en español
                    $diaSemanaEspañol = $diasSemanaEspañol[$nombreDia];
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                    // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    // Considera llegada tardía si la hora de inicio del registro es después del horario de inicio de la sucursal
                    return Carbon::parse($registro->start_time)->format('H:i') > $schedule->start_time;
                })
                ->groupBy('professional_id')
                ->map(function ($group) {
                    return [
                        'professional_id' => $group->first()->professional_id,
                        'name' => $group->first()->professional->name . ' ' . $group->first()->professional->surname . ' ' . $group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,
                        'cant' => $group->sum('professional_count')
                    ];
                })
                ->sortByDesc('cant')
                ->values();
            
            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al mostrar las llegadas tardes'], 500);
        }
    }

    /**
 * Obtiene los profesionales que llegaron tarde en un **mes y año** en una sucursal.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam mes integer required Mes (1–12). Example: 11
 * @queryParam year integer required Año. Example: 2025
 *
 * @response 200 [ ... ]
 * @response 500 {"msg": "Error al eliminar el record"}
 */
    public function arriving_late_branch_month(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
            ]);
            $llegadasTardias = Record::withCount('professional')->with('professional')
                ->where('branch_id', $data['branch_id'])
                ->whereMonth('start_time', $request->mes)->whereYear('start_time', $request->year)
                ->get()
                ->filter(function ($registro) use ($data) {
                    // Obtiene el nombre del día de la semana en español
                    //$diaSemana = $registro->start_time->formatLocalized('%A');
                    $diaSemana = new DateTime($registro->start_time);
                    $nombreDia = $diaSemana->format('l');
                    // Días de la semana en español
                    $diasSemanaEspañol = [
                        'Monday' => 'Lunes',
                        'Tuesday' => 'Martes',
                        'Wednesday' => 'Miércoles',
                        'Thursday' => 'Jueves',
                        'Friday' => 'Viernes',
                        'Saturday' => 'Sábado',
                        'Sunday' => 'Domingo',
                    ];

                    // Reemplazamos el día de la semana en inglés por su equivalente en español
                    $diaSemanaEspañol = $diasSemanaEspañol[$nombreDia];
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                    // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    // Considera llegada tardía si la hora de inicio del registro es después del horario de inicio de la sucursal
                    return Carbon::parse($registro->start_time)->format('H:i') > $schedule->start_time;
                })
                ->groupBy('professional_id')
                ->map(function ($group) {
                    return [
                        'professional_id' => $group->first()->professional_id,
                        'name' => $group->first()->professional->name . ' ' . $group->first()->professional->surname . ' ' . $group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,
                        'cant' => $group->sum('professional_count')
                    ];
                })
                ->sortByDesc('cant')
                ->values();
            
            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al eliminar el record'], 500);
        }
    }

    /**
 * Obtiene los registros de llegada tarde de un profesional **hoy** en una sucursal.
 *
 * Devuelve lista de registros + total.
 *
 * @authenticated
 * @queryParam professional_id integer required ID del profesional. Example: 123
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 [
 *   {
 *     "start_time": "2025-11-21 09:30:00",
 *     "end_time": "2025-11-21 18:00:00"
 *   },
 *   {
 *     "start_time": "Total",
 *     "end_time": 1
 *   }
 * ]
 * @response 500 {"msg": "Error al mostrar las llegadas tardes"}
 */
    public function arriving_late_professional_date(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'nullable|numeric',
                'professional_id' => 'nullable|numeric'
            ]);
            $cant = 0;
            $llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            $professionalId = Professional::find($data['professional_id']);
            $today = Carbon::now(); // Incluye toda la jornada del último día
            if (!$branchId || !$professionalId)
                return $llegadasTardias;

            $llegadasTardias = Record::with('professional')->where('branch_id', $branchId->id)->where('professional_id', $professionalId->id)
                ->whereDate('start_time', $today)
                ->get()
                ->filter(function ($registro) {
                    // Obtiene el nombre del día de la semana en español
                    //$diaSemana = $registro->start_time->formatLocalized('%A');
                    $diaSemana = new DateTime($registro->start_time);
                    $nombreDia = $diaSemana->format('l');
                    // Días de la semana en español
                    $diasSemanaEspañol = [
                        'Monday' => 'Lunes',
                        'Tuesday' => 'Martes',
                        'Wednesday' => 'Miércoles',
                        'Thursday' => 'Jueves',
                        'Friday' => 'Viernes',
                        'Saturday' => 'Sábado',
                        'Sunday' => 'Domingo',
                    ];

                    // Reemplazamos el día de la semana en inglés por su equivalente en español
                    $diaSemanaEspañol = $diasSemanaEspañol[$nombreDia];
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                    // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    // Considera llegada tardía si la hora de inicio del registro es después del horario de inicio de la sucursal
                    return Carbon::parse($registro->start_time)->format('H:i') > $schedule->start_time;
                })->map(function ($group) {
                    return [
                        /*'professional_id' => $group->first()->professional_id,
                        /*'name' => $group->first()->professional->name.' '.$group->first()->professional->surname.' '.$group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,*/
                        'start_time' => $group->start_time,
                        'end_time' => $group->end_time
                    ];
                })->values();
            $cant = $llegadasTardias->count();
            $total = [
                /*'professional_id' => 0,
                        'name' => 'Total',
                        'image_url' => '',
                        'charge' => '',*/
                'start_time' => 'Total',
                'end_time' => $cant
            ];
            $llegadasTardias[] = $total;

            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al mostrar las llegadas tardes'], 500);
        }
    }

    /**
 * Obtiene los registros de llegada tarde de un profesional en un **rango de fechas**.
 *
 * @authenticated
 * @queryParam professional_id integer required ID del profesional. Example: 123
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam startDate string required Fecha de inicio (Y-m-d). Example: 2025-11-01
 * @queryParam endDate string required Fecha de fin (Y-m-d). Example: 2025-11-30
 *
 * @response 200 [ ... ]
 * @response 500 {"msg": "Error al mostrar las llegadas tardes"}
 */
    public function arriving_late_professional_periodo(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $cant = 0;
            $llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            $professionalId = Professional::find($data['professional_id']);
            if ($branchId && $professionalId) {
                $llegadasTardias = Record::where('branch_id', $branchId->id)->where('professional_id', $professionalId->id)
                    ->whereDate('start_time', '>=', $request->startDate)->whereDate('start_time', '<=', $request->endDate) //->whereBetween('start_time', [$request->startDate, $request->endDate])
                    ->get()
                    ->filter(function ($registro) {
                        // Obtiene el nombre del día de la semana en español
                        //$diaSemana = $registro->start_time->formatLocalized('%A');
                        $diaSemana = new DateTime($registro->start_time);
                        $nombreDia = $diaSemana->format('l');
                        // Días de la semana en español
                        $diasSemanaEspañol = [
                            'Monday' => 'Lunes',
                            'Tuesday' => 'Martes',
                            'Wednesday' => 'Miércoles',
                            'Thursday' => 'Jueves',
                            'Friday' => 'Viernes',
                            'Saturday' => 'Sábado',
                            'Sunday' => 'Domingo',
                        ];

                        // Reemplazamos el día de la semana en inglés por su equivalente en español
                        $diaSemanaEspañol = $diasSemanaEspañol[$nombreDia];
                        // Obtiene el horario de inicio correspondiente al día de la semana del registro
                        $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                        // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                        if (!$schedule) {
                            return false;
                        }
                        // Considera llegada tardía si la hora de inicio del registro es después del horario de inicio de la sucursal
                        return Carbon::parse($registro->start_time)->format('H:i') > $schedule->start_time;
                    })->map(function ($group) use ($cant) {
                        return [
                            'start_time' => $group->start_time,
                            'end_time' => $group->end_time
                        ];
                    })->values();

                $cant = $llegadasTardias->count();
                $total = [
                    'start_time' => 'Total',
                    'end_time' => $cant
                ];
                $llegadasTardias[] = $total;
            }


            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al mostrar las llegadas tardes'], 500);
        }
    }

    /**
 * Obtiene los registros de llegada tarde de un profesional en un **mes y año**.
 *
 * @authenticated
 * @queryParam professional_id integer required ID del profesional. Example: 123
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam mes integer required Mes (1–12). Example: 11
 * @queryParam year integer required Año. Example: 2025
 *
 * @response 200 [ ... ]
 * @response 500 {"msg": "Error al mostrar las llegadas tardes"}
 */
    public function arriving_late_professional_month(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $cant = 0;
            $llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            $professionalId = Professional::find($data['professional_id']);
            if ($branchId && $professionalId) {
                $llegadasTardias = Record::with('professional')->where('branch_id', $branchId->id)->where('professional_id', $professionalId->id)
                    ->whereMonth('start_time', $request->mes)->whereYear('start_time', $request->year)
                    ->get()
                    ->filter(function ($registro) {
                        // Obtiene el nombre del día de la semana en español
                        //$diaSemana = $registro->start_time->formatLocalized('%A');
                        $diaSemana = new DateTime($registro->start_time);
                        $nombreDia = $diaSemana->format('l');
                         // Días de la semana en español
                        $diasSemanaEspañol = [
                            'Monday' => 'Lunes',
                            'Tuesday' => 'Martes',
                            'Wednesday' => 'Miércoles',
                            'Thursday' => 'Jueves',
                            'Friday' => 'Viernes',
                            'Saturday' => 'Sábado',
                            'Sunday' => 'Domingo',
                        ];

                        // Reemplazamos el día de la semana en inglés por su equivalente en español
                        $diaSemanaEspañol = $diasSemanaEspañol[$nombreDia];
                        // Obtiene el horario de inicio correspondiente al día de la semana del registro
                        $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                        // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                        if (!$schedule) {
                            return false;
                        }
                        // Considera llegada tardía si la hora de inicio del registro es después del horario de inicio de la sucursal
                        return Carbon::parse($registro->start_time)->format('H:i') > $schedule->start_time;
                    })->map(function ($group) use ($cant) {
                        return [
                            'start_time' => $group->start_time,
                            'end_time' => $group->end_time
                        ];
                    })->values();

                $cant = $llegadasTardias->count();
                $total = [
                    'start_time' => 'Total',
                    'end_time' => $cant
                ];
                $llegadasTardias[] = $total;
            }


            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al mostrar las llegadas tardes'], 500);
        }
    }

    /**
 * Obtiene **llegadas puntuales y tardías** de todos los profesionales en un **rango de fechas** en una sucursal.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam startDate string required Fecha de inicio (Y-m-d). Example: 2025-11-01
 * @queryParam endDate string required Fecha de fin (Y-m-d). Example: 2025-11-30
 *
 * @response 200 {
 *   "tardes": [ ... ],
 *   "tiempo": [ ... ]
 * }
 * @response 500 {"msg": "Error"}
 */
    public function arriving_branch_periodo(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
            ]);
            $llegadasTardias = Record::withCount('professional')->with('professional')
                ->where('branch_id', $data['branch_id'])
                ->whereDate('start_time', '>=', $request->startDate)->whereDate('start_time', '<=', $request->endDate) //->whereBetween('start_time', [$request->startDate, $request->endDate])
                ->get()
                ->filter(function ($registro) use ($data) {
                    // Obtiene el nombre del día de la semana en español
                    //$diaSemana = $registro->start_time->formatLocalized('%A');
                    $diaSemana = new DateTime($registro->start_time);
                    $nombreDia = $diaSemana->format('l');
                    // Días de la semana en español
                    $diasSemanaEspañol = [
                        'Monday' => 'Lunes',
                        'Tuesday' => 'Martes',
                        'Wednesday' => 'Miércoles',
                        'Thursday' => 'Jueves',
                        'Friday' => 'Viernes',
                        'Saturday' => 'Sábado',
                        'Sunday' => 'Domingo',
                    ];

                    // Reemplazamos el día de la semana en inglés por su equivalente en español
                    $diaSemanaEspañol = $diasSemanaEspañol[$nombreDia];
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                    // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    // Considera llegada tardía si la hora de inicio del registro es después del horario de inicio de la sucursal
                    return Carbon::parse($registro->start_time)->format('H:i') > $schedule->start_time;
                })
                ->groupBy('professional_id')
                ->map(function ($group) {
                    return [
                        'professional_id' => $group->first()->professional_id,
                        'name' => $group->first()->professional->name . ' ' . $group->first()->professional->surname . ' ' . $group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,
                        'cant' => $group->sum('professional_count')
                    ];
                })
                ->sortByDesc('cant')
                ->values();
            //Llegadas puntuales
            $llegadasTime = Record::withCount('professional')
                ->where('branch_id', $data['branch_id'])
                ->whereDate('start_time', '>=', $request->startDate)->whereDate('start_time', '<=', $request->endDate) //->whereBetween('start_time', [$request->startDate, $request->endDate])
                ->get()
                ->filter(function ($registro) use ($data) {
                    // Obtiene el nombre del día de la semana en español
                    //$diaSemana = $registro->start_time->formatLocalized('%A');
                    $diaSemana = new DateTime($registro->start_time);
                    $nombreDia = $diaSemana->format('l');
                    // Días de la semana en español
                    $diasSemanaEspañol = [
                        'Monday' => 'Lunes',
                        'Tuesday' => 'Martes',
                        'Wednesday' => 'Miércoles',
                        'Thursday' => 'Jueves',
                        'Friday' => 'Viernes',
                        'Saturday' => 'Sábado',
                        'Sunday' => 'Domingo',
                    ];

                    // Reemplazamos el día de la semana en inglés por su equivalente en español
                    $diaSemanaEspañol = $diasSemanaEspañol[$nombreDia];
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                    // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    // Considera llegada tardía si la hora de inicio del registro es después del horario de inicio de la sucursal
                    return Carbon::parse($registro->start_time)->format('H:i') <= $schedule->start_time;
                })
                ->groupBy('professional_id')
                ->map(function ($group) {
                    return [
                        'professional_id' => $group->first()->professional_id,
                        'name' => $group->first()->professional->name . ' ' . $group->first()->professional->surname . ' ' . $group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,
                        'cant' => $group->sum('professional_count')
                    ];
                })
                ->sortByDesc('cant')
                ->values();
           
            return response()->json(['tardes' => $llegadasTardias, 'tiempo' => $llegadasTime], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error'], 500);
        }
    }

    /**
 * Obtiene **llegadas puntuales y tardías** de todos los profesionales **hoy** en una sucursal.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "tardes": [ ... ],
 *   "tiempo": [ ... ]
 * }
 * @response 500 {"msg": "Error"}
 */
    public function arriving_branch_date(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
            ]);
            $today = Carbon::now(); // Incluye toda la jornada del último día
            $llegadasTime = Record::withCount('professional')->with('professional')
                ->where('branch_id', $data['branch_id'])
                ->whereDate('start_time', $today)
                ->get()
                ->filter(function ($registro) use ($data) {
                    // Obtiene el nombre del día de la semana en español
                    //$diaSemana = $registro->start_time->formatLocalized('%A');
                    $diaSemana = new DateTime($registro->start_time);
                    $nombreDia = $diaSemana->format('l');
                    // Días de la semana en español
                    $diasSemanaEspañol = [
                        'Monday' => 'Lunes',
                        'Tuesday' => 'Martes',
                        'Wednesday' => 'Miércoles',
                        'Thursday' => 'Jueves',
                        'Friday' => 'Viernes',
                        'Saturday' => 'Sábado',
                        'Sunday' => 'Domingo',
                    ];

                    // Reemplazamos el día de la semana en inglés por su equivalente en español
                    $diaSemanaEspañol = $diasSemanaEspañol[$nombreDia];
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                      // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    // Considera llegada tardía si la hora de inicio del registro es después del horario de inicio de la sucursal
                    return Carbon::parse($registro->start_time)->format('H:i') <= $schedule->start_time;
                })
                ->groupBy('professional_id')
                ->map(function ($group) {
                    return [
                        'professional_id' => $group->first()->professional_id,
                        'name' => $group->first()->professional->name . ' ' . $group->first()->professional->surname . ' ' . $group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,
                        'cant' => $group->sum('professional_count')
                    ];
                })
                ->sortByDesc('cant')
                ->values();
            $llegadasTardias = Record::withCount('professional')->with('professional')
                ->where('branch_id', $data['branch_id'])
                ->whereDate('start_time', $today)
                ->get()
                ->filter(function ($registro) use ($data) {
                    // Obtiene el nombre del día de la semana en español
                    //$diaSemana = $registro->start_time->formatLocalized('%A');
                    $diaSemana = new DateTime($registro->start_time);
                    $nombreDia = $diaSemana->format('l');
                    // Días de la semana en español
                    $diasSemanaEspañol = [
                        'Monday' => 'Lunes',
                        'Tuesday' => 'Martes',
                        'Wednesday' => 'Miércoles',
                        'Thursday' => 'Jueves',
                        'Friday' => 'Viernes',
                        'Saturday' => 'Sábado',
                        'Sunday' => 'Domingo',
                    ];

                    // Reemplazamos el día de la semana en inglés por su equivalente en español
                    $diaSemanaEspañol = $diasSemanaEspañol[$nombreDia];
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                    // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    // Considera llegada tardía si la hora de inicio del registro es después del horario de inicio de la sucursal
                    return Carbon::parse($registro->start_time)->format('H:i') > $schedule->start_time;
                })
                ->groupBy('professional_id')
                ->map(function ($group) {
                    return [
                        'professional_id' => $group->first()->professional_id,
                        'name' => $group->first()->professional->name . ' ' . $group->first()->professional->surname . ' ' . $group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,
                        'cant' => $group->sum('professional_count')
                    ];
                })
                ->sortByDesc('cant')
                ->values();
            return response()->json(['tardes' => $llegadasTardias, 'tiempo' => $llegadasTime], 200, [], JSON_NUMERIC_CHECK);
            //return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error'], 500);
        }
    }

    /**
 * Obtiene **llegadas puntuales** de todos los profesionales en un **mes y año** en una sucursal.
 *
 * ⚠️ Solo devuelve puntuales (no tardías).
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam mes integer required Mes (1–12). Example: 11
 * @queryParam year integer required Año. Example: 2025
 *
 * @response 200 [ ... ]
 * @response 500 {"msg": "Error"}
 */
    public function arriving_branch_month(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
            ]);
            $llegadasTardias = Record::withCount('professional')->with('professional')
                ->where('branch_id', $data['branch_id'])
                ->whereMonth('start_time', $request->mes)->whereYear('start_time', $request->year)
                ->get()
                ->filter(function ($registro) use ($data) {
                    // Obtiene el nombre del día de la semana en español
                    //$diaSemana = $registro->start_time->formatLocalized('%A');
                    $diaSemana = new DateTime($registro->start_time);
                    $nombreDia = $diaSemana->format('l');
                    // Días de la semana en español
                    $diasSemanaEspañol = [
                        'Monday' => 'Lunes',
                        'Tuesday' => 'Martes',
                        'Wednesday' => 'Miércoles',
                        'Thursday' => 'Jueves',
                        'Friday' => 'Viernes',
                        'Saturday' => 'Sábado',
                        'Sunday' => 'Domingo',
                    ];

                    // Reemplazamos el día de la semana en inglés por su equivalente en español
                    $diaSemanaEspañol = $diasSemanaEspañol[$nombreDia];
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                    // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    // Considera llegada tardía si la hora de inicio del registro es después del horario de inicio de la sucursal
                    return Carbon::parse($registro->start_time)->format('H:i') <= $schedule->start_time;
                })
                ->groupBy('professional_id')
                ->map(function ($group) {
                    return [
                        'professional_id' => $group->first()->professional_id,
                        'name' => $group->first()->professional->name . ' ' . $group->first()->professional->surname . ' ' . $group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,
                        'cant' => $group->sum('professional_count')
                    ];
                })
                ->sortByDesc('cant')
                ->values();
           
            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error'], 500);
        }
    }
}
