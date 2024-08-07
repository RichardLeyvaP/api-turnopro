<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Car;
use App\Models\CashierSale;
use App\Models\Finance;
use App\Models\OperationTip;
use App\Models\Professional;
use App\Models\ProfessionalPayment;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OperationTipController extends Controller
{
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
            if($data['coffe_percent']){
                $finance = Finance::orderBy('control', 'desc')->first(); 
            /*if ($finance !== null) {
                $finance->amount = $finance->amount + $data['coffe_percent'];
                $finance->save();
            } else {
                $finance = Finance::where('branch_id', $data['branch_id'])orderBy('control', 'desc')->first();*/
                if ($finance) {
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
            }
            $professional = Professional::find($data['professional_id']);
            //$finance = Finance::where('branch_id', $data['branch_id'])->where('expense_id', 4)->whereDate('data', Carbon::now())orderBy('control', 'desc')->first();
            /*$finance = Finance::orderBy('control', 'desc')->first();              
            if($finance !== null)
            {
                $control = $finance->control+1;
            }
            else {
                $control = 1;
            }*/
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
                        'date' => $query->date.' '.Carbon::parse($query->created_at)->format('H:i:s'),
                        'type' => $query->type,
                        'coffe_percent' => $query->coffe_percent,
                        'amount' => $query->amount,
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
                        'amount' => $query->amount,
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
                        'date' => $query->date,
                        'type' => $query->type,
                        'coffe_percent' => $query->coffe_percent,
                        'amount' => $query->amount
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
                'date' => 'Total',
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
                        'amount' => $query->amount
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
            
            $cashierSales = CashierSale::where('professional_id', $request->professional_id)->where('branch_id', $data['branch_id'])->where('pay', 1)->where('paycashier', 0)->get();
            foreach ($cashierSales as $cashierSale) {
                $product = $cashierSale['productStore']['product'];
                $sales[] = [
                    'id' => $cashierSale['id'],
                    'price' => intval($cashierSale['price']),
                    'pay' => $cashierSale['pay'],
                    'cant' => $cashierSale['cant'],
                    'name' => $product['name'],
                    'image_product' => $product['image_product']
                ];
            }
            return response()->json(['cars' => $cars, 'sales' => $sales], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
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
}
