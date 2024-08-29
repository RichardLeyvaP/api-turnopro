<?php

namespace App\Services;

use App\Models\BranchProfessional;
use App\Models\BranchRuleProfessional;
use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\Finance;
use App\Models\Order;
use App\Models\Professional;
use App\Models\ProfessionalPayment;
use App\Models\Retention;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class MetaService
{
    public function store($branch)
    {        
        // Eliminar los registros que coincidan
        Finance::where('branch_id', $branch->id)
        ->whereDate('data', Carbon::now())
        ->where('operation', 'Gasto')
        ->where(function($query) {
            $query->where('comment', 'like', '%Gasto por pago de bono de convivencias a%')
                ->orWhere('comment', 'like', '%Gasto por pago de bono de servicios a%');
        })
        ->delete();

        //Retention
        Retention::where('branch_id', $branch->id)
        ->whereDate('data', Carbon::now())->where('type', 'Services')->delete();

        ProfessionalPayment::where('branch_id', $branch->id)->whereDate('date', Carbon::now())->where(function($query) {
            $query->where('type', 'Bono convivencias')
                ->orWhere('type', 'Bono servicios');
        })->delete();
        $idService=null;
        $bonus = [];
        $percentWinSum = 0;
        $professionals = Professional::whereHas('branches', function ($query) use ($branch) {
            $query->where('branch_id', $branch->id);
        })->whereHas('charge', function ($query) {
            $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
        })->select('id', 'name', 'image_url', 'retention')->get();


        //$finance = Finance::where('branch_id', $branch->id)->where('expense_id', 5)->whereDate('data', Carbon::now())orderBy('control', 'desc')->first();
        $finance = Finance::orderBy('control', 'desc')->first();
        if ($finance !== null) {
            $control = $finance->control + 1;
        } else {
            $control = 1;
        }

        Log::info($professionals);
        foreach ($professionals as $professional) {
            Log::info($professional->id);
            $cars = Car::whereHas('reservation', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id)->whereDate('data', Carbon::now());
            })
                ->with(['clientProfessional.client', 'reservation'])
                ->whereHas('clientProfessional', function ($query) use ($professional) {
                    $query->where('professional_id', $professional->id);
                })
                ->where('pay', 1)
                ->get();
            //retention
            $retentionP = $professional->retention;
            $carIdsPay = $cars->pluck('id');
            $rules =  BranchRuleProfessional::where('professional_id', $professional->id)->whereHas('branchRule', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id)->where('estado', 0)->whereDate('data', Carbon::now());
            })->get();

            //$professionalPaymentsServices = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->where('type', 'Bono servicios')->get()->first();

            if ($rules->isEmpty()) {
                $idService = BranchServiceProfessional::where('professional_id', $professional->id)->whereHas('branchService.branch', function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id);
                })->where('meta', 1)->first();
                if ($idService != null) {
                    $orders = Order::where('branch_service_professional_id', $idService->id)->whereIn('car_id', $carIdsPay)->limit(4)->get();
                    if (!$orders->isEmpty()) {
                        $cant = $orders->count();
                        $amount = $orders->first()->price * $cant;
                        /*$filteredPayments = $professionalPayments->filter(function ($payment) {
                            return $payment->type == 'Bono convivencias';
                        })->first();*/
                        $professionalPayment = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->where('type', 'Bono convivencias')->first();
                        if ($professionalPayment == null) {
                            $professionalPayment = new ProfessionalPayment();
                        }
                            $retentionAmount = $retentionP ? $amount * $retentionP / 100 : 0;
                            $professionalPayment->branch_id = $branch->id;
                            $professionalPayment->professional_id = $professional->id;
                            $professionalPayment->date = Carbon::now();
                            $professionalPayment->amount = $amount - $retentionAmount;
                            $professionalPayment->type = 'Bono convivencias';
                            $professionalPayment->cant = $cant;
                            $professionalPayment->save();
                            $bonus[] = [
                                'name' => $professional->name,
                                'image_url' => $professional->image_url,
                                'bonus' => 'Bono convivencias',
                                'amount' => number_format(round($amount - $retentionAmount, 2), 2),
                            ];
                            $finance = new Finance();
                            $finance->control = $control++;
                            $finance->operation = 'Gasto';
                            $finance->amount = $amount - $retentionAmount;
                            $finance->comment = 'Gasto por pago de bono de convivencias a ' . $professional->name;
                            $finance->branch_id = $branch->id;
                            $finance->type = 'Sucursal';
                            $finance->expense_id = 5;
                            $finance->data = Carbon::now();
                            $finance->file = '';
                            $finance->save();
                            if($retentionP){
                                Log::info('Entra a retencion bono de convivencias'.$professional->name.$retentionAmount);
                                $retention = new Retention();
                                $retention->branch_id = $branch->id;
                                $retention->professional_id = $professional->id;
                                $retention->data = Carbon::now();
                                $retention->retention = intval($retentionAmount);
                                $retention->save();
                            }

                            foreach($orders as $order){
                                $order->meta = 1;
                                $order->percent_win = 0;
                                $order->save();
                            }
                        //}
                    }
                }
            }


            $profesionalbonus = BranchProfessional::where('professional_id', $professional->id)->where('branch_id', $branch->id)->first();

            //Venta de productos y servicios
            $orderServs = Order::whereIn('car_id', $carIdsPay)->where('is_product', 0)->get();
            $orderServPay = $orderServs->where('meta', 0)->sum('price');
            $catServices = $orderServs->count();
            if ($orderServPay >= $profesionalbonus->limit && $profesionalbonus->mountpay > 0) {
                /*$filteredPayments = $professionalPayments->filter(function ($payment) {
                    return $payment->type == 'Bono servicios';
                });*/
                $professionalPaymentService = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->where('type', 'Bono servicios')->first();
                if ($professionalPaymentService == null) {
                    $professionalPaymentService = new ProfessionalPayment();
                }
                    $retentionAmount = $retentionP ? $profesionalbonus->mountpay * $retentionP / 100 : 0;
                    $professionalPaymentService->branch_id = $branch->id;
                    $professionalPaymentService->professional_id = $professional->id;
                    $professionalPaymentService->date = Carbon::now();
                    $professionalPaymentService->amount = $profesionalbonus->mountpay - $retentionAmount;
                    $professionalPaymentService->type = 'Bono servicios';
                    $professionalPaymentService->cant = $catServices;
                    $professionalPaymentService->save();
                    $bonus[] = [
                        'name' => $professional->name,
                        'image_url' => $professional->image_url,
                        'bonus' => 'Bono servicios',
                        'amount' => number_format(round($profesionalbonus->mountpay-$retentionAmount, 2), 2),
                    ];
                    $finance = new Finance();
                    $finance->control = $control++;
                    $finance->operation = 'Gasto';
                    $finance->amount = $profesionalbonus->mountpay-$retentionAmount;
                    $finance->comment = 'Gasto por pago de bono de servicios a ' . $professional->name;
                    $finance->branch_id = $branch->id;
                    $finance->type = 'Sucursal';
                    $finance->expense_id = 5;
                    $finance->data = Carbon::now();
                    $finance->file = '';
                    $finance->save();
                    if($retentionP){
                        Log::info('Entra a retencion bono de servicios'.$professional->name.$retentionAmount);
                        $retention = new Retention();
                        $retention->branch_id = $branch->id;
                        $retention->professional_id = $professional->id;
                        $retention->data = Carbon::now();
                        $retention->retention = intval($retentionAmount);
                        $retention->save();
                    }
                //}
            }

            //retention de ganancia de servicios
            $percentWinSum = 0;
            if (!$cars->isEmpty())
                if ($retentionP) {
                    $percentWinSum = $cars->sum(function ($car) {
                        return $car->orders->sum(function ($order) {
                            return ($order->meta == 1 ? $order->price : 0) + ($order->meta == 0 ? $order->percent_win : 0);
                        });
                    });
                    if ($percentWinSum) {
                        if($retentionP){
                            Log::info('Entra a retencion de servicios'.$professional->name.$percentWinSum * $retentionP / 100);
                            $retention = new Retention();
                            $retention->branch_id = $branch->id;
                            $retention->professional_id = $professional->id;
                            $retention->data = Carbon::now();
                            $retention->retention = $percentWinSum * $retentionP / 100;
                            $retention->save();
                        }
                    }
                }
            //end Retention
            
        }
        return $bonus;
    }
}
