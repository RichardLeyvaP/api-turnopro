<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CarController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Entra a buscar los carros");
            $car = Car::join('client_person', 'client_person.id', '=', 'cars.client_person_id')->join('clients', 'clients.id', '=', 'client_person.client_id')->join('people', 'people.id', '=', 'client_person.person_id')->get(['clients.name as client_name', 'clients.surname as client_surname', 'clients.second_surname as client_second_surname', 'clients.email as client_email', 'clients.phone as client_phone', 'people.*', 'cars.*']);
            return response()->json(['cars' => $car], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()."Error al mostrar los carros"], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info("Guardar carro");
        Log::info($request);
        try {
            $data = $request->validate([
                'client_person_id' => 'required|numeric',
                'amount' => 'nullable|numeric',
                'pay' => 'boolean'
            ]);
            $car = new Car();
            $car->client_person_id = $data['client_person_id'];
            $car->amount = $data['amount'];
            $car->pay = $data['pay'];
            $car->save();

            return response()->json(['msg' => 'Carro insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error al insertar el carro'], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $car = Car::join('client_person', 'client_person.id', '=', 'cars.client_person_id')->join('clients', 'clients.id', '=', 'client_person.client_id')->join('people', 'people.id', '=', 'client_person.person_id')->where('cars.id', $data['id'])->get(['clients.name as client_name', 'clients.surname as client_surname', 'clients.second_surname as client_second_surname', 'clients.email as client_email', 'clients.phone as client_phone', 'people.*', 'cars.*']);
            return response()->json(['car' => $car], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el carrito"], 500);
        }
    }

   public function update(Request $request)
    {
        try {

            Log::info("Editar");
            Log::info($request);
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);

            $car = Car::find($data['id']);
            $car->pay = true;
            $car->save();
            return response()->json(['msg' => 'Carro actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => 'Error al actualizar el carro'], 500);
        }
    }

    public function destroy(Request $request)
    {
        Log::info("Eliminar");
        try{
        $data = $request->validate([
            'id' => 'required|numeric'
        ]);

            $car = Car::find($data['id']);
            $car->delete();
            return response()->json(['msg' => 'Carro eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => 'Error al eliminar el carro'], 500);
        }
    }
}
