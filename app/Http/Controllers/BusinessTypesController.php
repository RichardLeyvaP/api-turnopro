<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessTypes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BusinessTypesController extends Controller
{
    public function index()
    {
        try {
            return response()->json(['businessTypes' => BusinessTypes::all()], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los tipos de negocios"], 500);
        }
    }
    public function show(Request $request)
    {
        try {
            $business_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['businessTypes' => BusinessTypes::all()->find($business_data['id'])], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar el negocio"], 500);
        }
    }
    public function store(Request $request)
    {
        try {
            $business_type_data = $request->validate([
                'name' => 'required|unique:business_types',

            ]);

            $businessTypes = new BusinessTypes();
            $businessTypes->name = $business_type_data['name'];
            $businessTypes->save();

            return response()->json(['msg' => 'Tipo de negocio insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al insertar el tipo de negocio'], 500);
        }
    }

    public function update(Request $request)
    {
        try {

            $business_type_data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|unique:business_types'
            ]);
            $businessType = BusinessTypes::find($business_type_data['id']);
            $businessType->name = $business_type_data['name'];
            $businessType->save();

            return response()->json(['msg' => 'Tipo de negocio actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al actualizar el tipo de negocio'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {

            $business_type_data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Log::info($business_type_data['id']);
            BusinessTypes::destroy($business_type_data['id']);

            return response()->json(['msg' => 'Tipo de negocio eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);

            return response()->json(['msg' => 'Error al eliminar el tipo negocio'], 500);
        }
    }
}