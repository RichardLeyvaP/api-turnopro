<?php
namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    public function index()
    {
        try { 
            
            Log::info( "entra a cliente");

            $client=Client::all();
            Log::info( $client);
            return response()->json(['clients' => Client::all()], 200);
        } catch (\Throwable $th) {  
            Log::error($th);

            return response()->json(['msg' => "Error al mostrar las professionalas"], 500);
        }
    }
    public function show(Request $request)
    {
        try {
            $clients_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['client' => Client::find($clients_data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar la professionala"], 500);
        }
    }
    public function store(Request $request)
    {
        try {
            $clients_data = $request->validate([
                'name' => 'required|max:50',
                'surname' => 'required|max:50',
                'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email|unique:clients',
                'phone' => 'required|max:15'
            ]);

            $client = new Client();
            $client->name = $clients_data['name'];
            $client->surname = $clients_data['surname'];
            $client->second_surname = $clients_data['second_surname'];
            $client->email = $clients_data['email'];
            $client->phone = $clients_data['phone'];
            $client->save();

            return response()->json(['msg' => 'Cliente insertad0 correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al insertar al Cliente'], 500);
        }
    }

    public function update(Request $request)
    {
        try {

            Log::info("entra a actualizar");


            $clients_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'surname' => 'required|max:50',
                'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email',
                'phone' => 'required|max:15'
            ]);
            Log::info($request);
            $client = Client::find($clients_data['id']);
            $client->name = $clients_data['name'];
            $client->surname = $clients_data['surname'];
            $client->second_surname = $clients_data['second_surname'];
            $client->email = $clients_data['email'];
            $client->phone = $clients_data['phone'];
            $client->save();

            return response()->json(['msg' => 'professionala actualizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al actualizar la professionala'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            
            $clients_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Client::destroy($clients_data['id']);

            return response()->json(['msg' => 'professionala eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar la professionala'], 500);
        }
    }
}