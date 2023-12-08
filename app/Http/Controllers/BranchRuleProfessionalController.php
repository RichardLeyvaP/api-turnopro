<?php

namespace App\Http\Controllers;

use App\Models\BranchRule;
use App\Models\BranchRuleProfessional;
use App\Models\Professional;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BranchRuleProfessionalController extends Controller
{
    public function index()
    {
        try {
            $professionalrules = BranchRuleProfessional::with('branchRule.rule', 'professional')->get();
            return response()->json(['branchRuleProfesional' => $professionalrules], 200);
        } catch (\Throwable $th) {
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
                'estado' => 'required|boolean'
            ]); 
            $branchrule = BranchRule::whereHas('rule', function ($query) use ($data){
                $query->where('type', $data['type']);
            })->where('branch_id', $data['branch_id'])->first();

            $professional = Professional::find($data['professional_id']);
             $professional->branchRules()->attach($branchrule->id,['data'=>Carbon::now(), 'estado'=>$data['estado']]);
            return response()->json(['msg' => 'Estado de la rule asignado correctamente al professional'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error al asignar el estado de la rule a este professional'], 500);
        }
    }

    public function show(Request $request)
    {
        try {             
            Log::info("Entra a buscar el estado de las rules de un  professional");
            $data = $request->validate([
                'professional_id' => 'numeric'
            ]);
            return response()->json(['professional' => Professional::find($data['professional_id'])->branchRules], 200);            
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => "Error al mostrar el estado de las rules de un  professional"], 500);
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
            return response()->json(['msg' => 'Error al eliminar el estado del cumplimiento de la rule de este trabajador'], 500);
        }
    }
}
