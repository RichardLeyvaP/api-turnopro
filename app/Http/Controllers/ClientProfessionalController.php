<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientProfessional;
use App\Models\Professional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientProfessionalController extends Controller
{
    /**
 * Lista todos los profesionales con sus clientes asociados.
 *
 * Retorna una colección de profesionales, incluyendo los clientes asignados a cada uno mediante la relación muchos a muchos.
 *
 * @authenticated
 *
 * @response 200 {
 *   "professional": [
 *     {
 *       "id": 1,
 *       "name": "Dr. Martínez",
 *       "clients": [
 *         {
 *           "id": 5,
 *           "name": "Yasmany Sánchez",
 *           "email": "yasmany891230@gmail.com"
 *         }
 *       ]
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los clientes atendidos por empleado"}
 */
    public function index()
    {
        try {             
            return response()->json(['professional' => Professional::with('clients')->get()], 200);
        } catch (\Throwable $th) {  
        return response()->json(['msg' => "Error al mostrar los clientes atendidos por empleado"], 500);
        }
    }

    /**
 * Asigna un profesional a un cliente.
 *
 * Crea una relación entre un cliente y un profesional si aún no existe. Evita duplicados.
 *
 * @authenticated
 * @bodyParam client_id integer required ID del cliente. Example: 5
 * @bodyParam professional_id integer required ID del profesional. Example: 1
 *
 * @response 200 {"msg": "Empleado asignado correctamente al cliente"}
 * @response 500 {"msg": "Error al asignar el empleado a este cliente"}
 */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'client_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $client = Client::find($data['client_id']);
            $professional = Professional::find($data['professional_id']);
            $client_professional = $client->professionals()->where('professional_id', $data['professional_id'])->exists();
            //$result = ClientProfessional::where('client_id',$data['client_id'])->where('professional_id',$data['professional_id'])->get();
            if (!$client_professional) {                
                $professional->clients()->attach($client->id);
                $result = ClientProfessional::latest('id')->first();
                }
            return response()->json(['msg' => 'Empleado asignado correctamente al cliente'], 200);
        } catch (\Throwable $th) {
        return response()->json(['msg' => 'Error al asignar el empleado a este cliente'], 500);
        }
    }

    /**
 * Muestra la relación entre un cliente y un profesional.
 *
 * Dependiendo del parámetro enviado, devuelve los profesionales de un cliente o los clientes de un profesional.
 * Si se envían ambos, prioriza el `client_id`.
 *
 * @authenticated
 * @queryParam client_id integer Opcional. ID del cliente para obtener sus profesionales. Example: 5
 * @queryParam professional_id integer Opcional. ID del profesional para obtener sus clientes. Example: 1
 *
 * @response 200 {
 *   "cliente": {
 *     "id": 5,
 *     "name": "Yasmany Sánchez",
 *     "professionals": [
 *       {
 *         "id": 1,
 *         "name": "Dr. Martínez"
 *       }
 *     ]
 *   }
 * }
 * @response 200 {
 *   "professional": {
 *     "id": 1,
 *     "name": "Dr. Martínez",
 *     "clients": [
 *       {
 *         "id": 5,
 *         "name": "Yasmany Sánchez"
 *       }
 *     ]
 *   }
 * }
 * @response 500 {"msg": "Error al mostrar los clientes"}
 */
    public function show(Request $request)
    {
        try {             
            $data = $request->validate([
                'client_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            if ($data['client_id']) {
                return response()->json(['cliente' => Client::with('professionals')->find($data['client_id'])], 200);
            }
            if ($data['professional_id']) {
                return response()->json(['professional' => Professional::with('clients')->find($data['professional_id'])],200); 
            }
            
            } catch (\Throwable $th) { 
        return response()->json(['msg' => "Error al mostrar los clientes"], 500);
        }
    }

    /**
 * Actualiza la relación existente entre un cliente y un profesional.
 *
 * Actualiza la fila pivote en la tabla `client_professional` (útil si se añaden campos adicionales como fecha, estado, etc.).
 *
 * @authenticated
 * @bodyParam client_id integer required ID del cliente. Example: 5
 * @bodyParam professional_id integer required ID del profesional. Example: 1
 *
 * @response 200 {"msg": "Cliente reasignado correctamente"}
 * @response 500 {"msg": "Error al actualizar el cliente a es empleado"}
 */
    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'client_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $client = Client::find($data['client_id']);
            $professional = Professional::find($data['professional_id']);
            $professional->clients()->updateExistingPivot($client->id);
            return response()->json(['msg' => 'Cliente reasignado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar el cliente a es empleado'], 500);
        }
    }

    /**
 * Elimina la asignación de un cliente a un profesional.
 *
 * Rompe la relación entre el cliente y el profesional en la tabla pivote.
 *
 * @authenticated
 * @bodyParam client_id integer required ID del cliente. Example: 5
 * @bodyParam professional_id integer required ID del profesional. Example: 1
 *
 * @response 200 {"msg": "Cliente eliminado correctamente"}
 * @response 500 {"msg": "Error al eliminar el cliente a es empleado"}
 */
   public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'client_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $client = Client::find($data['client_id']);
            $professional = Professional::find($data['professional_id']);
            $professional->clients()->destroy($client->id);
            return response()->json(['msg' => 'Cliente eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el cliente a es empleado'], 500);
        }
    }
}
