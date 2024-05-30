<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Enrollment;
use App\Models\Expense;
use App\Models\Finance;
use App\Models\Revenue;
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
                'branch_id' => 'nullable',
                'business_id' => 'nullable',
                'type' => 'required|string',
                'enrollment_id' => 'nullable',
                'expense_id' => 'nullable',
                'revenue_id' => 'nullable',

            ]);
            Log::info($data);
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
            $finance->business_id = $data['business_id'];
            $finance->enrollment_id = $data['enrollment_id'];
            $finance->type = $data['type'];
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
                'branch_id' => 'nullable|numeric',
                'business_id' => 'nullable',
                'type' => 'required|string',
                'enrollment_id' => 'nullable'
            ]);
            //str_contains($expenseName, $phrase)
            if($data['type'] == 'Negocio'){
                $finances = Finance::where('business_id', $data['business_id'])->where('type', $data['type'])->with(['expense', 'revenue'])->orderByDesc('id')->get()->map(function ($query) {
                    $typeDetail = '';
                    if($query->revenue){
                        if($query->revenue == 'Ingreso venta de productos en la caja'){
                            $typeDetail = 'Ingreso Producto';
                        }
                        if($query->revenue == 'Ingresos por porciento de propinas'){
                            $typeDetail = 'Ingreso Propina';
                        }
                    }
                    if(str_contains($query->comment, 'Gasto por pago de bono de convivencias')){
                        $typeDetail = 'Gasto Servicio';
                    }
                    if(str_contains($query->comment, 'Gasto por pago de bono de servicios')){
                        $typeDetail = 'Gasto Servicio';
                    }
                    if(str_contains($query->comment, 'Gasto por pago de bono de productos')){
                        $typeDetail = 'Gasto Producto';
                    }
                    if($query->expense){
                        if($query->expense->name == 'Compra de productos'){
                            $typeDetail = 'Gasto Producto';
                        }
                        if($query->expense->name == 'Productos'){
                            $typeDetail = 'Gasto Producto';
                        }
                    }
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
                        'business_id' => $query->business_id,
                        'enrollment_id' => $query->enrollment_id,
                        'expense_id' => $query->expense_id,
                        'revenue_id' => $query->revenue_id,
                        'type' => $query->type,
                        'nameDetalle' => $query->expense ? $query->expense->name : $query->revenue->name,
                        'typeDetail' => $typeDetail,
                    ];
                });
            }
            if ($data['type'] == 'Sucursal'){
                $finances = Finance::where('branch_id', $data['branch_id'])->where('type', $data['type'])->with(['expense', 'revenue'])->orderByDesc('id')->get()->map(function ($query) {
                    $typeDetail = '';
                    if($query->revenue){
                        if($query->revenue->name == 'Ingreso venta de productos en la caja'){
                            $typeDetail = 'Ingreso Producto';
                        }
                        if($query->revenue->name == 'Ingresos por porciento de propinas'){
                            $typeDetail = 'Ingreso Propina';
                        }
                    }
                    if(str_contains($query->comment, 'Gasto por pago de bono de convivencias')){
                        $typeDetail = 'Gasto Servicio';
                    }
                    if(str_contains($query->comment, 'Gasto por pago de bono de servicios')){
                        $typeDetail = 'Gasto Servicio';
                    }
                    if(str_contains($query->comment, 'Gasto por pago de bono de productos')){
                        $typeDetail = 'Gasto Producto';
                    }
                    if($query->expense){
                        if($query->expense->name == 'Compra de productos'){
                            $typeDetail = 'Gasto Producto';
                        }
                        if($query->expense->name == 'Productos'){
                            $typeDetail = 'Gasto Producto';
                        }
                    }
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
                        'business_id' => $query->business_id,
                        'enrollment_id' => $query->enrollment_id,
                        'expense_id' => $query->expense_id,
                        'revenue_id' => $query->revenue_id,
                        'type' => $query->type,
                        'nameDetalle' => $query->expense ? $query->expense->name : $query->revenue->name,
                        'typeDetail' => $typeDetail
                    ];
                });
            }
            if ($data['type'] == 'Academia'){
                $finances = Finance::where('enrollment_id', $data['enrollment_id'])->where('type', $data['type'])->with(['expense', 'revenue'])->orderByDesc('id')->get()->map(function ($query) {
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
                        'business_id' => $query->business_id,
                        'enrollment_id' => $query->enrollment_id,
                        'expense_id' => $query->expense_id,
                        'revenue_id' => $query->revenue_id,
                        'type' => $query->type,
                        'nameDetalle' => $query->expense ? $query->expense->name : $query->revenue->name,
                        'typeDetail' => '',
                    ];
                });
            }
            if ($data['type'] == 'Todas'){
                $finances = Finance::with(['expense', 'revenue'])->orderByDesc('id')->get()->map(function ($query) {
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
                        'business_id' => $query->business_id,
                        'enrollment_id' => $query->enrollment_id,
                        'expense_id' => $query->expense_id,
                        'revenue_id' => $query->revenue_id,
                        'type' => $query->type,
                        'nameDetalle' => $query->expense ? $query->expense->name : $query->revenue->name,
                        'typeDetail' => ''
                    ];
                })->sortByDesc('data')->values();
            }
            
            return response()->json(['finances' => $finances], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()."Error interno del sistema"], 500);
        }
    }

    public function combinedData(Request $request)
{
    try {
        $businesses = Business::with(['professional', 'branches'])->get();
        $expenses = Expense::all();
        $revenues = Revenue::all();

        $data = $request->validate([
            'business_id' => 'required|numeric'
        ]);
        $branches = Branch::where('business_id', $data['business_id'])->get();

        $enrollments = Enrollment::where('business_id', $data['business_id'])->with(['business'])->get();

        $responseData = [
            'businesses' => $businesses,
            'expenses' => $expenses,
            'revenues' => $revenues,
            'branches' => $branches,
            'enrollments' => $enrollments
        ];

        return response()->json($responseData, 200, [], JSON_NUMERIC_CHECK);
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
                'branch_id' => 'nullable',
                'business_id' => 'nullable',
                'type' => 'required|string',
                'enrollment_id' => 'nullable',
                'expense_id' => 'nullable',
                'revenue_id' => 'nullable',
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
            if($request->mounth){
                $ingresos = Finance::where('branch_id', $data['branch_id'])->whereYear('data', $data['year'])->whereMonth('data', $request->mounth)->where('operation', 'Ingreso')
                ->get()->map(function ($query) {
                    return [
                        'data' => $query->data,
                        'operation' => $query->operation,
                        'ingreso' => $query->amount,
                        'gasto' => '',
                        'detailOperation' => $query->revenue->name,
                    ];
                })->sortByDesc('data')->values();

            $totalIngresos = $ingresos->sum('ingreso');
                if($totalIngresos){
            $ingresos->push((object)[
                'data' => '',
                'operation' => 'Total',
                'ingreso' => $totalIngresos,
                'gasto' => '',
                'detailOperation' => '',
            ]);}

            $gastos = Finance::where('branch_id', $data['branch_id'])->whereYear('data', $data['year'])->whereMonth('data', $request->mounth)->where('operation', 'Gasto')
                ->get()->map(function ($query) {
                    return [
                        'data' => $query->data,
                        'operation' => $query->operation,
                        'ingreso' => '',
                        'gasto' => $query->amount,
                        'detailOperation' => $query->expense->name,
                    ];
                })->sortByDesc('data')->values();

            $totalGastos = $gastos->sum('gasto');
                if($totalGastos){
            $gastos->push((object)[
                'data' => '',
                'operation' => 'Total',
                'ingreso' => '',
                'gasto' => $totalGastos,
                'detailOperation' => '',
            ]);}

            $resultado = $ingresos->concat($gastos);
            }
            else {
                $ingresos = Finance::where('branch_id', $data['branch_id'])->whereYear('data', $data['year'])->where('operation', 'Ingreso')
                ->get()->map(function ($query) {
                    return [
                        'data' => $query->data,
                        'operation' => $query->operation,
                        'ingreso' => $query->amount,
                        'gasto' => '',
                        'detailOperation' => $query->revenue->name,
                    ];
                })->sortByDesc('data')->values();

            $totalIngresos = $ingresos->sum('ingreso');
            if($totalIngresos){
            $ingresos->push((object)[
                'data' => '',
                'operation' => 'Total',
                'ingreso' => $totalIngresos,
                'gasto' => '',
                'detailOperation' => '',
            ]);
        }

            $gastos = Finance::where('branch_id', $data['branch_id'])->whereYear('data', $data['year'])->where('operation', 'Gasto')
                ->get()->map(function ($query) {
                    return [
                        'data' => $query->data,
                        'operation' => $query->operation,
                        'ingreso' => '',
                        'gasto' => $query->amount,
                        'detailOperation' => $query->expense->name,
                    ];
                })->sortByDesc('data')->values();

            $totalGastos = $gastos->sum('gasto');
                if($totalGastos){
                    $gastos->push((object)[
                        'data' => '',
                        'operation' => 'Total',
                        'ingreso' => '',
                        'gasto' => $totalGastos,
                        'detailOperation' => '',
                    ]);
                }
            

            $resultado = $ingresos->concat($gastos);
            }
            

            // Devolvemos el resultado
            return response()->json(['finances' => $resultado, 'totalIngresos' => $totalIngresos, 'totalGastos' => $totalGastos], 200);

            //return response()->json($result, 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al insertar el producto'], 500);
        }
    }

    public function details_operations(Request $request)
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


        $revenues = collect($monthsNames)->map(function ($monthName, $index) use ($currentYear, $data) {
            $financeData = Finance::whereYear('data', $currentYear)
                ->where('branch_id', $data['branch_id'])
                ->where('operation', 'Ingreso')
                ->whereMonth('data', $index + 1)
                ->with('revenue') // Asegúrate de cargar la relación revenue
                ->get();

            $revenueNames = Revenue::pluck('name')->toArray(); // Obtener todos los nombres de revenue

            $revenuesByType = $financeData->groupBy('revenue.name')->map->sum('amount'); // Agrupa y suma los ingresos por tipo de operación

            // Completar los totales para los tipos de ingresos que no están presentes en este mes
            foreach ($revenueNames as $revenueName) {
                if (!isset($revenuesByType[$revenueName])) {
                    $revenuesByType[$revenueName] = 0;
                }
            }

            // Calcular el total de ingresos para este mes
            $totalRevenue = collect($revenuesByType)->sum();

            return (object)[
                'month' => $monthName,
                'total_revenues' => $revenuesByType,
                'total_revenue' => $totalRevenue, // Agregar el total de ingresos para este mes
            ];
        });

        // Inicializar un array para almacenar los datos reestructurados
        $tableRevenue = [];
        // Inicializar un array para almacenar los totales por mes
        $monthTotals = array_fill_keys($monthsNames, 0);
        // Iterar sobre los datos de revenues
        foreach ($revenues as $revenue) {
            // Crear una fila para cada tipo de operación (revenue)
            foreach ($revenue->total_revenues as $revenueType => $revenueAmount) {
                // Verificar si ya existe una fila para este tipo de operación
                if (!isset($tableRevenue[$revenueType])) {
                    // Si no existe, crear una nueva fila con el nombre del tipo de operación
                    $tableRevenue[$revenueType] = [
                        'tipo' => 'Ingresos',
                        'operacion' => $revenueType,
                        // Inicializar los montos de los meses en 0
                    ];
                    // Inicializar los montos de los meses en 0
                    foreach ($monthsNames as $month) {
                        $tableRevenue[$revenueType][$month] = 0;
                    }
                }
                // Asignar el monto de ingreso al mes correspondiente en la fila
                $tableRevenue[$revenueType][$revenue->month] = $revenueAmount;
        
                // Actualizar el total del mes
                $monthTotals[$revenue->month] += $revenueAmount;
            }
        }
        
        // Crear la fila del total por meses de manera dinámica
        $totalRowRevenue = [
            'tipo' => 'Ingresos',
            'operacion' => 'Total',
        ];
        
        // Asignar los totales por mes a la fila
        foreach ($monthsNames as $month) {
            $totalRowRevenue[$month] = $monthTotals[$month];
        }
        
        // Agregar la fila del total por meses al final del array de datos
        $tableRevenue['Total'] = $totalRowRevenue;
        
        // Transformar los datos en una colección de objetos para usar en Vue.js
        $tableRevenueCollection = collect($tableRevenue)->values()->all();

        //-----------Gastos-------------
        $expenses = collect($monthsNames)->map(function ($monthName, $index) use ($currentYear, $data) {
            $expenseData = Finance::whereYear('data', $currentYear)
                ->where('branch_id', $data['branch_id'])
                ->where('operation', 'Gasto')
                ->whereMonth('data', $index + 1)
                ->with('expense') // Asegúrate de cargar la relación expense
                ->get();

            $expenseNames = Expense::pluck('name')->toArray(); // Obtener todos los nombres de expense

            $expensesByType = $expenseData->groupBy('expense.name')->map->sum('amount'); // Agrupa y suma los gastos por tipo de operación

            // Completar los totales para los tipos de gastos que no están presentes en este mes
            foreach ($expenseNames as $expenseName) {
                if (!isset($expensesByType[$expenseName])) {
                    $expensesByType[$expenseName] = 0;
                }
            }

            // Calcular el total de gastos para este mes
            $totalExpense = collect($expensesByType)->sum();

            return (object)[
                'month' => $monthName,
                'total_expenses' => $expensesByType,
                'total_expense' => $totalExpense, // Agregar el total de gastos para este mes
            ];
        });

        // Inicializar un array para almacenar los datos reestructurados
        $tableExpense = [];
        // Inicializar un array para almacenar los totales por mes
        $monthTotals = array_fill_keys($monthsNames, 0);
        // Iterar sobre los datos de revenues
        foreach ($expenses as $expense) {
            // Crear una fila para cada tipo de operación (revenue)
            foreach ($expense->total_expenses as $expenseType => $expenseAmount) {
                // Verificar si ya existe una fila para este tipo de operación
                if (!isset($tableExpense[$expenseType])) {
                    // Si no existe, crear una nueva fila con el nombre del tipo de operación
                    $tableExpense[$expenseType] = [
                        'tipo' => 'Gastos',
                        'operacion' => $expenseType,
                        // Inicializar los montos de los meses en 0
                    ];
                    // Inicializar los montos de los meses en 0
                    foreach ($monthsNames as $month) {
                        $tableExpense[$expenseType][$month] = 0;
                    }
                }
                // Asignar el monto de ingreso al mes correspondiente en la fila
                $tableExpense[$expenseType][$expense->month] = $expenseAmount;
        
                // Actualizar el total del mes
                $monthTotals[$expense->month] += $expenseAmount;
            }
        }
        
        // Crear la fila del total por meses de manera dinámica
        $totalRowExpense = [
            'tipo' => 'Gastos',
            'operacion' => 'Total',
        ];
        
        // Asignar los totales por mes a la fila
        foreach ($monthsNames as $month) {
            $totalRowExpense[$month] = $monthTotals[$month];
        }
        // Agregar la fila del total por meses al final del array de datos
        $tableExpense['Total'] = $totalRowExpense;
        
        $tableExpenseCollection = collect($tableExpense)->values()->all();
        
        return $tableFinance = array_merge_recursive($tableRevenueCollection, $tableExpenseCollection);


        // Transformar los datos en una colección de objetos para usar en Vue.js
        //$tableFinanceCollection = collect($tableFinance)->values()->all();

        // Devolver los datos de ingresos y gastos en un mismo array
        ///return $tableFinanceCollection;
                // Transformar los datos en una colección de objetos para usar en Vue.js

                //return $tableRevenueCollection->concat($tableExpenseCollection);
            } catch (\Throwable $th) {
                return response()->json(['msg' => 'Error interno del sistema'], 500);
            }
    }
    public function details_operations_month(Request $request)
    {
        try{
        $data = $request->validate([
            'branch_id' => 'required|numeric',
            'year' => 'nullable',
            'month' => 'nullable'
        ]);
        $currentYear = $data['year'];
        $currentMonth = $data['month'];

        $monthsNames = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];


        $revenues = collect($monthsNames)->map(function ($monthName, $index) use ($currentYear, $currentMonth, $data) {
            $financeData = Finance::whereYear('data', $currentYear)->whereMonth('data', $currentMonth)
                ->where('branch_id', $data['branch_id'])
                ->where('operation', 'Ingreso')
                ->whereMonth('data', $index + 1)
                ->with('revenue') // Asegúrate de cargar la relación revenue
                ->get();

            $revenueNames = Revenue::pluck('name')->toArray(); // Obtener todos los nombres de revenue

            $revenuesByType = $financeData->groupBy('revenue.name')->map->sum('amount'); // Agrupa y suma los ingresos por tipo de operación

            // Completar los totales para los tipos de ingresos que no están presentes en este mes
            foreach ($revenueNames as $revenueName) {
                if (!isset($revenuesByType[$revenueName])) {
                    $revenuesByType[$revenueName] = 0;
                }
            }

            // Calcular el total de ingresos para este mes
            $totalRevenue = collect($revenuesByType)->sum();

            return (object)[
                'month' => $monthName,
                'total_revenues' => $revenuesByType,
                'total_revenue' => $totalRevenue, // Agregar el total de ingresos para este mes
            ];
        });

        // Inicializar un array para almacenar los datos reestructurados
        $tableRevenue = [];
        // Inicializar un array para almacenar los totales por mes
        $monthTotals = array_fill_keys($monthsNames, 0);
        // Iterar sobre los datos de revenues
        foreach ($revenues as $revenue) {
            // Crear una fila para cada tipo de operación (revenue)
            foreach ($revenue->total_revenues as $revenueType => $revenueAmount) {
                // Verificar si ya existe una fila para este tipo de operación
                if (!isset($tableRevenue[$revenueType])) {
                    // Si no existe, crear una nueva fila con el nombre del tipo de operación
                    $tableRevenue[$revenueType] = [
                        'tipo' => 'Ingresos',
                        'operacion' => $revenueType,
                        // Inicializar los montos de los meses en 0
                    ];
                    // Inicializar los montos de los meses en 0
                    foreach ($monthsNames as $month) {
                        $tableRevenue[$revenueType][$month] = 0;
                    }
                }
                // Asignar el monto de ingreso al mes correspondiente en la fila
                $tableRevenue[$revenueType][$revenue->month] = $revenueAmount;
        
                // Actualizar el total del mes
                $monthTotals[$revenue->month] += $revenueAmount;
            }
        }
        
        // Crear la fila del total por meses de manera dinámica
        $totalRowRevenue = [
            'tipo' => 'Ingresos',
            'operacion' => 'Total',
        ];
        
        // Asignar los totales por mes a la fila
        foreach ($monthsNames as $month) {
            $totalRowRevenue[$month] = $monthTotals[$month];
        }
        
        // Agregar la fila del total por meses al final del array de datos
        $tableRevenue['Total'] = $totalRowRevenue;
        
        // Transformar los datos en una colección de objetos para usar en Vue.js
        $tableRevenueCollection = collect($tableRevenue)->values()->all();

        //-----------Gastos-------------
        $expenses = collect($monthsNames)->map(function ($monthName, $index) use ($currentYear, $currentMonth,$data) {
            $expenseData = Finance::whereYear('data', $currentYear)->whereMonth('data', $currentMonth)
                ->where('branch_id', $data['branch_id'])
                ->where('operation', 'Gasto')
                ->whereMonth('data', $index + 1)
                ->with('expense') // Asegúrate de cargar la relación expense
                ->get();

            $expenseNames = Expense::pluck('name')->toArray(); // Obtener todos los nombres de expense

            $expensesByType = $expenseData->groupBy('expense.name')->map->sum('amount'); // Agrupa y suma los gastos por tipo de operación

            // Completar los totales para los tipos de gastos que no están presentes en este mes
            foreach ($expenseNames as $expenseName) {
                if (!isset($expensesByType[$expenseName])) {
                    $expensesByType[$expenseName] = 0;
                }
            }

            // Calcular el total de gastos para este mes
            $totalExpense = collect($expensesByType)->sum();

            return (object)[
                'month' => $monthName,
                'total_expenses' => $expensesByType,
                'total_expense' => $totalExpense, // Agregar el total de gastos para este mes
            ];
        });

        // Inicializar un array para almacenar los datos reestructurados
        $tableExpense = [];
        // Inicializar un array para almacenar los totales por mes
        $monthTotals = array_fill_keys($monthsNames, 0);
        // Iterar sobre los datos de revenues
        foreach ($expenses as $expense) {
            // Crear una fila para cada tipo de operación (revenue)
            foreach ($expense->total_expenses as $expenseType => $expenseAmount) {
                // Verificar si ya existe una fila para este tipo de operación
                if (!isset($tableExpense[$expenseType])) {
                    // Si no existe, crear una nueva fila con el nombre del tipo de operación
                    $tableExpense[$expenseType] = [
                        'tipo' => 'Gastos',
                        'operacion' => $expenseType,
                        // Inicializar los montos de los meses en 0
                    ];
                    // Inicializar los montos de los meses en 0
                    foreach ($monthsNames as $month) {
                        $tableExpense[$expenseType][$month] = 0;
                    }
                }
                // Asignar el monto de ingreso al mes correspondiente en la fila
                $tableExpense[$expenseType][$expense->month] = $expenseAmount;
        
                // Actualizar el total del mes
                $monthTotals[$expense->month] += $expenseAmount;
            }
        }
        
        // Crear la fila del total por meses de manera dinámica
        $totalRowExpense = [
            'tipo' => 'Gastos',
            'operacion' => 'Total',
        ];
        
        // Asignar los totales por mes a la fila
        foreach ($monthsNames as $month) {
            $totalRowExpense[$month] = $monthTotals[$month];
        }
        // Agregar la fila del total por meses al final del array de datos
        $tableExpense['Total'] = $totalRowExpense;
        
        $tableExpenseCollection = collect($tableExpense)->values()->all();
        
        return $tableFinance = array_merge_recursive($tableRevenueCollection, $tableExpenseCollection);

        // Transformar los datos en una colección de objetos para usar en Vue.js
        //$tableFinanceCollection = collect($tableFinance)->values()->all();

        // Devolver los datos de ingresos y gastos en un mismo array
        ///return $tableFinanceCollection;
                // Transformar los datos en una colección de objetos para usar en Vue.js

                //return $tableRevenueCollection->concat($tableExpenseCollection);
            } catch (\Throwable $th) {
                return response()->json(['msg' => 'Error interno del sistema'], 500);
            }
    }
}
