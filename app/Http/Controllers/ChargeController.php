<?php
namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Charge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChargeController extends Controller
{
    public function index_web(Request $request)
    {
        Log::info( "entra a buscar cargos y sucursales");
        try { $branch_data = $request->validate([
            'business_id' => 'required|numeric'
        ]);
        $charges = Charge::all();
        $branches = Branch::where('business_id', $branch_data['business_id'])->select('id', 'name', 'image_data')->get();
        return response()->json(['branches' => $branches, 'charges' => $charges], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los cargos"], 500);
        }
    }

    public function index()
    {
        try { 
            
            Log::info( "entra a buscar cargos");
            return response()->json(['charges' => Charge::all()], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los cargos"], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $charge_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['client' => Charge::find($charge_data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el cargo"], 500);
        }
    }
    public function store(Request $request)
    {

        Log::info("crear cargo");
        Log::info($request);
        try {
            $charge_data = $request->validate([
                'name' => 'required|max:50',
                'description' => 'required|max:50',
               
              
            ]);

            $store = new Charge();
            $store->name = $charge_data['name'];
            $store->description = $charge_data['description'];
       
       
            $store->save();

            return response()->json(['msg' => 'Cargo insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al insertar el Cargo'], 500);
        }
    }

    public function update(Request $request)
    {
        try {

            Log::info("entra a actualizar");
                

            $charge_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'description' => 'required|max:50',
              
              
            ]);
            Log::info($request);
            $store = Charge::find($charge_data['id']);
            $store->name = $charge_data['name'];
            $store->description = $charge_data['description'];
          
            $store->save();

            return response()->json(['msg' => 'Cargo actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al actualizar el Cargo'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            
            $charge_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Charge::destroy($charge_data['id']);

            return response()->json(['msg' => 'Cargo eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el Cargo'], 500);
        }
    }
}