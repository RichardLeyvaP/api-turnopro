<?php

namespace App\Http\Controllers;

use App\Models\Associated;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AssociatedController extends Controller
{
    /**
 * Obtiene la lista completa de afiliados registrados.
 *
 * @authenticated
 *
 * @response 200 {
 *   "associates": [
 *     {
 *       "id": 1,
 *       "name": "Empresa ABC",
 *       "email": "contacto@empresaabc.com"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function index()
    {
        try {
            return response()->json(['associates' => Associated::all()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
 * Registra un nuevo afiliado.
 *
 * @authenticated
 * @bodyParam name string required Nombre del afiliado. Example: Empresa XYZ
 * @bodyParam email string required Email único del afiliado. Example: contacto@empresa.xyz
 *
 * @response 200 {"msg": "Asociado insertado correctamente"}
 * @response 400 {"msg": ["El campo email es obligatorio."]}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:50',
                'email' => 'required|max:100|email|unique:associates'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 400);
            }

            $associated = new Associated();
            $associated->name = $request->name;
            $associated->email = $request->email;
            $associated->save();

            return response()->json(['msg' => 'Asociado insertado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    /**
 * Obtiene los afiliados que **NO están asociados** a una sucursal específica.
 *
 * Útil para mostrar qué afiliados se pueden asignar a una sucursal.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "associates": [
 *     {
 *       "id": 2,
 *       "name": "Distribuidora 123",
 *       "email": "ventas@distribuidora123.com"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $associatedIds = Branch::find($data['branch_id'])->associates()->pluck('associated_id');

            // Obtener los associates que NO están en esa lista de IDs asociados
            $associatesNotInBranch = Associated::whereNotIn('id', $associatedIds)->get();
            return response()->json(['associates' => $associatesNotInBranch], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()."Error interno del sistema"], 500);
        }
    }

    /**
 * Actualiza los datos de un afiliado existente.
 *
 * @authenticated
 * @bodyParam id integer required ID del afiliado a actualizar. Example: 1
 * @bodyParam name string required Nuevo nombre del afiliado. Example: Empresa Actualizada
 * @bodyParam email string required Nuevo email (debe ser único). Example: nuevo@empresaactualizada.com
 *
 * @response 200 {"msg": "Asociado actualizado correctamente"}
 * @response 400 {"msg": ["El email ya está en uso."]}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function update(Request $request, Associated $associated)
    {
        try {
            $associated = Associated::find($request->id);
            $validator = Validator::make($request->all(), [
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'email' => 'required|email|unique:associates,email,' . $associated->id,
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 400);
            }

            
            $associated->name = $request->name;
            $associated->email = $request->email;
            $associated->save();

            return response()->json(['msg' => 'Asociado actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    /**
 * Elimina un afiliado del sistema.
 *
 * @authenticated
 * @bodyParam id integer required ID del afiliado a eliminar. Example: 1
 *
 * @response 200 {"msg": "Asociado eliminado correctamente"}
 * @response 500 {"msg": "Error intern del sistema"}
 */
    public function destroy(request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Associated::destroy($data['id']);

            return response()->json(['msg' => 'Asociado eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error intern del sistema'], 500);
        }
    }
}
