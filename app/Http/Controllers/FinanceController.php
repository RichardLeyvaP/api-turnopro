<?php

namespace App\Http\Controllers;

use App\Models\Finance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FinanceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return response()->json(['finances' => Finance::all()], 200);
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            Log::info( "Entra a buscar las finanzas de una branch");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $finances = Finance::where('branch_id', $data['branch_id'])->with(['expense', 'revenue'])->orderByDesc('control')->get()->map(function ($query){
                return [
                    'id' => $query->id,
                    'data' => $query->data,
                    'control' => $query->control,
                    'operation' => $query->operation,
                    'amount' => $query->amount,
                    'expense' => $query->expense ? $query->amount : '',
                    'revenue' => $query->revenue ? $query->amount : '',
                    'comment' => $query->comment,
                    'file' => $query->file,
                    'branch_id' => $query->branch_id,
                    'expense_id' => $query->expense_id,
                    'revenue_id' => $query->revenue_id,
                    'nameDetalle' => $query->expense ? $query->expense->name : $query->revenue->name,
                ];
            });
            return response()->json(['finances' => $finances], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Finance $finance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Finance $finance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Finance $finance)
    {
        //
    }
}
