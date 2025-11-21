<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchRule;
use App\Models\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BranchRuleController extends Controller
{
    /**
 * Obtiene todas las sucursales con sus reglas asociadas.
 *
 * @authenticated
 *
 * @response 200 {
 *   "branch": [
 *     {
 *       "id": 5,
 *       "name": "Centro",
 *       "rules": [ ... ]
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar las rules por branch"}
 */
    public function index()
    {
        try {             
            return response()->json(['branch' => Branch::with('rules')->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
        return response()->json(['msg' => "Error al mostrar las rules por branch"], 500);
        }
    }

    /**
 * Asigna una regla a una sucursal.
 *
 * @authenticated
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam rule_id integer required ID de la regla. Example: 3
 *
 * @response 200 {"msg": "Rule asignada correctamente a la branch"}
 * @response 500 {"msg": "Error al asignar la rule a la branch"}
 */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'rule_id' => 'required|numeric'
            ]);
            $branch = Branch::find($data['branch_id']);
            $rule = Rule::find($data['rule_id']);
            $branchrule = $branch->rules()->where('rule_id', $rule->id)->exists();
            if (!$branchrule) {                
                $branch->rules()->attach($rule->id);
                }
            return response()->json(['msg' => 'Rule asignada correctamente a la branch'], 200);
        } catch (\Throwable $th) {
        return response()->json(['msg' => 'Error al asignar la rule a la branch'], 500);
        }
    }

    /**
 * Obtiene la primera regla asociada a una sucursal (uso limitado).
 *
 * ⚠️ Solo devuelve **una regla**, incluso si hay varias.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "rules": {
 *     "id": 3,
 *     "name": "Puntualidad",
 *     "description": "..."
 *   }
 * }
 * @response 500 {"msg": "Error al mostrar los clientes"}
 */
    public function show(Request $request)
    {
        try {             
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $branch = Branch::find($data['branch_id']);
            return response()->json(['rules' => $branch->rules->first()],200, [], JSON_NUMERIC_CHECK); 
            
            } catch (\Throwable $th) { 
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los clientes"], 500);
        }
    }

    /**
 * Obtiene todas las reglas asociadas a una sucursal con detalles completos.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "rules": [
 *     {
 *       "id": 101,
 *       "name": "Puntualidad",
 *       "description": "Debe llegar a tiempo.",
 *       "type": "attendance",
 *       "rule_id": 3
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los clientes"}
 */
    public function branch_rules(Request $request)
    {
        try {             
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $branchrules = BranchRule::where('branch_id', $data['branch_id'])->get()->map(function ($branchrule) {
                return [
                    'id' => $branchrule->id,
                    'name' => $branchrule->rule->name,
                    'description' => $branchrule->rule->description,
                    'type' => $branchrule->rule->type,
                    'rule_id' => $branchrule->rule_id
                ];
            });
            return response()->json(['rules' => $branchrules],200, [], JSON_NUMERIC_CHECK); 
            
            } catch (\Throwable $th) {  
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los clientes"], 500);
        }
    }

    /**
 * Obtiene las reglas que **NO están asignadas** a una sucursal (para asignar nuevas).
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "rules": [
 *     {
 *       "id": 4,
 *       "name": "Uniforme",
 *       "description": "Debe usar uniforme completo.",
 *       "type": "appearance"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function branch_rules_noIn(Request $request)
    {
        try {             
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $branchrules = BranchRule::where('branch_id', $data['branch_id'])->get()->pluck('rule_id');
            $rules = Rule::whereNotIn('id', $branchrules)->get();
            return response()->json(['rules' => $rules],200, [], JSON_NUMERIC_CHECK); 
            
            } catch (\Throwable $th) {  
        return response()->json(['msg' => $th->getMessage()."Error interno del sistema"], 500);
        }
    }

    /**
 * Actualiza la asociación entre una regla y una sucursal (actualmente no modifica datos adicionales).
 *
 * @authenticated
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam rule_id integer required ID de la regla. Example: 3
 *
 * @response 200 {"msg": "rule reasignada correctamente"}
 * @response 500 {"msg": "Error al actualizar la rule de esa branch"}
 */
    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'rule_id' => 'required|numeric'
            ]);
            $branch = Branch::find($data['branch_id']);
            $rule = Rule::find($data['rule_id']);
            $branch->rules()->updateExistingPivot($rule->id);
            return response()->json(['msg' => 'rule reasignada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar la rule de esa branch'], 500);
        }
    }

    /**
 * Elimina la asociación de una regla en una sucursal.
 *
 * @authenticated
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam rule_id integer required ID de la regla. Example: 3
 *
 * @response 200 {"msg": "Rule eliminada correctamente de la branch"}
 * @response 500 {"msg": "Error al eliminar la rule de esta branch"}
 */
    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'rule_id' => 'required|numeric'
            ]);
            $branch = Branch::find($data['branch_id']);
            $rule = Rule::find($data['rule_id']);
            $branch->rules()->detach($rule->id);
            return response()->json(['msg' => 'Rule eliminada correctamente de la branch'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar la rule de esta branch'], 500);
        }
    }
}
