<?php

namespace App\Http\Controllers;

use App\Models\Revenue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RevenueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return response()->json(['revenues' => Revenue::all()], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|unique:expenses',

            ]);

            $revenue = new Revenue();
            $revenue->name = $data['name'];
            $revenue->save();

            return response()->json(['msg' => 'Operación de Ingreso creado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['revenues' => Revenue::find($data['id'])], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {

            $data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|unique:expenses'
            ]);
            $revenue = Revenue::find($data['id']);
            $revenue->name = $data['name'];
            $revenue->save();

            return response()->json(['msg' => 'Operación de Ingreso actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {

            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Log::info($data['id']);
            Revenue::destroy($data['id']);

            return response()->json(['msg' => 'Operación de Ingreso eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);

            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }
}
