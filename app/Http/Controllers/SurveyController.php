<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SurveyController extends Controller
{
    public function index()
    {
        try { 
            
            Log::info( "entra a buscar las resdes");
            return response()->json(['surveys' => Survey::all()], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }
    public function show(Request $request)
    {
        try {
             $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['rule' => Survey::find( $data['id'])], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }
    public function store(Request $request)
    {

        Log::info("crear encuesta");
        Log::info($request);
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
            Log::error($th);
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    public function update(Request $request)
    {
        try {

            Log::info("entra a actualizar");
             $data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50'
            ]);
            Log::info($request);
            $survey = Survey::find( $data['id']);
            $survey->name =  $data['name'];
            //$rule->automatic =  $rule_data['automatic'];
          
            $survey->save();

            return response()->json(['msg' => 'Encuesta actualizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            
             $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Survey::destroy( $data['id']);

            return response()->json(['msg' => 'Encuesta eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error inerno del sistema'], 500);
        }
    }
}
