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
            return response()->json(['branch' => Branch::with('branchservices')->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los servicios por sucursales"], 500);
        }
    }

    public function store(Request $request)
    {
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
            return response()->json([
                'msg' => 'Error interno del sistema',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show_service_idProfessional(Request $request) //todo modificar aqui
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required'
            ]);
            $data['branch_id'] = intval($data['branch_id']);
            $services = Service::whereHas('branchServices', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->with('branchServices.branchServiceProfessional:id')->get();
            return response()->json(['services' => $services], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los servicios"], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'sometimes|numeric|exists:branches,id'
            ]);

            $services = [];

            if (!empty($data['branch_id'])) {
                // Consulta directa a branch_service con join a services
                $services = BranchService::with('service')
                ->where('branch_id', $data['branch_id'])
                 ->whereNull('branch_service.deleted_at')
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
            return response()->json([
                'msg' => 'Error al mostrar los servicios',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function branch_service_show($data)
    {
        try {
            $branchservice = BranchService::where('branch_id', $data['branch_id'])->where('service_id', $data['service_id'])->first();
            if (!$branchservice) {
                $branchservice = new BranchService();
                $branchservice->branch_id = $data['branch_id'];
                $branchservice->service_id = $data['service_id'];
                $branchservice->save();
            }
            return $branchservice->id;
        } catch (\Throwable $th) {
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
            return response()->json(['msg' => $th->getMessage() . 'Error al actualizar el servicio en esta sucursal'], 500);
        }
    }

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
            return response()->json([
                'msg' => 'Error al desvincular el servicio',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
