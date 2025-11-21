<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientSurvey;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ClientSurveyController extends Controller
{

    /**
 * Lista todas las asignaciones de encuestas a clientes.
 *
 * Retorna todos los registros de la tabla pivote `client_survey`, incluyendo IDs de cliente, encuesta, sucursal y fecha.
 *
 * @authenticated
 *
 * @response 200 {
 *   "surveys": [
 *     {
 *       "id": 1,
 *       "client_id": 5,
 *       "survey_id": 3,
 *       "branch_id": 2,
 *       "data": "2025-11-21 10:30:00"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function index()
    {
        try { 
            
            return response()->json(['surveys' => ClientSurvey::all()], 200);
        } catch (\Throwable $th) {  
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    /**
 * Registra una o más encuestas asignadas a un cliente desde una sucursal.
 *
 * Busca al cliente por email o número de teléfono. Si existe, le asigna las encuestas indicadas en la sucursal especificada.
 * Si el cliente no existe, no se crea ningún registro (comportamiento silencioso).
 *
 * @unauthenticated (según lógica actual, no requiere autenticación explícita)
 * @bodyParam email string required Email o teléfono del cliente. Example: "yasmany891230@gmail.com"
 * @bodyParam branch_id integer required ID de la sucursal desde donde se asigna la encuesta. Example: 2
 * @bodyParam survey_id array required Lista de IDs de encuestas a asignar. Example: [1, 3, 5]
 *
 * @response 200 {"msg": "Insertado Correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function store(Request $request)
    {
        try {
        $request->validate([
            'email' => 'required',
            'branch_id' => 'required|numeric'
        ]);
        $surveys = $request->input('survey_id');
        if (!empty($surveys)) {
            $client = Client::where('email', $request->email)->orwhere('phone', $request->email)->first();
        if (!empty($client)) {
            foreach ($surveys as $survey) {
                $clientSurvey = new ClientSurvey();
                $clientSurvey->client_id = $client->id;
                $clientSurvey->survey_id = $survey;
                $clientSurvey->branch_id = $request->branch_id;
                $clientSurvey->data = Carbon::now();
                $clientSurvey->save();      
            }        
        }
        }
        return response()->json(['msg' => 'Insertado Correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    public function show($id)
    {
        // Implementar la lógica para mostrar un registro específico
    }

    public function update(Request $request, $id)
    {
        // Implementar la lógica para actualizar un registro existente
    }

    public function destroy($id)
    {
        // Implementar la lógica para eliminar un registro existente
    }

    /**
 * Obtiene el conteo de respuestas por encuesta en una sucursal específica.
 *
 * Retorna el nombre de cada encuesta y la cantidad de veces que ha sido asignada (vía `client_survey`) en la sucursal dada.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 2
 *
 * @response 200 [
 *   {
 *     "name": "Satisfacción del servicio",
 *     "client_surveys_count": 12
 *   },
 *   {
 *     "name": "Calidad del producto",
 *     "client_surveys_count": 8
 *   }
 * ]
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function surveyCounts(Request $request)
    {
        try { 
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);

            $surveyCounts = Survey::select('surveys.name')
        ->withCount(['clientSurveys' => function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
        }])
        ->orderBy('client_surveys_count', 'desc')
        ->get();
            
            return response()->json($surveyCounts, 200);
        } catch (\Throwable $th) {  
            return response()->json(['msg' => $th->getMessage()."Error interno del sistema"], 500);
        };
    }

}
