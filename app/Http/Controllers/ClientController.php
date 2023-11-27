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
            return response()->json(['clients' => Client::with('user')->get()], 200);
        } catch (\Throwable $th) {  
            Log::error($th);

            return response()->json(['msg' => "Error al mostrar los clientes"], 500);
        }
    }
    public function show(Request $request)
    {
        try {
            $clients_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['client' => Client::with('user')->find($clients_data['id'])], 200);
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
                'phone' => 'required|max:15',
                'user_id' => 'nullable|number'
            ]);

            $client = new Client();
            $client->name = $clients_data['name'];
            $client->surname = $clients_data['surname'];
            $client->second_surname = $clients_data['second_surname'];
            $client->email = $clients_data['email'];
            $client->phone = $clients_data['phone'];
            $client->user_id = $clients_data['user_id'];
            $client->save();

            return response()->json(['msg' => 'Cliente insertado correctamente'], 200);
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
                'phone' => 'required|max:15',
                'user_id' => 'required|number'
            ]);
            Log::info($request);
            $client = Client::find($clients_data['id']);
            $client->name = $clients_data['name'];
            $client->surname = $clients_data['surname'];
            $client->second_surname = $clients_data['second_surname'];
            $client->email = $clients_data['email'];
            $client->phone = $clients_data['phone'];
            $client->user_id = $clients_data['user_id'];
            $client->save();

            return response()->json(['msg' => 'Cliente actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al actualizar el cliente'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            
            $clients_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Client::destroy($clients_data['id']);

            return response()->json(['msg' => 'cliente eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el cliente'], 500);
        }
    }
}