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
    public function index()
    {
        try {
            $professionalrules = BranchRuleProfessional::with('branchRule.rule', 'professional')->get();
            return response()->json(['branchRuleProfesional' => $professionalrules], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las rules por trabajador"], 500);
        }
    }

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

            // Consulta para obtener las convivencias de los trabajadores de la sucursal en la date dada
            /*$convivencias = BranchRuleProfessional::with(['branchRule.rule', 'professional'])
                ->whereHas('branchRule', function ($query) use ($branch_id) {
                    $query->where('branch_id', $branch_id); // Filtrar por branch_id
                })
                ->whereDate('data', $date) // Filtrar por fecha
                ->get();

            // Formatear la respuesta para el frontend
            $formattedData = $convivencias->map(function ($convivencia) {
                return [
                    'id' => $convivencia->id,
                    'estado' => $convivencia->estado,
                    'professionalName' => $convivencia->professional->name, // Nombre del profesional
                    'professionalImage' => $convivencia->professional->image_url, // Nombre del profesional
                    'ruleName' => $convivencia->branchRule->rule->name, // Nombre de la regla
                    'last_edited_at' => $convivencia->last_edited_at, // Nombre de la regla
                ];
            });*/

            // Devolver la respuesta formateada
            return response()->json([
                'success' => true,
                'convivencias' => $formattedData,
            ], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            // Manejo de errores
            Log::error($th);
            return response()->json([
                'success' => false,
                'msg' => 'Error al obtener las convivencias: ' . $th->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info("Asignar cumplimiento de rule a un professional");
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
            Log::error($th);
            return response()->json(['msg' => 'Error al asignar el estado de la rule a este professional'], 500);
        }
    }

    //obtener la cantidad de estado por convivencias
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
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al mostrar las llegadas tardes'], 500);
        }
    }


    public function storeByType_ANTERIOR(Request $request)
    {
        Log::info("Asignar cumplimiento de rule a un professional");
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
            //   $existencia = Professional::whereHas('branchRules', function ($query) use ($branchrule){
            //         $query->whereDate('data', Carbon::now())->where('branch_rule_id', $branchrule->id);
            //     })->exists();
            $existencia = BranchRuleProfessional::where('branch_rule_id', $branchrule->id)->where('professional_id', $data['professional_id'])->whereDate('data', Carbon::now())->first();
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
                $existencia->estado = 0;
                $existencia->save();
                //$professional->branchrules()->updateExistingPivot($branchrule->id,['estado'=>$data['estado']]);     
                return response()->json(['msg' => 'Estado actualizado correctamente de una rule del professional'], 200);
            }
            return response()->json(['msg' => 'Estado de la rule asignado correctamente al professional'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al asignar el estado de la rule a este professional'], 500);
        }
    }

    public function storeByType(Request $request)
    {
        Log::info("Asignar cumplimiento de rule a un professional");
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
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al asignar el estado de la rule a este professional'], 500);
        }
    }

    public function storeByTypeId(Request $request)
    {
        Log::info("Asignar cumplimiento de rule a un professional por id-2");

        try {
            $data = $request->validate([
                'type' => 'required|string',
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'estado' => 'required|int',
                'id' => 'required|int'
            ]);
            Log::info($data['id']);
            Log::info($data['type']);
            $branchRuleProfessional = BranchRuleProfessional::where('id', $data['id'])->first();
            Log::info($branchRuleProfessional);
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
                Log::info($branchRuleProfessional);
                return response()->json(['msg' => 'Estado actualizado correctamente de una rule del professional'], 200);
            } else {
                return response()->json(['msg' => 'Rule del Professional no encontrado'], 204);
            }
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al asignar el estado de la rule a este professional'], 500);
        }
    }

    public function storeByType_time(Request $request)
    {
        Log::info("Asignar cumplimiento de rule a un professional");
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
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error interno del sistema'], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            Log::info("Entra a buscar el estado de las rules de un  professional");
            $data = $request->validate([
                'professional_id' => 'numeric'
            ]);
            return response()->json(['professional' => Professional::find($data['professional_id'])->branchRules], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar el estado de las rules de un  professional"], 500);
        }
    }

    public function rules_professional(Request $request)
    {
        try {
            Log::info("Entra a buscar el estado de las rules de un  professional");
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
            Log::error($th->getMessage());
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar el estado de las rules de un  professional"], 500);
        }
    }

    public function update(Request $request)
    {
        Log::info("actualizar estado del cumplimiento de rule a un professional");
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
            Log::error($th);
            return response()->json(['msg' => 'Error al actualizar estado del cumplimiento de rule del professional'], 500);
        }
    }

    /*public function update_rule_state(Request $request)
    {
        Log::info("actualizar estado del cumplimiento de rule a un professional");
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'estado' => 'required|numeric'
            ]);

            // Buscar el registro por ID
            $branchRuleProfessional = BranchRuleProfessional::findOrFail($data['id']);

            // Actualizar el estado
            $branchRuleProfessional->estado = $data['estado'];
            $branchRuleProfessional->last_edited_at = now(); // Actualizar la fecha y hora actual
            $branchRuleProfessional->save();

            // Devolver una respuesta exitosa
            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente.',
                'data' => $branchRuleProfessional,
            ], 200);

            return response()->json(['msg' => 'Estado actualizado correctamente del cumplimiento de una rule del professional'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al actualizar estado del cumplimiento de rule del professional'], 500);
        }
    }*/

    public function update_rule_state(Request $request)
    {
        Log::info("Actualización masiva de estados de convivencias");

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
            Log::error($th);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar estados',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {

            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            BranchRuleProfessional::destroy($data['id']);

            return response()->json(['msg' => 'Estado del cumplimiento de la rule eliminado correctamente de este trabajador'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al eliminar el estado del cumplimiento de la rule de este trabajador'], 500);
        }
    }
}
