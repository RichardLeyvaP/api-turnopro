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
            return response()->json(['professional' => Professional::with('clients')->get()], 200);
        } catch (\Throwable $th) {  
        return response()->json(['msg' => "Error al mostrar los clientes atendidos por empleado"], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'client_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $client = Client::find($data['client_id']);
            $professional = Professional::find($data['professional_id']);
            $client_professional = $client->professionals()->where('professional_id', $data['professional_id'])->exists();
            //$result = ClientProfessional::where('client_id',$data['client_id'])->where('professional_id',$data['professional_id'])->get();
            if (!$client_professional) {                
                $professional->clients()->attach($client->id);
                $result = ClientProfessional::latest('id')->first();
                }
            return response()->json(['msg' => 'Empleado asignado correctamente al cliente'], 200);
        } catch (\Throwable $th) {
        return response()->json(['msg' => 'Error al asignar el empleado a este cliente'], 500);
        }
    }
    public function show(Request $request)
    {
        try {             
            $data = $request->validate([
                'client_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            if ($data['client_id']) {
                return response()->json(['cliente' => Client::with('professionals')->find($data['client_id'])], 200);
            }
            if ($data['professional_id']) {
                return response()->json(['professional' => Professional::with('clients')->find($data['professional_id'])],200); 
            }
            
            } catch (\Throwable $th) { 
        return response()->json(['msg' => "Error al mostrar los clientes"], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'client_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $client = Client::find($data['client_id']);
            $professional = Professional::find($data['professional_id']);
            $professional->clients()->updateExistingPivot($client->id);
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
            $professional->clients()->destroy($client->id);
            return response()->json(['msg' => 'Cliente eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el cliente a es empleado'], 500);
        }
    }
}
