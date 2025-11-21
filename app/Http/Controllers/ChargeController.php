<?php
namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Charge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChargeController extends Controller
{

    /**
 * Obtiene los cargos y las sucursales de un negocio específico.
 *
 * Útil para formularios de asignación en el frontend web.
 *
 * @authenticated
 * @queryParam business_id integer required ID del negocio. Example: 1
 *
 * @response 200 {
 *   "branches": [
 *     {
 *       "id": 5,
 *       "name": "Centro",
 *       "image_data": "branches/5.jpg"
 *     }
 *   ],
 *   "charges": [
 *     {
 *       "id": 1,
 *       "name": "Barbero",
 *       "description": "Profesional de corte de cabello"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los cargos"}
 */
    public function index_web(Request $request)
    {
        try { $branch_data = $request->validate([
            'business_id' => 'required|numeric'
        ]);
        $charges = Charge::all();
        $branches = Branch::where('business_id', $branch_data['business_id'])->select('id', 'name', 'image_data')->get();
        return response()->json(['branches' => $branches, 'charges' => $charges], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            return response()->json(['msg' => "Error al mostrar los cargos"], 500);
        }
    }

    /**
 * Obtiene la lista de todos los cargos disponibles en el sistema.
 *
 * @authenticated
 *
 * @response 200 {
 *   "charges": [
 *     {
 *       "id": 1,
 *       "name": "Barbero",
 *       "description": "Profesional de corte de cabello"
 *     },
 *     {
 *       "id": 2,
 *       "name": "Administrador",
 *       "description": "Gestor del negocio"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los cargos"}
 */
    public function index()
    {
        try { 
            return response()->json(['charges' => Charge::all()], 200);
        } catch (\Throwable $th) { 
            return response()->json(['msg' => "Error al mostrar los cargos"], 500);
        }
    }

    /**
 * Obtiene los detalles de un cargo específico.
 *
 * @authenticated
 * @queryParam id integer required ID del cargo. Example: 1
 *
 * @response 200 {
 *   "client": {
 *     "id": 1,
 *     "name": "Barbero",
 *     "description": "Profesional de corte de cabello"
 *   }
 * }
 * @response 500 {"msg": "Error al mostrar el cargo"}
 */
    public function show(Request $request)
    {
        try {
            $charge_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['client' => Charge::find($charge_data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el cargo"], 500);
        }
    }

    /**
 * Crea un nuevo cargo en el sistema.
 *
 * @authenticated
 * @bodyParam name string required Nombre del cargo. Example: Cajero
 * @bodyParam description string required Descripción del cargo. Example: Encargado de caja
 *
 * @response 200 {"msg": "Cargo insertado correctamente"}
 * @response 500 {"msg": "Error al insertar el Cargo"}
 */
    public function store(Request $request)
    {
        try {
            $charge_data = $request->validate([
                'name' => 'required|max:50',
                'description' => 'required|max:50',
               
              
            ]);

            $store = new Charge();
            $store->name = $charge_data['name'];
            $store->description = $charge_data['description'];
       
       
            $store->save();

            return response()->json(['msg' => 'Cargo insertado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al insertar el Cargo'], 500);
        }
    }

    /**
 * Actualiza un cargo existente.
 *
 * @authenticated
 * @bodyParam id integer required ID del cargo. Example: 3
 * @bodyParam name string required Nuevo nombre. Example: Cajera
 * @bodyParam description string required Nueva descripción. Example: Encargada de caja
 *
 * @response 200 {"msg": "Cargo actualizado correctamente"}
 * @response 500 {"msg": "Error al actualizar el Cargo"}
 */
    public function update(Request $request)
    {
        try {
            $charge_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'description' => 'required|max:50',    
            ]);
            $store = Charge::find($charge_data['id']);
            $store->name = $charge_data['name'];
            $store->description = $charge_data['description'];
          
            $store->save();

            return response()->json(['msg' => 'Cargo actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar el Cargo'], 500);
        }
    }

    /**
 * Elimina un cargo del sistema.
 *
 * ⚠️ No se puede eliminar si hay profesionales asignados (depende de tu base de datos).
 *
 * @authenticated
 * @bodyParam id integer required ID del cargo. Example: 3
 *
 * @response 200 {"msg": "Cargo eliminado correctamente"}
 * @response 500 {"msg": "Error al eliminar el Cargo"}
 */
    public function destroy(Request $request)
    {
        try {
            
            $charge_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Charge::destroy($charge_data['id']);

            return response()->json(['msg' => 'Cargo eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el Cargo'], 500);
        }
    }
}