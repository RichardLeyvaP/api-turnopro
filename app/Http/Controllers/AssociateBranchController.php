<?php

namespace App\Http\Controllers;

use App\Models\AssociateBranch;
use App\Models\Associated;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssociateBranchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info("Asignar asociado a una sucursal");
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'associated_id' => 'required|numeric'
            ]);
            $branch = Branch::find($data['branch_id']);
            $associate = Associated::find($data['associated_id']);

            $branch->associates()->attach($associate->id);

            return response()->json(['msg' => 'Asociado asignado correctamente a la sucursal'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            Log::info("Entra a buscar los srvicios que realiza una branch");
            $data = $request->validate([
                'branch_id' => 'nullable|numeric'
            ]);
            $branch = Branch::find($data['branch_id']);
            
            $associatebranch = $branch->associates->map(function ($associate){
                return [
                    'id' => $associate->pivot->id,
                    'associated_id' => $associate->id,
                    'name' => $associate->name,
                    'email' => $associate->email
                ];
            });
            //$result = BranchServiceProfessional::with('branchService.service', 'professional')->find($data['id']);

            return response()->json(['associates' => $associatebranch], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error interno del servidor"], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AssociateBranch $associateBranch)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'associated_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $associate = Associated::find($data['associated_id']);
            $branch = Branch::find($data['branch_id']);
            $branch->associates()->detach($associate->id);
            return response()->json(['msg' => 'Afiliación eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }
}
