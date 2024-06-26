<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\Vacation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VacationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            Log::info('Entra a buscar las vacaciones');
            $vacations = Vacation::with(['professional'])->get()->map(function ($vacation) {
                $professional = $vacation->professional;
                return [
                    'id' => $vacation->id,
                    'professional_id' => $professional->id,
                    'name' => $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname,
                    'image_url' => $professional->image_url,
                    'description' => $vacation->description,
                    'startDate' => $vacation->startDate,
                    'endDate' => $vacation->endDate
                ];
            });
    
            $professionals = Professional::with('user', 'charge')->get()->map(function ($professional) {
                return [
                    'id' => $professional->id,
                    'name' => $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname,
                    'image_url' => $professional->image_url,
                    'charge' => $professional->charge->name
                ];
            });
    
            return response()->json(['vacations' => $vacations, 'professionals' => $professionals], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }
     /*public function index()
    {
        try {
            Log::info('Entra a buscar las vacaciones');
            $vacations = Vacation::with(['professional'])->get()->map(function ($vacation) {
                $professional = $vacation->professional;
                return [
                    'id' => $vacation->id,
                    'professional_id' => $professional->id,
                    'name' => $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname,
                    'image_url' => $professional->image_url,
                    'description' => $vacation->description,
                    'startDate' => $vacation->startDate,
                    'endDate' => $vacation->endDate
                ];
            });
            return response()->json(['vacations' => $vacations], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }*/


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info("Guardar vacaciones");
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'startDate' => 'required|date',
                'endDate' => 'required|date',
                'description' => 'nullable'
            ]);

            $vacacion = new Vacation();
            $vacacion->professional_id = $data['professional_id'];
            $vacacion->startDate = $data['startDate'];
            $vacacion->endDate = $data['endDate'];
            $vacacion->description = $data['description'];
            $vacacion->save();
           
            return response()->json(['msg' => 'Vacaciones registrada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    /**
     * Display the specified resource.
     */

     public function show(Request $request)
{
    try {
        $data = $request->validate([
            'branch_id' => 'required|numeric'
        ]);

        $vacations = Vacation::whereHas('professional.branches', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
        })->get()->map(function ($vacation) {
            $professional = $vacation->professional;
            return [
                'id' => $vacation->id,
                'professional_id' => $professional->id,
                'name' => $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname,
                'image_url' => $professional->image_url,
                'description' => $vacation->description,
                'startDate' => $vacation->startDate,
                'endDate' => $vacation->endDate

            ];
        });

        $professionals = Professional::whereHas('branches', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
        })->get()->map(function ($professional) {
            return [
                'id' => $professional->id,
                'name' => $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname,
                'image_url' => $professional->image_url,
                'charge' => $professional->charge->name

            ];
        });

        return response()->json([
            'vacations' => $vacations,
            'professionals' => $professionals
        ], 200);
    } catch (\Throwable $th) {
        Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
    }
}
    /*public function show(Request $request)
    {
        Log::info("Actualizar vacaciones");
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);

            $vacations = Vacation::whereHas('professional.branches', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            })->get()->map(function ($vacation) {
                $professional = $vacation->professional;
                return [
                    'id' => $vacation->id,
                    'professional_id' => $professional->id,
                    'name' => $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname,
                    'image_url' => $professional->image_url,
                    'description' => $vacation->description,
                    'startDate' => $vacation->startDate,
                    'endDate' => $vacation->endDate

                ];
            });
            
           
            return response()->json(['vacations' => $vacations], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }*/

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Vacation $vacation)
    {
        Log::info("Actualizar vacaciones");
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'startDate' => 'required|date',
                'endDate' => 'required|date',
                'description' => 'nullable'
            ]);

            $vacacion = Vacation::where('id', $data['id'])->first();
            $vacacion->professional_id = $data['professional_id'];
            $vacacion->startDate = $data['startDate'];
            $vacacion->endDate = $data['endDate'];
            $vacacion->description = $data['description'];
            $vacacion->save();
           
            return response()->json(['msg' => 'Vacaciones actualizadas correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            
            Vacation::destroy($data['id']);

            return response()->json(['msg' => 'Vacaciones eliminadas correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }
}
