<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BusinessController extends Controller
{
    public function index()
    {
        try {
            return response()->json(['business' => Business::with(['professional', 'branches'])->get()], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar los negocios"], 500);
        }
    }
    public function show(Request $request)
    {
        try {
            $business_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['business' => Business::with('professional')->find($business_data['id'])], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el negocio"], 500);
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
            return response()->json(['msg' => 'Error al eliminar el negocio'], 500);
        }
    }
}