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
    public function index()
    {
        try { 
            
            return response()->json(['surveys' => ClientSurvey::all()], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    public function store(Request $request)
    {
        try {
        $request->validate([
            'email' => 'required|email',
            'branch_id' => 'required|numeric'
        ]);
        $surveys = $request->input('survey_id');
        if (!empty($surveys)) {
        $client = Client::where('email', $request->email)->first();
        if (!empty($client)) {
            foreach ($surveys as $survey) {
                $clientSurvey = new ClientSurvey();
                $clientSurvey->client_id = $client->id;
                $clientSurvey->survey_id = $survey;
                $clientSurvey->branch_id = $request->branch_id;
                $clientSurvey->data = Carbon::now();
                $clientSurvey->save();
            }        
        //$client->surveys()->attach($surveys, ['data' => Carbon::now(), $request->branch_id]);
        }
        }
        /*$clientSurvey = new ClientSurvey();
        $clientSurvey->client_id = $client->id;
        $clientSurvey->survey_id = $request->survey_id;
        $clientSurvey->data = Carbon::now();*/
        // Añade aquí otras asignaciones de datos según tus necesidades
        //$clientSurvey->save();
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
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()."Error interno del sistema"], 500);
        };
    }

}
