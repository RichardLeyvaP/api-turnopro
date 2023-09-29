<?php
namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StoreController extends Controller
{
    public function index()
    {
        try { 
            
            Log::info( "entra a almacenes");
            return response()->json(['stores' => Store::all()], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los almacenes"], 500);
        }
    }
    public function show(Request $request)
    {
        try {
            $stores_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['client' => Store::find($stores_data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el almacen"], 500);
        }
    }
    public function store(Request $request)
    {

        Log::info("crear almacen");
        Log::info($request);
        try {
            $stores_data = $request->validate([
                'reference' => 'required|max:50',
                'description' => 'required|max:50',
                'address' => 'required|max:50'
              
            ]);

            $store = new Store();
            $store->reference = $stores_data['reference'];
            $store->description = $stores_data['description'];
            $store->address = $stores_data['address'];
       
            $store->save();

            return response()->json(['msg' => 'Almacén insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al insertar el almacén'], 500);
        }
    }

    public function update(Request $request)
    {
        try {

            Log::info("entra a actualizar");
                

            $stores_data = $request->validate([
                'id' => 'required|numeric',
                'reference' => 'required|max:50',
                'description' => 'required|max:50',
                'address' => 'required|max:50'
              
            ]);
            Log::info($request);
            $store = Store::find($stores_data['id']);
            $store->reference = $stores_data['reference'];
            $store->description = $stores_data['description'];
            $store->address = $stores_data['address'];
            $store->save();

            return response()->json(['msg' => 'Almacén actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al actualizar el almacén'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            
            $stores_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Store::destroy($stores_data['id']);

            return response()->json(['msg' => 'Almacén eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el almacén'], 500);
        }
    }
}