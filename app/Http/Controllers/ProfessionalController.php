<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfessionalController extends Controller
{
    public function index()
    {
        try {
            return response()->json(['profesionales' => Professional::with('user', 'charge')->get()], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar las personas"], 500);
        }
    }
    public function show(Request $request)
    {
        try {
            $persons_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['person' => Professional::with('user', 'charge')->find($persons_data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar la persona"], 500);
        }
    }
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|max:50',
                'surname' => 'required|max:50',
                'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email|unique:professionals',
                'phone' => 'required|max:15',
                'charge_id' => 'required|number',
                'user_id' => 'required|number'
            ]);

            $professional = new Professional();
            $professional->name = $data['name'];
            $professional->surname = $data['surname'];
            $professional->second_surname = $data['second_surname'];
            $professional->email = $data['email'];
            $professional->phone = $data['phone'];
            $professional->charge_id = $data['charge_id'];
            $professional->user_id = $data['user_id'];
            $professional->save();

            return response()->json(['msg' => 'Profesional insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' =>  $th->getMessage().'Error al insertar la persona'], 500);
        }
    }

    public function update(Request $request)
    {
        try {

            Log::info("entra a actualizar");


            $persons_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'surname' => 'required|max:50',
                'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email',
                'phone' => 'required|max:15',
                'charge_id' => 'required|numeric',
                'user_id' => 'required|numeric'
            ]);
            Log::info($request);
            $person = Professional::find($persons_data['id']);
            $person->name = $persons_data['name'];
            $person->surname = $persons_data['surname'];
            $person->second_surname = $persons_data['second_surname'];
            $person->email = $persons_data['email'];
            $person->phone = $persons_data['phone'];
            $person->charge_id = $persons_data['charge_id'];
            $person->user_id = $persons_data['user_id'];
            $person->save();

            return response()->json(['msg' => 'Profesional actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => $th->getMessage().'Error al actualizar la persona'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            
            $persons_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Professional::destroy($persons_data['id']);

            return response()->json(['msg' => 'Profesional eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar la persona'], 500);
        }
    }
}