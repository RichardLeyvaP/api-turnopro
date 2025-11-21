<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BusinessTypesController extends Controller
{
    /**
 * Obtiene la lista de todos los tipos de negocio disponibles.
 *
 * @authenticated
 *
 * @response 200 {
 *   "businessTypes": [
 *     {
 *       "id": 1,
 *       "name": "Barbería"
 *     },
 *     {
 *       "id": 2,
 *       "name": "Academia"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los tipos de negocios"}
 */
    public function index()
    {
        try {
            return response()->json(['businessTypes' => BusinessTypes::all()], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los tipos de negocios"], 500);
        }
    }

    /**
 * Obtiene los detalles de un tipo de negocio específico.
 *
 * @authenticated
 * @queryParam id integer required ID del tipo de negocio. Example: 1
 *
 * @response 200 {
 *   "businessTypes": {
 *     "id": 1,
 *     "name": "Barbería"
 *   }
 * }
 * @response 500 {"msg": "Error al mostrar el negocio"}
 */
    public function show(Request $request)
    {
        try {
            $business_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['businessTypes' => BusinessTypes::all()->find($business_data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el negocio"], 500);
        }
    }

    /**
 * Crea un nuevo tipo de negocio.
 *
 * @authenticated
 * @bodyParam name string required Nombre único del tipo de negocio. Example: Peluquería
 *
 * @response 200 {"msg": "Tipo de negocio insertado correctamente"}
 * @response 500 {"msg": "Error al insertar el tipo de negocio"}
 */
    public function store(Request $request)
    {
        try {
            $business_type_data = $request->validate([
                'name' => 'required|unique:business_types',

            ]);

            $businessTypes = new BusinessTypes();
            $businessTypes->name = $business_type_data['name'];
            $businessTypes->save();

            return response()->json(['msg' => 'Tipo de negocio insertado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al insertar el tipo de negocio'], 500);
        }
    }

    /**
 * Actualiza un tipo de negocio existente.
 *
 * @authenticated
 * @bodyParam id integer required ID del tipo de negocio. Example: 1
 * @bodyParam name string required Nuevo nombre (debe ser único). Example: Estética
 *
 * @response 200 {"msg": "Tipo de negocio actualizado correctamente"}
 * @response 500 {"msg": "Error al actualizar el tipo de negocio"}
 */
    public function update(Request $request)
    {
        try {

            $business_type_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|unique:business_types'
            ]);
            $businessType = BusinessTypes::find($business_type_data['id']);
            $businessType->name = $business_type_data['name'];
            $businessType->save();

            return response()->json(['msg' => 'Tipo de negocio actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar el tipo de negocio'], 500);
        }
    }

    /**
 * Elimina un tipo de negocio del sistema.
 *
 * @authenticated
 * @bodyParam id integer required ID del tipo de negocio. Example: 1
 *
 * @response 200 {"msg": "Tipo de negocio eliminado correctamente"}
 * @response 500 {"msg": "Error al eliminar el tipo negocio"}
 */
    public function destroy(Request $request)
    {
        try {

            $business_type_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            BusinessTypes::destroy($business_type_data['id']);

            return response()->json(['msg' => 'Tipo de negocio eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el tipo negocio'], 500);
        }
    }
}