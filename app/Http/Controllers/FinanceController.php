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

                $filename = $data['operation'] . '-' . $data['data'] . '.' . $request->file('file')->extension();
                $data['file'] = $request->file('file')->storeAs('finances', $filename, 'public');
            } else {
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
            return response()->json(['msg' => $th->getMessage() . 'Error interno del sistema'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            Log::info("Entra a buscar las finanzas de una branch");
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $finances = Finance::where('branch_id', $data['branch_id'])->with(['expense', 'revenue'])->orderByDesc('control')->get()->map(function ($query) {
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

            if ($request->hasFile('file')) {
                $destination = public_path("storage\\" . $finance->file);
                if (File::exists($destination)) {
                    File::delete($destination);
                }
                $filename = $finance->operation . '-' . $finance->data . '.' . $request->file('file')->extension();
                $finance->file = $request->file('file')->storeAs('finances', $filename, 'public');
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
            return response()->json(['msg' => $th->getMessage() . 'Error al insertar el producto'], 500);
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
            $destination = public_path("storage\\" . $finance->file);
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
            $currentYear = $data['year'];

            $monthsNames = [
                'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
            ];

            $finances = collect($monthsNames)->map(function ($monthName, $index) use ($currentYear, $data) {
                $financeData = Finance::whereYear('data', $currentYear)
                    ->where('branch_id', $data['branch_id'])
                    ->whereMonth('data', $index + 1)
                    ->selectRaw('COALESCE(SUM(CASE WHEN expense_id IS NOT NULL THEN amount ELSE 0 END), 0) as total_expenses,
                                 COALESCE(SUM(CASE WHEN revenue_id IS NOT NULL THEN amount ELSE 0 END), 0) as total_revenues')
                                 ->get();
            
                $totalExpenses = $financeData->sum('total_expenses');
                $totalRevenues = $financeData->sum('total_revenues');
                $difference = $totalRevenues - $totalExpenses;
            
                return (object)[
                    'month' => $monthName,
                    'total_expenses' => $totalExpenses,
                    'total_revenues' => $totalRevenues,
                    'difference' => $difference
                ];
            });
            
            // Calcular totales
            $totalExpenses = $finances->sum('total_expenses');
            $totalRevenues = $finances->sum('total_revenues');
            $totalDifference = $finances->sum('difference');
            
            // Agregar la fila de totales
            $finances->push((object)[
                'month' => 'Total',
                'total_expenses' => $totalExpenses,
                'total_revenues' => $totalRevenues,
                'difference' => $totalDifference
            ]);

            // Obtener la diferencia total del año anterior
            $lastYearDifference = Finance::whereYear('data', $currentYear - 1)
                ->selectRaw('COALESCE(SUM(CASE WHEN expense_id IS NOT NULL THEN -amount ELSE amount END), 0) as total_difference')
                ->first()->total_difference ?? 0;



            //$finances;
            $result = [
                'finances' => $finances,
                'last_year_difference' => $lastYearDifference
            ];
            // Obtener todas las sucursales con las sumas de ingresos y gastos por mes
            /*return $branches = Finance::whereYear('data', $currentYear)
                ->selectRaw('MONTH(data) as month, 
                     SUM(CASE WHEN expense_id IS NOT NULL THEN amount ELSE 0 END) as total_expenses,
                     SUM(CASE WHEN revenue_id IS NOT NULL THEN amount ELSE 0 END) as total_revenues')
                ->groupBy('month')
                ->get();*/

            return response()->json($result, 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al insertar el producto'], 500);
        }
    }

    public function revenue_expense_details(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'year' => 'nullable'
            ]);
            //$currentYear = $data['year'];
            $ingresos = Finance::whereYear('data', $data['year'])->where('operation', 'Ingreso')
                    ->get()->map(function ($query){
                        return [
                            'data' => $query->data,
                            'operation' => $query->operation,
                            'ingreso' => $query->amount,
                            'gasto' => '',
                            'detailOperation' => $query->revenue->name,
                        ];
                    })->sortByDesc('data')->values();

                    $totalIngresos = $ingresos->sum('ingreso');

                    $ingresos->push((object)[
                        'data' => '',
                        'operation' => 'Total',
                        'ingreso' => $totalIngresos,
                        'gasto' => '',
                        'detailOperation' => '',
                    ]);

                    $gastos = Finance::whereYear('data', $data['year'])->where('operation', 'Gasto')
                    ->get()->map(function ($query){
                        return [
                            'data' => $query->data,
                            'operation' => $query->operation,
                            'ingreso' => '',
                            'gasto' => $query->amount,
                            'detailOperation' => $query->expense->name,
                        ];
                    })->sortByDesc('data')->values();

                    $totalGastos = $gastos->sum('gasto');

                    $gastos->push((object)[
                        'data' => '',
                        'operation' => 'Total',
                        'ingreso' => '',
                        'gasto' => $totalGastos,
                        'detailOperation' => '',
                    ]);

                    $resultado = $ingresos->concat($gastos);

                    // Devolvemos el resultado
                    return response()->json(['finances'=> $resultado], 200);
            
            //return response()->json($result, 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al insertar el producto'], 500);
        }
    }

}
