<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Service;
use App\Models\BranchServiceProfessional;
use Illuminate\Http\Request;
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
    public function professional_services(Request $request)
    {
        try {
            $data = $request->validate([
               'professional_id' => 'required|numeric',
               'branch_id' => 'required|numeric'
           ]);
           $serviceModels = Service::whereHas('branchServices', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
           })->whereHas('professionals', function ($query) use ($data){
                $query->where('professional_id', $data['professional_id']);
           })->get()->map(function ($service){
                return [
                    "id" => $service->professionals->pluck('id')->first(),
                    "name"=> $service->name,
                    "simultaneou"=> $service->simultaneou,
                    "price_service"=> $service->price_service,
                    "type_service"=> $service->type_service,
                    "profit_percentaje"=> $service->profit_percentaje,
                    "duration_service"=> $service->duration_service,
                    "image_service"=> $service->image_service,
                    "service_comment"=> $service->service_comment
                ];
           });
           /*$BSProfessional = BranchServiceProfessional::whereHas('branchService', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
           })->where('professional_id', $data['professional_id'])->get();
           $serviceModels = $BSProfessional->map(function ($branchServiceProfessional){
                return[
                    "id" => $branchServiceProfessional->id,
                    "name"=> $branchServiceProfessional->branchService->service->name,
                    "simultaneou"=> $branchServiceProfessional->branchService->service->simultaneou,
                    "price_service"=> $branchServiceProfessional->branchService->service->price_service,
                    "type_service"=> $branchServiceProfessional->branchService->service->type_service,
                    "profit_percentaje"=> $branchServiceProfessional->branchService->service->profit_percentaje,
                    "duration_service"=> $branchServiceProfessional->branchService->service->duration_service,
                    "image_service"=> $branchServiceProfessional->branchService->service->image_service,
                    "service_comment"=> $branchServiceProfessional->branchService->service->service_comment
                ];
           });*/
           
           return response()->json(['professional_services' => $serviceModels], 200, [], JSON_NUMERIC_CHECK);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Error al mostrar la categoría de producto"], 500);
       }
    }
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_service_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);

            
            $psersonservice = new BranchServiceProfessional();
            $psersonservice->branch_service_id = $data['branch_service_id'];
            $psersonservice->professional_id = $data['professional_id'];
            $psersonservice->save();

            return response()->json(['msg' => 'Servicio asignado correctamente a este trabajador'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage().'Error al asignar el servicio a este empleado'], 500);
        }
    }

    public function show(Request $request)
    {
        try {             
            Log::info( "Entra a buscar los productos de un almacén");
            $data = $request->validate([
                'id' => 'nullable|numeric'
            ]);
            $result = BranchServiceProfessional::with('branchService.service', 'professional')->find($data['id']);
            
            return response()->json(['branchServiceProfesional' => $result], 200, [], JSON_NUMERIC_CHECK);            
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => "Error al mostrar los servicios por trabajador"], 500);
        }
    }

    public function branch_service_professional($data)
    {
        try {             
            Log::info( "Entra a buscar los productos de un almacén");
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
                'id' => 'required|numeric'
            ]);
            BranchServiceProfessional::destroy($data['id']);

            return response()->json(['msg' => 'Servicio eliminado correctamente de este trabajador'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el serviciode este trabajador'], 500);
        }
    }
}
