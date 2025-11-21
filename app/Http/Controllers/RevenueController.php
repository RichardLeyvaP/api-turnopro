<?php

namespace App\Http\Controllers;

use App\Models\Revenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RevenueController extends Controller
{
    
    /**
 * Obtiene la lista de todas las operaciones de ingreso disponibles.
 *
 * @authenticated
 *
 * @response 200 {
 *   "revenues": [
 *     {
 *       "id": 1,
 *       "name": "Venta de servicios"
 *     },
 *     {
 *       "id": 2,
 *       "name": "Venta de productos"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function index()
    {
        try {
            return response()->json(['revenues' => Revenue::all()], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    /**
 * Crea una nueva operación de ingreso.
 *
 * @authenticated
 * @bodyParam name string required Nombre de la operación de ingreso. Example: Venta de cursos
 *
 * @response 200 {"msg": "Operación de Ingreso creado correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required',

            ]);

            $revenue = new Revenue();
            $revenue->name = $data['name'];
            $revenue->save();

            return response()->json(['msg' => 'Operación de Ingreso creado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    /**
 * Obtiene los detalles de una operación de ingreso específica.
 *
 * @authenticated
 * @queryParam id integer required ID de la operación de ingreso. Example: 1
 *
 * @response 200 {
 *   "revenues": {
 *     "id": 1,
 *     "name": "Venta de servicios"
 *   }
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['revenues' => Revenue::find($data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    /**
 * Actualiza una operación de ingreso existente.
 *
 * @authenticated
 * @bodyParam id integer required ID de la operación de ingreso. Example: 1
 * @bodyParam name string required Nuevo nombre. Example: Servicios premium
 *
 * @response 200 {"msg": "Operación de Ingreso actualizado correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function update(Request $request)
    {
        try {

            $data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required'
            ]);
            $revenue = Revenue::find($data['id']);
            $revenue->name = $data['name'];
            $revenue->save();

            return response()->json(['msg' => 'Operación de Ingreso actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    /**
 * Elimina una operación de ingreso del sistema.
 *
 * @authenticated
 * @bodyParam id integer required ID de la operación de ingreso. Example: 1
 *
 * @response 200 {"msg": "Operación de Ingreso eliminado correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function destroy(Request $request)
    {
        try {

            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Revenue::destroy($data['id']);

            return response()->json(['msg' => 'Operación de Ingreso eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }
}
