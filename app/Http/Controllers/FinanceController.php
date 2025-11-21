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
 * Lista todos los registros financieros.
 *
 * Devuelve el listado completo de operaciones financieras (ingresos y gastos) del sistema.
 *
 * @authenticated
 *
 * @response 200 {
 *   "finances": [
 *     {
 *       "id": 1,
 *       "control": 1001,
 *       "operation": "Ingreso",
 *       "amount": 12000,
 *       "comment": "Pago de curso",
 *       "branch_id": null,
 *       "business_id": 1,
 *       "enrollment_id": 4,
 *       "type": "Academia",
 *       "expense_id": null,
 *       "revenue_id": 3,
 *       "data": "2025-11-20",
 *       "file": "finances/Ingreso-2025-11-20.1001.pdf"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function index()
    {
        try {
            return response()->json(['finances' => Finance::all()], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

/**
 * Crea un nuevo registro financiero.
 *
 * Genera automáticamente el número de control secuencial. Permite adjuntar un archivo comprobante.
 *
 * @authenticated
 * @bodyParam control integer required Número de control inicial (se usará para nombrar el archivo). Example: 1001
 * @bodyParam operation string required Tipo de operación: "Ingreso" o "Gasto". Example: "Ingreso"
 * @bodyParam amount number required Monto de la operación. Example: 12000
 * @bodyParam comment string optional Comentario adicional. Example: "Pago de matrícula"
 * @bodyParam branch_id integer optional ID de la sucursal (si aplica). Example: 5
 * @bodyParam business_id integer optional ID del negocio (si aplica). Example: 1
 * @bodyParam enrollment_id integer optional ID de la academia (si aplica). Example: 4
 * @bodyParam type string required Tipo de entidad: "Negocio", "Sucursal", "Academia", etc. Example: "Academia"
 * @bodyParam expense_id integer optional ID del tipo de gasto (si es gasto). Example: 2
 * @bodyParam revenue_id integer optional ID del tipo de ingreso (si es ingreso). Example: 3
 * @bodyParam data date required Fecha de la operación (formato Y-m-d). Example: "2025-11-20"
 * @bodyParam file file optional Archivo comprobante
 *
 * @response 200 {"msg": "Operacion insertado correctamente"}
 * @response 500 {"msg": "[error]Error interno del sistema"}
 */
    public function store(Request $request)
    {
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
                'data' => 'required|date',

            ]);
            $control = 0;
            if ($request->hasFile('file')) {

                $filename = $data['operation'] . '-' . $data['data'] . '.'.$data['control'] . $request->file('file')->extension();
                $data['file'] = $request->file('file')->storeAs('finances', $filename, 'public');
            } else {
                $data['file'] = '';
            }
            $financeControl = Finance::orderBy('control', 'desc')->first();
            if ($financeControl !== null) {
                $control = $financeControl->control + 1;
            } else {
                $control = 1;
            }
            $finance = new Finance();

            $finance->control = $control;
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
            return response()->json(['msg' => $th->getMessage() . 'Error interno del sistema'], 500);
        }
    }

    /**
 * Actualiza un registro financiero existente.
 *
 * Si se sube un nuevo archivo, reemplaza el anterior y elimina el antiguo del almacenamiento.
 *
 * @authenticated
 * @bodyParam id integer required ID del registro financiero. Example: 1
 * @bodyParam control integer required Nuevo número de control. Example: 1002
 * @bodyParam operation string required "Ingreso" o "Gasto". Example: "Gasto"
 * @bodyParam amount number required Nuevo monto. Example: 8500
 * @bodyParam comment string optional Nuevo comentario. Example: "Compra de insumos"
 * @bodyParam expense_id integer optional Nuevo ID de gasto. Example: 1
 * @bodyParam revenue_id integer optional Nuevo ID de ingreso. Example: null
 * @bodyParam data date required Nueva fecha. Example: "2025-11-21"
 * @bodyParam file file optional Nuevo comprobante.
 *
 * @response 200 {"msg": "Operación editada correctamente"}
 * @response 500 {"msg": "[error]Error al insertar el producto"}
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
                'data' => 'required|date'
            ]);
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
            $finance->data = $data['data'];
            $finance->save();
            return response()->json(['msg' => 'Operación editada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al insertar el producto'], 500);
        }
    }

    /**
 * Filtra registros financieros por tipo, entidad y período.
 *
 * Soporta filtrado por año completo o por mes específico. Incluye relaciones con `expense` y `revenue`.
 *
 * @authenticated
 * @queryParam type string required Tipo de entidad: "Negocio", "Sucursal", "Academia", o "Todas". Example: "Sucursal"
 * @queryParam business_id integer optional Requerido si type = "Negocio". Example: 1
 * @queryParam branch_id integer optional Requerido si type = "Sucursal". Example: 5
 * @queryParam enrollment_id integer optional Requerido si type = "Academia". Example: 4
 * @queryParam year integer required Año de consulta. Example: 2025
 * @queryParam mounth integer optional Mes (1–12) para filtrado mensual. Example: 11
 *
 * @response 200 {
 *   "finances": [
 *     {
 *       "id": 1,
 *       "operation": "Ingreso",
 *       "amount": 12000,
 *       "expense": 0,
 *       "revenue": 12000,
 *       "nameDetalle": "Ingreso por matrícula",
 *       "typeDetail": "",
 *       "comment": "HH Pago de curso",
 *       "data": "2025-11-20",
 *       "file": "finances/...",
 *       "branch_id": 5,
 *       "type": "Sucursal"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "[error]Error interno del sistema"}
 */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'nullable|numeric',
                'business_id' => 'nullable',
                'type' => 'required|string',
                'enrollment_id' => 'nullable',
                'year' => 'nullable'
            ]);
            //str_contains($expenseName, $phrase)            
            $comment = '';
            $finances = [];
            if($request->mounth){
                if($data['type'] == 'Negocio'){
                    $financeData = Finance::where('business_id', $data['business_id'])->where('type', $data['type'])->whereYear('data', $data['year'])->whereMonth('data', $request->mounth)->with(['expense', 'revenue'])->orderByDesc('id')->get();
                    $finances = $this->mapAmountDetails($financeData);
                }
                if ($data['type'] == 'Sucursal'){
                    $financeData = Finance::where('branch_id', $data['branch_id'])->where('type', $data['type'])->whereYear('data', $data['year'])->whereMonth('data', $request->mounth)->with(['expense', 'revenue'])->orderByDesc('id')->get();
                    $finances = $this->mapFinances($financeData);
                }
                if ($data['type'] == 'Academia'){
                    $financesData = Finance::where('enrollment_id', $data['enrollment_id'])->where('type', $data['type'])->whereYear('data', $data['year'])->whereMonth('data', $request->mounth)->with(['expense', 'revenue'])->orderByDesc('id')->get();
                    $finances = $this->mapAmountDetails($financesData);
                }
                if ($data['type'] == 'Todas'){
                    $financesData = Finance::with(['expense', 'revenue'])->whereYear('data', $data['year'])->whereMonth('data', $request->mounth)->orderByDesc('id')->get();
                    $finances = $this->mapAmountDetails($financesData);
                }
            }else{
                if($data['type'] == 'Negocio'){
                    $financeData = Finance::where('business_id', $data['business_id'])->where('type', $data['type'])->whereYear('data', $data['year'])->with(['expense', 'revenue'])->orderByDesc('id')->get();
                    $finances = $this->mapAmountDetails($financeData);
                }
                if ($data['type'] == 'Sucursal'){
                    $financeData = Finance::where('branch_id', $data['branch_id'])->where('type', $data['type'])->whereYear('data', $data['year'])->with(['expense', 'revenue'])->orderByDesc('id')->get();
                    $finances = $this->mapFinances($financeData);
                }
                if ($data['type'] == 'Academia'){
                    $financesData = Finance::where('enrollment_id', $data['enrollment_id'])->where('type', $data['type'])->whereYear('data', $data['year'])->with(['expense', 'revenue'])->orderByDesc('id')->get();
                    $finances = $this->mapAmountDetails($financesData);
                }
                if ($data['type'] == 'Todas'){
                    $financesData = Finance::with(['expense', 'revenue'])->whereYear('data', $data['year'])->orderByDesc('id')->get();
                    $finances = $this->mapAmountDetails($financesData);
                }
            }          
            
            return response()->json(['finances' => $finances], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()."Error interno del sistema"], 500);
        }
    }

    public function mapAmountDetails($finances)
    {
        return $finances->map(function ($query) {
                        // Asigna el amount dependiendo de si es un gasto o un ingreso
                        $amount = $query->amount; // Monto total
                        
                        // Asigna el monto según el tipo, incluso si ambos son nulos
                        $amountExpense = ($query->operation == 'Gasto') ? $amount : 0; // Monto para gastos
                        $amountRevenue = ($query->operation == 'Ingreso') ? $amount : 0; // Monto para ingresos
                        return [
                            'id' => $query->id,
                            'data' => $query->data,
                            'control' => $query->control,
                            'operation' => $query->operation,
                            'amount' => $query->amount,
                            'expense' => $amountExpense, // Asigna según el tipo
                           'revenue' => $amountRevenue, // Asigna según el tipo
                            'comment' => 'HH '.$query->comment,
                            'file' => $query->file,
                            'branch_id' => $query->branch_id,
                            'business_id' => $query->business_id,
                            'enrollment_id' => $query->enrollment_id,
                            'expense_id' => $query->expense_id,
                            'revenue_id' => $query->revenue_id,
                            'type' => $query->type,                            
                            'nameDetalle' => $query->expense 
                            ? $query->expense->name 
                            : ($query->revenue 
                                ? $query->revenue->name 
                                : ($query->type == 'Ingreso' 
                                    ? '[MANTENEDOR ELIMINADO]' 
                                    : '[MANTENEDOR ELIMINADO]')),
                            'typeDetail' => ''
                        ];
                    })->sortByDesc('data')->values();
    }

     // Supongamos que esto está en tu modelo Finance o en el controlador correspondiente
     public function mapFinances($finances)
     {
         return $finances->map(function ($query) {
             $typeDetail = '';
                         $comment = 'HH '.$query->comment;
                         if($query->revenue){
                             if($query->revenue->name == 'Ingreso venta de productos en la caja'){
                                 $typeDetail = 'Ingreso Producto';
                                 $comment = 'IngresoProducto '.$query->comment;
                             }
                             if($query->revenue->name == 'Ingresos por porciento de propinas'){
                                 $typeDetail = 'Ingreso Propina';
                                 $comment = 'IngresoServicio '.$query->comment;
                             }
                             if($query->revenue->name == 'Ingresos por pago de servicios'){
                                 $typeDetail = 'Ingreso Servicio';
                                 $comment = 'IngresoServicio '.$query->comment;
                             }
                         }
                         if($query->expense){
                             if($query->expense->name == 'Compra de productos'){
                                 $typeDetail = 'Gasto Producto';
                                 $comment = 'GastoProducto '.$query->comment;
                             }
                             if($query->expense->name == 'Productos'){
                                 $typeDetail = 'Gasto Producto';
                                 $comment = 'GastoProducto '.$query->comment;
                             }
                             if($query->expense->name == 'Pago a profesionales'){
                                 $typeDetail = 'Gasto Servicio';
                                 $comment = 'GastoServicio '.$query->comment;
                             }
                         }
                         if(str_contains($query->comment, 'Gasto por pago de bono de convivencias')){
                             $typeDetail = 'Gasto Servicio';
                             $comment = 'GastoServicio '.$query->comment;
                         }
                         if(str_contains($query->comment, 'Ingreso por venta de productos a cliente')){
                             $typeDetail = 'Ingreso Producto';
                             $comment = 'IngresoProducto '.$query->comment;
                         }
                         if(str_contains($query->comment, 'Ingreso venta de producto en la caja')){
                             $typeDetail = 'Ingreso Producto';
                             $comment = 'IngresoProducto '.$query->comment;
                         }
                         if(str_contains($query->comment, 'Gasto por pago de bono de servicios')){
                             $typeDetail = 'Gasto Servicio';
                             $comment = 'GastoServicio '.$query->comment;
                         }
                         if(str_contains($query->comment, 'Gasto por pago de bono de productos')){
                             $typeDetail = 'Gasto Producto';
                             $comment = 'GastoProducto '.$query->comment;
                         }
                         if(str_contains($query->comment, 'Gasto por pago de 10% de propinas')){
                             $typeDetail = 'Gasto Propina';
                             $comment = 'GastoServicio '.$query->comment;
                         }
                         if(str_contains($query->comment, 'Gasto por pago a cajero (a)')){
                             $typeDetail = 'Gasto Producto';
                             $comment = 'GastoProducto '.$query->comment;
                         }
                          // Asigna el amount dependiendo de si es un gasto o un ingreso
                          $amount = $query->amount; // Monto total
                         
                          // Asigna el monto según el tipo, incluso si ambos son nulos
                          $amountExpense = ($query->operation == 'Gasto') ? $amount : 0; // Monto para gastos
                          $amountRevenue = ($query->operation == 'Ingreso') ? $amount : 0; // Monto para ingresos
                         return [
                             'id' => $query->id,
                             'data' => $query->data,
                             'control' => $query->control,
                             'operation' => $query->operation,
                             'amount' => $query->amount,
                             'expense' => $amountExpense, // Asigna según el tipo
                             'revenue' => $amountRevenue, // Asigna según el tipo
                             'comment' => $comment,
                             'file' => $query->file,
                             'branch_id' => $query->branch_id,
                             'business_id' => $query->business_id,
                             'enrollment_id' => $query->enrollment_id,
                             'expense_id' => $query->expense_id,
                             'revenue_id' => $query->revenue_id,
                             'type' => $query->type,                            
                             'nameDetalle' => $query->expense 
                             ? $query->expense->name 
                             : ($query->revenue 
                                 ? $query->revenue->name 
                                 : ($query->type == 'Ingreso' 
                                     ? '[MANTENEDOR ELIMINADO]' 
                                     : '[MANTENEDOR ELIMINADO]')),
                             'typeDetail' => $typeDetail
                         ];
                                 });
     }

     /**
 * Obtiene datos maestros para el formulario de finanzas.
 *
 * Retorna negocios, gastos, ingresos, sucursales y academias asociadas a un negocio.
 *
 * @authenticated
 * @queryParam business_id integer required ID del negocio para filtrar sucursales y academias. Example: 1
 *
 * @response 200 {
 *   "businesses": [...],
 *   "expenses": [...],
 *   "revenues": [...],
 *   "branches": [...],
 *   "enrollments": [...]
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
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
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }
    
    /**
 * Elimina un registro financiero.
 *
 * También elimina el archivo comprobante asociado del almacenamiento público.
 *
 * @authenticated
 * @bodyParam id integer required ID del registro a eliminar. Example: 1
 *
 * @response 200 {"msg": "producto eliminado correctamente"}
 * @response 500 {"msg": "Error al eliminar el producto"}
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

    /**
 * Análisis mensual de ingresos vs gastos por sucursal.
 *
 * Devuelve un desglose por mes del año, con totales y comparación respecto al año anterior.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam year integer required Año a analizar. Example: 2025
 *
 * @response 200 {
 *   "finances": [
 *     { "month": "Enero", "total_expenses": 5000, "total_revenues": 15000, "difference": 10000 },
 *     { "month": "Total", "total_expenses": 60000, "total_revenues": 180000, "difference": 120000 }
 *   ],
 *   "last_year_difference": 95000
 * }
 * @response 500 {"msg": "[error]Error al insertar el producto"}
 */
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

            $result = [
                'finances' => $finances,
                'last_year_difference' => $lastYearDifference
            ];

            return response()->json($result, 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error al insertar el producto'], 500);
        }
    }

    /**
 * Detalle cronológico de ingresos y gastos por sucursal.
 *
 * Lista cada operación individual con su tipo y monto, separado por ingresos y gastos, con totales.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam year integer required Año. Example: 2025
 * @queryParam mounth integer optional Mes (1–12) para filtrado mensual. Example: 11
 *
 * @response 200 {
 *   "finances": [
 *     { "data": "2025-11-20", "operation": "Ingreso", "ingreso": 12000, "gasto": "", "detailOperation": "Matrícula" },
 *     { "data": "", "operation": "Total", "ingreso": 12000, "gasto": "", "detailOperation": "" },
 *     { "data": "2025-11-19", "operation": "Gasto", "ingreso": "", "gasto": 3000, "detailOperation": "Insumos" },
 *     { "data": "", "operation": "Total", "ingreso": "", "gasto": 3000, "detailOperation": "" }
 *   ],
 *   "totalIngresos": 12000,
 *   "totalGastos": 3000
 * }
 * @response 500 {"msg": "[error]Error al insertar el producto"}
 */
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
                        'detailOperation' => $query->revenue ? $query->revenue->name : 'MANTENEDOR ELIMINADO',
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
                                'detailOperation' => $query->expense ? $query->expense->name : 'MANTENEDOR ELIMINADO',
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
                        'detailOperation' => $query->revenue ? $query->revenue->name : 'MANTENEDOR ELIMINADO',
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
                        'detailOperation' => $query->expense ? $query->expense->name : 'MANTENEDOR ELIMINADO',
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
            return response()->json(['msg' => $th->getMessage() . 'Error al insertar el producto'], 500);
        }
    }

    /**
 * Desglose tabular de ingresos y gastos por tipo y mes (vista anual).
 *
 * Formato optimizado para tablas con columnas por mes y filas por tipo de operación.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam year integer required Año. Example: 2025
 *
 * @response 200 [
 *   { "tipo": "Ingresos", "operacion": "Matrículas", "Enero": 10000, "Febrero": 12000, ..., "Total": 144000 },
 *   { "tipo": "Gastos", "operacion": "Insumos", "Enero": 2000, "Febrero": 2500, ..., "Total": 30000 }
 * ]
 * @response 500 {"msg": "Error interno del sistema"}
 */
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
        
        $filteredRevenues = collect($tableRevenue)->filter(function ($revenue) {
            return collect($revenue)->except(['tipo', 'operacion'])->some(function ($amount) {
                return $amount > 0;
            });
        })->toArray();
        // Transformar los datos en una colección de objetos para usar en Vue.js
        $tableRevenueCollection = collect($filteredRevenues)->values()->all();

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
        
        // Filtrar los tipos de gastos que tienen un valor en al menos un mes
        $filteredExpenses = collect($tableExpense)->filter(function ($expense) {
            return collect($expense)->except(['tipo', 'operacion'])->some(function ($amount) {
                return $amount > 0;
            });
        })->toArray();
        
        $tableExpenseCollection = collect($filteredExpenses)->values()->all();
        
        return $tableFinance = array_merge_recursive($tableRevenueCollection, $tableExpenseCollection);
     } catch (\Throwable $th) {
       return response()->json(['msg' => 'Error interno del sistema'], 500);
     }
    }

    /**
 * Desglose tabular de ingresos y gastos por tipo (vista mensual).
 *
 * Similar a `details_operations`, pero filtrado para un mes específico.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam year integer required Año. Example: 2025
 * @queryParam month integer required Mes (1–12). Example: 11
 *
 * @response 200 [
 *   { "tipo": "Ingresos", "operacion": "Matrículas", "Enero": 0, ..., "Noviembre": 12000, ..., "Total": 12000 },
 *   { "tipo": "Gastos", "operacion": "Insumos", "Noviembre": 3000, "Total": 3000 }
 * ]
 * @response 500 {"msg": "Error interno del sistema"}
 */
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
        // Filtrar los tipos de ingresos que tienen un valor en al menos un mes
        $filteredRevenues = collect($tableRevenue)->filter(function ($revenue) {
            return collect($revenue)->except(['tipo', 'operacion'])->some(function ($amount) {
                return $amount > 0;
            });
        })->toArray();
        // Transformar los datos en una colección de objetos para usar en Vue.js
        $tableRevenueCollection = collect($filteredRevenues)->values()->all();

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
        // Filtrar los tipos de gastos que tienen un valor en al menos un mes
        $filteredExpenses = collect($tableExpense)->filter(function ($expense) {
            return collect($expense)->except(['tipo', 'operacion'])->some(function ($amount) {
                return $amount > 0;
            });
        })->toArray();
        
        $tableExpenseCollection = collect($filteredExpenses)->values()->all();
        
        return $tableFinance = array_merge_recursive($tableRevenueCollection, $tableExpenseCollection);

        } catch (\Throwable $th) {
           return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    /**
 * Lista detallada de operaciones financieras en un mes específico.
 *
 * Incluye fecha, monto, comentario, archivo y tipo de operación (con nombre legible).
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam year integer required Año. Example: 2025
 * @queryParam month integer required Mes (1–12). Example: 11
 *
 * @response 200 {
 *   "finances": [
 *     {
 *       "data": "2025-11-20",
 *       "operation": "Ingreso",
 *       "amount": 12000,
 *       "file": "finances/...",
 *       "comment": "Pago de curso",
 *       "typeOperation": "Ingreso por matrícula"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "[error]Error interno del sistema"}
 */
     public function finances_detail_operation_month(Request $request){
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'year' => 'nullable',
                'month' => 'nullable'
            ]);
            $financeDates = [];
            $finances = Finance::Where('branch_id', $data['branch_id'])->whereYear('data', $data['year'])->whereMonth('data', $data['month'])->get();
            foreach($finances as $finance){

                $name = '';
                if($finance['revenue_id'] == null &&  $finance['expense_id'] == null)
                {
                    $name = 'MANTENEDOR ELIMINADO';
                }
                else if($finance['revenue_id'])
                {
                    $name = $finance['revenue']['name'];
                }
                else{
                    $name = $finance['expense']['name'];
                }
                $financeDates [] = [
                    'data' => $finance['data'],
                    'operation' => $finance['operation'],
                    'amount' => $finance['amount'],
                    'file' => $finance['file'],
                    'comment' => $finance['comment'],                    
                    'typeOperation' => $name,                    
                ];
            }
            // Devolvemos el resultado
            return response()->json(['finances' => $financeDates], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error interno del sistema'], 500);
        }
    }
          
    /**
 * Lista detallada de operaciones financieras en un año completo.
 *
 * Similar a la versión mensual, pero sin filtro de mes.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam year integer required Año. Example: 2025
 *
 * @response 200 {
 *   "finances": [
 *     { "data": "2025-11-20", "operation": "Ingreso", "amount": 12000, "file": "...", "comment": "...", "typeOperation": "Matrícula" },
 *     { "data": "2025-11-15", "operation": "Gasto", "amount": 3000, "file": "...", "comment": "...", "typeOperation": "Insumos" }
 *   ]
 * }
 * @response 500 {"msg": "[error]Error interno del sistema"}
 */
    public function finances_detail_operation(Request $request){
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'year' => 'nullable'
            ]);
            $financeDates = [];
            $finances = Finance::Where('branch_id', $data['branch_id'])->whereYear('data', $data['year'])->get();
            foreach($finances as $finance){
                $name = '';
                if($finance['revenue_id'] == null &&  $finance['expense_id'] == null)
                {
                    $name = 'MANTENEDOR ELIMINADO';
                }
                else if($finance['revenue_id'])
                {
                    $name = $finance['revenue']['name'];
                }
                else{
                    $name = $finance['expense']['name'];
                }
                $financeDates [] = [
                    'data' => $finance['data'],
                    'operation' => $finance['operation'],
                    'amount' => $finance['amount'],
                    'file' => $finance['file'],
                    'comment' => $finance['comment'],                    
                    'typeOperation' => $name,                    
                ];
            }
            // Devolvemos el resultado
            return response()->json(['finances' => $financeDates], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error interno del sistema'], 500);
        }
    }
}
