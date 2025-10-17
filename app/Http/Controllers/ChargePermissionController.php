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
        return response()->json(['msg' => $th->getMessage().'Error interno del servidor'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {             
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
                return response()->json(['permissions' => $permissions],200, [], JSON_NUMERIC_CHECK); 
          
            } catch (\Throwable $th) {  
        return response()->json(['msg' => $th->getMessage()."Error interno del servidor"], 500);
        }
    }

    public function show_charge_NoIN(Request $request)
    {
        try {             
            $request->validate([
                'charge_id' => 'required|numeric'
            ]);
            $chragePermission = ChargePermission::where('charge_id', $request->charge_id)->get()->pluck('permission_id');
            $permissions = Permission::whereNotIn('id', $chragePermission)->get();
                return response()->json(['permissions' => $permissions],200, [], JSON_NUMERIC_CHECK); 
          
            } catch (\Throwable $th) {  
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
            return response()->json(['msg' => $th->getMessage().'Error al sacar al estudiante de este curso'], 500);
        }
    }
}
