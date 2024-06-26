<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchRuleProfessional;
use App\Models\Professional;
use App\Models\Record;
use App\Models\Schedule;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class RecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return response()->json(['records' => Record::with(['branch', 'professional'])->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar el historial de records"], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info("Guardar");
        Log::info($request);
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

                return response()->json(['msg' => 'Record creado correctamente'], 200);
            } else {
                return response()->json(['msg' => 'Ya registró entrada en el día de hoy'], 200);
            }
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al crear un record: ' . $th->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
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
            Log::error($th);
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
            Log::error($th);
            return response()->json(['msg' => $th->getmessage() . "Error interno del sistema"], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        Log::info("Guardar");
        Log::info($request);
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);

            $record = Record::where('branch_id', $data['branch_id'])->where('professional_id', $data['professional_id'])->whereDate('start_time', Carbon::now())->first();
            $record->end_time = Carbon::now();
            $record->save();
            return response()->json(['msg' => 'Record creado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al crear un record'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
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
            Log::error($th);
            return response()->json(['msg' => 'Error al eliminar el record'], 500);
        }
    }

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
                    Log::info($nombreDia);
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
                    Log::info($diaSemanaEspañol);
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                    Log::info($schedule);
                    // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    Log::info($schedule->start_time);
                    Log::info(Carbon::parse($registro->start_time)->format('H:i'));
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
            // Cantidad de profesionales que llegaron tarde
            //$tardyCount = $tardyProfessionals->count();
            /*$llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            // Obtén la fecha actual
            $hoy = Carbon::now();

            // Obtén el nombre del día de la semana
            $nombreDia = $hoy->format('l');
            if($branchId)
            $llegadasTardias = Record::withCount('professional')->with('professional')->where('branch_id', $branchId->id)
                ->whereBetween('start_time', [$request->start_date, $request->end_date])
                ->get()
                ->filter(function ($registro) {
                    // Considera llegada tardía si es después de las 9:00 AM
                    return Carbon::parse($registro->start_time)->hour >= 9;
                })->groupBy('professional_id')->map(function ($group){
                    return [
                        'professional_id' => $group->first()->professional_id,
                        'name' => $group->first()->professional->name.' '.$group->first()->professional->surname.' '.$group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,
                        'cant' => $group->sum('professional_count')
                    ];
                })->sortByDesc('cant')->values();*/

            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al eliminar el record'], 500);
        }
    }

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
                    Log::info($nombreDia);
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
                    Log::info($diaSemanaEspañol);
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                    Log::info($schedule);
                    // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    Log::info($schedule->start_time);
                    Log::info(Carbon::parse($registro->start_time)->format('H:i'));
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
            /*$llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            if ($branchId)
                $llegadasTardias = Record::withCount('professional')->with('professional')->where('branch_id', $branchId->id)
                    ->whereDate('start_time', $today)
                    ->get()
                    ->filter(function ($registro) {
                        // Considera llegada tardía si es después de las 9:00 AM
                        return Carbon::parse($registro->start_time)->hour >= 9;
                    })->groupBy('professional_id')->map(function ($group) {
                        return [
                            'professional_id' => $group->first()->professional_id,
                            'name' => $group->first()->professional->name . ' ' . $group->first()->professional->surname . ' ' . $group->first()->professional->second_surname,
                            'image_url' => $group->first()->professional->image_url,
                            'charge' => $group->first()->professional->charge->name,
                            'cant' => $group->sum('professional_count')
                        ];
                    })->sortByDesc('cant')->values();*/

            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al mostrar las llegadas tardes'], 500);
        }
    }

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
                    Log::info($nombreDia);
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
                    Log::info($diaSemanaEspañol);
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                    Log::info($schedule);
                    // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    Log::info($schedule->start_time);
                    Log::info(Carbon::parse($registro->start_time)->format('H:i'));
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
            /*$llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            if ($branchId)
                $llegadasTardias = Record::withCount('professional')->with('professional')->where('branch_id', $branchId->id)
                    ->whereMonth('start_time', $request->mes)->whereYear('start_time', $request->year)
                    ->get()
                    ->filter(function ($registro) {
                        // Considera llegada tardía si es después de las 9:00 AM
                        return Carbon::parse($registro->start_time)->hour >= 9;
                    })->groupBy('professional_id')->map(function ($group) {
                        return [
                            'professional_id' => $group->first()->professional_id,
                            'name' => $group->first()->professional->name . ' ' . $group->first()->professional->surname . ' ' . $group->first()->professional->second_surname,
                            'image_url' => $group->first()->professional->image_url,
                            'charge' => $group->first()->professional->charge->name,
                            'cant' => $group->sum('professional_count')
                        ];
                    })->sortByDesc('cant')->values();*/

            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al eliminar el record'], 500);
        }
    }

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
            Log::info($branchId);
            Log::info('sadsd');
            Log::info($professionalId);
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
                    Log::info($nombreDia);
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
                    Log::info($diaSemanaEspañol);
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                    Log::info($schedule);
                    // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    Log::info($schedule->start_time);
                    Log::info(Carbon::parse($registro->start_time)->format('H:i'));
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
            Log::info('ffafafafafa');
            Log::info($llegadasTardias);
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
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al mostrar las llegadas tardes'], 500);
        }
    }

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
                        Log::info($nombreDia);
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
                        Log::info($diaSemanaEspañol);
                        // Obtiene el horario de inicio correspondiente al día de la semana del registro
                        $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                        Log::info($schedule);
                        // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                        if (!$schedule) {
                            return false;
                        }
                        Log::info($schedule->start_time);
                        Log::info(Carbon::parse($registro->start_time)->format('H:i'));
                        // Considera llegada tardía si la hora de inicio del registro es después del horario de inicio de la sucursal
                        return Carbon::parse($registro->start_time)->format('H:i') > $schedule->start_time;
                    })->map(function ($group) use ($cant) {
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
            }


            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al mostrar las llegadas tardes'], 500);
        }
    }
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
                        Log::info($nombreDia);
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
                        Log::info($diaSemanaEspañol);
                        // Obtiene el horario de inicio correspondiente al día de la semana del registro
                        $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                        Log::info($schedule);
                        // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                        if (!$schedule) {
                            return false;
                        }
                        Log::info($schedule->start_time);
                        Log::info(Carbon::parse($registro->start_time)->format('H:i'));
                        // Considera llegada tardía si la hora de inicio del registro es después del horario de inicio de la sucursal
                        return Carbon::parse($registro->start_time)->format('H:i') > $schedule->start_time;
                    })->map(function ($group) use ($cant) {
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
            }


            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al mostrar las llegadas tardes'], 500);
        }
    }

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
                    Log::info($nombreDia);
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
                    Log::info($diaSemanaEspañol);
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                    Log::info($schedule);
                    // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    Log::info($schedule->start_time);
                    Log::info(Carbon::parse($registro->start_time)->format('H:i'));
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
                    Log::info($nombreDia);
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
                    Log::info($diaSemanaEspañol);
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                    Log::info($schedule);
                    // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    Log::info($schedule->start_time);
                    Log::info(Carbon::parse($registro->start_time)->format('H:i'));
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
            // Cantidad de profesionales que llegaron tarde
            //$tardyCount = $tardyProfessionals->count();
            /*$llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            // Obtén la fecha actual
            $hoy = Carbon::now();

            // Obtén el nombre del día de la semana
            $nombreDia = $hoy->format('l');
            if($branchId)
            $llegadasTardias = Record::withCount('professional')->with('professional')->where('branch_id', $branchId->id)
                ->whereBetween('start_time', [$request->start_date, $request->end_date])
                ->get()
                ->filter(function ($registro) {
                    // Considera llegada tardía si es después de las 9:00 AM
                    return Carbon::parse($registro->start_time)->hour >= 9;
                })->groupBy('professional_id')->map(function ($group){
                    return [
                        'professional_id' => $group->first()->professional_id,
                        'name' => $group->first()->professional->name.' '.$group->first()->professional->surname.' '.$group->first()->professional->second_surname,
                        'image_url' => $group->first()->professional->image_url,
                        'charge' => $group->first()->professional->charge->name,
                        'cant' => $group->sum('professional_count')
                    ];
                })->sortByDesc('cant')->values();*/

            return response()->json(['tardes' => $llegadasTardias, 'tiempo' => $llegadasTime], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error'], 500);
        }
    }

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
                    Log::info($nombreDia);
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
                    Log::info($diaSemanaEspañol);
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                    Log::info($schedule);
                    // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    Log::info($schedule->start_time);
                    Log::info(Carbon::parse($registro->start_time)->format('H:i'));
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
            /*$today = now()->endOfDay(); // Incluye toda la jornada del último día

            $llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            if ($branchId)
                $llegadasTardias = Record::withCount('professional')->with('professional')->where('branch_id', $branchId->id)
                    ->whereDate('start_time', $today)
                    ->get()
                    ->filter(function ($registro) {
                        // Considera llegada tardía si es después de las 9:00 AM
                        return Carbon::parse($registro->start_time)->hour <  9;
                    })->groupBy('professional_id')->map(function ($group) {
                        return [
                            'professional_id' => $group->first()->professional_id,
                            'name' => $group->first()->professional->name . ' ' . $group->first()->professional->surname . ' ' . $group->first()->professional->second_surname,
                            'image_url' => $group->first()->professional->image_url,
                            'charge' => $group->first()->professional->charge->name,
                            'cant' => $group->sum('professional_count')
                        ];
                    })->sortByDesc('cant')->values();*/
            $llegadasTardias = Record::withCount('professional')->with('professional')
                ->where('branch_id', $data['branch_id'])
                ->whereDate('start_time', $today)
                ->get()
                ->filter(function ($registro) use ($data) {
                    // Obtiene el nombre del día de la semana en español
                    //$diaSemana = $registro->start_time->formatLocalized('%A');
                    $diaSemana = new DateTime($registro->start_time);
                    $nombreDia = $diaSemana->format('l');
                    Log::info($nombreDia);
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
                    Log::info($diaSemanaEspañol);
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                    Log::info($schedule);
                    // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    Log::info($schedule->start_time);
                    Log::info(Carbon::parse($registro->start_time)->format('H:i'));
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
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error'], 500);
        }
    }

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
                    Log::info($nombreDia);
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
                    Log::info($diaSemanaEspañol);
                    // Obtiene el horario de inicio correspondiente al día de la semana del registro
                    $schedule = $registro->branch->schedule()->where('day', $diaSemanaEspañol)->first();
                    Log::info($schedule);
                    // Si no hay horario de inicio para ese día, no se considera como llegada tardía
                    if (!$schedule) {
                        return false;
                    }
                    Log::info($schedule->start_time);
                    Log::info(Carbon::parse($registro->start_time)->format('H:i'));
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
            /*$llegadasTardias = [];
            $branchId = Branch::find($data['branch_id']);
            if ($branchId)
                $llegadasTardias = Record::withCount('professional')->where('branch_id', $branchId->id)
                    ->whereMonth('start_time', $request->mes)->whereYear('start_time', $request->year)
                    ->get()
                    ->filter(function ($registro) {
                        // Considera llegada tardía si es después de las 9:00 AM
                        return Carbon::parse($registro->start_time)->hour < 9;
                    })->groupBy('professional_id')->map(function ($group) {
                        return [
                            'professional_id' => $group->first()->professional_id,
                            'name' => $group->first()->professional->name . ' ' . $group->first()->professional->surname . ' ' . $group->first()->professional->second_surname,
                            'image_url' => $group->first()->professional->image_url,
                            'charge' => $group->first()->professional->charge->name,
                            'cant' => $group->sum('professional_count')
                        ];
                    })->sortByDesc('cant')->values();*/

            return response()->json($llegadasTardias, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error'], 500);
        }
    }
}
