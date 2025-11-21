<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BranchStoreController extends Controller
{
    /**
 * Obtiene todas las sucursales con sus almacenes asignados.
 *
 * @authenticated
 *
 * @response 200 {
 *   "branch": [
 *     {
 *       "id": 5,
 *       "name": "Centro",
 *       "branchstores": [ ... ]
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los almacenes por sucursales"}
 */
    public function index()
    {
        try {             
            return response()->json(['branch' => Branch::with('branchstores')->get()], 200);
        } catch (\Throwable $th) {  
        return response()->json(['msg' => "Error al mostrar los almacenes por sucursales"], 500);
        }
    }

    /**
 * Asigna un almacén a una sucursal.
 *
 * @authenticated
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam store_id integer required ID del almacén. Example: 3
 *
 * @response 200 {"msg": "Almacén asignado correctamente a la sucursal"}
 * @response 500 {"msg": "Error al asignar el producto a este almacén"}
 */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'store_id' => 'required|numeric'
            ]);
            $branch = Branch::find($data['branch_id']);
            $store = Store::find($data['store_id']);

            $branch->branchstores()->attach($store->id);

            return response()->json(['msg' => 'Almacén asignado correctamente a la sucursal'], 200);
        } catch (\Throwable $th) {
        return response()->json(['msg' =>'Error al asignar el producto a este almacén'], 500);
        }
    }

    /**
 * Obtiene los almacenes asignados a una sucursal específica.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "stores": [
 *     {
 *       "id": 3,
 *       "name": "Almacén Principal",
 *       ...
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los productos"}
 */
    public function show(Request $request)
    {
        try {             
            $data = $request->validate([
                'branch_id' => 'numeric'
            ]);
            $branch = Branch::with('stores')->find($data['branch_id']);

                return response()->json(['stores' => $branch->stores],200); 
            
            } catch (\Throwable $th) {  
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los productos"], 500);
        }
    }

    /**
 * Obtiene los almacenes que **NO están asignados** a una sucursal (para poder asignarlos).
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "stores": [
 *     {
 *       "id": 4,
 *       "name": "Almacén Secundario",
 *       ...
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los productos"}
 */
    public function show_notIn(Request $request)
    {
        try {             
            $data = $request->validate([
                'branch_id' => 'numeric'
            ]);
            $storeIds = Branch::find($data['branch_id'])->stores()->pluck('store_id');

            // Obtener los associates que NO están en esa lista de IDs asociados
            $storeNotInBranch = Store::whereNotIn('id', $storeIds)->get();

                return response()->json(['stores' => $storeNotInBranch],200); 
            
            } catch (\Throwable $th) {  
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los productos"], 500);
        }
    }

    /**
 * Actualiza la asignación de almacenes en una sucursal (reemplaza por un único almacén).
 *
 * ⚠️ Este método usa `sync()`, lo que **elimina todas las asignaciones previas** y deja solo el `store_id` enviado.
 *
 * @authenticated
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam store_id integer required ID del almacén a asignar. Example: 3
 *
 * @response 200 {"msg": "Almacén actualizado correctamente"}
 * @response 500 {"msg": "Error al actualizar el almacén en esta sucursal"}
 */
    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'store_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $store = Store::find($data['store_id']);
            $branch = Branch::find($data['branch_id']);
            $branch->branchstores()->sync($store->id);
            return response()->json(['msg' => 'Almacén actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar el almacén en esta sucursal'], 500);
        }
        
    }

    /**
 * Actualiza la asignación de almacenes en una sucursal (reemplaza por un único almacén).
 *
 * ⚠️ Este método usa `sync()`, lo que **elimina todas las asignaciones previas** y deja solo el `store_id` enviado.
 *
 * @authenticated
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam store_id integer required ID del almacén a asignar. Example: 3
 *
 * @response 200 {"msg": "Almacén actualizado correctamente"}
 * @response 500 {"msg": "Error al actualizar el almacén en esta sucursal"}
 */
    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'store_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $store = Store::find($data['store_id']);
            $branch = Branch::find($data['branch_id']);
            $branch->branchstores()->detach($store->id);
            return response()->json(['msg' => 'Almacén eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el almacén en esta sucursal'], 500);
        }
    }
}
