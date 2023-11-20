<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientProfessional;
use App\Models\Professional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientProfessionalController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Entra a buscar los almacenes por sucursales");
            return response()->json(['professional' => Professional::with('professionalClients')->get()], 200);
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
                'professional_id' => 'required|numeric'
            ]);
            $client = Client::find($data['client_id']);
            $professional = Professional::find($data['professional_id']);
            $result = ClientProfessional::where('client_id',$data['client_id'])->where('professional_id',$data['professional_id'])->get();
            if (count($result) == 0) {                
                $professional->professionalClients()->attach($client->id);
                return $result = ClientProfessional::latest('id')->first();
                }
            return response()->json(['msg' => 'Empleado asignado correctamente al cliente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => 'Error al asignar el empleado a este cliente'], 500);
        }
    }

    public function client_professional($data)
    {
        try {             
            Log::info( "Entra a buscar los clientes atendidos por un professional");        
            $client_professional_id = 0;
            $client_professional_id = ClientProfessional::where('client_professional.client_id',$data['client_id'])->where('client_professional.professional_id',$data['professional_id'])->value('id');
            if (!$client_professional_id) {
                $client = Client::find($data['client_id']);
                $professional = Professional::find($data['professional_id']);
                //$result = ClientProfessional::where('client_id',$data['client_id'])->where('professional_id',$data['professional_id'])->get();
                //if (count($result) == 0) {                
                    $professional->professionalClients()->attach($client->id);
                    $result = ClientProfessional::latest('id')->first();
                    //}
                $client_professional_id = $result->id;
            }
            return $client_professional_id;
        } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => 'Error al asignar el empleado a este cliente'], 500);
        }
    }

    public function show(Request $request)
    {
        try {             
            Log::info( "Entra a buscar los clientes atendidos por un empleado o el empleado que atendio a al cliente");
            $data = $request->validate([
                'client_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            if ($data['client_id']) {
                return response()->json(['cliente' => Client::with('clientProfessionals')->find($data['client_id'])], 200);
            }
            if ($data['professional_id']) {
                return response()->json(['professional' => Professional::with('professionalClients')->find($data['professional_id'])],200); 
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
                'professional_id' => 'required|numeric'
            ]);
            $client = Client::find($data['client_id']);
            $professional = Professional::find($data['professional_id']);
            $professional->professionalClients()->updateExistingPivot($client->id);
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
                'professional_id' => 'required|numeric'
            ]);
            $client = Client::find($data['client_id']);
            $professional = Professional::find($data['professional_id']);
            $professional->professionalClients()->destroy($client->id);
            return response()->json(['msg' => 'Cliente eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el cliente a es empleado'], 500);
        }
    }
}
