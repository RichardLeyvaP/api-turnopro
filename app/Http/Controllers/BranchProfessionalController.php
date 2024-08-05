<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchProfessional;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Professional;
use App\Models\ProfessionalWorkPlace;
use App\Models\Record;
use App\Models\Restday;
use App\Models\Service;
use App\Models\Vacation;
use App\Models\Workplace;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BranchProfessionalController extends Controller
{
    public function index()
    {
        try {
            Log::info("Entra a buscar los Professionales por sucursales");
            return response()->json(['branch' => Branch::with('professionals')->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los professionals por sucursales"], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info("Asignar professionals a una sucursal");
        Log::info($request);
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'ponderation' => 'nullable',
                'limit' => 'nullable',
                'mountpay' => 'nullable'
            ]);
            $branch = Branch::find($data['branch_id']);
            $professional = Professional::find($data['professional_id']);

            $branch->professionals()->attach($professional->id, ['ponderation' => $data['ponderation'], 'limit' => $data['limit'], 'mountpay' => $data['mountpay']]);
            DB::commit();
            return response()->json(['msg' => 'Professional asignado correctamente a la sucursal'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            DB::rollback();
            return response()->json(['msg' => $th->getMessage() . 'Error al asignar el professional a esta sucursal'], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            Log::info("Dado un professionals devuelve las branches a las que pertenece");
            $data = $request->validate([
                'professional_id' => 'required|numeric'
            ]);
            $professional = Professional::find($data['professional_id']);
            return response()->json(['branches' => $professional->branches], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las branches"], 500);
        }
    }

    public function branch_professionals(Request $request)
    {
        try {
            Log::info("Dado una branch devuelve los professionales que trabajan en ella");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $now = Carbon::now();
            $professionals = BranchProfessional::where('branch_id', $data['branch_id'])->with('professional.charge')->get()/*->map(function ($query){
                $professional = $query->professional;
                return [
                    'id' => $query->id,
                    'professional_id' => $query->professional_id,
                    'ponderation' => $query->ponderation,
                    'name' => $professional->name.' '.$professional->surname,
                    'image_url' => $professional->image_url,
                    'charge' => $professional->chrage
                ];
            })*/;
            $data = [];
            foreach ($professionals as $branchprofessional) {
                $data[] = [
                    'id' => $branchprofessional['id'],
                    'professional_id' => $branchprofessional['professional_id'],
                    'ponderation' => $branchprofessional['ponderation'],
                    'limit' => $branchprofessional['limit'],
                    'mountpay' => $branchprofessional['mountpay'],
                    'name' => $branchprofessional['professional']['name'],
                    'image_url' => $branchprofessional['professional']['image_url'].'?$'.$now,
                    'charge' => $branchprofessional['professional']['charge']['name'],
                ];
            }
            return response()->json(['professionals' => $data], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las branches"], 500);
        }
    }

    public function branch_professionals_barber_totem(Request $request)
    {
        try {
            Log::info("Dado una branch devuelve los professionales que trabajan en ella");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $professionals = Professional::whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->whereHas('charge', function ($query) {
                $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
            })->select('id', 'name', 'surname', 'second_surname', 'image_url')->get();
       
            return response()->json(['professionals' => $professionals], 200, [], JSON_NUMERIC_CHECK);

        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las branches"], 500);
        }
    }

    public function branch_professionals_barber(Request $request)
    {
        try {
            Log::info("Dado una branch devuelve los professionales que trabajan en ella");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $services = $request->input('services');
            $professionals = [];
            $professionals1 = Professional::whereHas('branchServices', function ($query) use ($services, $data) {
                $query->whereIn('service_id', $services)->where('branch_id', $data['branch_id']);
            }, '=', count($services))->whereHas('charge', function ($query) {
                $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
            })->with('branches')->select('id', 'name', 'surname', 'second_surname', 'image_url')->get()->map(function ($professional) {
                $ponderation = $professional->branches->first()->pivot->ponderation;
                return [
                    'id' => $professional->id,
                    'name' => $professional->name,
                    'surname' => $professional->surname,
                    'second_surname' => $professional->second_surname,
                    'image_url' => $professional->image_url . '?$' . Carbon::now(),
                    'ponderation' => $ponderation
                ];
            })->sortBy('ponderation')->values();

            foreach ($professionals1 as $professional1) {
                $vacation = [];
                $diasSemana = [];
                // Obtener vacaciones del profesional
                $vacation = Vacation::where('professional_id', $professional1['id'])
                                    ->whereDate('endDate', '>=', Carbon::now())
                                    ->get();

                                    Log::info('Professional'.$professional1['id']);
                                    Log::info('Vacaciones');
                                    Log::info($vacation);
                
                if ($vacation->isEmpty()) {
                    // Si no hay vacaciones, inicializar el array de vacaciones como nulo
                    Log::info('No hay vacaciones');
                    $professional1['vacations'] = [];
                } else {
                    // Si hay vacaciones, mapearlas al formato deseado                    
                    Log::info('Hayay vacaciones');
                    $professional1['vacations'] = $vacation->map(function ($query) {
                        return [
                            'startDate' => $query->startDate,
                            'endDate' => $query->endDate,
                        ];
                    });
                }
            
                // Obtener días libres del profesional
                $diasSemana  = Restday::where('professional_id', $professional1['id'])
                                       ->where('state', 1)
                                       ->pluck('day')
                                       ->toArray();

           
                // Si hay días libres, convertirlos y agregarlos al array de vacaciones del profesional
                if (!empty($diasSemana)) {
                    Log::info('Professional'.$professional1['id']);
                                       Log::info('días libres array');
                                       Log::info($diasSemana );
                    $fechasDiasLibres = $this->obtenerFechasDiasSemana($diasSemana);
                    Log::info('fechas Dias Libres antes del for');
                    Log::info($fechasDiasLibres);
                    if (!empty($fechasDiasLibres)) {
                        /*Log::info('fechas Dias Libres');
                        Log::info($fechasDiasLibres );*/
                        foreach ($fechasDiasLibres as $fecha) {
                            $professional1['vacations'][] = [
                                'startDate' => $fecha,
                                'endDate' => $fecha,
                            ];
                        }
                    }
                }
            
                // Agregar el profesional actual al array de profesionales
                $professionals[] = $professional1;
            }
            
            return response()->json(['professionals' => $professionals], 200, [], JSON_NUMERIC_CHECK);

        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las branches"], 500);
        }
    }
    function obtenerFechasDiasSemana($diasSemana)
    {
        // Definir los nombres de los días de la semana en inglés
        $diasSemanaIngles = [
            'Lunes' => 'Monday',
            'Martes' => 'Tuesday',
            'Miércoles' => 'Wednesday',
            'Jueves' => 'Thursday',
            'Viernes' => 'Friday',
            'Sábado' => 'Saturday',
            'Domingo' => 'Sunday'
        ];

        // Obtener el día actual en inglés
        $diaActualIngles = Carbon::now()->isoFormat('dddd');
        Log::info('Dia actual en ingles');
        Log::info($diaActualIngles);
         // Obtener el día actual en español
         /*$diaActualEspañol = ucfirst(Carbon::now()->translatedFormat('l'));
        Log::info('Día actual en español: ' . $diaActualEspañol);
            // Inicializar el array de fechas
        $fechas = [];

        // Si el día actual está en el array de días de la semana, agregar su fecha correspondiente
        if (in_array($diaActualEspañol, $diasSemana)) {
            Log::info('El día actual está en los días seleccionados');
            $fechas[] = Carbon::now()->format('Y-m-d');
        }*/
        // Obtener las fechas para cada día de la semana en el array
        foreach ($diasSemana as $dia) {
            // Restablecer la fecha actual para cada iteración del bucle
            $fechaActual = Carbon::now();
            $diaIngles = $diasSemanaIngles[$dia];
            Log::info('$diaIngles seleccionados');
            Log::info($diaIngles);
            $fecha = $this->siguienteFechaDiaSemana($fechaActual, $diaIngles);
            if ($fecha->isPast()) { // Si la fecha ya pasó, avanzar una semana
                $fecha->addWeek();
            }
            while ($fecha->year === $fechaActual->year) { // Verificar todo el año
                $fechas[] = $fecha->format('Y-m-d');
                $fecha->addWeek(); // Avanzar una semana
            }
            /*while ($fecha->month === $fechaActual->month) {
                $fechas[] = $fecha->format('Y-m-d');
                $fecha->addWeek(); // Avanzar una semana
            }*/
        }
        // Ordenar las fechas
        sort($fechas);

        return $fechas;
    }

    function siguienteFechaDiaSemana($fechaActual, $diaBuscado)
    {
        $diaActual = ucfirst($fechaActual->isoFormat('dddd')); // Obtener el nombre del día actual en español y capitalizar la primera letra
        if ($diaActual === $diaBuscado) {
            return $fechaActual->copy();
        }
        while (ucfirst($fechaActual->isoFormat('dddd')) !== $diaBuscado) {
            $fechaActual->addDay();
        }
        return $fechaActual->copy();
    }

    public function branch_professionals_barber_tecnico(Request $request)
    {
        try {
            Log::info("Dado una branch devuelve los professionales y tecnicos que trabajan en ella");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $professionals = Professional::whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->whereHas('charge', function ($query) {
                $query->where('name', 'Barbero')
                    ->orWhere('name', 'Tecnico')
                    ->orWhere('name', 'Barbero y Encargado');
            })->get();
            return response()->json(['professionals' => $professionals], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las branches"], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'ponderation' => 'nullable',
                'limit' => 'nullable',
                'mountpay' => 'nullable'
            ]);
            $branch = Branch::find($data['branch_id']);
            $professional = Professional::find($data['professional_id']);
            $branch->professionals()->updateExistingPivot($professional->id, ['ponderation' => $data['ponderation'], 'limit' => $data['limit'], 'mountpay' => $data['mountpay']]);
            return response()->json(['msg' => 'Professionals reasignado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al actualizar el professionals de esa branch'], 500);
        }
    }

    public function update_state(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'type' => 'required|string',
                'state' => 'required|numeric'
            ]);
            $tittle = '';
            $description = '';            
            $ProfessionalWorkPlace = [];
            DB::beginTransaction();
            $professional = Professional::find($data['professional_id']);
            if ($professional->state == 2) {
                $professional->end_time = Carbon::now();

                //actualizar lugar de llegada
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

            }
            if ($data['state'] == 1) {
                if ($professional->state == 4) {
                    $notification = new Notification();
                    $notification->professional_id = $data['professional_id'];
                    $notification->branch_id = $data['branch_id'];
                    $notification->tittle = 'Rechazada su solicitud de Salida';
                    $notification->description = 'Su solicitud de Salida fue rechazada';
                    $notification->state = 3;   
                    $notification->type = $data['type'];                  
                    $notification->save();
                }
                if ($professional->state == 3) {
                    $notification = new Notification();
                    $notification->professional_id = $data['professional_id'];
                    $notification->branch_id = $data['branch_id'];
                    $notification->tittle = 'Rechazada su solicitud de Colación';
                    $notification->description = 'Su solicitud de Colación fue rechazada';
                    $notification->state = 3;                    
                    $notification->type = $data['type'];                    
                    $notification->save();
                }
                //para las notificaciones de solicitud a 1
                Notification::where('branch_id', $data['branch_id'])->where('state', 0)->where('stateApk', 'profesional'.$professional->id)->update(['state' => 1]);
            }
            elseif ($data['state'] == 2 || $data['state'] == 0) {
                if ($data['type'] == 'Barbero' || $data['type'] == 'Barbero y Encargado') {
                    $ProfessionalWorkPlace = ProfessionalWorkPlace::where('professional_id', $professional->id)->whereDate('data', Carbon::now())->whereHas('workplace', function ($query) use ($data) {
                        $query->where('busy', 1)->where('branch_id', $data['branch_id']);
                    })->latest('created_at')->first();
                    if ($ProfessionalWorkPlace != null) {
                        $workplace = Workplace::where('id', $ProfessionalWorkPlace->workplace_id)->first();
                    $workplace->busy = 0;
                    $workplace->save();
                    $ProfessionalWorkPlace->state = 0;
                    $ProfessionalWorkPlace->save();
                    }
                }//end if de barbero
                if ($data['type'] == 'Tecnico') {
                    $ProfessionalWorkPlace = ProfessionalWorkPlace::where('professional_id', $professional->id)->whereDate('data', Carbon::now())->whereHas('workplace', function ($query) use ($data) {
                        $query->where('branch_id', $data['branch_id']);
                    })->latest('created_at')->first();
                    if ($ProfessionalWorkPlace != null){
                        $places = json_decode($ProfessionalWorkPlace->places, true);
                    Workplace::whereIn('id', $places)->update(['select' => 0]);
                    $ProfessionalWorkPlace->state = 0;
                    $ProfessionalWorkPlace->save();
                    }
                }//end if de tecnico

                if ($data['state'] == 2) {                                    
                    $professional->start_time = Carbon::now();
                    $notification = new Notification();
                    $notification->professional_id = $data['professional_id'];
                    $notification->branch_id = $data['branch_id'];
                    $notification->tittle = 'Aceptada su solicitud de Colación';
                    $notification->description = 'Aceptada su solicitud de Colación, de ('.Carbon::now()->format('H:i').' a '.Carbon::now()->addMinutes(60)->format('H:i').')';
                    $notification->state = 3;
                    $notification->type = $data['type'];                     
                    $notification->save();
                }else {
                    $notification = new Notification();
                    $notification->professional_id = $data['professional_id'];
                    $notification->branch_id = $data['branch_id'];
                    $notification->tittle = 'Aceptada su solicitud de Salida';
                    $notification->description = 'Aceptada su solicitud de Salida,'.Carbon::now()->format('H:i');
                    $notification->state = 3;
                    $notification->type = $data['type'];
                    $notification->save();
                    $record = Record::where('branch_id', $data['branch_id'])->where('professional_id', $data['professional_id'])->whereDate('start_time', Carbon::now())->first();
                    if ($record != null) {
                        $record->end_time = Carbon::now();
                        $record->save();
                    }
                }
                //para las notificaciones de solicitud a 1
                Notification::where('branch_id', $data['branch_id'])->where('state', 0)->where('stateApk', 'profesional'.$professional->id)->update(['state' => 1]);
                
            }
            elseif ($data['state'] == 4 || $data['state'] == 3){
                $branch = Branch::find($data['branch_id']);
                //if ($data['type'] == 'Ambos') {
                /*$professionals = BranchProfessional::with('professional.charge')->where('branch_id', $data['branch_id'])->whereHas('professional.charge', function ($query) {
                    $query->where('name', 'Coordinador')->orWhere('name', 'Encargado')->orWhere('name', 'Barbero y Encargado');
                })->get();*/
                $professionals = BranchProfessional::with(['professional' => function($query) {
                    $query->select('id', 'charge_id'); // Especifica los campos necesarios
                }, 'professional.charge' => function($query) {
                    $query->select('id', 'name'); // Especifica los campos necesarios
                }])
                ->where('branch_id', $data['branch_id'])
                ->whereHas('professional.charge', function ($query) {
                    $query->whereIn('name', ['Coordinador', 'Encargado', 'Barbero y Encargado']);
                })
                ->get(['id', 'professional_id', 'branch_id']); // Especifica los campos necesarios de BranchProfessional
                // Agrupa los profesionales por su cargo
                $groupedProfessionals = $professionals->groupBy('professional.charge.name');

                // Extrae los IDs de los profesionales para cada cargo
                $encargados = $groupedProfessionals->has('Encargado') ? $groupedProfessionals->get('Encargado')->pluck('professional_id') : collect();
                $coordinadors = $groupedProfessionals->has('Coordinador') ? $groupedProfessionals->get('Coordinador')->pluck('professional_id') : collect();
                $barberoEncargados = $groupedProfessionals->has('Barbero y Encargado') ? $groupedProfessionals->get('Barbero y Encargado')->pluck('professional_id') : collect();
                $charge = $professional->charge->name;
                $charge = $charge == 'Tecnico' ? 'Técnico' : $charge;
                if ($data['state'] == 4) {
                    $tittle = 'Solicitud de Salida';
                    $description = 'EL'.' '.$charge.' '.$professional->name.' '.'esta pidiendo solicitud de salida';
                }else {
                    $tittle = 'Solicitud de Colación';
                    $description = 'EL'.' '.$charge.' '.$professional->name.' '.'esta pidiendo solicitud de colación';
                }
                if (!$encargados->isEmpty()) {
                    foreach ($encargados as $encargado) {
                        $notification = new Notification();
                        $notification->professional_id = $encargado;
                        $notification->tittle = $tittle;
                        $notification->description = $description;
                        $notification->type = 'Encargado';
                        $notification->stateApk = 'profesional'.$data['professional_id'];
                        $branch->notifications()->save($notification);
                    }
                }
                if (!$coordinadors->isEmpty()) {
                    foreach ($coordinadors as $coordinador) {
                        $notification = new Notification();
                        $notification->professional_id = $coordinador;
                        $notification->tittle = $tittle;
                        $notification->description = $description;
                        $notification->type = 'Coordinador';
                        $notification->stateApk = 'profesional'.$data['professional_id'];
                        $branch->notifications()->save($notification);
                    }
                }
                if (!$barberoEncargados->isEmpty()) {
                    foreach ($barberoEncargados as $barberoEncargado) {
                        $notification = new Notification();
                        $notification->professional_id = $barberoEncargado;
                        $notification->tittle = $tittle;
                        $notification->description = $description;
                        $notification->type = 'Encargado';
                        $notification->stateApk = 'profesional'.$data['professional_id'];
                        $branch->notifications()->save($notification);
                    }
                }
                //}
            }
            $professional->state = $data['state'];
            $professional->save();
            DB::commit();
            return response()->json(['msg' => 'Estado modificado correctamente'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al actualizar el professionals de esa branch'], 500);
        }
    }

    public function branch_colacion(Request $request)
    {

        try {
            Log::info("Dado una branch devuelve los professionales que trabajan en ella");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $professionals = Professional::whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->where('state', 2)->get()->map(function ($query) {
                return [
                    'professional_name' => $query->name . " " . $query->surname,
                    'client_image' => $query->image_url ? $query->image_url : "professionals/default_profile.jpg",
                    'professional_id' => $query->id,
                    'professional_state' => $query->state,
                    'start_time' => Carbon::parse($query->start_time)->format('H:i'),
                    'charge' => $query->charge->name
                ];
            });
            return response()->json(['professionals' => $professionals], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las branches"], 500);
        }
    }

    public function branch_colacion3(Request $request)
    {

        try {
            Log::info("Dado una branch devuelve los professionales que trabajan en ella");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $professionals = Professional::whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->where('state', 3)->get()->map(function ($query) {
                return [
                    'professional_name' => $query->name . " " . $query->surname,
                    'client_image' => $query->image_url ? $query->image_url : "professionals/default_profile.jpg",
                    'professional_id' => $query->id,
                    'professional_state' => $query->state,
                    'start_time' => Carbon::parse($query->start_time)->format('H:i'),
                    'charge' => $query->charge->name
                ];
            });
            return response()->json(['professionals' => $professionals], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las branches"], 500);
        }
    }

    public function branch_colacion4(Request $request)
    {

        try {
            Log::info("Dado una branch devuelve los professionales que trabajan en ella");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $professionals = Professional::whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->where('state', 4)->get()->map(function ($query) {
                return [
                    'professional_name' => $query->name . " " . $query->surname,
                    'client_image' => $query->image_url ? $query->image_url : "professionals/default_profile.jpg",
                    'professional_id' => $query->id,
                    'professional_state' => $query->state,
                    'start_time' => Carbon::parse($query->start_time)->format('H:i'),
                    'charge' => $query->charge->name
                ];
            });
            return response()->json(['professionals' => $professionals], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las branches"], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $branch = Branch::find($data['branch_id']);
            $professional = Professional::find($data['professional_id']);
            $branch->professionals()->detach($professional->id);
            return response()->json(['msg' => 'Professional eliminada correctamente de la branch'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al eliminar la professional de esta branch'], 500);
        }
    }
}
