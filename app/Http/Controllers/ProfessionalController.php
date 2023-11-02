<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\ClientProfessional;
use App\Models\Professional;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function professionals_branch(Request $request)
    {
        try {
            $data = $request->validate([
               'professional_id' => 'required|numeric',
               'branch_id' => 'required|numeric'
           ]);
           $professionals = Professional::whereHas('branchServices', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
           })->find($data['professional_id']);
           
           if ($professionals) {
                $date = Carbon::now();
                $dataUser = [];
                $dataUser['id'] = $professionals->id;
                $dataUser['usuario'] = $professionals->name;
                $dataUser['fecha'] = $date->toDateString();
                $dataUser['hora'] = $date->Format('g:i:s A');
                return response()->json(['professional_branch' => $dataUser], 200);
           }
           return response()->json(['msg' => "Professionals no pertenece a esta Sucursal"], 500);
       } catch (\Throwable $th) {
           return response()->json(['msg' => "Professionals no pertenece a esta Sucursal"], 500);
       }
    }

    public function professionals_ganancias(Request $request)
    {
        try {
            $data = $request->validate([
               'professional_id' => 'required|numeric',
               'startDate' => 'required|date',
               'endDate' => 'required|date'
           ]);
           $cars = Car::whereHas('clientProfessional', function ($query) use ($data){
                $query->where('professional_id', $data['professional_id']);
           })->selectRaw('DATE(created_at) as date, SUM(amount) as earnings, SUM(amount) as total_earnings, AVG(amount) as average_earnings')->whereBetween('created_at', [$data['startDate'], $data['endDate']])->groupBy('date')->get();
           $earningByDay = $cars->map(function ($car){
            return [
                'date' => $car->date,
                'earnings' => $car->earnings,
            ];
           });
           $totalEarnings = $cars->sum('total_earnings');
           $averageEarnings = $cars->avg('average_earnings');
           
          return response()->json(['earningByDay' => $earningByDay, 'totalEarnings' => $totalEarnings, 'averageEarnings' => $averageEarnings], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => "Profssional no obtuvo ganancias en este período"], 500);
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
            return response()->json(['msg' =>  'Error al insertar la persona'], 500);
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
            return response()->json(['msg' => 'Error al actualizar la persona'], 500);
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