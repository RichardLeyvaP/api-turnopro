<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Professional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BranchProfessionalController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Entra a buscar los Professionales por sucursales");
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
                'professional_id' => 'required|numeric'
            ]);
            $branch = Branch::find($data['branch_id']);
            $professional = Professional::find($data['professional_id']);

            $branch->professionals()->attach($professional->id);

            return response()->json(['msg' => 'Professional asignado correctamente a la sucursal'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error al asignar el professional a esta sucursal'], 500);
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
                return response()->json(['branches' => $professional->branches],200, [], JSON_NUMERIC_CHECK); 
          
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
            $professionals = Professional::whereHas('branches', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            })->with('charge')->get();
                return response()->json(['professionals' => $professionals],200, [], JSON_NUMERIC_CHECK); 
          
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error al mostrar las branches"], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $branch = Branch::find($data['branch_id']);
            $professional = Professional::find($data['professional_id']);
            $branch->professionals()->sync($professional->id);
            return response()->json(['msg' => 'Professionals reasignado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error al actualizar el professionals de esa branch'], 500);
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
            return response()->json(['msg' => $th->getMessage().'Error al eliminar la professional de esta branch'], 500);
        }
    }
}
