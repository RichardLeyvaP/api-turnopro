<?php

namespace App\Http\Controllers;

use App\Models\PersonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PersonServiceController extends Controller
{
   public function index()
    {
        try {
            $result = PersonService::join('people', 'people.id', '=','branch_service_person.person_id')->join('branch_service', 'branch_service.id', '=', 'branch_service_person.branch_service_id')->join('services', 'services.id', '=', 'branch_service.service_id')->get();
            return response()->json(['persons' => $result], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los servicios por trabajador"], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_service_id' => 'required|numeric',
                'person_id' => 'required|numeric'
            ]);

            
            $psersonservice = new PersonService();
            $psersonservice->branch_service_id = $data['branch_service_id'];
            $psersonservice->person_id = $data['person_id'];
            $psersonservice->save();

            return response()->json(['msg' => 'Servicio asignado correctamente a este trabajador'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al asignar el servicio a este empleado'], 500);
        }
    }

    public function show(Request $request)
    {
        try {             
            Log::info( "Entra a buscar los productos de un almacén");
            $data = $request->validate([
                'branch_service_id' => 'nullable|numeric',
                'person_id' => 'nullable|numeric'
            ]);
            if ($data['branch_service_id'] && $data['person_id'] == null) {
                $result = PersonService::join('people', 'people.id', '=','branch_service_person.person_id')->join('branch_service', 'branch_service.id', '=', 'branch_service_person.branch_service_id')->join('services', 'services.id', '=', 'branch_service.service_id')->where('branch_service_person.branch_service_id', $data['branch_service_id'])->get();
            return response()->json(['persons' => $result], 200);
            }
            if ($data['person_id'] && $data['branch_service_id'] == null) {
                $result = PersonService::join('people', 'people.id', '=','branch_service_person.person_id')->join('branch_service', 'branch_service.id', '=', 'branch_service_person.branch_service_id')->join('services', 'services.id', '=', 'branch_service.service_id')->where('people.id', $data['person_id'])->get();
                return response()->json(['persons' => $result], 200);
            } else {
                $result = PersonService::join('people', 'people.id', '=','branch_service_person.person_id')->join('branch_service', 'branch_service.id', '=', 'branch_service_person.branch_service_id')->join('services', 'services.id', '=', 'branch_service.service_id')->where('people.id', $data['person_id'])->where('branch_service_person.branch_service_id', $data['branch_service_id'])->get();
                return response()->json(['persons' => $result], 200);
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
                'person_id' => 'required|numeric'
            ]);

            
            $psersonservice = PersonService::find($data['id']);
            $psersonservice->branch_service_id = $data['branch_service_id'];
            $psersonservice->person_id = $data['person_id'];
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
            PersonService::destroy($data['id']);

            return response()->json(['msg' => 'Servicio eliminado correctamente de este trabajador'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el serviciode este trabajador'], 500);
        }
    }
}
