<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CashierSale;
use App\Models\CourseProfessional;
use App\Models\Finance;
use App\Models\Order;
use App\Models\Professional;
use App\Models\ProfessionalPayment;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProfessionalPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required',
                'professional_id' => 'required',
                'amount' => 'required|numeric',
                'type' => 'required|string',
            ]);
            if($data['type'] == 'Pago Academia'){
                
                Log::info('entra a pago los cursos');
                $ids = $request->input('course_ids');
                $courseProfessional = CourseProfessional::find($ids);
                $enrollment_id = $courseProfessional->course->enrollment_id;
                $professionalPayment = new ProfessionalPayment();
                $professionalPayment->enrollment_id = $enrollment_id;
                $professionalPayment->professional_id = $data['professional_id'];
                $professionalPayment->date = Carbon::now();
                $professionalPayment->amount = $data['amount'];
                $professionalPayment->type = $data['type'];

            // Guardar el modelo
            $professionalPayment->save();
            $courseProfessional->pay = $professionalPayment->id;
            $courseProfessional->save();
            /*Log::info($request->input('course_ids'));
            if ($request->input('course_ids')) {
                // Actualizar carros con professional_payment_id
                Log::info('entra a pago los cursos');
                $course_ids = $request->input('course_ids');
                CourseProfessional::whereIn('id', $course_ids)->update(['professional_payment_id' => $professionalPayment->id]);
            }*/

            $professional = Professional::find($data['professional_id']);

            $finance = Finance::where('enrollment_id', $ids)->where('expense_id', 6)->whereDate('data', Carbon::now())->orderByDesc('control')->first();
                            
            if($finance !== null)
            {
                $control = $finance->control+1;
            }
            else {
                $control = 1;
            }
            $finance = new Finance();
                            $finance->control = $control;
                            $finance->operation = 'Gasto';
                            $finance->amount = $data['amount'];
                            $finance->comment = 'Gasto por pago de curso a '.$professional->name .' '.$professional->surname;
                            $finance->enrollment_id = $enrollment_id;
                            $finance->type = 'Academia';
                            $finance->expense_id = 6;
                            $finance->data = Carbon::now();                
                            $finance->file = '';
                            $finance->save();
            }else{
                $professionalPayment = new ProfessionalPayment();
                $professionalPayment->branch_id = $data['branch_id'];
                $professionalPayment->professional_id = $data['professional_id'];
                $professionalPayment->date = Carbon::now();
                $professionalPayment->amount = $data['amount'];
                $professionalPayment->type = $data['type'];

            // Guardar el modelo
            $professionalPayment->save();
            Log::info($request->input('car_ids'));
            if ($request->input('car_ids')) {
                // Actualizar carros con professional_payment_id
                Log::info('entra a pago los carros');
                $carIds = $request->input('car_ids');
                Car::whereIn('id', $carIds)->update(['professional_payment_id' => $professionalPayment->id]);
            }

            $professional = Professional::find($data['professional_id']);

            $finance = Finance::where('branch_id', $data['branch_id'])->where('expense_id', 4)->whereDate('data', Carbon::now())->orderByDesc('control')->first();
                            
            if($finance !== null)
            {
                $control = $finance->control+1;
            }
            else {
                $control = 1;
            }
            $finance = new Finance();
                            $finance->control = $control;
                            $finance->operation = 'Gasto';
                            $finance->amount = $data['amount'];
                            $finance->comment = 'Gasto por pago a '.$professional->name .' '.$professional->surname;
                            $finance->branch_id = $data['branch_id'];
                            $finance->type = 'Sucursal';
                            $finance->expense_id = 4;
                            $finance->data = Carbon::now();                
                            $finance->file = '';
                            $finance->save();
            }
            return response()->json($professionalPayment, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Error de validación: ' . $e->getMessage()], 400);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }

    public function store_cashier(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required',
                'professional_id' => 'required',
                'amount' => 'required|numeric',
                'type' => 'required|string',
            ]);
            
                $professionalPayment = new ProfessionalPayment();
                $professionalPayment->branch_id = $data['branch_id'];
                $professionalPayment->professional_id = $data['professional_id'];
                $professionalPayment->date = Carbon::now();
                $professionalPayment->amount = $data['amount'];
                $professionalPayment->type = $data['type'];

            // Guardar el modelo
            $professionalPayment->save();
            Log::info($request->input('ids'));
            if ($request->input('ids')) {
                // Actualizar carros con professional_payment_id
                Log::info('entra a pago los carros');
                $Ids = $request->input('ids');
                CashierSale::whereIn('id', $Ids)->update(['paycashier' => $professionalPayment->id]);
            }

            $professional = Professional::find($data['professional_id']);

            $finance = Finance::where('branch_id', $data['branch_id'])->where('expense_id', 4)->whereDate('data', Carbon::now())->orderByDesc('control')->first();
                            
            if($finance !== null)
            {
                $control = $finance->control+1;
            }
            else {
                $control = 1;
            }
            $finance = new Finance();
                            $finance->control = $control;
                            $finance->operation = 'Gasto';
                            $finance->amount = $data['amount'];
                            $finance->comment = 'Gasto por pago a '.$professional->name;
                            $finance->branch_id = $data['branch_id'];
                            $finance->type = 'Sucursal';
                            $finance->expense_id = 4;
                            $finance->data = Carbon::now();                
                            $finance->file = '';
                            $finance->save();

            return response()->json($professionalPayment, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Error de validación: ' . $e->getMessage()], 400);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }
    

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            $request->validate([
                'professional_id' => 'required|exists:professionals,id',
                'branch_id' => 'required|exists:branches,id',
            ]);

            $professionalId = $request->professional_id;
            $branchId = $request->branch_id;

            $payments = ProfessionalPayment::where('professional_id', $professionalId)
                                          ->where('branch_id', $branchId)
                                          ->get()->map(function ($query){
                                            return [
                                                'id' => $query->id,
                                                'branch_id ' =>$query->branch_id,
                                                'professional_id' => $query->professional_id,
                                                'date' => $query->date.' '.Carbon::parse($query->created_at)->format('H:i'),
                                                'type' => $query->type,
                                                'amount' => $query->amount
                                            ];
                                          })->sortByDesc('date')->values();

            return response()->json($payments, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Error de validación: ' . $e->getMessage()], 400);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }

    public function show_apk(Request $request)
    {
        try {
            $request->validate([
                'professional_id' => 'required|exists:professionals,id',
                'branch_id' => 'required|exists:branches,id',
                'charge' => 'nullable'
            ]);

            $professionalId = $request->professional_id;
            $branchId = $request->branch_id;

            //$startOfMonth = now()->startOfMonth()->toDateString();
            //$endOfMonth = now()->endOfMonth()->toDateString();

            $payments = ProfessionalPayment::where('professional_id', $professionalId)
                                        //->whereDate('date', '>=', $startOfMonth)->whereDate('date', '<=', $endOfMonth)
                                          ->where('branch_id', $branchId)
                                          ->get()->map(function ($query){
                                            return [
                                                'id' => $query->id,
                                                'branch_id ' =>$query->branch_id,
                                                'professional_id' => $query->professional_id,
                                                'date' => $query->date.' '.Carbon::parse($query->created_at)->format('H:i'),
                                                'type' => $query->type,
                                                'amount' => $query->amount,
                                                'cant' => $query->cant
                                            ];
                                          })->sortByDesc('date')->values();
                                          
                                          //pendiente por pagar
                                          $retention = Professional::where('id', $request->professional_id)->value('retention');
                                          $paymentIds = $payments->pluck('id');
                                          
                if($request->charge == 'Tecnico'){
                   $pendienteMount = 0;
                    $pagadoMount = $payments->sum('amount') ? intval($payments->sum('amount')) : intval(0);
                    $clientAttended = intval(Car::where('tecnico_id', $professionalId)->where('pay', 1)->sum('technical_assistance'));
                    $servCant = 0;
                    $amountGenerate = 0;
                    $propina80 = 0;
                    $metaCant = 0;
                    $metaAmount = 0;
                    $retentionpay = 0;
                    $winnerRetention = 0;
                    $winnerAmount = 0;
                    $productCant = 0;
                    $productBono = 0;
                    $productBonoCant = 0;
                    $servBonoCant = 0;
                    $servAmount = 0;
                    $productAmount = 0; 
                }
                else if ($request->charge == 'Barbero' || $request->charge == 'Barbero y Encargado') 
                {
                    //pagado
                    $carPagado = Car::whereIn('professional_payment_id', $paymentIds)->get();
                    $carIdsPay = $carPagado->pluck('id');
                    $propinaPay = $carPagado->sum('tip');
                    $propinaPay80 = $propinaPay * 0.80;
                    $orderServPay = Order::whereIn('car_id', $carIdsPay)->where('is_product', 0)->get();
                    $orderProdPay = Order::whereIn('car_id', $carIdsPay)->where('is_product', 1)->get();
                    $servMountPay = $orderServPay->sum('percent_win');
                    $retentionpay = $retention ? ($servMountPay * $retention)/100 : 0;
                    $metaPagado = $payments->where('type', 'Bono convivencias');
                    $clientAttended = $carPagado->count() ? $carPagado->count() : 0;
                    $servCant = $orderServPay->count() ? $orderServPay->count() : 0;
                    $productCant = $orderProdPay->sum('price') ? $orderProdPay->sum('price') : 0;
                    $amountGenerate = $carPagado->sum('amount') ? $carPagado->sum('amount') : 0;
                    $propina80 = $carPagado->sum('tip') * 0.80 ? $carPagado->sum('tip') * 0.80 : 0;
                    $metaCant = $metaPagado ? $metaPagado->sum('cant') : 0;
                    $metaAmount = $metaPagado ? $metaPagado->sum('amount') : 0;
                    $productBono = $payments->where('type', 'Bono productos');
                    $productBonoCant = $productBono ? $productBono->sum('cant') : 0;
                    $productAmount = $productBono ? $productBono->sum('amount') : 0;
                    $ServBono = $payments->where('type', 'Bono servicios');
                    $servBonoCant = $ServBono ? $ServBono->sum('cant') : 0;
                    $servAmount = $ServBono ? $ServBono->sum('amount') : 0;
                    $pagadoMount = $payments->sum('amount') ? intval($payments->sum('amount')) : intval(0);

                    $winnerRetention = $servMountPay-$retentionpay;
                    $winnerAmount = $servMountPay - $retentionpay + $propinaPay80;
                    //Pendiente
                    $carPendiente = Car::whereHas('reservation', function ($query) use ($request) {
                        $query->where('branch_id', $request->branch_id);
                    })
                    ->with(['clientProfessional.client', 'reservation'])
                    ->whereHas('clientProfessional', function ($query) use ($request) {
                        $query->where('professional_id', $request->professional_id);
                    })
                    ->where('pay', 1)
                    ->where('professional_payment_id', null)
                    ->get();
                    $carIdsPend = $carPendiente->pluck('id');
                    $propinaPen = $carPendiente->sum('tip');
                    $propinaPend80 = $propinaPen * 0.80;
                    $orderServ = Order::whereIn('car_id', $carIdsPend)->where('is_product', 0)->get();
                    $orderServPen = $orderServ->sum('percent_win');
                    $servMountPenRet = $orderServPen * $retention/100;
                    $pendienteMount = $orderServPen - $servMountPenRet + $propinaPend80 ? $orderServPen - $servMountPenRet + $propinaPend80 : 0;

                }
                else{
                    $pendienteMount = 0;
                    $pagadoMount = $payments->sum('amount') ? intval($payments->sum('amount')) : intval(0);
                    $clientAttended = 0;
                    $servCant = 0;
                    $amountGenerate = 0;
                    $propina80 = 0;
                    $metaCant = 0;
                    $metaAmount = 0;
                    $retentionpay = 0;
                    $winnerRetention = 0;
                    $winnerAmount = 0;
                    $productCant = 0;
                    $productBono = 0;
                    $productBonoCant = 0;
                    $servBonoCant = 0;
                    $servAmount = 0;
                    $productAmount = 0;
                }
                

            return response()->json(['payments' => $payments, 'pendiente' => intval($pendienteMount), 'pagado' => intval($pagadoMount), 'clientAtended' => $clientAttended, 'servCant' => $servCant, 'amountGenerate' => intval($amountGenerate), 'propina80' => intval($propina80), 'metaCant' => $metaCant, 'metaAmount' => intval($metaAmount), 'productBonoCant' => $productBonoCant, 'productAmount' => $productAmount, 'servBonoCant' => $servBonoCant, 'servAmount' => $servAmount, 'retention' => $retentionpay, 'winnerRetention' => intval($winnerRetention), 'winnerAmount' => intval($winnerAmount), 'productCant' => $productCant], 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Error de validación: ' . $e->getMessage()], 400);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }

    public function show_periodo(Request $request)
    {
        try {
            $request->validate([
                'professional_id' => 'required|exists:professionals,id',
                'branch_id' => 'required|exists:branches,id',
                'startDate' => 'required|date',
                'endDate' => 'required|date'
            ]);

            $professionalId = $request->professional_id;
            $branchId = $request->branch_id;

            $payments = ProfessionalPayment::where('professional_id', $professionalId)
                                          ->where('branch_id', $branchId)
                                          ->whereDate('date', '>=', $request->startDate)
                                          ->whereDate('date', '<=', $request->endDate)
                                          ->get()->map(function ($query){
                                            return [
                                                'id' => $query->id,
                                                'branch_id ' =>$query->branch_id,
                                                'professional_id' => $query->professional_id,
                                                'date' => $query->date.' '.Carbon::parse($query->created_at)->format('H:i'),
                                                'type' => $query->type,
                                                'amount' => $query->amount
                                            ];
                                          })->sortByDesc('date')->values();

            $totalAmount = $payments->sum('amount');
                if($totalAmount){
            // Agregar fila de total
            $totalRow = [
                'id' => '',
                'branch_id' => '',
                'professional_id' => '',
                'date' => 'Total',
                'type' => '',
                'amount' => $totalAmount
            ];

            $payments->push($totalRow);
        }
            return response()->json($payments, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Error de validación: ' . $e->getMessage()], 400);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }

    public function branch_payment_show(Request $request)
    {
        try {
            $request->validate([
                'branch_id' => 'required|exists:branches,id',
            ]);

            $branchId = $request->branch_id;

            $payments = ProfessionalPayment::where('branch_id', $branchId)
                                          ->get()->map(function ($query){
                                            return [
                                                'id' => $query->id,
                                                'branch_id ' =>$query->branch_id,
                                                'professional_id' => $query->professional_id,
                                                'nameProfessional' => $query->professional->name.' '.$query->professional->surname.' '.$query->professional->second_surname,
                                                'image_url' => $query->professional->image_url,
                                                'date' => $query->date,
                                                'type' => $query->type,
                                                'amount' => $query->amount
                                            ];
                                          });

            return response()->json($payments, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Error de validación: ' . $e->getMessage()], 400);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProfessionalPayment $professionalPayment)
    {
        //
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
            // Buscar el pago de profesional a eliminar
            $professionalPayment = ProfessionalPayment::findOrFail($data['id']);

            // Buscar y actualizar los carros asociados para establecer el campo professional_payment_id en null
            Car::where('professional_payment_id', $data['id'])->update(['professional_payment_id' => null]);

            // Eliminar el pago de profesional
            $professionalPayment->delete();

            return response()->json(['message' => 'Pago de profesional eliminado correctamente'], 200);
        } catch (QueryException $e) {
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }

    public function professional_win_year(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'year' => 'nullable',
                'professional_id' => 'required|numeric'
            ]);
            $year = $data['year']; // Año deseado
        $professionalId = $data['professional_id']; // ID del profesional

        // Obtener los datos de la base de datos
        $result = DB::table('professionals_payments')
            ->selectRaw('MONTH(date) AS month, SUM(amount) AS earnings')
            ->whereYear('date', $data['year'])
            ->where('professional_id', $data['professional_id'])
            ->where('branch_id', $data['branch_id'])
            ->groupBy(DB::raw('MONTH(date)'))
            ->orderBy('month')
            ->get();

        // Inicializar el array de resultados
        $monthlyEarnings = [];
        $totalEarnings = 0;

        // Llenar el array con los nombres de los meses en español y sus ganancias correspondientes
        for ($month = 1; $month <= 12; $month++) {
            $monthName = Carbon::createFromDate($year, $month)->locale('es_ES')->monthName;
            $monthlyEarnings[$monthName] = 0;
        }

        // Actualizar los valores de ganancias en el array
        foreach ($result as $row) {
            $monthName = Carbon::createFromDate($year, $row->month)->locale('es_ES')->monthName;
            $monthlyEarnings[$monthName] = $row->earnings;
            $totalEarnings += $row->earnings;
        }
        // Calcular el promedio por mes
        $averageEarnings = count($result) > 0 ? $totalEarnings / count($result) : 0;
        // Devolver el array de resultados
        $monthlyEarnings;
        $meta = ProfessionalPayment::where('professional_id', $data['professional_id'])
           ->whereYear('date', $data['year'])
             ->where('branch_id', $data['branch_id'])
             ->where(function($query) {
                $query->where('type', 'Bono convivencias')
                ->orwhere('type', 'Bono productos')
                ->orwhere('type', 'Bono servicios');
            })
             ->get();

                    return response()->json(['monthlyEarnings' => $monthlyEarnings, 'totalEarnings' => $totalEarnings, 'averageEarnings' => $averageEarnings, 'metaCant' => $meta->count(), 'metaAmount' => $meta->sum('amount')], 200);
                } catch (\Throwable $th) {
                    Log::error($th);
                    return response()->json(['msg' => $th->getMessage() . 'Error al insertar el producto'], 500);
                }
    }

}
