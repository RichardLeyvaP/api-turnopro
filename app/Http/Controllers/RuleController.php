<?php
namespace App\Http\Controllers;

use App\Models\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RuleController extends Controller
{

    /**
 * Lista todas las reglas del sistema.
 *
 * @authenticated
 *
 * @response 200 {
 *   "rules": [
 *     {
 *       "id": 1,
 *       "name": "Tiempo de trabajo",
 *       "description": "Regla para controlar horarios de entrada/salida",
 *       "type": "Tiempo",
 *       "automatic": 0
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar las reglas"}
 */
    public function index()
    {
        try { 
            
            return response()->json(['rules' => Rule::all()], 200);
        } catch (\Throwable $th) {  
            return response()->json(['msg' => "Error al mostrar las reglas"], 500);
        }
    }

    /**
 * Muestra los detalles de una regla específica.
 *
 * @authenticated
 * @queryParam id integer required ID de la regla. Example: 1
 *
 * @response 200 {
 *   "rule": {
 *     "id": 1,
 *     "name": "Tiempo de trabajo",
 *     "description": "Regla para controlar horarios",
 *     "type": "Tiempo",
 *     "automatic": 0
 *   }
 * }
 * @response 500 {"msg": "Error al mostrar la regla"}
 */
    public function show(Request $request)
    {
        try {
             $rule_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['rule' => Rule::find( $rule_data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar la regla"], 500);
        }
    }

    /**
 * Crea una nueva regla.
 *
 * El campo `automatic` se establece automáticamente en `0` (no automática).
 *
 * @authenticated
 * @bodyParam name string required Nombre de la regla. Max: 50 caracteres. Example: "Tiempo de trabajo"
 * @bodyParam description string required Descripción. Max: 220 caracteres. Example: "Controla horarios de entrada y salida"
 * @bodyParam type string required Tipo de la regla. Max: 50 caracteres. Example: "Tiempo"
 *
 * @response 200 {"msg": "Regla insertada correctamente"}
 * @response 500 {"msg": "Error al insertar la Regla"}
 */
    public function store(Request $request)
    {
        try {
             $rule_data = $request->validate([
                'name' => 'required|max:50',
                'description' => 'required|max:220',
                'type' => 'required|max:50',
                //'automatic' => 'required'      
            ]);

            $rule = new Rule();
            $rule->name =  $rule_data['name'];
            $rule->description =  $rule_data['description'];
            $rule->type =  $rule_data['type'];
            $rule->automatic =  0;
       
       
            $rule->save();

            return response()->json(['msg' => 'Regla insertada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al insertar la Regla'], 500);
        }
    }

    /**
 * Actualiza una regla existente.
 *
 * @authenticated
 * @bodyParam id integer required ID de la regla a actualizar. Example: 1
 * @bodyParam name string required Nuevo nombre. Max: 50. Example: "Horario laboral"
 * @bodyParam description string required Nueva descripción. Max: 220. Example: "Regula la jornada diaria"
 * @bodyParam type string required Nuevo tipo. Max: 50. Example: "Jornada"
 *
 * @response 200 {"msg": "Regla actualizada correctamente"}
 * @response 500 {"msg": "Error al actualizar la Regla"}
 */
    public function update(Request $request)
    {
        try {

             $rule_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'description' => 'required|max:220',
                'type' => 'required|max:50'//, 
                //'automatic' => 'required' 
            ]);
            $rule = Rule::find( $rule_data['id']);
            $rule->name =  $rule_data['name'];
            $rule->description =  $rule_data['description'];
            $rule->type =  $rule_data['type'];
            //$rule->automatic =  $rule_data['automatic'];
          
            $rule->save();

            return response()->json(['msg' => 'Regla actualizada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar la Regla'], 500);
        }
    }

    /**
 * Elimina una regla del sistema.
 *
 * ⚠️ **Advertencia**: Esta acción es irreversible y puede afectar reglas asignadas a sucursales.
 *
 * @authenticated
 * @bodyParam id integer required ID de la regla a eliminar. Example: 1
 *
 * @response 200 {"msg": "Regla eliminada correctamente"}
 * @response 500 {"msg": "Error al eliminar la Regla"}
 */
    public function destroy(Request $request)
    {
        try {
            
             $rule_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Rule::destroy( $rule_data['id']);

            return response()->json(['msg' => 'Regla eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar la Regla'], 500);
        }
    }
}