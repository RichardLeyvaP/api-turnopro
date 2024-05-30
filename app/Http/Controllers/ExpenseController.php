<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return response()->json(['expenses' => Expense::all()], 200);
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
                'name' => 'required',

            ]);

            $expense = new Expense();
            $expense->name = $data['name'];
            $expense->save();

            return response()->json(['msg' => 'Operación de Gasto creado correctamente'], 200);
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
            return response()->json(['businessTypes' => Expense::find($data['id'])], 200);
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
                'name' => 'required'
            ]);
            $expense = Expense::find($data['id']);
            $expense->name = $data['name'];
            $expense->save();

            return response()->json(['msg' => 'Operación de Gasto actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
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
            Expense::destroy($data['id']);

            return response()->json(['msg' => 'Operación de Gasto eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);

            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }
}
