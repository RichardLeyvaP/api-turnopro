<?php

namespace App\Http\Controllers;

use App\Models\BranchProfessional;
use App\Models\BranchRule;
use App\Models\BranchRuleProfessional;
use App\Models\Professional;
use App\Models\Rule;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las rules por trabajador"], 500);
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
            $professional->branchRules()->attach($branchrule->id,['data'=>Carbon::now(), 'estado'=>$data['estado']]);
            return response()->json(['msg' => 'Estado de la rule asignado correctamente al professional'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' =>'Error al asignar el estado de la rule a este professional'], 500);
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
                'estado' => 'required|int'
            ]); 
            $professional = Professional::find($data['professional_id']);
            $branchrule = BranchRule::whereHas('rule', function ($query) use ($data){
                $query->where('type', $data['type']);
            })->where('branch_id', $data['branch_id'])->first();
           $existencia = Professional::whereHas('branchRules', function ($query) use ($branchrule){
                $query->whereDate('data', Carbon::now())->where('branch_rule_id', $branchrule->id);
            })->exists();
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
                $professional->branchrules()->updateExistingPivot($branchrule->id,['estado'=>$data['estado']]);     
            return response()->json(['msg' => 'Estado actualizado correctamente de una rule del professional'], 200);
            }
            return response()->json(['msg' => 'Estado de la rule asignado correctamente al professional'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error al asignar el estado de la rule a este professional'], 500);
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
        return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
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
            }
            else {
                $data['data'] = Carbon::now()->toDateString();
            }
            $result = [];
            $i = 0;
            $professional = Professional::find($data['professional_id']);
            $branchrules = BranchRule::where('branch_id', $data['branch_id'])->get();
            foreach ($branchrules as $branchrule) {
                $existencia = Professional::whereHas('branchRules', function ($query) use ($branchrule, $data){
                    $query->whereDate('data', $data['data'])->where('branch_rule_id', $branchrule->id);
                })->exists();
                if (!$existencia) {
                    $professional->branchRules()->attach($branchrule->id,['data'=>$data['data'], 'estado'=>3]);
                }
            }
           
            $branchRuleProfessionals = BranchRuleProfessional::whereHas('branchRule', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            })->where('professional_id', $data['professional_id'])->whereDate('data', $data['data'])->get()->map(function ($branchRuleProfessional){
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
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error al mostrar el estado de las rules de un  professional"], 500);
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
            $professional->branchrules()->updateExistingPivot($branchrule->id,['estado'=>$data['estado']]);     
            return response()->json(['msg' => 'Estado actualizado correctamente del cumplimiento de una rule del professional'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => 'Error al actualizar estado del cumplimiento de rule del professional'], 500);
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
