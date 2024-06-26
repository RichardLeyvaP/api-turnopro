<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Models\ChargePermission;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChargePermissionController extends Controller
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
        Log::info("Matricular estudiante al curso");
        Log::info($request);
        try {
            $request->validate([
                'charge_id' => 'required|numeric',
                'permission_id' => 'required|numeric'
            ]);
            $charge = Charge::find($request->charge_id);
            $permission = Permission::find($request->permission_id);

            $charge->permissions()->attach($permission->id);

            return response()->json(['msg' => 'Permiso Asignado Coorectamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error interno del servidor'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {             
            Log::info("Dado una cargo devuelve los permisos");
            $request->validate([
                'charge_id' => 'required|numeric'
            ]);
            $charge = Charge::find($request->charge_id);
            $permissions = $charge->permissions->map(function ($query){
                return [
                    'id' => $query->pivot->value('id'),
                    'charge_id' => $query->pivot->charge_id,
                    'permission_id' => $query->pivot->permission_id,
                    'name' => $query->name,
                    'module' => $query->module,
                    'description' => $query->description,
                ];
            });
            /*$permissions = Permission::whereHas('charges', function ($query) use ($request){
                $query->where('charge_id', $request->charge_id);
            })->with('charges')->get();*/
                return response()->json(['permissions' => $permissions],200, [], JSON_NUMERIC_CHECK); 
          
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error interno del servidor"], 500);
        }
    }

    public function show_charge_NoIN(Request $request)
    {
        try {             
            Log::info("Dado una cargo devuelva solo los permisos que no posee");
            $request->validate([
                'charge_id' => 'required|numeric'
            ]);
            $chragePermission = ChargePermission::where('charge_id', $request->charge_id)->get()->pluck('permission_id');
            $permissions = Permission::whereNotIn('id', $chragePermission)->get();/*->map(function ($query){
                return [
                    'id' => $query->pivot->value('id'),
                    'charge_id' => $query->pivot->charge_id,
                    'permission_id' => $query->pivot->permission_id,
                    'name' => $query->name,
                    'module' => $query->module,
                    'description' => $query->description,
                ];
            });
            /*$permissions = $charge->permissions->whereNotIn('permission_id', $chragePermission)->map(function ($query){
                return [
                    'id' => $query->pivot->value('id'),
                    'charge_id' => $query->pivot->charge_id,
                    'permission_id' => $query->pivot->permission_id,
                    'name' => $query->name,
                    'module' => $query->module,
                    'description' => $query->description,
                ];
            });*/
            /*$permissions = Permission::whereHas('charges', function ($query) use ($request){
                $query->where('charge_id', $request->charge_id);
            })->with('charges')->get();*/
                return response()->json(['permissions' => $permissions],200, [], JSON_NUMERIC_CHECK); 
          
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error interno del servidor"], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChargePermission $chargePermission)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'charge_id' => 'required|numeric',
                'permission_id' => 'required|numeric'
            ]);
            $charge = Charge::find($request->charge_id);
            $permission = Permission::find($request->permission_id);
            $charge->permissions()->detach($permission->id);
            return response()->json(['msg' => 'Estudiante desmatriculado correctamente del curso'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage().'Error al sacar al estudiante de este curso'], 500);
        }
    }
}
