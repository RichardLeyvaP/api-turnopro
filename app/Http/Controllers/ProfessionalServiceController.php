<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchServiceProfessional;
use App\Models\Professional;
use App\Models\ProfessionalService;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfessionalServiceController extends Controller
{
   public function index()
    {
        try {
            $professionalservices = ProfessionalService::with('branchService.service', 'professional')->get();
            /*$result= $professionalservices->map(function ($professionalservicesdata){
                return[
                    'id' => $professionalservicesdata->id,
                    'name' => $professionalservicesdata->branchService->service->name,
                    'simultaneou' => $professionalservicesdata->branchService->service->simultaneou,
                    'price_service' => $professionalservicesdata->branchService->service->price_service,
                    'type_service' => $professionalservicesdata->branchService->service->type_service,
                    'profit_percentaje' => $professionalservicesdata->branchService->service->profit_percentaje,
                    'duration_service' => $professionalservicesdata->branchService->service->duration_service,
                    'image_service' => $professionalservicesdata->branchService->service->image_service,
                    'service_comment' => $professionalservicesdata->branchService->service->service_comment,
                    'nameProfessional' => $professionalservicesdata->professional->name .' '. $professionalservicesdata->professional->surname .' '. $professionalservicesdata->professional->second_surname
                ];
            });*/
            return response()->json(['branchServiceProfesional' => $professionalservices], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los servicios por trabajador"], 500);
        }
    }
    public function professional_services(Request $request)
    {
        try {
            $data = $request->validate([
               'professional_id' => 'required|numeric'
           ]);
           $BSProfessional = BranchServiceProfessional::with('branchService.service')->where('professional_id', $data['professional_id'])->get();
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
           });
           
           return response()->json(['professional_services' => $serviceModels], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => "Error al mostrar la categoría de producto"], 500);
       }
    }
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_service_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);

            
            $psersonservice = new ProfessionalService();
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
            $result = ProfessionalService::with('branchService.service', 'professional')->find($data['id']);
            
            return response()->json(['branchServiceProfesional' => $result], 200);            
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => "Error al mostrar los servicios por trabajador"], 500);
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

            
            $psersonservice = ProfessionalService::find($data['id']);
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
            ProfessionalService::destroy($data['id']);

            return response()->json(['msg' => 'Servicio eliminado correctamente de este trabajador'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el serviciode este trabajador'], 500);
        }
    }
}
