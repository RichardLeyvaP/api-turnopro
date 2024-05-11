<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Order;
use App\Models\Professional;
use App\Models\ProfessionalPayment;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
            ]);

            $professionalId = $request->professional_id;
            $branchId = $request->branch_id;

            $startOfMonth = now()->startOfMonth()->toDateString();
            $endOfMonth = now()->endOfMonth()->toDateString();

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
                                                'amount' => $query->amount
                                            ];
                                          })->sortByDesc('date')->values();
                                          
                                          //pendiente por pagar
                                          $retention = number_format(Professional::where('id', $request->professional_id)->value('retention') / 100, 2);

                                          ///$paymentIds = $payments->pluck('id');
                                          $cars = Car::whereHas('reservation', function ($query) use ($request, $startOfMonth, $endOfMonth) {
                    $query->where('branch_id', $request->branch_id)->whereDate('data', '>=', $startOfMonth)->whereDate('data', '<=', $endOfMonth);
                })
                ->with(['clientProfessional.client', 'reservation'])
                ->whereHas('clientProfessional', function ($query) use ($request) {
                    $query->where('professional_id', $request->professional_id);
                })
                ->where('pay', 1)
                ->get();
                //pagado
                $carPagado = $cars->where('professional_payment_id', '!=', null);
                $carIdsPay = $carPagado->pluck('id');
                $propinaPay = $carPagado->sum('tip');
                $propinaPay80 = $propinaPay*0.80;
                $orderServPay = Order::whereIn('car_id', $carIdsPay)->where('is_product', 0)->get();
                $servMountPay = $orderServPay->sum('percent_win');
                $pagadoMount = $servMountPay-($servMountPay*$retention) + $propinaPay80 ? $servMountPay-($servMountPay*$retention) + $propinaPay80 : 0;
                $metaPagado = $orderServPay->filter(function ($order) {
                    return $order->percent_win == $order->price;
                });
                $clientAttended = $carPagado->count() ? $carPagado->count() : 0;
                $servCant = $carPagado->sum('amount') ? $carPagado->sum('amount') : 0;
                $amountGenerate = $carPagado->sum('amount') ? $carPagado->sum('amount') : 0;
                $propina80 = $carPagado->sum('tip')*0.80 ? $carPagado->sum('tip')*0.80 : 0;
                $metaCant = $metaPagado->count() ? $metaPagado->count() : 0;
                $metaAmount = $metaPagado->sum('percent_win') ? $metaPagado->sum('percent_win') : 0;
                $retention = $orderServPay->sum('percent_win')*$retention ? $orderServPay->sum('percent_win')*$retention : 0;
                $winnerRetention = $orderServPay->sum('percent_win')-($orderServPay->sum('percent_win')*$retention) ? $orderServPay->sum('percent_win')-($orderServPay->sum('percent_win')*$retention) : 0;
                $winnerAmount = $orderServPay->sum('percent_win')-($orderServPay->sum('percent_win')*$retention)+($carPagado->sum('tip')*0.80) ? $orderServPay->sum('percent_win')-($orderServPay->sum('percent_win')*$retention)+($carPagado->sum('tip')*0.80) : 0;
                /*$detailPay = [
                    //'clientAtended' => $carPagado->count() ? $carPagado->count() : 0,
                    //'servCant' => $orderServPay->count() ? $orderServPay->count() : 0,
                    //'amountGenerate' => $carPagado->sum('amount') ? $carPagado->sum('amount') : 0,
                    //'propina80' => $carPagado->sum('tip')*0.80 ? $carPagado->sum('tip')*0.80 : 0,
                    //'metaCant' => $metaPagado->count() ? $metaPagado->count() : 0,
                    //'metaAmount' => $metaPagado->sum('percent_win') ? $metaPagado->sum('percent_win') : 0,
                    //'retention' => $orderServPay->sum('percent_win')*$retention ? $orderServPay->sum('percent_win')*$retention : 0,
                    //'winnerRetention' => $orderServPay->sum('percent_win')-($orderServPay->sum('percent_win')*$retention) ? $orderServPay->sum('percent_win')-($orderServPay->sum('percent_win')*$retention) : 0,
                    //'winnerAmount' => $orderServPay->sum('percent_win')-($orderServPay->sum('percent_win')*$retention)+($carPagado->sum('tip')*0.80) ? $orderServPay->sum('percent_win')-($orderServPay->sum('percent_win')*$retention)+($carPagado->sum('tip')*0.80) : 0
                ];*/
                //Pendiente
                $carPendiente = $cars->where('professional_payment_id', null);
                $carIdsPend = $carPendiente->pluck('id');
                $propinaPen = $carPendiente->sum('tip');
                $propinaPend80 = $propinaPen*0.80;
                $orderServPen = Order::whereIn('car_id', $carIdsPend)->where('is_product', 0)->get();
                $servMountPen = $orderServPen->sum('percent_win');
                $pendienteMount = $servMountPen - ($servMountPen*$retention) + $propinaPend80 ? $servMountPen - ($servMountPen*$retention) + $propinaPend80 : 0;
                /*->map(function ($car) use ($retention, $request) {
                    $orderServ = Order::where('car_id', $car->id)
                        ->where('is_product', 0)
                        ->get();

                    $client = $car->clientProfessional->client;

                    return [
                        'id' => $car->id,
                        'professional_id' => $data['professional_id'],
                        'clientName' => $client->name . ' ' . $client->surname,
                        'client_image' => $client->client_image ? $client->client_image : 'comments/default.jpg',
                        'branch_id' => $data['branch_id'],
                        'data' => $car->reservation->data,
                        'attendedClient' => 1,
                        'services' => $orderServ->count(),
                        'totalServices' => $retention ? $orderServ->sum('percent_win') - ($orderServ->sum('percent_win') * $retention) : $orderServ->sum('percent_win'),
                        'clientAleator' => $car->select_professional,
                        'amountGenerate' => $car->amount,
                        'tip' => $car->tip * 0.80
                    ];
                });*/


            return response()->json(['payments' => $payments, 'pendiente' => $pendienteMount, 'pagado' => $pagadoMount, 'clientAtended' => $clientAttended, 'servCant' => $servCant, 'amountGenerate' => $amountGenerate, 'propina80' => $propina80, 'metaCant' => $metaCant, 'metaAmount' => $metaAmount, 'retention' => $retention, 'winnerRetention' => $winnerRetention, 'winnerAmount' => $winnerAmount], 200);
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
}
