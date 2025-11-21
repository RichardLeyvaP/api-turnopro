<?php

namespace App\Http\Controllers;

use App\Models\BranchProfessional;
use App\Models\BranchRule;
use App\Models\BranchRuleProfessional;
use App\Models\Professional;
use App\Models\Rule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BranchRuleProfessionalController extends Controller
{
    /**
 * Obtiene todas las asignaciones de reglas a profesionales con sus detalles.
 *
 * @authenticated
 *
 * @response 200 {
 *   "branchRuleProfesional": [
 *     {
 *       "id": 123,
 *       "branchRule": { "rule": { "name": "Puntualidad", ... } },
 *       "professional": { "name": "Yasmany", ... }
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar las rules por trabajador"}
 */
    public function index()
    {
        try {
            $professionalrules = BranchRuleProfessional::with('branchRule.rule', 'professional')->get();
            return response()->json(['branchRuleProfesional' => $professionalrules], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las rules por trabajador"], 500);
        }
    }

    /**
 * Obtiene las convivencias (reglas) de una sucursal en una fecha específica con estadísticas por profesional.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam date string required Fecha en formato Y-m-d (se usa la fecha de Chile). Example: 2025-11-21
 *
 * @response 200 {
 *   "convivencias": [
 *     {
 *       "id": 123,
 *       "estado": 1,
 *       "professionalName": "Yasmany",
 *       "professionalImage": "professionals/123.jpg",
 *       "ruleName": "Puntualidad",
 *       "last_edited_at": "2025-11-21 10:30:00",
 *       "professionalStats": {
 *         "fulfilled": 5,
 *         "not_fulfilled": 1,
 *         "not_updated": 2,
 *         "total": 8
 *       }
 *     }
 *   ]
 * }
 * @response 500 {"success": false, "msg": "Error al obtener las convivencias: ..."}
 */
    public function index_branch_data(Request $request)
    {
        try {
            // Validar los parámetros de entrada
            $request->validate([
                'branch_id' => 'required|integer|exists:branches,id',
                'date' => 'required|date',
            ]);

            // Obtener los parámetros
            $timezone = 'America/Santiago';
            $dateChile = now($timezone)->format('Y-m-d'); // Formato: 2025-05-12
    
            $branch_id = $request->input('branch_id');
            $date = $request->input('date'); // Ignoramos el parámetro del front
            //$date = $dateChile; // Usamos la fecha de Chile

            $convivencias = BranchRuleProfessional::with(['branchRule.rule', 'professional'])
                ->whereHas('branchRule', function ($query) use ($branch_id) {
                    $query->where('branch_id', $branch_id);
                })
                ->whereDate('data', $date)
                ->get();

            // 1. Primero calculamos las estadísticas por profesional
            $statsByProfessional = $convivencias->groupBy('professional.id')->map(function ($group) {
                return [
                    'fulfilled' => $group->where('estado', 1)->count(),      // Estado 1: Cumplidas
                    'not_fulfilled' => $group->where('estado', 0)->count(), // Estado 0: Incumplidas
                    'not_updated' => $group->where('estado', 3)->count(),   // Estado 3: No actualizadas
                    'total' => $group->count(),
                ];
            });

            // 2. Mantenemos tu formato original exacto y agregamos las estadísticas
            $formattedData = $convivencias->map(function ($convivencia) use ($statsByProfessional) {
                return [
                    'id' => $convivencia->id,
                    'estado' => $convivencia->estado,
                    'professionalName' => $convivencia->professional->name,
                    'professionalImage' => $convivencia->professional->image_url,
                    'ruleName' => $convivencia->branchRule->rule->name,
                    'last_edited_at' => $convivencia->last_edited_at,
                    // Agregamos las estadísticas para este profesional
                    'professionalStats' => $statsByProfessional[$convivencia->professional->id] ?? [
                        'fulfilled' => 0,
                        'not_fulfilled' => 0,
                        'not_updated' => 0,
                        'total' => 0
                    ]
                ];
            });

            return response()->json([
                'convivencias' => $formattedData
            ]);
            // Devolver la respuesta formateada
            return response()->json([
                'success' => true,
                'convivencias' => $formattedData,
            ], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'msg' => 'Error al obtener las convivencias: ' . $th->getMessage(),
            ], 500);
        }
    }

    /**
 * Asigna una regla de sucursal a un profesional con un estado inicial.
 *
 * @authenticated
 * @bodyParam branch_rule_id integer required ID de la regla de sucursal. Example: 101
 * @bodyParam professional_id integer required ID del profesional. Example: 123
 * @bodyParam estado boolean required Estado inicial (true = cumplida, false = no cumplida). Example: true
 *
 * @response 200 {"msg": "Estado de la rule asignado correctamente al professional"}
 * @response 500 {"msg": "Error al asignar el estado de la rule a este professional"}
 */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_rule_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'estado' => 'required|boolean'
            ]);
            $branchrule = BranchRule::find($data['branch_rule_id']);
            $professional = Professional::find($data['professional_id']);
            $professional->branchRules()->attach($branchrule->id, ['data' => Carbon::now(), 'estado' => $data['estado']]);
            return response()->json(['msg' => 'Estado de la rule asignado correctamente al professional'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al asignar el estado de la rule a este professional'], 500);
        }
    }

    /**
 * Obtiene el resumen de cumplimiento de reglas por tipo para un profesional en un rango de fechas.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam professional_id integer required ID del profesional. Example: 123
 * @queryParam startDate string required Fecha de inicio (Y-m-d). Example: 2025-11-01
 * @queryParam endDate string required Fecha de fin (Y-m-d). Example: 2025-11-30
 *
 * @response 200 [
 *   {
 *     "rule_name": "Puntualidad",
 *     "estado_0": 2,
 *     "estado_1": 10,
 *     "estado_3": 1
 *   }
 * ]
 * @response 500 {"msg": "Error al mostrar las llegadas tardes"}
 */
    public function branch_rule_professional_periodo(Request $request)
    {

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'startDate' => 'required|date',
                'endDate' => 'required|date',
            ]);
            $results = [];
            $results = BranchRuleProfessional::whereHas('branchRule', function ($query) use ($data) {
                // Filtramos por la sucursal
                $query->where('branch_id', $data['branch_id']);
            })
                ->where('professional_id', $data['professional_id']) // Filtramos por el profesional
                ->whereDate('data', '>=', $data['startDate']) // Filtramos por fecha mayor o igual al inicio
                ->whereDate('data', '<=', $data['endDate']) // Filtramos por fecha menor o igual al fin
                ->join('branch_rule', 'branch_rule_professional.branch_rule_id', '=', 'branch_rule.id')
                ->join('rules', 'branch_rule.rule_id', '=', 'rules.id')
                ->select(
                    'rules.name as rule_name',
                    DB::raw('SUM(CASE WHEN branch_rule_professional.estado = 0 THEN 1 ELSE 0 END) as estado_0'),
                    DB::raw('SUM(CASE WHEN branch_rule_professional.estado = 1 THEN 1 ELSE 0 END) as estado_1'),
                    DB::raw('SUM(CASE WHEN branch_rule_professional.estado = 3 THEN 1 ELSE 0 END) as estado_3')
                )
                ->groupBy('rules.name') // Agrupamos por el nombre de la regla
                ->get();


            return response()->json($results, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al mostrar las llegadas tardes'], 500);
        }
    }

    /**
 * Actualiza o crea el estado de una regla de tipo específico (ej. "Tiempo") para un profesional.
 *
 * Si el tipo es "Tiempo" y estado = 0, marca al profesional como "living = 1" en BranchProfessional.
 *
 * @authenticated
 * @bodyParam type string required Tipo de regla (ej. "Tiempo", "Uniforme"). Example: Tiempo
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam professional_id integer required ID del profesional. Example: 123
 * @bodyParam estado integer required Estado (0: no cumplida, 1: cumplida, 3: no actualizada). Example: 0
 * @bodyParam id integer optional ID de la asignación (si existe). Example: 456
 *
 * @response 200 {"msg": "Estado actualizado correctamente de una rule del professional"}
 * @response 500 {"msg": "Error al asignar el estado de la rule a este professional"}
 */
    public function storeByType(Request $request)
    {
        try {
            $data = $request->validate([
                'type' => 'required|string',
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'estado' => 'required|int',
                'id' => 'sometimes|int'
            ]);
            $professional = Professional::find($data['professional_id']);
            $branchrule = BranchRule::whereHas('rule', function ($query) use ($data) {
                $query->where('type', $data['type']);
            })->where('branch_id', $data['branch_id'])->first();
            $existencia = BranchRuleProfessional::whereDate('data', Carbon::now())
                ->where('branch_rule_id', $branchrule->id)
                ->where('professional_id', $data['professional_id'])
                ->first();
            if ($data['type'] == 'Tiempo' && $data['estado'] == 0) {
                $branchProfessional = BranchProfessional::where('branch_id', $data['branch_id'])
                    ->where('professional_id', $data['professional_id'])
                    ->firstOrFail();
                // Asignar el siguiente número de llegada
                $branchProfessional->living = 1;

                // Guardar los cambios
                $branchProfessional->save();
            }
            if ($existencia) {
                $existencia->estado = $data['estado'];
                $existencia->save();
                //$professional->branchrules()->updateExistingPivot($branchrule->id,['estado'=>$data['estado']]);     
                return response()->json(['msg' => 'Estado actualizado correctamente de una rule del professional'], 200);
            }
            return response()->json(['msg' => 'Estado de la rule asignado correctamente al professional'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al asignar el estado de la rule a este professional'], 500);
        }
    }

    /**
 * Actualiza el estado de una regla específica por su ID.
 *
 * Incluye lógica para "Tiempo" → actualiza `living = 1`.
 *
 * @authenticated
 * @bodyParam type string required Tipo de regla. Example: Tiempo
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam professional_id integer required ID del profesional. Example: 123
 * @bodyParam estado integer required Nuevo estado. Example: 0
 * @bodyParam id integer required ID de la asignación en BranchRuleProfessional. Example: 456
 *
 * @response 200 {"msg": "Estado actualizado correctamente de una rule del professional"}
 * @response 204 {"msg": "Rule del Professional no encontrado"}
 * @response 500 {"msg": "Error al asignar el estado de la rule a este professional"}
 */
    public function storeByTypeId(Request $request)
    {
        try {
            $data = $request->validate([
                'type' => 'required|string',
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'estado' => 'required|int',
                'id' => 'required|int'
            ]);
            $branchRuleProfessional = BranchRuleProfessional::where('id', $data['id'])->first();
            if ($branchRuleProfessional) {
                $branchRuleProfessional->estado = $data['estado'];
                $branchRuleProfessional->save();

                if ($data['type'] == 'Tiempo' && $data['estado'] == 0) {
                    $branchProfessional = BranchProfessional::where('branch_id', $data['branch_id'])
                        ->where('professional_id', $data['professional_id'])
                        ->firstOrFail();
                    // Asignar el siguiente número de llegada
                    $branchProfessional->living = 1;

                    // Guardar los cambios
                    $branchProfessional->save();
                }
                return response()->json(['msg' => 'Estado actualizado correctamente de una rule del professional'], 200);
            } else {
                return response()->json(['msg' => 'Rule del Professional no encontrado'], 204);
            }
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al asignar el estado de la rule a este professional'], 500);
        }
    }

    /**
 * Actualiza solo el estado de la regla de tipo "Tiempo" y marca al profesional como living.
 *
 * @authenticated
 * @bodyParam type string required Debe ser "Tiempo". Example: Tiempo
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam professional_id integer required ID del profesional. Example: 123
 * @bodyParam estado integer required Estado (0 para activar living). Example: 0
 *
 * @response 200 {"msg": "Estado actualizado"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function storeByType_time(Request $request)
    {
        try {
            $data = $request->validate([
                'type' => 'required|string',
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'estado' => 'required|int'
            ]);
            if ($data['type'] == 'Tiempo' && $data['estado'] == 0) {
                $branchProfessional = BranchProfessional::where('branch_id', $data['branch_id'])
                    ->where('professional_id', $data['professional_id'])
                    ->firstOrFail();
                // Asignar el siguiente número de llegada
                $branchProfessional->living = 1;

                // Guardar los cambios
                $branchProfessional->save();
            }
            return response()->json(['msg' => 'Estado actualizado'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error interno del sistema'], 500);
        }
    }

    /**
 * Obtiene todas las reglas asignadas a un profesional (sin filtrar por sucursal ni fecha).
 *
 * @authenticated
 * @queryParam professional_id integer required ID del profesional. Example: 123
 *
 * @response 200 {
 *   "professional": [
 *     {
 *       "id": 123,
 *       "branchRule": { ... },
 *       "pivot": { "estado": 1, "data": "2025-11-21" }
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar el estado de las rules de un professional"}
 */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'numeric'
            ]);
            return response()->json(['professional' => Professional::find($data['professional_id'])->branchRules], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el estado de las rules de un  professional"], 500);
        }
    }

    /**
 * Obtiene las reglas de una sucursal para un profesional en una fecha (por defecto: hoy).
 *
 * Si el profesional está activo (state 1 o 2) y no tiene una regla asignada para la fecha, se crea con estado = 3 (no actualizada).
 *
 * @authenticated
 * @queryParam professional_id integer required ID del profesional. Example: 123
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam data string optional Fecha en formato Y-m-d. Example: 2025-11-21
 *
 * @response 200 {
 *   "rules": [
 *     {
 *       "id": 456,
 *       "name": "Puntualidad",
 *       "description": "...",
 *       "type": "attendance",
 *       "state": 3
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar el estado de las rules de un professional"}
 */
    public function rules_professional(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id'  => 'required|numeric'
            ]);
            if ($request->has('data')) {
                $data['data'] = $request->data;
            } else {
                $data['data'] = Carbon::now()->toDateString();
            }
            $result = [];
            $i = 0;
            $professional = Professional::find($data['professional_id']);
            $branchrules = BranchRule::where('branch_id', $data['branch_id'])->get();
            if ($professional->state == 1 || $professional->state == 2) {
                foreach ($branchrules as $branchrule) {
                    $existencia = Professional::whereHas('branchRules', function ($query) use ($branchrule, $data) {
                        $query->whereDate('data', $data['data'])->where('branch_rule_id', $branchrule->id);
                    })->where('id', $data['professional_id'])->exists();
                    if (!$existencia) {
                        $professional->branchRules()->attach($branchrule->id, ['data' => $data['data'], 'estado' => 3]);
                    }
                }
            }

            $branchRuleProfessionals = BranchRuleProfessional::whereHas('branchRule', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->where('professional_id', $data['professional_id'])->whereDate('data', $data['data'])->get()->map(function ($branchRuleProfessional) {
                return [
                    'id' => $branchRuleProfessional->id,
                    'name' => $branchRuleProfessional->branchRule->rule->name,
                    'description' => $branchRuleProfessional->branchRule->rule->description,
                    'type' => $branchRuleProfessional->branchRule->rule->type,
                    'state' => $branchRuleProfessional->estado
                ];
            });
            return response()->json(['rules' => $branchRuleProfessionals], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar el estado de las rules de un  professional"], 500);
        }
    }

    /**
 * Actualiza el estado de una regla de sucursal asignada a un profesional.
 *
 * @authenticated
 * @bodyParam branch_rule_id integer required ID de la regla de sucursal. Example: 101
 * @bodyParam professional_id integer required ID del profesional. Example: 123
 * @bodyParam estado boolean required Nuevo estado. Example: true
 *
 * @response 200 {"msg": "Estado actualizado correctamente del cumplimiento de una rule del professional"}
 * @response 500 {"msg": "Error al actualizar estado del cumplimiento de rule del professional"}
 */
    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_rule_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'estado' => 'required|boolean'
            ]);
            $professional = Professional::find($data['professional_id']);
            $branchrule = BranchRule::find($data['branch_rule_id']);
            $professional->branchrules()->updateExistingPivot($branchrule->id, ['estado' => $data['estado']]);
            return response()->json(['msg' => 'Estado actualizado correctamente del cumplimiento de una rule del professional'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar estado del cumplimiento de rule del professional'], 500);
        }
    }

    /**
 * Actualiza masivamente el estado de varias reglas de convivencia.
 *
 * Útil para el frontend al guardar múltiples cambios a la vez.
 *
 * @authenticated
 * @bodyParam changes array required Lista de cambios.
 * @bodyParam changes.*.id integer required ID de la asignación. Example: 456
 * @bodyParam changes.*.estado integer required Nuevo estado. Example: 1
 *
 * @response 200 {
 *   "success": true,
 *   "message": "Estados actualizados correctamente.",
 *   "updated_count": 5,
 *   "updated_ids": [456, 457]
 * }
 * @response 500 {
 *   "success": false,
 *   "message": "Error al actualizar estados",
 *   "error": "..."
 * }
 */
    public function update_rule_state(Request $request)
    {
        try {
            $data = $request->validate([
                'changes' => 'required|array',
                'changes.*.id' => 'required|numeric',
                'changes.*.estado' => 'required|numeric'
            ]);

            DB::beginTransaction();

            $updatedIds = [];
            $now = now();

            foreach ($data['changes'] as $change) {
                $branchRuleProfessional = BranchRuleProfessional::find($change['id']);

                if ($branchRuleProfessional) {
                    $branchRuleProfessional->estado = $change['estado'];
                    $branchRuleProfessional->last_edited_at = $now;
                    $branchRuleProfessional->save();

                    $updatedIds[] = $branchRuleProfessional->id;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estados actualizados correctamente.',
                'updated_count' => count($updatedIds),
                'updated_ids' => $updatedIds
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar estados',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
 * Elimina una asignación de regla a profesional.
 *
 * @authenticated
 * @bodyParam id integer required ID de la asignación en BranchRuleProfessional. Example: 456
 *
 * @response 200 {"msg": "Estado del cumplimiento de la rule eliminado correctamente de este trabajador"}
 * @response 500 {"msg": "Error al eliminar el estado del cumplimiento de la rule de este trabajador"}
 */
    public function destroy(Request $request)
    {
        try {

            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            BranchRuleProfessional::destroy($data['id']);

            return response()->json(['msg' => 'Estado del cumplimiento de la rule eliminado correctamente de este trabajador'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el estado del cumplimiento de la rule de este trabajador'], 500);
        }
    }
}
