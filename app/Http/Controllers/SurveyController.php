<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SurveyController extends Controller
{

    /**
 * Obtiene la lista de todas las encuestas disponibles.
 *
 * @authenticated
 *
 * @response 200 {
 *   "surveys": [
 *     {
 *       "id": 1,
 *       "name": "Satisfacción del cliente"
 *     },
 *     {
 *       "id": 2,
 *       "name": "Calidad del servicio"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function index()
    {
        try { 
            
            return response()->json(['surveys' => Survey::all()], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    /**
 * Obtiene los detalles de una encuesta específica.
 *
 * @authenticated
 * @queryParam id integer required ID de la encuesta. Example: 1
 *
 * @response 200 {
 *   "rule": {
 *     "id": 1,
 *     "name": "Satisfacción del cliente"
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
            return response()->json(['rule' => Survey::find( $data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    /**
 * Crea una nueva encuesta.
 *
 * @authenticated
 * @bodyParam name string required Nombre de la encuesta. Example: Evaluación post-servicio
 *
 * @response 200 {"msg": "Encuesta insertada correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function store(Request $request)
    {
        try {
             $data = $request->validate([
                'name' => 'required',
                //'automatic' => 'required'      
            ]);

            $survey = new Survey();
            $survey->name =  $data['name'];
       
       
            $survey->save();

            return response()->json(['msg' => 'Encuesta insertada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    /**
 * Actualiza una encuesta existente.
 *
 * @authenticated
 * @bodyParam id integer required ID de la encuesta. Example: 1
 * @bodyParam name string required Nuevo nombre (máx. 50 caracteres). Example: Encuesta de satisfacción
 *
 * @response 200 {"msg": "Encuesta actualizada correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function update(Request $request)
    {
        try {

             $data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50'
            ]);
            $survey = Survey::find( $data['id']);
            $survey->name =  $data['name'];
            //$rule->automatic =  $rule_data['automatic'];
          
            $survey->save();

            return response()->json(['msg' => 'Encuesta actualizada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    /**
 * Elimina una encuesta del sistema.
 *
 * @authenticated
 * @bodyParam id integer required ID de la encuesta. Example: 1
 *
 * @response 200 {"msg": "Encuesta eliminada correctamente"}
 * @response 500 {"msg": "Error inerno del sistema"}
 */
    public function destroy(Request $request)
    {
        try {
            
             $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Survey::destroy( $data['id']);

            return response()->json(['msg' => 'Encuesta eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error inerno del sistema'], 500);
        }
    }
}
