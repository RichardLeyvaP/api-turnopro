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
 * Asigna un permiso a un cargo.
 *
 * Crea una relación entre un cargo y un permiso mediante una relación muchos a muchos.
 *
 * @authenticated
 * @bodyParam charge_id integer required ID del cargo al que se le asignará el permiso. Example: 5
 * @bodyParam permission_id integer required ID del permiso a asignar. Example: 12
 *
 * @response 200 {"msg": "Permiso Asignado Coorectamente"}
 * @response 500 {"msg": "Error interno del servidor"}
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
 * Obtiene todos los permisos asignados a un cargo específico.
 *
 * Devuelve una lista de permisos vinculados al cargo, incluyendo metadatos de la relación pivote.
 *
 * @authenticated
 * @queryParam charge_id integer required ID del cargo. Example: 5
 *
 * @response 200 {
 *   "permissions": [
 *     {
 *       "id": 12,
 *       "charge_id": 5,
 *       "permission_id": 12,
 *       "name": "Crear usuarios",
 *       "module": "Usuarios",
 *       "description": "Permite crear nuevos usuarios en el sistema"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error interno del servidor"}
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

    /**
 * Obtiene los permisos que **NO están asignados** a un cargo específico.
 *
 * Útil para interfaces de asignación donde se muestra la lista de permisos disponibles.
 *
 * @authenticated
 * @queryParam charge_id integer required ID del cargo. Example: 5
 *
 * @response 200 {
 *   "permissions": [
 *     {
 *       "id": 8,
 *       "name": "Eliminar reservas",
 *       "module": "Reservas",
 *       "description": "Permite eliminar reservas de clientes"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error interno del servidor"}
 */
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
 * Elimina la asignación de un permiso a un cargo.
 *
 * Rompe la relación entre el cargo y el permiso especificados.
 *
 * @authenticated
 * @bodyParam charge_id integer required ID del cargo. Example: 5
 * @bodyParam permission_id integer required ID del permiso a eliminar. Example: 12
 *
 * @response 200 {"msg": "Estudiante desmatriculado correctamente del curso"}
 * @response 500 {"msg": "Error al sacar al estudiante de este curso"}
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
