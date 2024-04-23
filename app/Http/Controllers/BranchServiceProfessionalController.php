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

        $assignedServices = BranchServiceProfessional::with(['branchService.service'])
            ->whereHas('branchService.branch', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })
            ->where('professional_id', $data['professional_id'])
            ->get(['branch_service_id', 'type_service', 'percent']);

        $unassignedServiceIds = BranchService::whereNotIn('id', $assignedServices->pluck('branch_service_id')->toArray())
            ->where('branch_id', $data['branch_id'])
            ->pluck('id');

        $unassignedServices = BranchService::whereIn('id', $unassignedServiceIds)
            ->with('service')
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
            $assignedServicesData[] = [
                'id' => $branchservprof->branch_service_id,
                "name" => $service->name,
                "type_service" => $branchservprof->type_service,
                "image_service" => $service->image_service,
                "profit_percentaje" => $branchservprof->percent,
            ];
        }

        return response()->json([
            'assignedServices' => $assignedServicesData,
            'unassignedServices' => $unassignedServices
        ], 200, [], JSON_NUMERIC_CHECK);
    } catch (\Throwable $th) {
        return response()->json(['msg' => $th->getMessage() . "Error al mostrar la categoría de producto"], 500);
    }
}
    /*public function services_professional_branch(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $serviceModels = BranchServiceProfessional::with(['branchService.service'])
            ->whereHas('branchService.branch', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })
            ->where('professional_id', $data['professional_id'])
            ->get(['branch_service_id', 'type_service', 'percent']);

        $formattedData = [];
        foreach ($serviceModels as $branchservprof) {
            $branchService = $branchservprof->branchService;
            $service = $branchService->service;
            $formattedData[] = [
                'id' => $branchservprof->branch_service_id,
                "name" => $service->name,
                "type_service" => $branchservprof->type_service,
                "image_service" => $service->image_service,
                "profit_percentaje" => $branchservprof->percent,
            ];
        }

        return response()->json(['branchServices' => $formattedData], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar la categoría de producto"], 500);
        }
    }*/
    public function services_professional_branch_web(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
           $serviceModels = BranchServiceProfessional::with(['branchService.service'])
            ->whereHas('branchService.branch', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })
            ->where('professional_id', $data['professional_id'])
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

        $serviceModels = Service::with(['branchServices' => function ($query) use ($data) {
                $query->select('service_id')->where('branch_id', $data['branch_id']);
            }, 'professionals' => function ($query) use ($data) {
                $query->select('service_id')->where('professional_id', $data['professional_id']);
            }])
            ->whereHas('branchServices', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })
            ->whereHas('professionals', function ($query) use ($data) {
                $query->where('professional_id', $data['professional_id']);
            })
            ->select([
                'id', 
                'name', 
                'simultaneou', 
                'price_service', 
                'type_service', 
                'profit_percentaje', 
                'duration_service', 
                'image_service', 
                'service_comment'
            ])
            ->get()
            ->map(function ($service) {
                return [
                    "id" => $service->id, // Using correct 'id' from the 'services' table
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
                'branch_service_id' => 'required|numeric',
                'professional_id' => 'required|numeric',
                'percent' => 'nullable|numeric',
                'type_service' => 'nullable'
            ]);
            $branchservice = BranchService::find($data['branch_service_id']);
            $professional = professional::find($data['professional_id']);
            if($data['type_service'] == 'Regular'){
                $data['percent'] = $branchservice->service->profit_percentaje;
                $data['type_service'] = 'Regular';
            }
            Log::info($data);

            $professional->branchservices()->attach($branchservice->id, ['percent' => $data['percent'], 'type_service' => $data['type_service']]);

            //$psersonservice = new BranchServiceProfessional();
            //$psersonservice->branch_service_id = $data['branch_service_id'];
            //$psersonservice->professional_id = $data['professional_id'];
            //$psersonservice->save();

            return response()->json(['msg' => 'Servicio asignado correctamente a este trabajador'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error interno del sistema'], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            Log::info("Entra a buscar los srvicios que realiza una branch");
            $data = $request->validate([
                'branch_id' => 'nullable|numeric'
            ]);
            $branch = Branch::find($data['branch_id']);
            $services = $branch->services->map(function ($service){
                return [
                    'id' => $service->pivot->id,
                    'service_id' => $service->id,
                    'name' => $service->name,
                    'price_service' => $service->price_service,
                    'type_service' => $service->type_service,
                    'profit_percentaje' => $service->profit_percentaje,                    
                    'duration_service' => $service->duration_service,
                    'image_service' => $service->image_service,
                    'service_comment' => $service->service_comment
                ];
            });
            //$result = BranchServiceProfessional::with('branchService.service', 'professional')->find($data['id']);

            return response()->json(['branchServices' => $services], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los servicios por trabajador"], 500);
        }
    }

    public function branch_service_professionals(Request $request)
    {
        try {
            Log::info("Entra a buscar los professionals que realizan servicio de una branch");
            $data = $request->validate([
                'branch_service_id' => 'nullable|numeric'
            ]);
            $branchServices = BranchServiceProfessional::where('branch_service_id', $data['branch_service_id'])->whereHas('professional', function ($query){
                $query->whereHas('charge', function ($query) {
                    $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
                });
            })->get()->map(function ($branchService){
                $professional = $branchService->professional;
                return [
                    'id' => $branchService->id,
                    'name' => $professional->name.' '.$professional->surname.' '.$professional->second_surname,
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
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los servicios por trabajador"], 500);
        }
    }
    /*public function services_professional_branch_free(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $ids = BranchServiceProfessional::whereHas('branchService.branch', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            })->whereHas('professional', function ($query) use ($data){
                $query->where('id', $data['professional_id']);
            })->get()->pluck('branch_service_id'); 
            $serviceModels = BranchService::whereNotIn('id', $ids)->where('branch_id', $data['branch_id'])->get()->map(function ($branchserv) {
                $service = $branchserv->service;
                return [
                    'id' => $branchserv->id,
                    "name" => $service->name,
                    "type_service" => $service->type_service,
                    "image_service" => $service->image_service,
                    "profit_percentaje" => $service->profit_percentaje,
                ];
            });                 
            return response()->json(['branchServices' => $serviceModels], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar la categoría de producto"], 500);
        }
    }*/
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
            })->whereHas('branches', function ($query) use ($branch_id){
                $query->where('branch_id', $branch_id);
            })->get()->map(function ($professional){
                return [
                    'id' => $professional->id,
                    'name' => $professional->name.' '.$professional->surname.' '.$professional->second_surname,

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
            Log::info("Asignar");
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
            Log::error($th);
            return response()->json(['msg' => 'Error al actualizar el servicio a este empleado'], 500);
        }
    }

    public function destroy(Request $request)
    {
            try {
                $data = $request->validate([
                    'branch_service_id' => 'required|numeric',
                    'professional_id' => 'required|numeric'
                ]);
                $branchService = BranchService::find($data['branch_service_id']);
                $professional = Professional::find($data['professional_id']);
                $branchService->branchServiceProfessional()->detach($professional->id);
                return response()->json(['msg' => 'Servicio eliminado correctamente'], 200);
            } catch (\Throwable $th) {
                return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
            }
        }
}
