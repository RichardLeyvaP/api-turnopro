<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchService;
use App\Models\Service;
use App\Models\BranchServiceProfessional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BranchServiceController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Entra a buscar los servicios por sucursales");
            return response()->json(['branch' => Branch::with('branchservices')->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => "Error al mostrar los servicios por sucursales"], 500);
        }
    }

    public function store(Request $request)
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
        return response()->json(['msg' =>'Error interno del sistema'], 500);
        }
    }

    public function show_service_idProfessional(Request $request)//todo modificar aqui
    {
        try {             
            Log::info( "Entra a buscar los servicio q brinda una sucursal");
            $data = $request->validate([
                'branch_id' => 'required'
            ]);
            $data['branch_id'] = intval($data['branch_id']);
            $services = Service::whereHas('branchServices', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->with('branchServices.branchServiceProfessional:id')->get();
                return response()->json(['services' => $services],200, [], JSON_NUMERIC_CHECK); 
          
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los servicios"], 500);
        }
    }


    public function show(Request $request)
    {
        try {             
            Log::info( "Entra a buscar los servicio q brinda una sucursal o la sucursales donde se brinda determinado servicio");
            $data = $request->validate([
                'branch_id' => 'nullable|numeric'
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
               $branch = Branch::find($data['branch_id']);
            $services = $branch->services->map(function ($service){
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
                return response()->json(['services' => $services],200); 
          
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los servicios"], 500);
        }
    }

    public function branch_service_show($data)
    {
        try {             
            Log::info( "Entra a buscar id de la relacion entre una sucursal y un servicio determinado servicio");
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
            return response()->json(['msg' => $th->getMessage().'Error al actualizar el servicio en esta sucursal'], 500);
        }
    }

    public function destroy(Request $request)
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
    }
}
