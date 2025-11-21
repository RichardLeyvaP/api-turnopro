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
    /**
 * Obtiene todas las sucursales con sus profesionales asignados.
 *
 * @authenticated
 *
 * @response 200 {
 *   "branch": [
 *     {
 *       "id": 5,
 *       "name": "Centro",
 *       "professionals": [ ... ]
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los professionals por sucursales"}
 */
    public function index()
    {
        try {
            return response()->json(['branch' => Branch::with('professionals')->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los professionals por sucursales"], 500);
        }
    }

    /**
 * Asigna un profesional a una sucursal con configuración de comisiones escalonadas.
 *
 * @authenticated
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam professional_id integer required ID del profesional. Example: 123
 * @bodyParam ponderation integer nullable Ponderación para asignación automática (0-100). Example: 80
 * @bodyParam limit integer nullable Límite de clientes. Example: 10
 * @bodyParam mountpay number nullable Monto fijo por servicio. Example: 5.00
 * @bodyParam salary number nullable Salario base. Example: 300.00
 * @bodyParam tier1_min_sales integer required Piso de ventas Nivel 1 (0 si no aplica). Example: 0
 * @bodyParam tier1_commission_rate number required Porcentaje comisión Nivel 1. Example: 70.0
 * @bodyParam tier2_min_sales integer required Piso de ventas Nivel 2. Example: 1000
 * @bodyParam tier2_commission_rate number required Porcentaje comisión Nivel 2. Example: 75.0
 * @bodyParam tier3_min_sales integer required Piso de ventas Nivel 3. Example: 2000
 * @bodyParam tier3_commission_rate number required Porcentaje comisión Nivel 3. Example: 80.0
 *
 * @response 200 {"msg": "Professional asignado correctamente a la sucursal"}
 * @response 422 {"msg": "El piso del Nivel 1 debe ser menor al Nivel 2"}
 * @response 500 {"msg": "Error al asignar el professional: ..."}
 */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'ponderation' => 'nullable',
                'limit' => 'nullable',
                'mountpay' => 'nullable',
                'salary' => 'nullable|numeric',
                'tier1_min_sales' => 'required|integer|min:0',
                'tier1_commission_rate' => 'required|numeric|min:0|max:100',
                'tier2_min_sales' => 'required|integer|min:0',
                'tier2_commission_rate' => 'required|numeric|min:0|max:100',
                'tier3_min_sales' => 'required|integer|min:0',
                'tier3_commission_rate' => 'required|numeric|min:0|max:100'
            ]);

            // Validación de secuencia de niveles (solo si no son cero)
            if ($data['tier1_min_sales'] > 0 && $data['tier2_min_sales'] > 0) {
                if ($data['tier1_min_sales'] >= $data['tier2_min_sales']) {
                    return response()->json(['msg' => 'El piso del Nivel 1 debe ser menor al Nivel 2'], 422);
                }
            }

            if ($data['tier2_min_sales'] > 0 && $data['tier3_min_sales'] > 0) {
                if ($data['tier2_min_sales'] >= $data['tier3_min_sales']) {
                    return response()->json(['msg' => 'El piso del Nivel 2 debe ser menor al Nivel 3'], 422);
                }
            }

            $branch = Branch::find($data['branch_id']);
            $professional = Professional::find($data['professional_id']);

            $attachData = [
                'ponderation' => $data['ponderation'],
                'limit' => $data['limit'],
                'mountpay' => $data['mountpay'],
                'salary' => $data['salary'],
                'tier1_min_sales' => $data['tier1_min_sales'],
                'tier1_commission_rate' => $data['tier1_commission_rate'],
                'tier2_min_sales' => $data['tier2_min_sales'],
                'tier2_commission_rate' => $data['tier2_commission_rate'],
                'tier3_min_sales' => $data['tier3_min_sales'],
                'tier3_commission_rate' => $data['tier3_commission_rate']
            ];

            // Limpiar campos nulos
            $attachData = array_filter($attachData, function($value) {
                return $value !== null;
            });

            $branch->professionals()->attach($professional->id, $attachData);

            DB::commit();
            return response()->json(['msg' => 'Professional asignado correctamente a la sucursal'], 200);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(['msg' => 'Error al asignar el professional: ' . $th->getMessage()], 500);
        }
    }

    /**
 * Obtiene las sucursales a las que pertenece un profesional.
 *
 * @authenticated
 * @queryParam professional_id integer required ID del profesional. Example: 123
 *
 * @response 200 {
 *   "branches": [
 *     {
 *       "id": 5,
 *       "name": "Centro",
 *       "pivot": { ... }
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar las branches"}
 */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric'
            ]);
            $professional = Professional::find($data['professional_id']);
            return response()->json(['branches' => $professional->branches], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar las branches"], 500);
        }
    }
    
    /**
 * Obtiene todos los profesionales asignados a una sucursal con su configuración.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "professionals": [
 *     {
 *       "id": 123,
 *       "professional_id": 123,
 *       "name": "Yasmany",
 *       "image_url": "professionals/123.jpg",
 *       "charge": "Barbero",
 *       "ponderation": 80,
 *       "salary": 300.00,
 *       "tier1_min_sales": 0,
 *       "tier1_commission_rate": 70.0,
 *       ...
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar las branches"}
 */
    public function branch_professionals(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $now = Carbon::now();
            $professionals = BranchProfessional::where('branch_id', $data['branch_id'])->whereHas('professional', function ($query) {
                $query->whereNull('deleted_at'); // Verifica que el profesional no esté eliminado
            })->with('professional.charge')->get();
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
                    'salary' => $branchprofessional['salary'],
                    'tier1_min_sales' => $branchprofessional['tier1_min_sales'] ?? 0,
                    'tier1_commission_rate' => $branchprofessional['tier1_commission_rate'] ?? 0,
                    'tier2_min_sales' => $branchprofessional['tier2_min_sales'] ?? 0,
                    'tier2_commission_rate' => $branchprofessional['tier2_commission_rate'] ?? 0,
                    'tier3_min_sales' => $branchprofessional['tier3_min_sales'] ?? 0,
                    'tier3_commission_rate' => $branchprofessional['tier3_commission_rate'] ?? 0,
                    ];
            }
            return response()->json(['professionals' => $data], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las branches"], 500);
        }
    }

    /**
 * Obtiene los barberos (y barberos-encargados) de una sucursal para el tótem.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "professionals": [
 *     {
 *       "id": 123,
 *       "name": "Yasmany",
 *       "surname": "Sánchez",
 *       "image_url": "professionals/123.jpg"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar las branches"}
 */
    public function branch_professionals_barber_totem(Request $request)
    {
        try {
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
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las branches"], 500);
        }
    }

    /**
 * Obtiene barberos disponibles en una sucursal que ofrecen ciertos servicios.
 *
 * Incluye vacaciones, días libres y ponderación.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam services array required IDs de los servicios requeridos. Example: [1,2,3]
 *
 * @response 200 {
 *   "professionals": [
 *     {
 *       "id": 123,
 *       "name": "Yasmany",
 *       "ponderation": 80,
 *       "vacations": [
 *         {"startDate": "2025-12-01", "endDate": "2025-12-05"},
 *         {"startDate": "2025-11-25", "endDate": "2025-11-25"}
 *       ],
 *       ...
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar las branches"}
 */
    public function branch_professionals_barber(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $services = $request->input('services');
            $professionals = [];
            $professionals1 = Professional::whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']); // ✅ Profesional asignado a la sucursal
            })
            ->whereHas('branchServiceProfessionals', function ($query) use ($services, $data) {
                $query->whereHas('branchService', function ($q) use ($services, $data) {
                    $q->whereIn('service_id', $services)
                      ->where('branch_id', $data['branch_id']); // servicio en la misma sucursal
                });
            }, '=', count($services)) // ✅ Tiene exactamente los servicios solicitados
            ->whereHas('charge', function ($query) {
                $query->where('name', 'Barbero')
                      ->orWhere('name', 'Barbero y Encargado');
            })
            ->with('branches') // sigue cargando todas las sucursales (para usar en map)
            ->select('id', 'name', 'surname', 'second_surname', 'image_url', 'state')
            ->get()
            ->map(function ($professional) use ($data) {
                $pivot = $professional->branches()->where('branch_id', $data['branch_id'])->first();

                $ponderation = $pivot ? $pivot->pivot->ponderation : 100;

                return [
                    'id' => $professional->id,
                    'name' => $professional->name,
                    'surname' => $professional->surname,
                    'second_surname' => $professional->second_surname,
                    'image_url' => $professional->image_url . '?' . Carbon::now()->timestamp, // evitar caché
                    'ponderation' => $ponderation,
                    'state' => 1,
                ];
            })
            ->sortBy('ponderation')
            ->values();

            foreach ($professionals1 as $professional1) {
                $vacation = [];
                $diasSemana = [];
                // Obtener vacaciones del profesional
                $vacation = Vacation::where('professional_id', $professional1['id'])
                                    ->whereDate('endDate', '>=', Carbon::now())
                                    ->get();
                
                if ($vacation->isEmpty()) {
                    // Si no hay vacaciones, inicializar el array de vacaciones como nulo
                    $professional1['vacations'] = [];
                } else {
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
            
            return response()->json(['professionals' => $professionals], 200, [], JSON_NUMERIC_CHECK);

        } catch (\Throwable $th) {
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

        $añoActual = Carbon::now()->year;
        $añoLimite = $añoActual + 2;
        $fechas = []; // ¡Importante inicializarlo aquí!

        foreach ($diasSemana as $dia) {
            $diaIngles = $diasSemanaIngles[$dia] ?? null;

            if (!$diaIngles) {
                continue; // Saltar si no se encuentra la traducción
            }

            $fecha = Carbon::now();

            // Si hoy NO es el día deseado, ir al próximo
            if ($fecha->isoFormat('dddd') !== $diaIngles) {
                $fecha = $fecha->next($diaIngles);
            }

            // Generar todas las fechas hasta el límite
            while ($fecha->year <= $añoLimite) {
                $fechas[] = $fecha->format('Y-m-d');
                $fecha->addWeek();
            }
        }

        // Eliminar duplicados y ordenar
        $fechas = array_unique($fechas);
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

    /**
 * Obtiene profesionales de tipo barbero, técnico o barbero-encargado en una sucursal.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "professionals": [
 *     {
 *       "id": 123,
 *       "name": "Yasmany",
 *       "charge": { "name": "Barbero" },
 *       ...
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar las branches"}
 */
    public function branch_professionals_barber_tecnico(Request $request)
    {
        try {
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
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las branches"], 500);
        }
    }

    /**
 * Actualiza la configuración de un profesional en una sucursal.
 *
 * @authenticated
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam professional_id integer required ID del profesional. Example: 123
 * @bodyParam ponderation integer nullable Ponderación. Example: 80
 * @bodyParam limit integer nullable Límite de clientes. Example: 10
 * @bodyParam mountpay number nullable Monto fijo. Example: 5.00
 * @bodyParam salary number nullable Salario base. Example: 300.00
 * @bodyParam tier1_min_sales integer required Piso ventas N1. Example: 0
 * @bodyParam tier1_commission_rate number required % comisión N1. Example: 70.0
 * @bodyParam tier2_min_sales integer required Piso ventas N2. Example: 1000
 * @bodyParam tier2_commission_rate number required % comisión N2. Example: 75.0
 * @bodyParam tier3_min_sales integer required Piso ventas N3. Example: 2000
 * @bodyParam tier3_commission_rate number required % comisión N3. Example: 80.0
 *
 * @response 200 {"msg": "Professional actualizado correctamente"}
 * @response 400 {"msg": "El piso del Nivel 1 debe ser menor al Nivel 2"}
 * @response 500 {"msg": "Error al actualizar el professional: ..."}
 */
    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'ponderation' => 'nullable',
                'limit' => 'nullable',
                'mountpay' => 'nullable',
                'salary' => 'nullable|numeric',
                'tier1_min_sales' => 'required|integer|min:0',
                'tier1_commission_rate' => 'required|numeric|min:0|max:100',
                'tier2_min_sales' => 'required|integer|min:0',
                'tier2_commission_rate' => 'required|numeric|min:0|max:100',
                'tier3_min_sales' => 'required|integer|min:0',
                'tier3_commission_rate' => 'required|numeric|min:0|max:100'
            ]);

            // Validación de secuencia de niveles (solo si no son cero)
            if ($data['tier1_min_sales'] > 0 && $data['tier2_min_sales'] > 0) {
                if ($data['tier1_min_sales'] >= $data['tier2_min_sales']) {
                    return response()->json(['msg' => 'El piso del Nivel 1 debe ser menor al Nivel 2'], 400);
                }
            }

            if ($data['tier2_min_sales'] > 0 && $data['tier3_min_sales'] > 0) {
                if ($data['tier2_min_sales'] >= $data['tier3_min_sales']) {
                    return response()->json(['msg' => 'El piso del Nivel 2 debe ser menor al Nivel 3'], 400);
                }
            }

            $branch = Branch::find($data['branch_id']);
            $professional = Professional::find($data['professional_id']);
            
            $updateData = [
                'ponderation' => $data['ponderation'],
                'limit' => $data['limit'],
                'mountpay' => $data['mountpay'],
                'salary' => $data['salary'],
                'tier1_min_sales' => $data['tier1_min_sales'],
                'tier1_commission_rate' => $data['tier1_commission_rate'],
                'tier2_min_sales' => $data['tier2_min_sales'],
                'tier2_commission_rate' => $data['tier2_commission_rate'],
                'tier3_min_sales' => $data['tier3_min_sales'],
                'tier3_commission_rate' => $data['tier3_commission_rate']
            ];

            // Limpiar campos nulos
            $updateData = array_filter($updateData, function($value) {
                return $value !== null;
            });

            $branch->professionals()->updateExistingPivot($professional->id, $updateData);

            return response()->json(['msg' => 'Professional actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar el professional: ' . $th->getMessage()], 500);
        }
    }
    
    /**
 * Actualiza el estado de un profesional en una sucursal (trabajando, en colación, salida, etc.).
 *
 * Gestiona notificaciones, puestos de trabajo y registros.
 *
 * @authenticated
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam professional_id integer required ID del profesional. Example: 123
 * @bodyParam type string required Tipo de profesional. Example: Barbero
 * @bodyParam state integer required Nuevo estado (0: disponible, 1: rechazado, 2: colación, 3: solicita colación, 4: solicita salida). Example: 2
 *
 * @response 200 {"msg": "Estado modificado correctamente"}
 * @response 500 {"msg": "Error al actualizar el professionals de esa branch"}
 */
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
                    $ProfessionalWorkPlace = ProfessionalWorkPlace::with(['workplace' => function ($query) use ($data) {
                        $query->where('busy', 1)->where('branch_id', $data['branch_id']);
                    }])
                    ->where('professional_id', $professional->id)
                    ->whereDate('data', Carbon::now())
                    ->latest('created_at')
                    ->first();
                    if ($ProfessionalWorkPlace && $ProfessionalWorkPlace->workplace) {
                        $ProfessionalWorkPlace->workplace->busy = 0;
                        $ProfessionalWorkPlace->workplace->save();
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
                    $professional->start_time = Carbon::now();
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
                $reservations = $professional->reservations()
                    ->where('branch_id', $data['branch_id'])
                    ->where('confirmation', 4)
                    ->whereDate('data', Carbon::now())
                    ->whereHas('tail', function ($query) {
                        $query->where('aleatorie', '!=', 0);
                    })
                    ->get();
                    if ($reservations->isNotEmpty()) {
                    // Itera sobre las reservas y actualiza el campo 'aleatorie' de las relaciones 'tail'
                        foreach ($reservations as $reservation) {
                            $tail = $reservation->tail;
                            $tail->aleatorie = 1;
                            $tail->save();
                        }
                    }
            }
            elseif ($data['state'] == 4 || $data['state'] == 3){
                $branch = Branch::find($data['branch_id']);
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
            return response()->json(['msg' => $th->getMessage() . 'Error al actualizar el professionals de esa branch'], 500);
        }
    }
    
    /**
 * Obtiene los profesionales que están en colación (state = 2) en una sucursal.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "professionals": [
 *     {
 *       "professional_name": "Yasmany Sánchez",
 *       "client_image": "professionals/123.jpg",
 *       "professional_id": 123,
 *       "start_time": "13:30",
 *       "charge": "Barbero"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar las branches"}
 */
    public function branch_colacion(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $professionals = Professional::whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id'])->where('arrival', '!=', null);
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
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las branches"], 500);
        }
    }

    /**
 * Obtiene los profesionales que han solicitado colación (state = 3) en una sucursal.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "professionals": [ ... ]
 * }
 * @response 500 {"msg": "Error al mostrar las branches"}
 */
    public function branch_colacion3(Request $request)
    {

        try {
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
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las branches"], 500);
        }
    }

    /**
 * Obtiene los profesionales que han solicitado salida (state = 4) en una sucursal.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "professionals": [ ... ]
 * }
 * @response 500 {"msg": "Error al mostrar las branches"}
 */
    public function branch_colacion4(Request $request)
    {

        try {
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
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las branches"], 500);
        }
    }

    /**
 * Elimina la asignación de un profesional en una sucursal.
 *
 * @authenticated
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam professional_id integer required ID del profesional. Example: 123
 *
 * @response 200 {"msg": "Professional eliminada correctamente de la branch"}
 * @response 500 {"msg": "Error al eliminar la professional de esta branch"}
 */
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
