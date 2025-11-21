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

    /**
 * Obtiene la lista completa de servicios disponibles.
 *
 * @authenticated
 *
 * @response 200 {
 *   "services": [
 *     {
 *       "id": 1,
 *       "name": "Corte de cabello",
 *       "simultaneou": false,
 *       "price_service": 10000.00,
 *       "type_service": "barber",
 *       "profit_percentaje": 70.0,
 *       "duration_service": 30,
 *       "image_service": "services/1.jpg?$2025-11-21T10:30:00Z",
 *       "service_comment": "Corte clásico"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los servicios"}
 */
    public function index()
    {
        try {             
            $now = Carbon::now();
            $services = Service::all();
            foreach ($services as $service) {
                // Agrega el dato adicional que necesitas al campo image_product
                $service->image_service = $service->image_service.'?$'.$now;
            }
            return response()->json(['services' => $services], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            return response()->json(['msg' => "Error al mostrar los servicios"], 500);
        }
    }

    /**
 * Crea un nuevo servicio.
 *
 * @authenticated
 * @bodyParam name string required Nombre del servicio (mínimo 3 caracteres). Example: Corte de cabello
 * @bodyParam simultaneou boolean required ¿El servicio puede realizarse simultáneamente por varios profesionales? Example: false
 * @bodyParam price_service number required Precio del servicio. Example: 10000.00
 * @bodyParam type_service string required Tipo de servicio (ej. "barber", "tecnico"). Example: barber
 * @bodyParam profit_percentaje number optional Porcentaje de ganancia para el profesional. Example: 70.0
 * @bodyParam duration_service number required Duración en minutos. Example: 30
 * @bodyParam image_service file optional Imagen del servicio.
 * @bodyParam service_comment string optional Descripción del servicio (mínimo 3 caracteres). Example: Corte clásico
 *
 * @response 200 {"msg": "Servicio insertado correctamente"}
 * @response 500 {"msg": "Error al insertar el servicio"}
 */
    public function store(Request $request)
    {
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
        return response()->json(['msg' => $th->getMessage().'Error al insertar el servicio'], 500);
        }
    }

    /**
 * Obtiene los detalles de un servicio específico.
 *
 * @authenticated
 * @queryParam id integer required ID del servicio. Example: 1
 *
 * @response 200 {
 *   "service": {
 *     "id": 1,
 *     "name": "Corte de cabello",
 *     "simultaneou": false,
 *     "price_service": 10000.00,
 *     "type_service": "barber",
 *     "profit_percentaje": 70.0,
 *     "duration_service": 30,
 *     "image_service": "services/1.jpg",
 *     "service_comment": "Corte clásico"
 *   }
 * }
 * @response 500 {"msg": "Error al mostrar el servicio"}
 */
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

    /**
 * Obtiene los servicios **no asignados** a una sucursal (para poder asignarlos).
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "services": [
 *     {
 *       "id": 2,
 *       "name": "Afeitado",
 *       "price_service": 5000.00,
 *       "type_service": "barber",
 *       ...
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar el servicio"}
 */
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
            return response()->json(['msg' => "Error al mostrar el servicio"], 500);
        }
    }

    /**
 * Actualiza un servicio existente.
 *
 * @authenticated
 * @bodyParam id integer required ID del servicio. Example: 1
 * @bodyParam name string required Nuevo nombre (mínimo 3 caracteres). Example: Corte premium
 * @bodyParam simultaneou boolean required ¿Es simultáneo? Example: false
 * @bodyParam price_service number required Nuevo precio. Example: 12000.00
 * @bodyParam type_service string required Nuevo tipo. Example: barber
 * @bodyParam profit_percentaje number optional Nuevo porcentaje de ganancia. Example: 75.0
 * @bodyParam duration_service number required Nueva duración en minutos. Example: 35
 * @bodyParam image_service file optional Nueva imagen.
 * @bodyParam service_comment string optional Nueva descripción (mínimo 3 caracteres). Example: Corte premium con detalles
 *
 * @response 200 {"msg": "Servicio actualizado correctamente"}
 * @response 500 {"msg": "Error al actualizar el servicio"}
 */
    public function update(Request $request)
    {
        try{
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
        return response()->json(['msg' => 'Error al actualizar el servicio'], 500);
        }
    }

    /**
 * Elimina un servicio del sistema.
 *
 * ⚠️ No se puede eliminar si está en uso por alguna sucursal (depende de tu base de datos).
 *
 * @authenticated
 * @bodyParam id integer required ID del servicio. Example: 1
 *
 * @response 200 {"msg": "Servicio eliminado correctamente"}
 * @response 500 {"msg": "Error al eliminar el servicio"}
 */
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
            return response()->json(['msg' => 'Error al eliminar el servicio'], 500);
        }
    }
}
