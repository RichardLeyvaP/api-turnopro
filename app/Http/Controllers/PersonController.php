<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PersonController extends Controller
{
    public function index()
    {
        try {
            return response()->json(['persons' => Person::with('business')->get()], 200);
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
            return response()->json(['person' => Person::with('business')->find($persons_data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar la persona"], 500);
        }
    }
    public function store(Request $request)
    {
        try {
            $persons_data = $request->validate([
                'name' => 'required|max:50',
                'surname' => 'required|max:50',
                'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email|unique:people',
                'phone' => 'required|max:15'
            ]);

            $person = new Person();
            $person->name = $persons_data['name'];
            $person->surname = $persons_data['surname'];
            $person->second_surname = $persons_data['second_surname'];
            $person->email = $persons_data['email'];
            $person->phone = $persons_data['phone'];
            $person->save();

            return response()->json(['msg' => 'Persona insertada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al insertar la persona'], 500);
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
                'phone' => 'required|max:15'
            ]);
            Log::info($request);
            $person = Person::find($persons_data['id']);
            $person->name = $persons_data['name'];
            $person->surname = $persons_data['surname'];
            $person->second_surname = $persons_data['second_surname'];
            $person->email = $persons_data['email'];
            $person->phone = $persons_data['phone'];
            $person->save();

            return response()->json(['msg' => 'Persona actualizada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al actualizar la persona'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            
            $persons_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Person::destroy($persons_data['id']);

            return response()->json(['msg' => 'Persona eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar la persona'], 500);
        }
    }
}