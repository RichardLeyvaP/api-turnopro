<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchService;
use App\Models\Service;
use App\Models\BranchServiceProfessional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BranchServiceController extends Controller
{
    public function index()
    {
        try {
            Log::info("Entra a buscar los servicios por sucursales");
            return response()->json(['branch' => Branch::with('branchservices')->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los servicios por sucursales"], 500);
        }
    }

    /*public function store(Request $request)
    {
        Log::info("Asignar servicio a una sucursal");
        Log::info($request);
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'service_id' => 'required|numeric',
                'ponderation' => 'nullable'
            ]);
            $branch = Branch::find($data['branch_id']);
            $service = Service::find($data['service_id']);

            $branch->services()->attach($service->id, ['ponderation' => $data['ponderation']]);

            return response()->json(['msg' => 'Servicio asignado correctamente a la sucursal'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }*/

    public function store(Request $request)
    {
        Log::info("Asignar servicio a una sucursal");
        Log::info($request);

        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric|exists:branches,id',
                'service_id' => 'required|numeric|exists:services,id',
                'ponderation' => 'nullable|numeric' // Agregué validación para número
            ]);

            // Buscar relación existente (incluyendo eliminados lógicos)
            $existingRelation = BranchService::withTrashed()
                ->where('branch_id', $data['branch_id'])
                ->where('service_id', $data['service_id'])
                ->first();

            if ($existingRelation) {
                // Si existe y está eliminada lógicamente
                if ($existingRelation->trashed()) {
                    $existingRelation->restore();
                    $existingRelation->ponderation = $data['ponderation'];
                    $existingRelation->save();

                    return response()->json([
                        'msg' => 'Relación restaurada y ponderación actualizada',
                        'action' => 'restored'
                    ], 200);
                }

                // Si ya existe una relación activa
                return response()->json([
                    'msg' => 'El servicio ya está asignado a esta sucursal',
                    'action' => 'already_exists'
                ], 409); // Código 409 Conflict
            }

            // Crear nueva relación
            $branch = Branch::findOrFail($data['branch_id']);
            $branch->services()->attach($data['service_id'], [
                'ponderation' => $data['ponderation']
            ]);

            return response()->json([
                'msg' => 'Servicio asignado correctamente a la sucursal',
                'action' => 'created'
            ], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'msg' => 'Error interno del sistema',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show_service_idProfessional(Request $request) //todo modificar aqui
    {
        try {
            Log::info("Entra a buscar los servicio q brinda una sucursal");
            $data = $request->validate([
                'branch_id' => 'required'
            ]);
            $data['branch_id'] = intval($data['branch_id']);
            $services = Service::whereHas('branchServices', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->with('branchServices.branchServiceProfessional:id')->get();
            return response()->json(['services' => $services], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los servicios"], 500);
        }
    }


    /*public function show(Request $request)
    {
        try {
            Log::info("Entra a buscar los servicio q brinda una sucursal o la sucursales donde se brinda determinado servicio");
            $data = $request->validate([
                'branch_id' => 'sometimes|numeric'
            ]);

            /*$services = Service::whereHas('branchServices', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
               })->get()->map(function ($service){
                $branchService = $service->branches->first()->pivot;
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'simultaneou' => $service->simultaneou,
                    'price_service' => $service->price_service,
                    'type_service' => $service->type_service,
                    'profit_percentaje' => $service->profit_percentaje,
                    'duration_service' => $service->duration_service,
                    'image_service' => $service->image_service,
                    'ponderation' => $branchService // Verificar si $branchService es null
                ];
               });*/
    /*$services = [];
            if ($data['branch_id'] != null) {
                $branch = Branch::find($data['branch_id']);
                $services = $branch->services->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'price_service' => $service->price_service,
                        'type_service' => $service->type_service,
                        'profit_percentaje' => $service->profit_percentaje,
                        'duration_service' => $service->duration_service,
                        'image_service' => $service->image_service,
                        'service_comment' => $service->service_comment,
                        'ponderation' => $service->pivot->ponderation
                    ];
                })->sortBy('name')->sortBy('ponderation')->values();
            }

            return response()->json(['services' => $services], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los servicios"], 500);
        }
    }*/

    public function show(Request $request)
    {
        try {
            Log::info("Buscar servicios de una sucursal");
            $data = $request->validate([
                'branch_id' => 'sometimes|numeric|exists:branches,id'
            ]);

            $services = [];

            if (!empty($data['branch_id'])) {
                // Consulta directa a branch_service con join a services
                $services = BranchService::with('service')
                ->where('branch_id', $data['branch_id'])
                ->get()
                ->map(function ($branchService) {
                    return [
                        'id' => $branchService->service->id,
                        'name' => $branchService->service->name,
                        'price_service' => $branchService->service->price_service,
                        'type_service' => $branchService->service->type_service,
                        'profit_percentaje' => $branchService->service->profit_percentaje,
                        'duration_service' => $branchService->service->duration_service,
                        'image_service' => $branchService->service->image_service,
                        'service_comment' => $branchService->service->service_comment,
                        'ponderation' => $branchService->ponderation
                    ];
                })
                ->sortBy([
                    ['ponderation', 'asc'],
                    ['name', 'asc']
                ])
                ->values();
            }

            return response()->json(['services' => $services], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'msg' => 'Error al mostrar los servicios',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function branch_service_show($data)
    {
        try {
            Log::info("Entra a buscar id de la relacion entre una sucursal y un servicio determinado servicio");
            $branchservice = BranchService::where('branch_id', $data['branch_id'])->where('service_id', $data['service_id'])->first();
            if (!$branchservice) {
                $branchservice = new BranchService();
                $branchservice->branch_id = $data['branch_id'];
                $branchservice->service_id = $data['service_id'];
                $branchservice->save();
            }
            return $branchservice->id;
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al asignar el servicio a la sucursal'], 500);
        }
    }


    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'service_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
                'ponderation' => 'nullable'
            ]);
            $service = Service::find($data['service_id']);
            $branch = Branch::find($data['branch_id']);
            $branch->services()->updateExistingPivot($service->id, ['ponderation' => $data['ponderation']]);
            return response()->json(['msg' => 'Servicio actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al actualizar el servicio en esta sucursal'], 500);
        }
    }

    /*public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'service_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $service = Service::find($data['service_id']);
            $branch = Branch::find($data['branch_id']);
            $branch->services()->detach($service->id);
            return response()->json(['msg' => 'Servicio eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al eliminar el servicio en esta sucursal'], 500);
        }
    }*/

    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric|exists:branch_service,id'
            ]);

            DB::transaction(function () use ($data) {
                // Buscar y eliminar branch_service
                $branchService = BranchService::findOrFail($data['id']);
                $branchService->delete();

                // Eliminar lógicamente los relacionados en branch_service_professional
                BranchServiceProfessional::where('branch_service_id', $data['id'])
                    ->delete();
            });

            return response()->json([
                'msg' => 'Servicio desvinculado correctamente con eliminación lógica en cascada'
            ], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json([
                'msg' => 'Error al desvincular el servicio',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
