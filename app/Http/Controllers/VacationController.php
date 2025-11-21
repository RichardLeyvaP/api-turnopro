<?php

namespace App\Http\Controllers;

use App\Models\Professional;
use App\Models\Vacation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VacationController extends Controller
{
    /**
 * Lista todas las vacaciones registradas y todos los profesionales del sistema.
 *
 * Incluye datos completos del profesional (incluso si está eliminado lógicamente).
 *
 * @authenticated
 *
 * @response 200 {
 *   "vacations": [
 *     {
 *       "id": 1,
 *       "professional_id": 10,
 *       "name": "Carlos Pérez García",
 *       "image_url": "professionals/10.jpg",
 *       "description": "Vacaciones de verano",
 *       "startDate": "2025-12-01",
 *       "endDate": "2025-12-15"
 *     }
 *   ],
 *   "professionals": [
 *     {
 *       "id": 10,
 *       "name": "Carlos Pérez García",
 *       "image_url": "professionals/10.jpg",
 *       "charge": "Barbero"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function index()
    {
        try {
            $vacations = Vacation::with(['professional'])->get()->map(function ($vacation) {
                // $professional = $vacation->professional;
                $professional = $vacation->professional()->withTrashed()->first();
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
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    /**
 * Registra un nuevo período de vacaciones para un profesional.
 *
 * @authenticated
 * @bodyParam professional_id integer required ID del profesional. Example: 10
 * @bodyParam startDate date required Fecha de inicio (Y-m-d). Example: "2025-12-01"
 * @bodyParam endDate date required Fecha de fin (Y-m-d). Example: "2025-12-15"
 * @bodyParam description string optional Descripción del período. Example: "Vacaciones de verano"
 *
 * @response 200 {"msg": "Vacaciones registrada correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function store(Request $request)
    {
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
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    /**
 * Obtiene vacaciones y profesionales asociados a una sucursal específica.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 3
 *
 * @response 200 {
 *   "vacations": [...],
 *   "professionals": [...]
 * }
 * @response 500 {"msg": "[error]Error interno del sistema"}
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
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    /**
 * Actualiza un período de vacaciones existente.
 *
 * @authenticated
 * @bodyParam id integer required ID del registro de vacaciones. Example: 1
 * @bodyParam professional_id integer required Nuevo ID del profesional. Example: 11
 * @bodyParam startDate date required Nueva fecha de inicio. Example: "2025-12-05"
 * @bodyParam endDate date required Nueva fecha de fin. Example: "2025-12-20"
 * @bodyParam description string optional Nueva descripción. Example: "Vacaciones extensión"
 *
 * @response 200 {"msg": "Vacaciones actualizadas correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function update(Request $request, Vacation $vacation)
    {
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
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

   /**
 * Elimina un período de vacaciones.
 *
 * ⚠️ **Advertencia**: Esta acción es irreversible.
 *
 * @authenticated
 * @bodyParam id integer required ID del registro a eliminar. Example: 1
 *
 * @response 200 {"msg": "Vacaciones eliminadas correctamente"}
 * @response 500 {"msg": "Error interno del sistema"}
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
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }
}
