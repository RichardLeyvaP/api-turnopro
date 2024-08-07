<?php

namespace App\Http\Controllers;

use App\Models\BranchService;
use App\Models\Service;
use App\Services\ServiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    public function index()
    {
        try {             
            Log::info( "Entra a buscar servicios");
            $now = Carbon::now();
            $services = Service::all();
            foreach ($services as $service) {
                // Agrega el dato adicional que necesitas al campo image_product
                $service->image_service = $service->image_service.'?$'.$now;
            }
            return response()->json(['services' => $services], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los servicios"], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info("Guardar Servicio");
        Log::info($request);
        try {
            $data = $request->validate([
                'name' => 'required|min:3',
                'simultaneou' => 'required|boolean',
                'price_service' => 'required|numeric',
                'type_service' => 'required',
                'profit_percentaje' => 'nullable|numeric',
                'duration_service' => 'required|numeric',
                'image_service' => 'nullable',
                'service_comment' => 'nullable|min:3'
            ]);            
            $service = new Service();
            $service->name = $data['name'];
            $service->simultaneou = $data['simultaneou'];
            $service->price_service = $data['price_service'];
            $service->type_service = $data['type_service'];
            $service->profit_percentaje = $data['profit_percentaje'];
            $service->duration_service = $data['duration_service'];
            $service->service_comment = $data['service_comment'];
            $service->save();

            $filename = "services/default.png";
            if ($request->hasFile('image_service')) {
                $filename = $request->file('image_service')->storeAs('services',$service->id.'.'.$request->file('image_service')->extension(),'public');
            }
            $service->image_service = $filename;
            $service->save();

            return response()->json(['msg' => 'Servicio insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error al insertar el servicio'], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $service = Service::find($data['id']);
            return response()->json(['service' => $service], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el servicio"], 500);
        }
    }

    public function branch_service_show(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $branchservices = BranchService::where('branch_id', $data['branch_id'])->get()->pluck('service_id');
            $services = Service::whereNotIn('id', $branchservices)->get();
            //$service = Service::find($data['id']);
            return response()->json(['services' => $services], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar el servicio"], 500);
        }
    }

    /*public function service_show($data)
    {
        try {
            return Service::find($data['id']);
        } catch (\Throwable $th) {
            return null;
        }
    }*/

    public function update(Request $request)
    {
        try{
        Log::info("Editar");
            Log::info($request);
            $data = $request->validate([
                'id' => 'required',
                'name' => 'required|min:3',
                'simultaneou' => 'required|boolean',
                'price_service' => 'required|numeric',
                'type_service' => 'required',
                'profit_percentaje' => 'nullable',
                'duration_service' => 'required|numeric',
                'image_service' => 'nullable',
                'service_comment' => 'nullable|min:3'
            ]);
            Log::info($request->profit_percentaje);
            //$filename = "services/default.png";
            $service = Service::find($data['id']);
            if ($request->hasFile('image_service')) {
                if($service->image_service != 'services/default.jpg'){
                $destination = public_path("storage\\" . $service->image_service);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
                }    
                $service->image_service = $request->file('image_service')->storeAs('services', $service->id . '.' . $request->file('image_service')->extension(), 'public');
            }
            /*if($request->hasFile('image_service')){
                Log::info('$request->hasFile(image_service)');
                Log::info($request->hasFile('image_service'));
                Log::info('$request->hasFile(image_service)');
                Log::info($request->hasFile('image_service'));
            if($service->image_service != $data['image_service'])
                {
                    $destination=public_path("storage\\".$service->image_service);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }                    
                    $service->image_service = $request->file('image_service')->storeAs('services',$service->id.'.'.$request->file('image_service')->extension(),'public');
                }
            }*/
                //else{
                   // $service->image_service = $filename;
                //}
                if($service->profit_percentaje){
                    $service->profit_percentaje = $request->profit_percentaje;
                }
            $service->name = $data['name'];
            $service->simultaneou = $data['simultaneou'];
            $service->price_service = $data['price_service'];
            $service->type_service = $data['type_service'];            
            $service->duration_service = $data['duration_service'];
            $service->service_comment = $data['service_comment'];
            $service->save();

            return response()->json(['msg' => 'Servicio actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => 'Error al actualizar el servicio'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $service = Service::find($data['id']);
            if ($service->image_service != "services/default.jpg") {
            $destination=public_path("storage\\".$service->image_service);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
            }
            $service->delete();

            return response()->json(['msg' => 'Servicio eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al eliminar el servicio'], 500);
        }
    }
}
