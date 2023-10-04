<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientPerson;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientPersonController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Entra a buscar los almacenes por sucursales");
            return response()->json(['person' => Person::with('personclients')->get()], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => "Error al mostrar los clientes atendidos por empleado"], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info("Asignar empleado a atender cliente");
        Log::info($request);
        try {
            $data = $request->validate([
                'client_id' => 'required|numeric',
                'person_id' => 'required|numeric'
            ]);
            $client = Client::find($data['client_id']);
            $person = Person::find($data['person_id']);
            $result = ClientPerson::join('clients', 'clients.id', '=', 'client_person.client_id')->join('people', 'people.id', '=', 'client_person.person_id')->where('client_person.client_id',$data['client_id'])->where('client_person.person_id',$data['person_id'])->get('client_person.*');
            if (count($result) == 0) {                
                $person->personclients()->attach($client->id);
                $result = ClientPerson::latest('id')->first();
                }
            return response()->json(['msg' => $result.'Empleado asignado correctamente al cliente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error al asignar el empleado a este cliente'], 500);
        }
    }

    public function show(Request $request)
    {
        try {             
            Log::info( "Entra a buscar los clientes atendidos por un empleado o el empleado que atendio a al cliente");
            $data = $request->validate([
                'client_id' => 'required|numeric',
                'person_id' => 'required|numeric'
            ]);
            if ($data['client_id']) {
                return response()->json(['cliente' => Client::with('clientpersons')->find($data['client_id'])], 200);
            }
            if ($data['person_id']) {
                return response()->json(['person' => Person::with('personclients')->find($data['person_id'])],200); 
            }
            
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => "Error al mostrar los clientes"], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            $data = $request->validate([
                'client_id' => 'required|numeric',
                'person_id' => 'required|numeric'
            ]);
            $client = Client::find($data['client_id']);
            $person = Person::find($data['person_id']);
            $person->personclients()->updateExistingPivot($client->id);
            return response()->json(['msg' => 'Cliente reasignado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar el cliente a es empleado'], 500);
        }
    }

   public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'client_id' => 'required|numeric',
                'person_id' => 'required|numeric'
            ]);
            $client = Client::find($data['client_id']);
            $person = Person::find($data['person_id']);
            $person->personclients()->destroy($client->id);
            return response()->json(['msg' => 'Cliente eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el cliente a es empleado'], 500);
        }
    }
}
