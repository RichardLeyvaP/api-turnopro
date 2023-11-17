<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BranchServiceController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Entra a buscar los servicios por sucursales");
            return response()->json(['branch' => Branch::with('branchservices')->get()], 200);
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
                'service_id' => 'required|numeric'
            ]);
            $branch = Branch::find($data['branch_id']);
            $service = Service::find($data['service_id']);

            $branch->branchservices()->attach($service->id);

            return response()->json(['msg' => 'Servicio asignado correctamente a la sucursal'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' =>'Error al asignar el servicio a esta sucursal'], 500);
        }
    }

    public function show(Request $request)
    {
        try {             
            Log::info( "Entra a buscar los servicio q brinda una sucursal o la sucursales donde se brinda determinado servicio");
            $data = $request->validate([
                'service_id' => 'nullable|numeric',
                'branch_id' => 'nullable|numeric'
            ]);
            if ($data['service_id']) {
                return response()->json(['service' => Service::with('branchServices')->find($data['service_id'])], 200);
            }
            if ($data['branch_id']) {
                return response()->json(['branch' => Branch::with('branchservices')->find($data['branch_id'])],200); 
            }
            
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error al mostrar los servicios"], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'service_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $service = Service::find($data['service_id']);
            $branch = Branch::find($data['branch_id']);
            $branch->branchservices()->sync($service->id);
            return response()->json(['msg' => 'Servicio actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar el servicio en esta sucursal'], 500);
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
            $branch->branchservices()->detach($service->id);
            return response()->json(['msg' => 'Servicio eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el servicio en esta sucursal'], 500);
        }
    }
}
