<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchProfessional;
use App\Models\Comment;
use App\Models\Professional;
use App\Models\ProfessionalWorkPlace;
use App\Models\Restday;
use App\Models\Service;
use App\Models\Vacation;
use App\Models\Workplace;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

            return response()->json(['msg' => 'Professional asignado correctamente a la sucursal'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
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
            //$totaltime = Service::whereIn('id', $services)->get()->sum('duration_service');
            /*$professionals = Professional::whereHas('branches', function ($query) use ($data, $services) {

             /*  $query->where('branch_id', $data['branch_id']);
            })->whereHas('branchServices', function ($query) use ($services) {
                $query->whereIn('service_id', $services);
            }, '=', count($services))->whereHas('charge', function ($query) {
                $query->where('id', 1);*/
            /*})->get();*/
            /*$professionals = Professional::whereHas('branches', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            })->where('charge_id', 1)->get();*/
            return response()->json(['professionals' => $professionals], 200, [], JSON_NUMERIC_CHECK);

            /*Log::info("Dado una branch devuelve los professionales que trabajan en ella");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $services = $request->input('services');
            //$totaltime = Service::whereIn('id', $services)->get()->sum('duration_service');
            $professionals = Professional::whereHas('branches', function ($query) use ($data, $services) {
                $query->where('branch_id', $data['branch_id']);
            })->whereHas('branchServices', function ($query) use ($services) {
                $query->whereIn('service_id', $services);
            }, '=', count($services))->whereHas('charge', function ($query) {
                $query->where('id', 1);
            })->get();
            /*$professionals = Professional::whereHas('branches', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            })->where('charge_id', 1)->get();*/
            //return response()->json(['professionals' => $professionals],200, [], JSON_NUMERIC_CHECK); */

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
                    'image_url' => $professional->image_url,
                    'ponderation' => $ponderation
                ];
            })->sortBy('ponderation')->values();

            foreach ($professionals1 as $professional1) {
                // Obtener vacaciones del profesional
                $vacation = Vacation::where('professional_id', $professional1['id'])
                                    ->whereDate('endDate', '>=', Carbon::now())
                                    ->get();
                
                if ($vacation->isEmpty()) {
                    // Si no hay vacaciones, inicializar el array de vacaciones como nulo
                    $professional1['vacations'] = [];
                } else {
                    // Si hay vacaciones, mapearlas al formato deseado
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
                    $fechasDiasLibres = $this->obtenerFechasDiasSemana($diasSemana);
                    if (!empty($fechasDiasLibres)) {
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
            
            //$totaltime = Service::whereIn('id', $services)->get()->sum('duration_service');
            /*$professionals = Professional::whereHas('branches', function ($query) use ($data, $services) {

             /*  $query->where('branch_id', $data['branch_id']);
            })->whereHas('branchServices', function ($query) use ($services) {
                $query->whereIn('service_id', $services);
            }, '=', count($services))->whereHas('charge', function ($query) {
                $query->where('id', 1);*/
            /*})->get();*/
            /*$professionals = Professional::whereHas('branches', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            })->where('charge_id', 1)->get();*/
            return response()->json(['professionals' => $professionals], 200, [], JSON_NUMERIC_CHECK);

            /*Log::info("Dado una branch devuelve los professionales que trabajan en ella");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $services = $request->input('services');
            //$totaltime = Service::whereIn('id', $services)->get()->sum('duration_service');
            $professionals = Professional::whereHas('branches', function ($query) use ($data, $services) {
                $query->where('branch_id', $data['branch_id']);
            })->whereHas('branchServices', function ($query) use ($services) {
                $query->whereIn('service_id', $services);
            }, '=', count($services))->whereHas('charge', function ($query) {
                $query->where('id', 1);
            })->get();
            /*$professionals = Professional::whereHas('branches', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            })->where('charge_id', 1)->get();*/
            //return response()->json(['professionals' => $professionals],200, [], JSON_NUMERIC_CHECK); */

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

        // Si el día actual en inglés está en el array de días de la semana, agregar su fecha correspondiente
        if (in_array($diaActualIngles, array_values($diasSemanaIngles))) {
            $fechas[] = Carbon::now()->format('Y-m-d');
        }
        // Obtener las fechas para cada día de la semana en el array
        foreach ($diasSemana as $dia) {
            // Restablecer la fecha actual para cada iteración del bucle
            $fechaActual = Carbon::now();
            $diaIngles = $diasSemanaIngles[$dia];
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
            $professional = Professional::find($data['professional_id']);
            if ($professional->state == 2) {
                $professional->end_time = Carbon::now();
            }
            $professional->state = $data['state'];
            $professional->start_time = Carbon::now();
            $professional->save();
            if ($data['state'] == 2) {
                $ProfessionalWorkPlace = ProfessionalWorkPlace::where('professional_id', $professional->id)->whereDate('data', Carbon::now())->whereHas('workplace', function ($query) use ($data) {
                    $query->where('busy', 1)->where('branch_id', $data['branch_id']);
                })->first();
                $workplace = Workplace::where('id', $ProfessionalWorkPlace->workplace_id)->first();
                if ($data['type'] == 'Barbero') {
                    $workplace->busy = 0;
                    $workplace->save();
                }
                if ($data['type'] == 'Tecnico') {
                    $workplace->busy = 0;
                    $workplace->save();
                    $places = json_decode($ProfessionalWorkPlace->places, true);
                    Workplace::whereIn('id', $places)->update(['select' => 0]);
                }
            }

            return response()->json(['msg' => 'Estado modificado correctamente'], 200);
        } catch (\Throwable $th) {
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
            return response()->json(['msg' => $th->getMessage() . 'Error al eliminar la professional de esta branch'], 500);
        }
    }
}
