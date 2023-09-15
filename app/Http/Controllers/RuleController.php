<?php
namespace App\Http\Controllers;

use App\Models\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RuleController extends Controller
{
    public function index()
    {
        try { 
            
            Log::info( "entra a buscar reglas");
            return response()->json(['rules' => Rule::all()], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las reglas"], 500);
        }
    }
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
    public function store(Request $request)
    {

        Log::info("crear regla");
        Log::info($request);
        try {
             $rule_data = $request->validate([
                'name' => 'required|max:50',
                'description' => 'required|max:220',
               
              
            ]);

            $rule = new Rule();
            $rule->name =  $rule_data['name'];
            $rule->description =  $rule_data['description'];
       
       
            $rule->save();

            return response()->json(['msg' => 'Regla insertada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al insertar la Regla'], 500);
        }
    }

    public function update(Request $request)
    {
        try {

            Log::info("entra a actualizar");
             $rule_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'description' => 'required|max:220',
              
              
            ]);
            Log::info($request);
            $rule = Rule::find( $rule_data['id']);
            $rule->name =  $rule_data['name'];
            $rule->description =  $rule_data['description'];
          
            $rule->save();

            return response()->json(['msg' => 'Regla actualizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al actualizar la Regla'], 500);
        }
    }

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