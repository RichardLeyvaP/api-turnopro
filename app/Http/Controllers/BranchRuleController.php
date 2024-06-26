<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchRule;
use App\Models\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BranchRuleController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Devuelve las branches y sus reglas");
            return response()->json(['branch' => Branch::with('rules')->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => "Error al mostrar las rules por branch"], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info("Asignar regla de convivencia a branch");
        Log::info($request);
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
            Log::error($th);
        return response()->json(['msg' => 'Error al asignar la rule a la branch'], 500);
        }
    }

    public function show(Request $request)
    {
        try {             
            Log::info( "Entra a buscar las rules de una branch");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $branch = Branch::find($data['branch_id']);
            return response()->json(['rules' => $branch->rules->first()],200, [], JSON_NUMERIC_CHECK); 
            
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los clientes"], 500);
        }
    }

    public function branch_rules(Request $request)
    {
        try {             
            Log::info( "Entra a buscar las rules de una branch");
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
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los clientes"], 500);
        }
    }

    public function branch_rules_noIn(Request $request)
    {
        try {             
            Log::info( "Entra a buscar las rules de una que aun no posee una branch");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $branchrules = BranchRule::where('branch_id', $data['branch_id'])->get()->pluck('rule_id');
            $rules = Rule::whereNotIn('id', $branchrules)->get();
            return response()->json(['rules' => $rules],200, [], JSON_NUMERIC_CHECK); 
            
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error interno del sistema"], 500);
        }
    }

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
            Log::error($th);
            return response()->json(['msg' => 'Error al actualizar la rule de esa branch'], 500);
        }
    }

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
            Log::error($th);
            return response()->json(['msg' => 'Error al eliminar la rule de esta branch'], 500);
        }
    }
}
