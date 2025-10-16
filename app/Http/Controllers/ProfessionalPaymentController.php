<?php

namespace App\Http\Controllers;

use App\Models\Advance;
use App\Models\Branch;
use App\Models\BranchProfessional;
use App\Models\Car;
use App\Models\CashierSale;
use App\Models\CourseProfessional;
use App\Models\Finance;
use App\Models\OperationTip;
use App\Models\Order;
use App\Models\Professional;
use App\Models\ProfessionalPayment;
use App\Models\Retention;
use App\Models\WorkerPurchase;
use App\Services\ProfessionalPaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProfessionalPaymentController extends Controller
{

    private ProfessionalPaymentService $professionalPaymentService;

    public function __construct(ProfessionalPaymentService $professionalPaymentService)
    {
        $this->professionalPaymentService = $professionalPaymentService;
    }
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
                $professional = Professional::find($data['professional_id']);

                //$finance = Finance::where('enrollment_id', $ids)->where('expense_id', 6)->whereDate('data', Carbon::now())orderBy('control', 'desc')->first();
                $finance = Finance::orderBy('control', 'desc')->first();             
                if($finance !== null)
                {
                    $control = $finance->control+1;
                }
                else {
                    $control = 1;
                }
                $finance = new Finance();
                                $finance->control = $control++;
                                $finance->operation = 'Gasto';
                                $finance->amount = $data['amount'];
                                $finance->comment = 'Gasto por pago de curso a '.$professional->name;
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
            if ($request->input('car_ids')) {
                $carIds = $request->input('car_ids');
                Car::whereIn('id', $carIds)->update(['professional_payment_id' => $professionalPayment->id]);
            }

            $professional = Professional::find($data['professional_id']);

            //$finance = Finance::where('branch_id', $data['branch_id'])->where('expense_id', 4)->whereDate('data', Carbon::now())orderBy('control', 'desc')->first();
            $finance = Finance::orderBy('control', 'desc')->first();         
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
            }
            return response()->json($professionalPayment, 201);
        } catch (ValidationException $e) {
            Log::error($e);
            return response()->json(['error' => 'Error de validación: ' . $e->getMessage()], 400);
        } catch (QueryException $e) {
            Log::error($e);
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }

    public function store_barbero_payment(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric|exists:branches,id',
                'professional_id' => 'required|numeric|exists:professionals,id',
                'payments' => 'required|array',
                'type' => 'required|string',
                'amountAcadem' => 'nullable|numeric',
                'typeAcadem' => 'nullable|string',
                'paymentDate' => 'nullable|date',
            ]); 
            // Determinar la fecha a usar (si viene en el request o la actual)
            $data['paymentDate'] = isset($data['paymentDate']) 
            ? Carbon::parse($data['paymentDate']) 
            : Carbon::now();

            Log::info('Datos recibidos para pago a profesional Barbero', [
                'professional_id' => $data['professional_id'],
                'branch_id' => $data['branch_id'],
                'payments_data' => $data['payments'],
                'type' => $data['type'],
                'amountAcadem' => $data['amountAcadem'],
                'typeAcadem' => $data['typeAcadem'],
                'paymentDate' => $data['paymentDate'],
            ]);

           

            if($data['amountAcadem']){
                
                $ids = $request->input('course_ids');
                // Obtener todos los CourseProfessional de una vez
                $courseProfessionals = CourseProfessional::whereIn('id', $ids)->get();

                // Verificar que hayamos encontrado registros
                if ($courseProfessionals->isEmpty()) {
                    return response()->json(['error' => 'No se encontraron cursos profesionales'], 404);
                }

                // Todos deberían tener el mismo enrollment_id (asumo que es por profesional)
                $enrollment_id = $courseProfessionals->first()->course->enrollment_id;

                // Crear el pago
                $professionalPayment = new ProfessionalPayment();
                $professionalPayment->enrollment_id = $enrollment_id;
                $professionalPayment->professional_id = $data['professional_id'];
                $professionalPayment->date = $data['paymentDate'];
                $professionalPayment->amount = $data['amountAcadem'];
                $professionalPayment->type = $data['typeAcadem'];
                $professionalPayment->save();

                // Actualizar todos los CourseProfessional con el ID del pago
                CourseProfessional::whereIn('id', $ids)->update([
                    'pay' => $professionalPayment->id
                ]);

                $professional = Professional::find($data['professional_id']);

                $finance = Finance::orderBy('control', 'desc')->first();             
                if($finance !== null)
                {
                    $control = $finance->control+1;
                }
                else {
                    $control = 1;
                }
                $finance = new Finance();
                $finance->control = $control++;
                $finance->operation = 'Gasto';
                $finance->amount = $data['amountAcadem'];
                $finance->comment = 'Gasto por pago de curso a '.$professional->name;
                $finance->enrollment_id = $enrollment_id;
                $finance->type = 'Academia';
                $finance->expense_id = 6;
                $finance->data = $data['paymentDate'];                
                $finance->file = '';
                $finance->professional_payment_id = $professionalPayment->id;
                $finance->save();
            }
            
            $data = $this->adjustNetPayments($data);
            $payment = $this->professionalPaymentService->processPayment($data);
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado exitosamente',
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Error de validación en pago', [
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
            
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Recurso no encontrado', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Recurso no encontrado'], 404);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al procesar pago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    public function store_charge_payment(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric|exists:branches,id',
                'professional_id' => 'required|numeric|exists:professionals,id',
                'payments' => 'required|array',
                'type' => 'required|string',
                'paymentDate' => 'nullable|date',
            ]);

            $data['paymentDate'] = isset($data['paymentDate']) 
            ? Carbon::parse($data['paymentDate']) 
            : Carbon::now();
           
            Log::info('Datos recibidos para pago a profesional Cargos', [
                'professional_id' => $data['professional_id'],
                'branch_id' => $data['branch_id'],
                'payments_data' => $data['payments'],
                'type' => $data['type'],
                'paymentDate' => $data['paymentDate'],
            ]);
            $data = $this->adjustNetPayments($data);

            $payment = $this->professionalPaymentService->processPayment($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago registrado exitosamente',
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Error de validación en pago', [
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
            
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Recurso no encontrado', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Recurso no encontrado'], 404);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al procesar pago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error interno del servidor'], 500);
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
            if ($request->input('ids')) {
                $Ids = $request->input('ids');
                CashierSale::whereIn('id', $Ids)->update(['paycashier' => $professionalPayment->id]);
            }

            $professional = Professional::find($data['professional_id']);

            $finance = Finance::orderBy('control', 'desc')->first();
                            
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
                            $finance->comment = 'Gasto por pago a cajero (a) '.$professional->name;
                            $finance->branch_id = $data['branch_id'];
                            $finance->type = 'Sucursal';
                            $finance->expense_id = 4;
                            $finance->data = Carbon::now();                
                            $finance->file = '';
                            $finance->save();

            return response()->json($professionalPayment, 201);
        } catch (ValidationException $e) {
            Log::error($e);
            return response()->json(['error' => 'Error de validación: ' . $e->getMessage()], 400);
        } catch (QueryException $e) {
            Log::error($e);
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }

    protected function adjustNetPayments(array $data): array
    {
        $payments = $data['payments'];
        
        // 1. Calcular base disponible y deducciones
        $availableBase = ($payments['salary']['already_paid'] ? 0 : $payments['salary']['salary_bruto']) 
                    + ($payments['cars']['total_neto'] ?? 0);
        
        $totalDeductions = ($payments['advances']['total_advance'] ?? 0) 
                        + ($payments['workerPurchases']['total_purchases'] ?? 0);
        
        // 2. Calcular excedente (si lo hay)
        $excess = max(0, $totalDeductions - $availableBase);
        
        // 3. Ajustar comisiones y propinas si hay excedente
        if ($excess > 0) {
            $commissionNeto = $payments['products']['commission_neto'] ?? 0;
            $tipNeto = $payments['tips']['tip_neto'] ?? 0;
            $totalBonuses = $commissionNeto + $tipNeto;
            
            if ($totalBonuses > 0) {
                // Calcular proporciones
                $commissionRatio = $commissionNeto / $totalBonuses;
                $tipsRatio = $tipNeto / $totalBonuses;
                
                // Ajustar valores netos (sin permitir negativos)
                $payments['products']['commission_neto'] = max(0, $commissionNeto - ($excess * $commissionRatio));
                $payments['tips']['tip_neto'] = max(0, $tipNeto - ($excess * $tipsRatio));
                
                // Actualizar el array de datos
                $data['payments'] = $payments;
                
                // Registrar ajuste realizado
                Log::info('Ajuste aplicado a valores netos', [
                    'excess' => $excess,
                    'commission_neto_original' => $commissionNeto,
                    'commission_neto_ajustado' => $payments['products']['commission_neto'],
                    'tip_neto_original' => $tipNeto,
                    'tip_neto_ajustado' => $payments['tips']['tip_neto'],
                    'professional_id' => $data['professional_id']
                ]);
            }
        }
        
        return $data;
    }

    public function store_cashier_payment(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric|exists:branches,id',
                'professional_id' => 'required|numeric|exists:professionals,id',
                'payments' => 'required|array', // Asegura que payments sea un array
                'type' => 'required|string',
                'paymentDate' => 'nullable|date',
            ]);

             $data['paymentDate'] = isset($data['paymentDate']) 
            ? Carbon::parse($data['paymentDate']) 
            : Carbon::now();

             // Registrar log detallado
            Log::info('Datos recibidos para pago a profesional cajeros', [
                'professional_id' => $data['professional_id'],
                'branch_id' => $data['branch_id'],
                'payments_data' => $data['payments'], // Registra toda la estructura de pagos
                'type' => $data['type'],
                'paymentDate' => $data['paymentDate'],
            ]);

            // Aplicar ajuste a valores netos
        $data = $this->adjustNetPayments($data);
            
            $payment = $this->professionalPaymentService->processPayment($data);
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Pago registrado exitosamente',
        ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Error de validación en pago', [
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
            
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Recurso no encontrado', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Recurso no encontrado'], 404);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al procesar pago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    public function store_payment_automatically(Request $request)
    {
        $codigo = $request->query('codigo');

        if ($codigo != 'P{\nkNgP9hjm/L*~Sks25h^C30_|17') {
            return response()->json(['msg' => 'Código inválido'], 403);
        }

        try {
            DB::beginTransaction();
            
            $branchProfessionals = BranchProfessional::with(['professional'])
                            ->whereHas('professional', function($query) {
                                $query->whereNull('deleted_at');
                            })
                            ->where('branch_id', '!=',20) // Corregido: eliminado '1='
                            ->get();

            // Calcular tiempo estimado (ejemplo: 2 segundos por profesional + margen)
            $tiempoEstimado = ($branchProfessionals->count() * 1) + 10;
            set_time_limit($tiempoEstimado);
            
            $results = [];
            $currentYearMonth = now()->format('Y-m');
            
            
            foreach ($branchProfessionals as $branchProfessional) {
                $professionalId = $branchProfessional->professional_id;
                $professionalName = $branchProfessional->professional->name ?? 'Nombre no disponible';
                
                Log::info("Procesando profesional: ID {$professionalId} - {$professionalName}");
                
                try {
                   // Calcular pagos
                    $paymentData = [
                        'branch_id' => $branchProfessional->branch_id,
                        'professional_id' => $professionalId,
                    ];
                    
                    $paymentsData = $this->professionalPaymentService->calculatePayments($paymentData);

                    // Solo procesar si hay cantidad a pagar
                    if ($paymentsData['totalNetoPay'] >= 0) {
                        $paymentData['payments'] = $paymentsData;
                         // Aplicar ajuste a valores netos
                        $data = $this->adjustNetPayments($paymentData);
                        $processResult = $this->professionalPaymentService->processPayment($data);
                        
                        $result = [
                            'professional_id' => $professionalId,
                            'status' => 'success',
                            'amount' => $paymentsData['totalNetoPay'],
                            'processed_at' => now()
                        ];
                        
                        // Pequeña pausa entre pagos para evitar sobrecarga
                        usleep(500000); // 0.5 segundos
                    } else {
                        $result = [
                            'professional_id' => $professionalId,
                            'status' => 'no_payment',
                            'message' => 'No hay cantidad a pagar (totalNetoPay = 0)'
                        ];
                    }
                    
                    $results[] = $result;
                    Log::info("Resultado del profesional ID {$professionalId}: " . json_encode($result));
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    $result = [
                        'professional_id' => $professionalId,
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    ];
                    $results[] = $result;
                    Log::error("Error procesando profesional ID {$professionalId}: " . $e->getMessage());
                    
                    return response()->json([
                        'success' => false,
                        'error' => $e->getMessage(),
                        'failed_on' => $professionalId,
                        'results' => $results
                    ], 500);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'month' => $currentYearMonth,
                'processed_count' => count(array_filter($results, fn($r) => $r['status'] === 'success')),
                'skipped_count' => count(array_filter($results, fn($r) => $r['status'] === 'skipped')),
                'no_payment_count' => count(array_filter($results, fn($r) => $r['status'] === 'no_payment')),
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error general en store_payment_automatically: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Todos los cambios fueron revertidos'
            ], 500);
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
            $currentYear = now()->year; // Obtiene el año actual

            $payments = ProfessionalPayment::where('professional_id', $professionalId)
                                            ->where(function($query) use ($branchId) {
                                                $query->where('branch_id', $branchId)
                                                    ->orWhere('enrollment_id', '!=', null);
                                            })
                                            ->whereYear('date', $currentYear) // Filtra por año actual usando el campo date
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
            Log::error($e);
            return response()->json(['error' => 'Error de validación: ' . $e->getMessage()], 400);
        } catch (QueryException $e) {
            Log::error($e);
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error($e);
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

            $startOfMonth = now()->startOfMonth()->toDateString();
            $endOfMonth = now()->endOfMonth()->toDateString();

            $payments = ProfessionalPayment::where('professional_id', $professionalId)
                                        ->whereDate('date', '>=', $startOfMonth)->whereDate('date', '<=', $endOfMonth)
                                          ->where('branch_id', $branchId)
                                          ->get()->map(function ($query){
                                            return [
                                                'id' => $query->id,
                                                'branch_id ' => strval($query->branch_id),
                                                'professional_id' => strval($query->professional_id),
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
                    $pagadoMount = $payments->sum('amount') ? $payments->sum('amount') : intval(0);
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
                 $payments_redondeado = round($pagadoMount, 0);

            return response()->json(['payments' => $payments, 'pendiente' => number_format(round($pendienteMount, 2), 2), 'pagado' => number_format(round($payments_redondeado, 2), 2), 'clientAtended' => $clientAttended, 'servCant' => $servCant, 'amountGenerate' => number_format(round($amountGenerate, 2), 2), 'propina80' => number_format(round($propina80, 2), 2), 'metaCant' => $metaCant, 'metaAmount' => number_format(round($metaAmount, 2), 2), 'productBonoCant' => $productBonoCant, 'productAmount' => number_format(round($productAmount, 2), 2), 'servBonoCant' => $servBonoCant, 'servAmount' => number_format(round($servAmount, 2), 2), 'retention' => number_format(round($retentionpay, 2), 2), 'winnerRetention' => number_format(round($winnerRetention, 2), 2), 'winnerAmount' => number_format(round($winnerAmount, 2), 2), 'productCant' => number_format(round($productCant, 2), 2)], 200);
        } catch (ValidationException $e) {
            Log::error($e);
            return response()->json(['error' => 'Error de validación: ' . $e->getMessage()], 400);
        } catch (QueryException $e) {
            Log::error($e);
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error($e);
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
                                            ->where(function($query) use ($branchId) {
                                                $query->where('branch_id', $branchId)
                                                    ->orWhere('enrollment_id', '!=', null);
                                            })
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
            Log::error($e);
            return response()->json(['error' => 'Error de validación: ' . $e->getMessage()], 400);
        } catch (QueryException $e) {
            Log::error($e);
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error($e);
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
                                                'nameProfessional' => $query->professional->name,
                                                'image_url' => $query->professional->image_url,
                                                'date' => $query->date,
                                                'type' => $query->type,
                                                'amount' => $query->amount
                                            ];
                                          });

            return response()->json($payments, 200);
        } catch (ValidationException $e) {
            Log::error($e);
            return response()->json(['error' => 'Error de validación: ' . $e->getMessage()], 400);
        } catch (QueryException $e) {
            Log::error($e);
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }

    public function branch_payment_show_bonus(Request $request)
    {
        try {
            $request->validate([
                'branch_id' => 'required|exists:branches,id',
            ]);

            $branchId = $request->branch_id;
            $bonusPay = [];
            // Obtener la fecha actual
                $day = Carbon::today();

                // Consultar los pagos realizados en el día actual para la sucursal dada
                $bonus = ProfessionalPayment::where('branch_id', $branchId)
                    ->whereDate('date', $day)
                    ->whereIn('type', ['Bono servicios', 'Bono convivencias'])
                    ->get();
                foreach($bonus as $bono){
                    $professional = $bono['professional'];
                    $bonusPay[] = [
                        'name' => $professional->name,
                                'image_url' => $professional->image_url,
                                'bonus' => $bono['type'],
                                'amount' => $bono['amount'],
                    ];
                }
            return response()->json(['bonus' => $bonusPay], 200, [], JSON_NUMERIC_CHECK);
        } catch (ValidationException $e) {
            Log::error($e);
            return response()->json(['error' => 'Error de validación: ' . $e->getMessage()], 400);
        } catch (QueryException $e) {
            Log::error($e);
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'id' => 'required|numeric|exists:professionals_payments,id',
                'amount' => 'required|numeric'
            ]);
            $payment = ProfessionalPayment::findOrFail($data['id']);
                $payment->amount = $data['amount'];
                $payment->save();

                // 2. Actualizar el monto correspondiente en finances
                Finance::where('professional_payment_id', $data['id'])
                    ->update(['amount' => $data['amount']]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Pago editado exitosamente',
        ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Error de validación en pago', [
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 400);
            
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Recurso no encontrado', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Recurso no encontrado'], 404);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al procesar edicion de pago', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Error interno del servidor'], 500);
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
            // Buscar el pago de profesional a eliminar
            $professionalPayment = ProfessionalPayment::findOrFail($data['id']);

            Finance::where('professional_payment_id', $data['id'])->delete();
            // Buscar y actualizar los carros asociados para establecer el campo professional_payment_id en null
            //Car::where('professional_payment_id', $data['id'])->update(['professional_payment_id' => null]);

            // Eliminar el pago de profesional
            $professionalPayment->delete();

            return response()->json(['message' => 'Pago de profesional eliminado correctamente'], 200);
        } catch (QueryException $e) {
            Log::error($e);
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error($e);
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
            // Usamos el día 1 para asegurar que todos los meses son válidos
            $monthName = Carbon::createFromDate($year, $month, 1)->locale('es_ES')->monthName;
            $monthlyEarnings[$monthName] = 0;
        }

        // Actualizar los valores de ganancias en el array
        foreach ($result as $row) {
            $monthName = Carbon::createFromDate($year, $row->month)->locale('es_ES')->monthName;
            $monthlyEarnings[$monthName] = intval($row->earnings);
            $totalEarnings += intval($row->earnings);
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

                    return response()->json(['monthlyEarnings' => $monthlyEarnings, 'totalEarnings' => number_format(round($totalEarnings, 0), 2), 'averageEarnings' => number_format(round($averageEarnings, 0), 2), 'metaCant' => $meta->count(), 'metaAmount' => number_format(round($meta->sum('amount'), 0), 2)], 200);
                } catch (\Throwable $th) {
                    Log::error($th);
                    return response()->json(['msg' => $th->getMessage() . 'Error al insertar el producto'], 500);
                }
    }

}
