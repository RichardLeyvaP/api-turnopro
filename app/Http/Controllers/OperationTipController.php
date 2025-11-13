<?php

namespace App\Http\Controllers;

use App\Models\Advance;
use App\Models\Branch;
use App\Models\BranchProfessional;
use App\Models\Car;
use App\Models\CashierSale;
use App\Models\Finance;
use App\Models\OperationTip;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Professional;
use App\Models\ProfessionalPayment;
use App\Models\Trace;
use App\Models\WorkerPurchase;
use App\Services\ProfessionalPaymentService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use function PHPSTORM_META\type;

class OperationTipController extends Controller
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required',
                'professional_id' => 'required',
                'amount' => 'required|numeric',
                'coffe_percent' => 'required|numeric',
                'type' => 'required|string',
            ]);
            $control = 0;
            /*$operationTip = OperationTip::where('branch_id', $data['branch_id'])->where('professional_id', $data['professional_id'])->whereDate('date', Carbon::now())->first();
            if ($operationTip !== null) {
                $operationTip->amount = $operationTip->amount + $data['amount'];
                $operationTip->coffe_percent = $operationTip->coffe_percent + $data['coffe_percent'];
                $operationTip->save();
            } else {*/
                $branch = Branch::find($data['branch_id']);
                $operationTip = new OperationTip();
                $operationTip->branch_id = $data['branch_id'];
                $operationTip->professional_id = $data['professional_id'];
                $operationTip->date = Carbon::now();
                $operationTip->amount = $data['amount'];
                $operationTip->type = $data['type'];
                $operationTip->coffe_percent = $data['coffe_percent'];
                // Guardar el modelo
                $operationTip->save();
            //}
        
            Log::info($request->input('car_ids'));
            if ($request->input('car_ids')) {
                // Actualizar carros con professional_payment_id
                Log::info('entra a pago los carros');
                $carIds = $request->input('car_ids');
                Car::whereIn('id', $carIds)->update(['operation_tip_id' => $operationTip->id]);
            }
            /*if($data['coffe_percent']){
                $finance = Finance::orderBy('control', 'desc')->first(); 
            /*if ($finance !== null) {
                $finance->amount = $finance->amount + $data['coffe_percent'];
                $finance->save();
            } else {
                $finance = Finance::where('branch_id', $data['branch_id'])orderBy('control', 'desc')->first();*/
                /*if ($finance) {
                    $control = $finance->control + 1;
                } else {
                    $control = 1;
                }
                $finance = new Finance();
                $finance->control = $control++;
                $finance->operation = 'Ingreso';
                $finance->amount = $data['coffe_percent'];
                $finance->comment = 'Ingreso por concepto de 10% de propinas en sucursal  '.$branch->name;
                $finance->branch_id = $data['branch_id'];
                $finance->type = 'Sucursal';
                $finance->revenue_id = 6;
                $finance->data = Carbon::now();
                $finance->file = '';
                $finance->save();
            //}
            }*/
            $professional = Professional::find($data['professional_id']);
            $finance = Finance::orderBy('control', 'desc')->first();
                            
            if($finance !== null)
            {
                $control = $finance->control+1;
            }
            else {
                $control = 1;
            }
            Log::info($control);
            $finance = new Finance();
                            $finance->control = $control++;
                            $finance->operation = 'Gasto';
                            $finance->amount = $data['amount'];
                            $finance->comment = 'Gasto por pago de 10% de propinas a cajero (a) '.$professional->name;
                            $finance->branch_id = $data['branch_id'];
                            $finance->type = 'Sucursal';
                            $finance->expense_id = 4;
                            $finance->data = Carbon::now();                
                            $finance->file = '';
                            $finance->save();
            return response()->json($operationTip, 201);
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

            $payments1 = OperationTip::where('professional_id', $professionalId)
                ->where('branch_id', $branchId)
                ->get()->map(function ($query) {
                    return [
                        'id' => $query->id,
                        'branch_id ' => $query->branch_id,
                        'professional_id' => $query->professional_id,
                        'date' => $query->date.' '.Carbon::parse($query->created_at)->format('H:i'),
                        'type' => $query->type,
                        'coffe_percent' => round($query->coffe_percent, 2),
                        'amount' => round($query->amount, 2),
                        'car' => 1
                    ];
                });

                $payments2 = ProfessionalPayment::where('professional_id', $professionalId)
                ->where('branch_id', $branchId)
                ->get()
                ->map(function ($query) {
                    return [
                        'id' => $query->id,
                        'branch_id' => $query->branch_id,
                        'professional_id' => $query->professional_id,
                        'date' => $query->date.' '.Carbon::parse($query->created_at)->format('H:i'),
                        'type' => $query->type,
                        'coffe_percent' => 0, // Valor por defecto ya que no existe en esta tabla
                        'amount' => round($query->amount, 2),
                        'car' => 0
                    ];
                });

                $combinedPayments = $payments1->concat($payments2)
                             ->sortByDesc('date')
                             ->values();

            return response()->json($combinedPayments, 200);
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

    public function operation_tip_show(Request $request)
    {
        try {
            $request->validate([
                'branch_id' => 'required|exists:branches,id',
            ]);

            $branchId = $request->branch_id;

            $payments = OperationTip::where('branch_id', $branchId)
                ->get()->map(function ($query) use ($branchId){
                    $professional = $query->professional;
                    return [
                        'id' => $query->id,
                        'branch_id ' => $branchId,
                        'professional_id' => $query->professional_id,
                        'nameProfessional' => $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname,
                        'image_url' => $professional->image_url,
                        'date' => $query->date,
                        'type' => $query->type,
                        'coffe_percent' => $query->coffe_percent,
                        'amount' => round($query->amount, 2),
                        
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

    public function operation_tip_periodo(Request $request)
    {
        try {
            $request->validate([
                'branch_id' => 'required|exists:branches,id',
                'professional_id' => 'required|exists:professionals,id',
                'startDate' => 'required|date',
                'endDate' => 'required|date'
            ]);

            $professionalId = $request->professional_id;
            $branchId = $request->branch_id;

            $payments = OperationTip::where('branch_id', $branchId)->where('professional_id', $professionalId)->whereDate('date', '>=', $request->startDate)->whereDate('date', '<=', $request->endDate)
                ->get()->map(function ($query) use ($branchId){
                    $professional = $query->professional;
                    return [
                        'id' => $query->id,
                        'branch_id ' => $branchId,
                        'professional_id' => $query->professional_id,
                        'date' => $query->date.' '.Carbon::parse($query->created_at)->format('H:i'),
                        'type' => $query->type,
                        'coffe_percent' => round($query->coffe_percent, 2),
                        'amount' => round($query->amount, 2),
                        'car' => 1
                    ];
                });

                $payments2 = ProfessionalPayment::where('professional_id', $professionalId)
                ->where('branch_id', $branchId)
                ->get()
                ->map(function ($query) {
                    return [
                        'id' => $query->id,
                        'branch_id' => $query->branch_id,
                        'professional_id' => $query->professional_id,
                        'date' => $query->date.' '.Carbon::parse($query->created_at)->format('H:i'),
                        'type' => $query->type,
                        'coffe_percent' => 0, // Valor por defecto ya que no existe en esta tabla
                        'amount' => round($query->amount, 2),
                        'car' => 0
                    ];
                });

                $combinedPayments = $payments->concat($payments2)
                             ->sortByDesc('date')
                             ->values();

                // Calcular totales
            $totalCoffePercent = $combinedPayments->sum('coffe_percent');
            $totalAmount = $combinedPayments->sum('amount');
                if($totalAmount){
            // Agregar fila de total
            $totalRow = [
                'id' => '',
                'branch_id' => '',
                'professional_id' => '',
                'date' => 'Total',
                'type' => '',
                'coffe_percent' => $totalCoffePercent,
                'amount' => $totalAmount
            ];

            $combinedPayments->push($totalRow);
                }
           
            return response()->json($combinedPayments, 200);
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

    public function operation_tip_periodo_Ant(Request $request)
    {
        try {
            $request->validate([
                'branch_id' => 'required|exists:branches,id',
                'startDate' => 'required|date',
                'endDate' => 'required|date'
            ]);

            $branchId = $request->branch_id;

            $payments = OperationTip::where('branch_id', $branchId)->whereDate('date', '>=', $request->startDate)->whereDate('date', '<=', $request->endDate)
                ->get()->map(function ($query) use ($branchId){
                    $professional = $query->professional;
                    return [
                        'id' => $query->id,
                        'branch_id ' => $branchId,
                        'professional_id' => $query->professional_id,
                        'nameProfessional' => $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname,
                        'image_url' => $professional->image_url,
                        'date' => $query->date,
                        'type' => $query->type,
                        'coffe_percent' => $query->coffe_percent,
                        'amount' => round($query->amount, 2)
                    ];
                });

                // Calcular totales
            $totalCoffePercent = $payments->sum('coffe_percent');
            $totalAmount = $payments->sum('amount');
                if($totalAmount){
            // Agregar fila de total
            $totalRow = [
                'id' => '',
                'branch_id' => '',
                'professional_id' => '',
                'nameProfessional' => 'Total',
                'image_url' => '',
                'date' => '',
                'type' => '',
                'coffe_percent' => $totalCoffePercent,
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

    public function cashier_car_notpay(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric|exists:professionals,id'
            ]);
              Log::info('Estas son las trazas');
            $cashier = Professional::where('id', $request->professional_id)->first();
            $nameCashier = $cashier->name;
            $branch = Branch::where('id', $data['branch_id'])->first();

            // Obtener el salario del profesional en esta sucursal
            $branchProfessional = BranchProfessional::where('branch_id', $data['branch_id'])
                ->where('professional_id', $data['professional_id'])
                ->first();
            $products = $this->professionalPaymentService->calculateProductCommissionsNopay($data, $branchProfessional, $cashier);
            $nameBranch = $branch->name;

            $traces = Trace::where('branch', $nameBranch)
                ->where('cashier', $nameCashier)
                ->where('operation', 'Paga Carro')
                ->get('details');
                 // Array para almacenar los IDs de los carros
                $carIds = [];
                //$car_ids = [];

                // Expresión regular para extraer los números después de 'Carro:'
                $regex = '/Carro:\s*(\d+)/';
                Log::info('Estas son las trazas');
                // Iterar sobre los detalles y extraer los IDs
                foreach ($traces as $trace) {
                    if (preg_match($regex, $trace->details, $matches)) {
                        $carIds[] = (int) $matches[1]; // El ID del carro está en $matches[1]
                    }
                }
                Log::info('$Id carros con propinas cajera', $carIds);
            //$retention =  number_format(Professional::where('id', $data['professional_id'])->first()->retention/100, 2);
            $cars = Car::where('operation_tip_id', Null)->whereHas('reservation', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->with(['reservation', 'clientProfessional.client', 'clientProfessional.professional'])->where('pay', 1)->where('tip', '>', 0)->whereIn('id', $carIds)->get()->map(function ($car) {
                //$ordersServices = count($car->orders->where('is_product', 0));
                //$orderServ = Order::where('car_id', $car->id)->where('is_product', 0)->get();
                //$tipProfessional = $car->tip * 0.80;
                //$rest = $car->tip - $tipProfessional;
                $tipCashier = $car->tip * 0.10;
                $tipCoffe = $car->tip * 0.10;
                $professional = $car->clientProfessional->professional;
                $client = $car->clientProfessional->client;
                return [
                    'id' => $car->id,
                    'professional_id' => $professional->id,
                    'clientName' => $client->name,
                    'client_image' => $client->client_image ? $client->client_image : 'comments/default.jpg',
                    'professionalName' => $professional->name,
                    'image_url' => $professional->image_url,
                    'branch_id' => $car->reservation->branch_id,
                    'data' => $car->reservation->data,
                    'tip' => $car->tip,
                    'tipCashier' => $tipCashier,
                    'tipCoffe' => $tipCoffe
                ];
            });

            $sales = [];
            
            $cashierSales = CashierSale::with([
            'productStore' => function ($query) {
                $query->withTrashed(); // incluye ProductStore eliminados
            },
            'productStore.product' => function ($query) {
                $query->withTrashed(); // incluye Product eliminados
            }
        ])
        ->where('professional_id', $request->professional_id)
        ->where('branch_id', $data['branch_id'])
        ->where('pay', 1)
        ->where('paycashier', 0)
        ->get();
            foreach ($cashierSales as $cashierSale) {
                $productStore = $cashierSale->productStore;
                $product = $productStore ? $productStore->product : null;

                // Si incluso con withTrashed() sigue siendo null, saltamos
                if (!$product) {
                    // Opcional: usar datos por defecto
                    $sales[] = [
                        'id' => $cashierSale->id,
                        'price' => round($cashierSale->price, 2),
                        'pay' => $cashierSale->pay,
                        'cant' => $cashierSale->cant,
                        'name' => '[Producto eliminado]',
                        'image_product' => null
                    ];
                    continue;
                }

                $sales[] = [
                    'id' => $cashierSale->id,
                    'price' => round($cashierSale->price, 2),
                    'pay' => $cashierSale->pay,
                    'cant' => $cashierSale->cant,
                    'name' => $product->name,
                    'image_product' => $product->image_product
                ];
            }

            $payments = $this->professionalPaymentService->calculatePayments($data);

            return response()->json(['cars' => $cars, 'sales' => $products, 'payments' => $payments], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
        }
    }

    public function cashier_car_salary_notpay(Request $request)
    {
        try {
            Log::info('Iniciando Solicitud de adelantos', ['request' => $request->all()]);
            $data = $request->validate([
                'branch_id' => 'required|numeric|exists:branches,id',
                'professional_id' => 'required|numeric|exists:professionals,id',
                'charge' => 'nullable|string',
            ]);
            $payments = $this->professionalPaymentService->calculatePayments($data);
            Log::info('Respuesta del ProfessionalPaymentService', [
                'response' => $payments
            ]);
            // Obtener información del cajero, sucursal y su salario en esa sucursal
            Log::info('Buscando información del profesional y sucursal');
            $professional = Professional::where('id', $data['professional_id'])->first();
            $branch = Branch::where('id', $data['branch_id'])->first();

            
            Log::info('Información del profesional', ['name' => $professional->name]);
            Log::info('Información de la sucursal', ['name' => $branch->name]);
                       

        Log::info('Resumen de cálculos', [
            'totales_brutos' => [
                //'sales' => $totalSalesBruto,
                'tips' => $payments['tips']['tip_neto'],
                'advances' => $payments['advances']['total_advance'],
                'purchases' => $payments['workerPurchases']['total_purchases'],
                //'orders' => $totalOrdersCommissionBruto,
                'products' => $payments['products']['total_commission'],
                'salary' => $payments['salary']['salary_bruto'],
                'services' => $payments['cars']['total_combined']
            ],
            'totales_netos' => [
                //'sales' => $totalSalesNeto,
                'tips' => $payments['tips']['tip_neto'],
                'advances' => $payments['advances']['total_advance'],
                'purchases' => $payments['workerPurchases']['total_purchases'],
                //'orders' => $totalOrdersCommissionNeto,
                'products' => $payments['products']['commission_neto'],
                'salary' => $payments['salary']['salary_neto'],
                'services' => $payments['cars']['total_neto']
            ],
            'retencion_total' => [
                //'sales' => $totalSalesBruto - $totalSalesNeto,
                'tips' => $payments['tips']['tip_neto'],
                'advances' => $payments['advances']['total_advance'],
                'purchases' => $payments['workerPurchases']['total_purchases'],
                //'orders' => $totalOrdersCommissionBruto - $totalOrdersCommissionNeto,
                'products' => $payments['products']['retention_amount'],
                'salary' => $payments['salary']['retention_salary'],
                'services' => $payments['cars']['total_neto']
            ]
        ]);
        

        return response()->json([
            // Totales
            //'total_sales' => round($totalSalesNeto, 2),
            'total_tip_cashier' => $payments['tips']['tip_neto'],
            'total_advance' => $payments['advances']['total_advance'],
            'total_pruchase' => $payments['workerPurchases']['total_purchases'],
            'salary' => $payments['salary']['salary_neto'],
            'retention_salary' => $payments['salary']['retention_salary'],
            //'total_orders' => round($totalOrdersCommissionNeto, 2),
            'total_services' => $payments['cars']['total_neto'],
            'retention_services' => $payments['cars']['retention_amount'],
            'total_tip_car' => $payments['cars']['total_tips'],
            'total' => $payments['totalNeto'],
            'total_products' => $payments['products']['commission_neto'],
            'total_retention_products' => $payments['products']['retention_amount'],

            
            // IDs
            'sales_ids' => $payments['products']['sales_ids'],
            'tip_ids' => $payments['tips']['tip_ids'],
            'advance_ids' => $payments['advances']['advance_ids'],
            'purchase_ids' => $payments['workerPurchases']['purchase_ids'],
            'order_ids' => $payments['products']['order_ids'],
            'car_ids' => $payments['cars']['car_ids'],
        ], 200);


        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    public function cashier_car_notpay_original(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            //$retention =  number_format(Professional::where('id', $data['professional_id'])->first()->retention/100, 2);
            $cars = Car::where('operation_tip_id', Null)->whereHas('reservation', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->with(['reservation', 'clientProfessional.client', 'clientProfessional.professional'])->where('pay', 1)->where('tip', '>', 0)->get()->map(function ($car) {
                //$ordersServices = count($car->orders->where('is_product', 0));
                //$orderServ = Order::where('car_id', $car->id)->where('is_product', 0)->get();
                //$tipProfessional = $car->tip * 0.80;
                //$rest = $car->tip - $tipProfessional;
                $tipCashier = $car->tip * 0.10;
                $tipCoffe = $car->tip * 0.10;
                $professional = $car->clientProfessional->professional;
                $client = $car->clientProfessional->client;
                return [
                    'id' => $car->id,
                    'professional_id' => $professional->id,
                    'clientName' => $client->name . ' ' . $client->surname,
                    'client_image' => $client->client_image ? $client->client_image : 'comments/default.jpg',
                    'professionalName' => $professional->name . ' ' . $professional->surname,
                    'image_url' => $professional->image_url,
                    'branch_id' => $car->reservation->branch_id,
                    'data' => $car->reservation->data,
                    'tip' => $car->tip,
                    'tipCashier' => $tipCashier,
                    'tipCoffe' => $tipCoffe
                ];
            });

            $professionals = Professional::whereHas('branches', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
               })->whereHas('charge', function ($query) {
                $query->where('name', 'Cajero (a)');
            })->get()->map(function ($query){
                return [
                    'id' => $query->id,
                    'name' => $query->name.' '.$query->surname.' '.$query->second_surname,
                    'charge' => $query->charge->name
                ];
               });
            return response()->json(['cars' => $cars,'professionals' => $professionals], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar ls ordenes"], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OperationTip $operationTip)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OperationTip $operationTip)
    {
        //
    }


    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'type' => 'required|numeric'
            ]);
            if($data['type'] == 1){
                $operationTip = OperationTip::findOrFail($data['id']);

                // Buscar y actualizar los carros asociados para establecer el campo professional_payment_id en null
                Car::where('operation_tip_id', $data['id'])->update(['operation_tip_id' => null]);
    
                $finance = Finance::where('branch_id', $operationTip->branch_id)->where('revenue_id', 6)->whereDate('data', $operationTip->date)->first();
                if ($finance !== null) {
                    $amount = $finance->amount - $operationTip->coffe_percent;
                    if ($amount <= 0) {
                        $finance->delete();
                    } else {
                        $finance->amount = $amount;
                        $finance->save();
                    }
                }
    
    
                // Eliminar el pago de profesional
                $operationTip->delete();
    
            }else{
                $professionalPayment = ProfessionalPayment::findOrFail($data['id']);
                $finance = Finance::where('branch_id', $professionalPayment->branch_id)->where('expense_id', 4)->whereDate('data', $professionalPayment->date)->where('amount', $professionalPayment->amount)->first();
                CashierSale::where('paycashier', $data['id'])->update(['paycashier' => 0]);
                $professionalPayment->delete();
                $finance->delete();
            }
            return response()->json(['message' => 'Pago de profesional eliminado correctamente'], 200);
        } catch (QueryException $e) {
            Log::error($e);
            return response()->json(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }

    //Métodos dashboard no Administrador
    public function calculate(Request $request)
    {
        // Validación de entrada
        $validator = Validator::make($request->all(), [
            'professional_id' => [
                'required',
                'integer',
                Rule::exists('professionals', 'id')
            ],
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')
            ]
        ], [
            'professional_id.exists' => 'El profesional no existe',
            'branch_id.exists' => 'La sucursal no existe'
        ]);

        if ($validator->fails()) {
            Log::warning('Validación fallida para cálculo de pagos', [
                'errors' => $validator->errors()->all(),
                'input' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            
            Log::info('Iniciando cálculo de pagos', [
                'professional_id' => $data['professional_id'],
                'branch_id' => $data['branch_id']
            ]);

            $professional = Professional::where('id', $data['professional_id'])->first();
            $branch = Branch::where('id', $data['branch_id'])->first();

            // Llamar al servicio
            $result = $this->professionalPaymentService->calculatePayments($data);
            
            Log::info('Cálculo de pagos completado exitosamente', [
                'professional_id' => $data['professional_id'],
                'branch_id' => $data['branch_id'],
                'total_neto' => $result['total_neto'] ?? null
            ]);

            // Obtener el rango de fechas del mes anterior
            // Rangos de fechas
        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();
            $previousMonthStart = now()->subMonth()->startOfMonth();
            $previousMonthEnd = now()->subMonth()->endOfMonth();
            $productPayments = ProfessionalPayment::where('professional_id', $data['professional_id'])
                ->where('branch_id', $data['branch_id'])
                ->where('type', 'Pago venta de Productos')
                ->whereBetween('date', [$previousMonthStart, $previousMonthEnd])
                ->get(['id', 'amount', 'date', 'type']);

            $totalProductPayments = $productPayments->sum('amount');

            // 2. Pagos de Propinas (de OperationTip)
            $tipPayments = OperationTip::where('professional_id', $data['professional_id'])
                ->where('branch_id', $data['branch_id'])
                ->where('type', 'Pago Comision de Propinas')
                ->whereBetween('date', [$previousMonthStart, $previousMonthEnd])
                ->get(['id', 'amount', 'date']);

            $totalTipPayments = $tipPayments->sum('amount');
                        if (!$totalTipPayments) {
                            $resultTips = $this->professionalPaymentService->calculateTipsLastMoth($data, $branch, $professional);
                            $totalTipPayments = $resultTips['tip_neto'];
                        }
            // 2. Pagos del mes actual (sin filtro por tipo)
            $currentMonthPayments = ProfessionalPayment::where('professional_id', $data['professional_id'])
                ->where('branch_id', $data['branch_id'])
                ->whereBetween('date', [$currentMonthStart, $currentMonthEnd])
                 ->sum('amount');

            // 3. Pagos del mes anterior (sin filtro por tipo)
            $previousMonthPayments = ProfessionalPayment::where('professional_id', $data['professional_id'])
                ->where('branch_id', $data['branch_id'])
                ->whereBetween('date', [$previousMonthStart, $previousMonthEnd])
                 ->sum('amount');

            $advances = $this->professionalPaymentService->calculateAdvancesDetails($data);

            $resultTips = $this->professionalPaymentService->calculateTipsDetails($data, $branch, $professional);
            $branchProfessional = BranchProfessional::where('branch_id', $data['branch_id'])
            ->where('professional_id', $data['professional_id'])
            ->first();
             $resultProducts = $this->professionalPaymentService->calculateProductCommissionsWithDetails($data, $branchProfessional, $professional);


            return response()->json(['payments' => $result, 'total_product_ant' => $totalProductPayments, 'total_product_act' => $resultProducts['commission_neto'], 'total_tip_ant' => $totalTipPayments, 'total_tip_act' => $resultTips['tip_neto'], 'advances' => $advances, 'current_charged' => $currentMonthPayments, 'previous_charged' => $previousMonthPayments]);

        } catch (\Exception $e) {
            Log::error('Error al calcular pagos', [
                'professional_id' => $request->input('professional_id'),
                'branch_id' => $request->input('branch_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno al calcular los pagos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function professional_branch_products(Request $request)
    {
         // Validación de entrada
        $validator = Validator::make($request->all(), [
            'professional_id' => [
                'required',
                'integer',
                Rule::exists('professionals', 'id')
            ],
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')
            ],
            'year' => [
                'required',
                'integer',
                'min:2000',
            ],
            'month' => [
                'required',
                'integer',
                'min:1',
                'max:12'
            ],
            'charge' => [
                'nullable',
                'string',
                Rule::exists('charges', 'name')
            ]
        ], [
            'professional_id.exists' => 'El profesional no existe',
            'branch_id.exists' => 'La sucursal no existe',
            'charge.exists' => 'El cargo no existe',
            'year.required' => 'El año es requerido',
            'year.integer' => 'El año debe ser un número entero',
            'year.min' => 'El año debe ser mayor o igual a 2000',
            'month.required' => 'El mes es requerido',
            'month.integer' => 'El mes debe ser un número entero',
            'month.min' => 'El mes debe ser mayor o igual a 1',
            'month.max' => 'El mes debe ser menor o igual a 12'
        ]);

        if ($validator->fails()) {
            Log::warning('Validación fallida para cálculo de pagos', [
                'errors' => $validator->errors()->all(),
                'input' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            
            // Calcular fechas basadas en año y mes
            $startDate = Carbon::create($data['year'], $data['month'], 1)->startOfMonth();
            $endDate = Carbon::create($data['year'], $data['month'], 1)->endOfMonth();
            
            // Agregar fechas al array de datos para el servicio
            $data['startDate'] = $startDate->format('Y-m-d');
            $data['endDate'] = $endDate->format('Y-m-d');

            Log::info('Mostrando datos de venta de productos', [
                'professional_id' => $data['professional_id'],
                'branch_id' => $data['branch_id'],
                'year' => $data['year'],
                'month' => $data['month'],
                'charge' => $data['charge'] ?? null
            ]);

            $professional = Professional::where('id', $data['professional_id'])->first();
            $branch = Branch::where('id', $data['branch_id'])->first();
            $branchProfessional = BranchProfessional::where('branch_id', $data['branch_id'])
            ->where('professional_id', $data['professional_id'])
            ->first();
            // Llamar al servicio
            $result = $this->professionalPaymentService->calculateProductCommissionsWithDetails($data, $branchProfessional, $professional);
            
            

            return response()->json(['products' => $result]);

        } catch (\Exception $e) {
            Log::error('Error al mostrar datos de venta de productos', [
                'professional_id' => $request->input('professional_id'),
                'branch_id' => $request->input('branch_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del sistema',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function professional_branch_tips(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'professional_id' => [
                'required',
                'integer',
                Rule::exists('professionals', 'id')
            ],
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')
            ],
            'year' => [
                'required',
                'integer',
                'min:2000',
            ],
            'month' => [
                'required',
                'integer',
                'min:1',
                'max:12'
            ],
            'charge' => [
                'nullable',
                'string',
                Rule::exists('charges', 'name')
            ]
        ], [
            'professional_id.exists' => 'El profesional no existe',
            'branch_id.exists' => 'La sucursal no existe',
            'charge.exists' => 'El cargo no existe',
            'year.required' => 'El año es requerido',
            'year.integer' => 'El año debe ser un número entero',
            'year.min' => 'El año debe ser mayor o igual a 2000',
            'month.required' => 'El mes es requerido',
            'month.integer' => 'El mes debe ser un número entero',
            'month.min' => 'El mes debe ser mayor o igual a 1',
            'month.max' => 'El mes debe ser menor o igual a 12'
        ]);


        if ($validator->fails()) {
            Log::warning('Validación fallida para cálculo de pagos', [
                'errors' => $validator->errors()->all(),
                'input' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            // Calcular fechas basadas en año y mes
            $startDate = Carbon::create($data['year'], $data['month'], 1)->startOfMonth();
            $endDate = Carbon::create($data['year'], $data['month'], 1)->endOfMonth();
            
            // Agregar fechas al array de datos para el servicio
            $data['startDate'] = $startDate->format('Y-m-d');
            $data['endDate'] = $endDate->format('Y-m-d');
            Log::info('Mostrando datos de comision de propinas', [
                'professional_id' => $data['professional_id'],
                'branch_id' => $data['branch_id'],
                'startDate' => $data['startDate'] ?? null,
                'endDate' => $data['endDate'] ?? null,
                'charge' => $data['charge'] ?? null
            ]);

            $professional = Professional::where('id', $data['professional_id'])->first();
            $branch = Branch::where('id', $data['branch_id'])->first();

            // Llamar al servicio
            $result = $this->professionalPaymentService->calculateTipsDetails($data, $branch, $professional);
            
            

            return response()->json(['tips' => $result]);

        } catch (\Exception $e) {
            Log::error('Error al mostrar datos de comision de propinas', [
                'professional_id' => $request->input('professional_id'),
                'branch_id' => $request->input('branch_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del sistema',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
