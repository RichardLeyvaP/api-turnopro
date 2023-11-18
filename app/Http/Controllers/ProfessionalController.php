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
            return response()->json(['msg' => "Error al mostrar las professionalas"], 500);
        }
    }
    public function show(Request $request)
    {
        try {
            $professionals_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['professional' => Professional::with('user', 'charge')->find($professionals_data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar la professionala"], 500);
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

    public function branch_professionals(Request $request)
    {
        try {
            $data = $request->validate([
               'branch_id' => 'required|numeric'
           ]);
           $professionals = Professional::whereHas('branchServices', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
           })->get();
           
           return response()->json(['professionals' => $professionals], 200);
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
           $startDate = Carbon::parse($data['startDate']);
           $endDate = Carbon::parse($data['endDate']);
          $dates = [];
          $i=0;
           $cars = Car::whereHas('clientProfessional', function ($query) use ($data){
                $query->where('professional_id', $data['professional_id']);
           })->selectRaw('DATE(updated_at) as date, SUM(amount) as earnings, SUM(amount) as total_earnings, AVG(amount) as average_earnings')->whereBetween('updated_at', [$data['startDate'], $data['endDate']])->where('pay', 1)->groupBy('date')->get();
           for($date = $startDate; $date->lte($endDate);$date->addDay()){
            $machingResult = $cars->firstWhere('date', $date->toDateString());
            $dates[$i]['date'] = $date->toDateString();
            $dates[$i++]['earnings'] = $machingResult ? $machingResult->earnings: 0;
          }
           /*$earningByDay = $cars->map(function ($car){
            return [
                'date' => $car->date,
                'earnings' => $car->earnings,
            ];
           });*/
           $totalEarnings = $cars->sum('total_earnings');
           $averageEarnings = $cars->avg('average_earnings');
           
          return response()->json(['earningByDay' => $dates, 'totalEarnings' => $totalEarnings, 'averageEarnings' => $averageEarnings], 200);
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
            $professional->state = 0;
            $professional->save();

            return response()->json(['msg' => 'Profesional insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' =>  'Error al insertar la professionala'], 500);
        }
    }

    public function update(Request $request)
    {
        try {

            Log::info("entra a actualizar");


            $professionals_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'surname' => 'required|max:50',
                'second_surname' => 'required|max:50',
                'email' => 'required|max:50|email',
                'phone' => 'required|max:15',
                'charge_id' => 'required|numeric',
                'user_id' => 'required|numeric',
                'state' => 'required|numeric',
            ]);
            Log::info($request);
            $professional = Professional::find($professionals_data['id']);
            $professional->name = $professionals_data['name'];
            $professional->surname = $professionals_data['surname'];
            $professional->second_surname = $professionals_data['second_surname'];
            $professional->email = $professionals_data['email'];
            $professional->phone = $professionals_data['phone'];
            $professional->charge_id = $professionals_data['charge_id'];
            $professional->user_id = $professionals_data['user_id'];
            $professional->state = $professionals_data['state'];
            $professional->save();

            return response()->json(['msg' => 'Profesional actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al actualizar la professionala'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            
            $professionals_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Professional::destroy($professionals_data['id']);

            return response()->json(['msg' => 'Profesional eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar la professionala'], 500);
        }
    }
}