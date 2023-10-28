<?php

namespace App\Http\Controllers;

use App\Models\Branch;
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
            $professionalservices = ProfessionalService::with('branchService', 'professional')->get();
            $result= $professionalservices->map(function ($professionalservicesdata){
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
            });
            /*$result = ProfessionalService::join('professionals', 'professionals.id', '=','branch_service_professional.professional_id')->join('branch_service', 'branch_service.id', '=', 'branch_service_professional.branch_service_id')->join('services', 'services.id', '=', 'branch_service.service_id')->get();*/
            return response()->json(['profesionales' => $result], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los servicios por trabajador"], 500);
        }
    }
    public function person_services(Request $request)
    {
        try {
            $data = $request->validate([
               'professional_id' => 'required|numeric'
           ]);
           $person = Professional::find($data['professional_id']);
           
           $services = $person->branchServices->pluck('service_id');
           $serviceModels = Service::find($services);

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
                'branch_service_id' => 'nullable|numeric',
                'professional_id' => 'nullable|numeric'
            ]);
            if ($data['branch_service_id'] && $data['professional_id'] == null) {
                $result = ProfessionalService::join('professional', 'professional.id', '=','branch_service_professional.professional_id')->join('branch_service', 'branch_service.id', '=', 'branch_service_professional.branch_service_id')->join('services', 'services.id', '=', 'branch_service.service_id')->where('branch_service_professional.branch_service_id', $data['branch_service_id'])->get();
            return response()->json(['profesionales' => $result], 200);
            }
            if ($data['professional_id'] && $data['branch_service_id'] == null) {
                $result = ProfessionalService::join('professional', 'professional.id', '=','branch_service_professional.professional_id')->join('branch_service', 'branch_service.id', '=', 'branch_service_professional.branch_service_id')->join('services', 'services.id', '=', 'branch_service.service_id')->where('rofessional.id', $data['professional_id'])->get();
                return response()->json(['profesionales' => $result], 200);
            } else {
                $result = ProfessionalService::join('professional', 'professional.id', '=','branch_service_professional.professional_id')->join('branch_service', 'branch_service.id', '=', 'branch_service_professional.branch_service_id')->join('services', 'services.id', '=', 'branch_service.service_id')->where('professional.id', $data['professional_id'])->where('branch_service_professional.branch_service_id', $data['branch_service_id'])->get();
                return response()->json(['profesionales' => $result], 200);
            }
            
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