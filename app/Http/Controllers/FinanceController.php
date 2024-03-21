<?php

namespace App\Http\Controllers;

use App\Models\Finance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
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
        Log::info("Guardar Producto");
        Log::info($request);
        try {
            $data = $request->validate([
                'control' => 'required|numeric',
                'operation' => 'required|string',
                'amount' => 'required|numeric',
                'comment' => 'nullable|string',
                'branch_id' => 'required|numeric',
                'expense_id' => 'nullable',
                'revenue_id' => 'nullable'
            ]);
            $data['data'] = Carbon::now()->format('Y-m-d');
            Log::info($request->file('file'));
            if ($request->hasFile('file')) {
                
            $filename = $data['operation'].'-'.$data['data'].'.'.$request->file('file')->extension();
                $data['file'] = $request->file('file')->storeAs('finances',$filename,'public');
            }
            else{
                $data['file'] = '';
            }
            Log::info($data);
            $finance = new Finance();
            
            $finance->control = $data['control'];
            $finance->operation = $data['operation'];
            $finance->amount = $data['amount'];
            $finance->comment = $data['comment'];
            $finance->branch_id = $data['branch_id'];
            $finance->expense_id = $data['expense_id'];
            $finance->revenue_id = $data['revenue_id'];
            $finance->data = $data['data'];
            $finance->file = $data['file'];
            $finance->save();
            return response()->json(['msg' => 'Operacion insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
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
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'control' => 'required|numeric',
                'operation' => 'required|string',
                'amount' => 'required|numeric',
                'comment' => 'nullable|string',
                'branch_id' => 'required|numeric',
                'expense_id' => 'nullable',
                'revenue_id' => 'nullable'
            ]);
            Log::info($data);
            $finance = Finance::find($data['id']);
            
            if($request->hasFile('file'))
                {
                    $destination=public_path("storage\\".$finance->file);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }                 
                    $filename = $finance->operation.'-'.$finance->data.'.'.$request->file('file')->extension();   
                    $finance->file = $request->file('file')->storeAs('finances',$filename,'public');
                }
           $finance->control = $data['control'];
            $finance->operation = $data['operation'];
            $finance->amount = $data['amount'];
            $finance->comment = $data['comment'];
            $finance->expense_id = $data['expense_id'];
            $finance->revenue_id = $data['revenue_id'];
            $finance->save();
            return response()->json(['msg' => 'Operación editada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error al insertar el producto'], 500);
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
            $finance = Finance::find($data['id']);
            $destination=public_path("storage\\".$finance->file);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
        
            $finance->delete();

            return response()->json(['msg' => 'producto eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar el producto'], 500);
        }
    }

    public function revenue_expense_analysis(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'year' => 'nullable'
            ]);
            Log::info($data);
            $finance = Finance::find($data['id']);
            
            if($request->hasFile('file'))
                {
                    $destination=public_path("storage\\".$finance->file);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }                 
                    $filename = $finance->operation.'-'.$finance->data.'.'.$request->file('file')->extension();   
                    $finance->file = $request->file('file')->storeAs('finances',$filename,'public');
                }
           $finance->control = $data['control'];
            $finance->operation = $data['operation'];
            $finance->amount = $data['amount'];
            $finance->comment = $data['comment'];
            $finance->expense_id = $data['expense_id'];
            $finance->revenue_id = $data['revenue_id'];
            $finance->save();
            return response()->json(['msg' => 'Operación editada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' => $th->getMessage().'Error al insertar el producto'], 500);
        }
    }
}
