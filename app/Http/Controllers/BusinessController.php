<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\BusinessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BusinessController extends Controller
{
    private BusinessService $businessService;

    public function __construct(BusinessService $businessService)
    {
        $this->businessService = $businessService;
    }

    public function index()
    {
        try {
            return response()->json(['business' => Business::join('professionals', 'businesses.professional_id', '=', 'professionals.id')
                ->select('businesses.id', 'businesses.name', 'businesses.address', 'professionals.name as professional_name')
                ->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los negocios"], 500);
        }
    }
    public function business_branch_academy()
    {
        try {
            $business = Business::with('branches', 'enrollments')->first();

            $branches = $business->branches;
            $enrollments = $business->enrollments;

            $resultArray = [];

            // Agregar las branches al resultado
            foreach ($branches as $branch) {
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

            // Agregar las enrollments al resultado
            foreach ($enrollments as $enrollment) {
                $resultArray[] = [
                    'id' => $enrollment->id,
                    'icon'=> "mdi-school",
                    'title' => $enrollment->name,
                    'subtitle' => 'Academia', 
                    'phone' => $enrollment->phone, 
                    'location' => $enrollment->address, 
                    'location_link' => $enrollment->location,
                    'phone_link' => "https://wa.me/".$enrollment->phone,
                    'image' => $enrollment->image_data,
                    'business_id' => $enrollment->id,
                    'type' => 'Academia'
                ];
            }
            return response()->json(['business' => $resultArray], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar el negocio"], 500);
        }
    }
    public function show(Request $request)
    {
        try {
            $business_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['business' => Business::with('professional')->find($business_data['id'])], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar el negocio"], 500);
        }
    }
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
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "La compañía no obtuvo ganancias en este dia"], 500);
        }
    }
    public function store(Request $request)
    {
        try {
            $business_data = $request->validate([
                'name' => 'required|max:50',
                'address' => 'required|max:50',
                'professional_id' => 'required|numeric',
            ]);

            $business = new Business();
            $business->name = $business_data['name'];
            $business->address = $business_data['address'];
            $business->professional_id = $business_data['professional_id'];
            $business->save();

            return response()->json(['msg' => 'Negocio insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al insertar la professionala'], 500);
        }
    }

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
            Log::error($th);
            return response()->json(['msg' => 'Error al actualizar el negocio'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $business_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Business::destroy($business_data['id']);

            return response()->json(['msg' => 'Negocio eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al eliminar el negocio'], 500);
        }
    }
}
