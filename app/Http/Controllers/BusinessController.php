<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Professional;
use App\Services\BusinessService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BusinessController extends Controller
{
    private BusinessService $businessService;

    public function __construct(BusinessService $businessService)
    {
        $this->businessService = $businessService;
    }

    /**
 * Obtiene la lista de todos los negocios con sus administradores.
 *
 * @authenticated
 *
 * @response 200 {
 *   "business": [
 *     {
 *       "id": 1,
 *       "name": "Barbería Central",
 *       "professional": { ... }
 *     }
 *   ],
 *   "professionals": [
 *     {
 *       "id": 123,
 *       "name": "Yasmany",
 *       "image_url": "professionals/123.jpg",
 *       "charge": "Administrador"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar los negocios"}
 */
    public function index()
    {
        try {
            $professionals = Professional::with('user', 'charge')->whereHas('charge', function ($query){
                $query->where('name', 'Administrador');
            })->get()->map(function ($professional) {
                return [
                    'id' => $professional->id,
                    'name' => $professional->name,
                    'image_url' => $professional->image_url,
                    'charge' => $professional->charge->name

                ];
            });
            return response()->json(['business' => Business::with('professional')->get(), 'professionals' => $professionals], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los negocios"], 500);
        }
    }

    /**
 * Obtiene la lista de sucursales del negocio principal (excluye la academia con ID 20).
 *
 * Diseñado para landing o app móvil.
 *
 * @response 200 {
 *   "business": [
 *     {
 *       "id": 5,
 *       "icon": "mdi-store",
 *       "title": "Centro",
 *       "subtitle": "Sucursal",
 *       "phone": "+5359380373",
 *       "location": "Calle Principal 123",
 *       "location_link": "-33.456789,-70.645678",
 *       "phone_link": "https://wa.me/+5359380373",
 *       "image": "branches/5.jpg",
 *       "business_id": 1,
 *       "type": "Branch"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar el negocio"}
 */
    public function business_branch_academy()
    {
        try {
            $business = Business::with('branches')->first();

            $branches = $business->branches;
            $enrollments = $business->enrollments;

            $resultArray = [];

            // Agregar las branches al resultado
            foreach ($branches as $branch) {
                if ($branch->id !=20) {                   
                $resultArray[] = [
                    'id' => $branch->id,
                    'icon'=> "mdi-store",
                    'title' => $branch->name,
                    'subtitle' => 'Sucursal', 
                    'phone' => $branch->phone, 
                    'location' => $branch->address, 
                    'location_link' => $branch->location,
                    'phone_link' => "https://wa.me/".$branch->phone,
                    'image' => $branch->image_data,
                    'business_id' => $branch->business_id,
                    'type' => 'Branch'
                ];
                }
            }
            return response()->json(['business' => $resultArray], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el negocio"], 500);
        }
    }

    /**
 * Obtiene los detalles de un negocio específico.
 *
 * @authenticated
 * @queryParam id integer required ID del negocio. Example: 1
 *
 * @response 200 {
 *   "business": {
 *     "id": 1,
 *     "name": "Barbería Central",
 *     "professional": { ... }
 *   }
 * }
 * @response 500 {"msg": "Error al mostrar el negocio"}
 */
    public function show(Request $request)
    {
        try {
            $business_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['business' => Business::with('professional')->find($business_data['id'])], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el negocio"], 500);
        }
    }

    /**
 * Obtiene las ganancias totales del negocio (todas las sucursales) por período.
 *
 * Soporta tres modos:
 * - Sin parámetros: datos del día actual.
 * - Con `mes` y `year`: datos del mes/año.
 * - Con `startDate` y `endDate`: rango de fechas.
 *
 * @authenticated
 * @queryParam mes integer optional Mes (1-12). Example: 11
 * @queryParam year integer optional Año. Example: 2025
 * @queryParam startDate string optional Fecha de inicio (Y-m-d). Example: 2025-11-01
 * @queryParam endDate string optional Fecha de fin (Y-m-d). Example: 2025-11-30
 *
 * @response 200 {
 *   "total": 2500.00,
 *   "services": [...],
 *   "products": [...]
 * }
 * @response 500 {"msg": "La compañía no obtuvo ganancias en este dia"}
 */
    public function business_winner(Request $request)
    {
        try {
            if ($request->has('mes')) {
                return response()->json($this->businessService->business_winner_month($request->mes, $request->year), 200, [], JSON_NUMERIC_CHECK);
            }
            if ($request->has('startDate') && $request->has('endDate')) {
                return response()->json($this->businessService->business_winner_periodo($request->startDate, $request->endDate), 200, [], JSON_NUMERIC_CHECK);
            } else {
                return response()->json($this->businessService->business_winner_date(), 200, [], JSON_NUMERIC_CHECK);
            }
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "La compañía no obtuvo ganancias en este dia"], 500);
        }
    }

    /**
 * Registra un nuevo negocio.
 *
 * @authenticated
 * @bodyParam name string required Nombre del negocio. Example: Barbería Central
 * @bodyParam address string required Dirección. Example: Calle Principal 123
 * @bodyParam professional_id integer required ID del administrador (profesional con cargo "Administrador"). Example: 123
 * @bodyParam api_url string optional URL de la API personalizada. Example: https://api.miempresa.com
 * @bodyParam start_date string optional Fecha de inicio (Y-m-d). Example: 2025-01-01
 * @bodyParam end_date string optional Fecha de fin (Y-m-d). Example: 2026-01-01
 * @bodyParam image_url file optional Logo del negocio.
 *
 * @response 200 {"msg": "Negocio insertado correctamente"}
 * @response 500 {"msg": "Error al insertar El negocio"}
 */
    public function store(Request $request)
    {
        try {
            $business_data = $request->validate([
                'name' => 'required|max:50',
                'address' => 'required|max:50',
                'professional_id' => 'required|numeric',
                'api_url' => 'nullable',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
            ]);
            $codigo = 0;
            do {
                // Genera un código alfanumérico aleatorio
                $codigo = Str::random(7);
        
                // Verifica si el código ya existe en la base de datos
            } while (Business::where('code', $codigo)->exists());
            $business = new Business();
            $business->name = $business_data['name'];
            $business->address = $business_data['address'];
            $business->professional_id = $business_data['professional_id'];
            $business->start_date = $business_data['start_date'];
            $business->end_date = $business_data['end_date'];
            $business->api_url = $business_data['api_url'];
            $business->code = $codigo;
            $business->save();
            $filename = "business/default.jpg"; 
            if ($request->hasFile('image_url')) {
               $filename = $request->file('image_url')->storeAs('business',$business->id.'.'.$request->file('image_url')->extension(),'public');
            }
            $business->image_url = $filename;
            $business->save();
            return response()->json(['msg' => 'Negocio insertado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al insertar El negocio'], 500);
        }
    }

    /**
 * Actualiza los datos básicos de un negocio (sin imagen ni fechas).
 *
 * @authenticated
 * @bodyParam id integer required ID del negocio. Example: 1
 * @bodyParam name string required Nuevo nombre. Example: Barbería Premium
 * @bodyParam address string required Nueva dirección. Example: Avenida Siempre Viva 742
 * @bodyParam professional_id integer required Nuevo administrador. Example: 124
 *
 * @response 200 {"msg": "Negocio actualizado correctamente"}
 * @response 500 {"msg": "Error al actualizar el negocio"}
 */
    public function update(Request $request)
    {
        try {

            $business_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'address' => 'required|max:50',
                'professional_id' => 'required|numeric',
            ]);

            $business = Business::find($business_data['id']);
            $business->name = $business_data['name'];
            $business->address = $business_data['address'];
            $business->professional_id = $business_data['professional_id'];
            $business->save();

            return response()->json(['msg' => 'Negocio actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar el negocio'], 500);
        }
    }

    /**
 * Actualiza un negocio con soporte completo (incluye imagen, fechas y API).
 *
 * Si no tiene código, se genera uno alfanumérico único.
 *
 * @authenticated
 * @bodyParam id integer required ID del negocio. Example: 1
 * @bodyParam name string required Nombre. Example: Barbería Premium
 * @bodyParam address string required Dirección. Example: Avenida Siempre Viva 742
 * @bodyParam professional_id integer required Administrador. Example: 124
 * @bodyParam api_url string optional URL de API. Example: https://api.miempresa.com
 * @bodyParam start_date string optional Fecha de inicio (Y-m-d). Example: 2025-01-01
 * @bodyParam end_date string optional Fecha de fin (Y-m-d). Example: 2026-01-01
 * @bodyParam image_url file optional Nuevo logo.
 *
 * @response 200 {"msg": "Negocio actualizado correctamente"}
 * @response 500 {"msg": "Error al actualizar el negocio"}
 */
    public function update_post(Request $request)
    {
        try {

            $business_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'address' => 'required|max:50',
                'professional_id' => 'required|numeric',
                'api_url' => 'nullable',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
            ]);
            $codigo = 0;
            $business = Business::find($business_data['id']);
            if ($request->hasFile('image_url')) {
                if($business->image_url != 'business/default.jpg'){
                $destination = public_path("storage\\" . $business->image_url);
                if (File::exists($destination)) {
                    File::delete($destination);
                }              
                    $business->image_url = $request->file('image_url')->storeAs('business',$business->id.'.'.$request->file('image_url')->extension(),'public');
                }
            }
            if ($business->code == NULL) {
                do {
                    // Genera un código alfanumérico aleatorio
                    $codigo = Str::random(7);
            
                    // Verifica si el código ya existe en la base de datos
                } while (Business::where('code', $codigo)->exists());
               $business->code = $codigo;
            }
            $business->name = $business_data['name'];
            $business->address = $business_data['address'];
            $business->professional_id = $business_data['professional_id'];
            $business->start_date = $business_data['start_date'];
            $business->end_date = $business_data['end_date'];
            $business->api_url = $business_data['api_url'];
            $business->save();

            return response()->json(['msg' => 'Negocio actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al actualizar el negocio'], 500);
        }
    }

    /**
 * Elimina un negocio del sistema.
 *
 * También elimina su imagen si no es la predeterminada.
 *
 * @authenticated
 * @bodyParam id integer required ID del negocio. Example: 1
 *
 * @response 200 {"msg": "Negocio eliminado correctamente"}
 * @response 500 {"msg": "Error al eliminar el negocio"}
 */
    public function destroy(Request $request)
    {
        try {
            $business_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $business = Business::find($business_data['id']);
            if ($business->image_url != "business/default.jpg") {
                $destination=public_path("storage\\".$business->image_url);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }
                }
            Business::destroy($business_data['id']);

            return response()->json(['msg' => 'Negocio eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el negocio'], 500);
        }
    }
}
