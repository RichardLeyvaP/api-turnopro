<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchService;
use App\Models\Service;
use App\Models\BranchServiceProfessional;
use App\Models\Professional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BranchServiceProfessionalController extends Controller
{
    public function index()
    {
        try {
            $professionalservices = BranchServiceProfessional::with('branchService.service', 'professional')->get();
            return response()->json(['branchServiceProfesional' => $professionalservices], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los servicios por trabajador"], 500);
        }
    }
    public function store_professional_service(Request $request)
    {
        try {
            $request->validate([
                'branch_service_ids' => 'required|array',
                'branch_service_ids.*' => 'exists:branch_service,id',
                'professional_id' => 'required|exists:professionals,id',
            ]);

            $branchServiceIds = $request->branch_service_ids;
            $professionalId = $request->professional_id;

            // Eliminar todas las asociaciones existentes para el professional_id dado
            DB::table('branch_service_professional')->where('professional_id', $professionalId)->delete();

            // Crear nuevas asociaciones con los branch_service_ids proporcionados
            $dataToInsert = collect($branchServiceIds)->map(function ($branchServiceId) use ($professionalId) {
                return [
                    'branch_service_id' => $branchServiceId,
                    'professional_id' => $professionalId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            DB::table('branch_service_professional')->insert($dataToInsert);

            return response()->json(['message' => 'Asociaciones actualizadas con éxito'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
        }
    }
    public function services_professional_branch(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $metaData = [];
            $assignedServices = BranchServiceProfessional::with(['branchService.service'])
            ->whereNull('deleted_at') // Solo no eliminados
            ->whereHas('branchService', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id'])
                    ->whereNull('deleted_at'); // Asegurar que el BranchService no esté eliminado
            })
             ->whereHas('branchService.service', function ($query) {
                $query->whereNull('services.deleted_at');
            })
            ->where('professional_id', $data['professional_id'])
            ->get(['branch_service_id', 'type_service', 'percent', 'meta']);

            $unassignedServiceIds = BranchService::whereNotIn('id', $assignedServices->pluck('branch_service_id')->toArray())
                ->where('branch_id', $data['branch_id'])
                ->whereNull('deleted_at')
                ->whereHas('service', function ($query) {
                $query->whereNull('deleted_at'); // Solo si el servicio está activo
                })
                ->pluck('id');

            $unassignedServices = BranchService::whereIn('id', $unassignedServiceIds)
                ->with(['service' => function ($q) {
                    $q->whereNull('deleted_at'); // Opcional: si Service también tiene SoftDeletes
                }])
                ->whereNull('deleted_at')
                ->get()
                ->map(function ($branchService) {
                    $service = $branchService->service;
                    return [
                        'id' => $branchService->id,
                        "name" => $service->name,
                        "type_service" => $service->type_service,
                        "image_service" => $service->image_service,
                        "profit_percentaje" => $service->profit_percentaje,
                    ];
                });

            $assignedServicesData = [];
            foreach ($assignedServices as $branchservprof) {
                $branchService = $branchservprof->branchService;
                $service = $branchService->service;
                if ($branchservprof->meta) {
                    $metaData[] = [
                        'id' => $branchservprof->branch_service_id,
                        "name" => $service->name,
                        "type_service" => $branchservprof->type_service,
                        "image_service" => $service->image_service,
                        "profit_percentaje" => $branchservprof->percent,
                        "meta" => $branchservprof->meta,
                    ];
                }
                $assignedServicesData[] = [
                    'id' => $branchservprof->branch_service_id,
                    "name" => $service->name,
                    "type_service" => $branchservprof->type_service,
                    "image_service" => $service->image_service,
                    "profit_percentaje" => $branchservprof->percent,
                    "meta" => $branchservprof->meta,
                ];
            }

            return response()->json([
                'assignedServices' => $assignedServicesData,
                'unassignedServices' => $unassignedServices,
                'metaData' => $metaData
            ], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar la categoría de producto"], 500);
        }
    }

    public function services_professional_branch_web(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $serviceModels = BranchServiceProfessional::with([
            'branchService' => [
                'service' // Cargamos el servicio, pero lo filtraremos en el whereHas
            ]
            ])
            ->where('professional_id', $data['professional_id'])
            ->whereNull('branch_service_professional.deleted_at') // No eliminado lógicamente
            ->whereHas('branchService', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id'])
                    ->whereNull('branch_service.deleted_at') // branch_service no eliminado
                    ->whereHas('service', function ($q) {
                        $q->whereNull('services.deleted_at'); // servicio no eliminado
                    });
            })
            ->get(['branch_service_id', 'type_service', 'percent', 'id']);

            $formattedData = [];
            foreach ($serviceModels as $branchservprof) {
                $branchService = $branchservprof->branchService;
                $service = $branchService->service;
                $formattedData[] = [
                    'id' => $branchservprof->id,
                    "name" => $service->name,
                    "type_service" => $branchservprof->type_service,
                    "image_service" => $service->image_service,
                    "profit_percentaje" => $branchservprof->percent,
                    "price_service" => $service->price_service
                ];
            }

            return response()->json(['branchServicesPro' => $formattedData], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar la categoría de producto"], 500);
        }
    }
    public function professional_services(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $BSProfessional = BranchServiceProfessional::whereHas('branchService', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->where('professional_id', $data['professional_id'])->get();
            $serviceModels = $BSProfessional->map(function ($branchServiceProfessional) {
                $service = $branchServiceProfessional->branchService->service;
                return [
                    "id" => $branchServiceProfessional->id,
                    "name" => $service->name,
                    "simultaneou" => $service->simultaneou,
                    "price_service" => $service->price_service,
                    "type_service" => $service->type_service,
                    "profit_percentaje" => $service->profit_percentaje,
                    "duration_service" => $service->duration_service,
                    "image_service" => $service->image_service,
                    "service_comment" => $service->service_comment
                ];
            });
            return response()->json(['professional_services' => $serviceModels], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar la categoría de producto"], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_service_id' => 'required|numeric|exists:branch_service,id',
                'professional_id' => 'required|numeric|exists:professionals,id',
                'percent' => 'nullable|numeric',
                'type_service' => 'nullable|string'
            ]);

            // Buscar relación existente (incluyendo eliminados lógicos)
            $existingRelation = BranchServiceProfessional::withTrashed()
                ->where('branch_service_id', $data['branch_service_id'])
                ->where('professional_id', $data['professional_id'])
                ->first();

            // Si ya existe la relación
            if ($existingRelation) {
                // Si está eliminada lógicamente
                if ($existingRelation->trashed()) {
                    $existingRelation->restore();
                    
                    // Actualización campo por campo
                    if ($data['type_service'] == 'Regular') {
                        $existingRelation->percent = BranchService::find($data['branch_service_id'])
                                            ->service->profit_percentaje;
                        $existingRelation->type_service = 'Regular';
                    } else {
                        if (isset($data['percent'])) {
                            $existingRelation->percent = $data['percent'];
                        }
                        if (isset($data['type_service'])) {
                            $existingRelation->type_service = $data['type_service'];
                        }
                    }
                    
                    $existingRelation->save();

                    return response()->json([
                        'msg' => 'Relación profesional-servicio restaurada y actualizada',
                        'action' => 'restored'
                    ], 200);
                }

                return response()->json([
                    'msg' => 'Este profesional ya tiene asignado este servicio',
                    'action' => 'already_exists'
                ], 409);
            }

            // Crear nueva relación (sin asignación masiva)
            $newRelation = new BranchServiceProfessional();
            $newRelation->branch_service_id = $data['branch_service_id'];
            $newRelation->professional_id = $data['professional_id'];
            
            if ($data['type_service'] == 'Regular') {
                $newRelation->percent = BranchService::find($data['branch_service_id'])
                                    ->service->profit_percentaje;
                $newRelation->type_service = 'Regular';
            } else {
                $newRelation->percent = $data['percent'] ?? null;
                $newRelation->type_service = $data['type_service'] ?? null;
            }

            $newRelation->save();

            return response()->json([
                'msg' => 'Servicio asignado correctamente al profesional',
                'action' => 'created'
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'msg' => 'Error al asignar el servicio al profesional',
                'error' => $th->getMessage()
            ], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'nullable|numeric|exists:branches,id'
            ]);
            $services = BranchService::where('branch_id', $data['branch_id'])
            ->with('service') // Carga la relación con el servicio
            ->get()
            ->map(function ($branchService) {
                $service = $branchService->service;
                
                return [
                    'id' => $branchService->id,
                    'service_id' => $service->id,
                    'name' => $service->name,
                    'price_service' => $service->price_service,
                    'type_service' => $service->type_service,
                    'profit_percentaje' => $service->profit_percentaje,
                    'duration_service' => $service->duration_service,
                    'image_service' => $service->image_service,
                    'service_comment' => $service->service_comment,
                    'ponderation' => $branchService->ponderation
                ];
            })
            ->sortBy('ponderation')
            ->values();
            //$result = BranchServiceProfessional::with('branchService.service', 'professional')->find($data['id']);

            return response()->json(['branchServices' => $services], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los servicios por trabajador"], 500);
        }
    }

    public function branch_service_professionals(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_service_id' => 'nullable|numeric'
            ]);
            $branchServices = BranchServiceProfessional::where('branch_service_id', $data['branch_service_id'])->whereHas('professional', function ($query) {
                $query->whereHas('charge', function ($query) {
                    $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
                });
            })->get()->map(function ($branchService) {
                $professional = $branchService->professional;
                return [
                    'id' => $branchService->id,
                    'name' => $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname,
                    'image_url' => $professional->image_url,
                    'email' => $professional->email,
                    'phone' => $professional->phone,
                    'branch_service_id' => $branchService->branch_service_id,
                    'professional_id' => $branchService->professional_id

                ];
            });
            //$result = BranchServiceProfessional::with('branchService.service', 'professional')->find($data['id']);

            return response()->json(['professionals' => $branchServices], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los servicios por trabajador"], 500);
        }
    }
    
    public function professionals_branch_service(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_service_id' => 'required|numeric'
            ]);
            $branchservprof = BranchServiceProfessional::where('branch_service_id', $data['branch_service_id'])->get();
            $branch_id = $branchservprof->first()->branchService->branch_id;
            $ids = $branchservprof->pluck('professional_id');
            $professionals = Professional::whereNotIn('id', $ids)->whereHas('charge', function ($query) {
                $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
            })->whereHas('branches', function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id);
            })->get()->map(function ($professional) {
                return [
                    'id' => $professional->id,
                    'name' => $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname,

                ];
            });
            //$service = Service::find($data['id']);
            return response()->json(['professionals' => $professionals], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el servicio"], 500);
        }
    }

    public function branch_service_professional($data)
    {
        try {
            $branchServiceProfessional = BranchServiceProfessional::where('branch_service_id', $data['branch_service_id'])->where('professional_id', $data['professional_id'])->first();
            if (!$branchServiceProfessional) {
                $branchServiceProfessional = new BranchServiceProfessional();
                $branchServiceProfessional->branch_service_id = $data['branch_service_id'];
                $branchServiceProfessional->professional_id = $data['professional_id'];
                $branchServiceProfessional->save();
            }
            return $branchServiceProfessional->id;
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al asignar el servicio a al professional'], 500);
        }
    }

    public function update_meta(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_service_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'meta' => 'required|numeric'
            ]);
            $branchservice = BranchService::find($data['branch_service_id']);
            $professional = professional::find($data['professional_id']);
            $professional->branchServices()->updateExistingPivot($branchservice->id, ['meta' => $data['meta']]);

            return response()->json(['msg' => 'Servicio actualizado correctamente a este trabajador'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar el servicio a este empleado'], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'branch_service_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);

            $psersonservice = BranchServiceProfessional::find($data['id']);
            $psersonservice->branch_service_id = $data['branch_service_id'];
            $psersonservice->professional_id = $data['professional_id'];
            $psersonservice->save();

            return response()->json(['msg' => 'Servicio actualizado correctamente a este trabajador'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar el servicio a este empleado'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_service_id' => 'required|numeric|exists:branch_service,id',
                'professional_id' => 'required|numeric|exists:professionals,id'
            ]);

            // Buscar la relación específica
            $relation = BranchServiceProfessional::where([
                'branch_service_id' => $data['branch_service_id'],
                'professional_id' => $data['professional_id']
            ])->first();

            if (!$relation) {
                return response()->json([
                    'msg' => 'La relación no existe'
                ], 404);
            }

            // Verificar si ya está eliminada lógicamente
            if ($relation->trashed()) {
                return response()->json([
                    'msg' => 'La relación ya fue eliminada anteriormente'
                ], 410); // 410 Gone
            }

            // Eliminación lógica manual (sin usar delete() para evitar mass assignment)
            $relation->deleted_at = now();
            $relation->save();

            return response()->json([
                'msg' => 'Relación desvinculada correctamente (eliminación lógica)',
                'deleted_at' => $relation->deleted_at
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'msg' => 'Error al desvincular el servicio del profesional',
                'error' => $th->getMessage()
            ], 500);
        }
    }
}
