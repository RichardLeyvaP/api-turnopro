<?php

namespace App\Services;

use App\Models\Box;
use App\Models\BranchProfessional;
use App\Models\BranchRuleProfessional;
use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\Finance;
use App\Models\Order;
use App\Models\Professional;
use App\Models\ProfessionalPayment;
use App\Models\Retention;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetaService
{
    public function store($branch)
    {       
        DB::beginTransaction(); 
        try{
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
        ->whereDate('data', Carbon::now())->where(function($query) {
            $query->where('type', 'Services')
                ->orWhere('type', 'BonoConvivencia')
                ->orWhere('type', 'BonoService');
        })->delete();

        $subquery = ProfessionalPayment::where('branch_id', $branch->id)->whereDate('date', Carbon::now())->where(function($query) {
            $query->where('type', 'Bono convivencias')
                ->orWhere('type', 'Bono servicios');
        })->delete();

        // Calcular la suma de los montos
        //$totalAmount = $subquery->sum('amount');

        // Eliminar los registros
        //$subquery->delete();
        /*$box = Box::whereDate('data', Carbon::now())->where('branch_id', $branch->id)->first();
        if ($box != null) {
            // Si la diferencia es positiva, se resta de box->existence
            // Si es negativa, se suma a box->existence
            $box->existence += $totalAmount;
            $box->save(); // Guardar los cambios en $box
        }*/
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
                        foreach($orders as $order){
                            if ($order->meta == 1) {
                                $order->percent_win = $order->price * $idService->percent / 100;                                
                                $order->meta = 1;
                                $order->save();
                            }
                        }
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
                                'amount' => round($amount - $retentionAmount, 2),
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
                            /*if($retentionP){
                                Log::info('Entra a retencion bono de convivencias'.$professional->name.$retentionAmount);
                                $retention = new Retention();
                                $retention->branch_id = $branch->id;
                                $retention->professional_id = $professional->id;
                                $retention->data = Carbon::now();
                                $retention->retention = round($retentionAmount, 2);
                                $retention->type = 'BonoConvivencia';
                                $retention->save();
                            }*/

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
                        'amount' => round($profesionalbonus->mountpay-$retentionAmount, 2),
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
                        $retention->retention = round($retentionAmount, 2);
                        $retention->type = 'BonoService';
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
                            $retention->retention = round($percentWinSum * $retentionP / 100, 2);
                            $retention->save();
                        }
                    }
                }
            //end Retention
            
        }
        DB::commit();
        return $bonus;
        } catch (Exception $e) {
            // Manejo de la excepción en el servicio, puedes lanzar una excepción personalizada
            DB::rollback();
            throw new \RuntimeException("Error al ejecutar el MetaServie(store): " . $e->getMessage());
        }
    }

    public function store1($branch, $data, $professional_id)
    {        
        DB::beginTransaction();
        try{
            $professional = Professional::findOrfail($professional_id);
            // Eliminar los registros que coincidan
            Finance::where('branch_id', $branch->id)
            ->whereDate('data', $data)
            ->where('operation', 'Gasto')
            ->where(function($query) use ($professional){
                $query->where('comment', 'like', '%Gasto por pago de bono de convivencias a '.$professional->name.'%')
                    ->orWhere('comment', 'like', '%Gasto por pago de bono de servicios a '.$professional->name.'%');
            })
            ->delete();

            //Retention
            Retention::where('branch_id', $branch->id)->where('professional_id', $professional_id)
            ->whereDate('data', $data)->where(function($query) {
                $query->where('type', 'BonoConvivencia')
                    ->orWhere('type', 'BonoService')
                    ->orWhere('type', 'Service');
            })->delete();

            $subquery = ProfessionalPayment::where('branch_id', $branch->id)->whereDate('date', $data)->where('professional_id', $professional_id)->where(function($query) {
                $query->where('type', 'Bono convivencias')
                    ->orWhere('type', 'Bono servicios');
            })->delete();

            
            // Calcular la suma de los montos
            //$totalAmount = $subquery->sum('amount');

            // Eliminar los registros
            //$subquery->delete();
            /*$box = Box::whereDate('data', Carbon::now())->where('branch_id', $branch->id)->first();
            if ($box != null) {
                // Si la diferencia es positiva, se resta de box->existence
                // Si es negativa, se suma a box->existence
                $box->existence += $totalAmount;
                $box->save(); // Guardar los cambios en $box
            }*/
            $idService=null;
            $bonus = [];
            $percentWinSum = 0;


            //$finance = Finance::where('branch_id', $branch->id)->where('expense_id', 5)->whereDate('data', Carbon::now())orderBy('control', 'desc')->first();
            $finance = Finance::orderBy('control', 'desc')->first();
            if ($finance !== null) {
                $control = $finance->control + 1;
            } else {
                $control = 1;
            }
            //foreach ($professionals as $professional) {
                Log::info($professional->id);
                $cars = Car::whereHas('reservation', function ($query) use ($branch, $data) {
                    $query->where('branch_id', $branch->id)->whereDate('data', $data);
                })
                    ->with(['clientProfessional.client', 'reservation'])
                    ->whereHas('clientProfessional', function ($query) use ($professional_id) {
                        $query->where('professional_id', $professional_id);
                    })
                    ->where('pay', 1)
                    ->get();
                    Log::info('Carros pagados');
                    Log::info($cars);
                //retention
                $retentionP = $professional->retention;
                $carIdsPay = $cars->pluck('id');
                $rules =  BranchRuleProfessional::where('professional_id', $professional_id)->whereHas('branchRule', function ($query) use ($branch, $data) {
                    $query->where('branch_id', $branch->id)->where('estado', 0)->whereDate('data', $data);
                })->get();

                //$professionalPaymentsServices = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->where('type', 'Bono servicios')->get()->first();

                if ($rules->isEmpty()) {
                    $idService = BranchServiceProfessional::where('professional_id', $professional_id)->whereHas('branchService.branch', function ($query) use ($branch) {
                        $query->where('branch_id', $branch->id);
                    })->where('meta', 1)->first();
                    Log::info('Servicio Meta');
                    Log::info($idService);
                    if ($idService != null) {
                        $orders = Order::where('branch_service_professional_id', $idService->id)->whereIn('car_id', $carIdsPay)->limit(4)->get();
                        Log::info('Ordenes de los carros');
                        Log::info($orders);
                        if (!$orders->isEmpty()) {
                            $cant = $orders->count();
                            $amount = $orders->first()->price * $cant;
                            /*$filteredPayments = $professionalPayments->filter(function ($payment) {
                                return $payment->type == 'Bono convivencias';
                            })->first();*/
                            Log::info('Cantidad a pagar');
                            Log::info($amount);
                            $professionalPayment = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', $data)->where('type', 'Bono convivencias')->first();
                            if ($professionalPayment == null) {
                                $professionalPayment = new ProfessionalPayment();
                            }
                                $retentionAmount = $retentionP ? $amount * $retentionP / 100 : 0;
                                $professionalPayment->branch_id = $branch->id;
                                $professionalPayment->professional_id = $professional_id;
                                $professionalPayment->date = $data;
                                $professionalPayment->amount = $amount - $retentionAmount;
                                $professionalPayment->type = 'Bono convivencias';
                                $professionalPayment->cant = $cant;
                                $professionalPayment->save();
                                $bonus[] = [
                                    'name' => $professional->name,
                                    'image_url' => $professional->image_url,
                                    'bonus' => 'Bono convivencias',
                                    'amount' => round($amount - $retentionAmount, 2),
                                ];
                                $finance = new Finance();
                                $finance->control = $control++;
                                $finance->operation = 'Gasto';
                                $finance->amount = $amount - $retentionAmount;
                                $finance->comment = 'Gasto por pago de bono de convivencias a ' . $professional->name;
                                $finance->branch_id = $branch->id;
                                $finance->type = 'Sucursal';
                                $finance->expense_id = 5;
                                $finance->data = $data;
                                $finance->file = '';
                                $finance->save();
                                /*if($retentionP){
                                    Log::info('Entra a retencion bono de convivencias'.$professional->name.$retentionAmount);
                                    $retention = new Retention();
                                    $retention->branch_id = $branch->id;
                                    $retention->professional_id = $professional->id;
                                    $retention->data = $data;
                                    $retention->retention = round($retentionAmount, 2);
                                    $retention->type = 'BonoConvivencia';
                                    $retention->save();
                                }*/

                                foreach($orders as $order){
                                    $order->meta = 1;
                                    $order->percent_win = 0;
                                    $order->save();
                                }
                            //}
                        }
                    }
                }


                $profesionalbonus = BranchProfessional::where('professional_id', $professional_id)->where('branch_id', $branch->id)->first();

                //Venta de productos y servicios
                $orderServs = Order::whereIn('car_id', $carIdsPay)->where('is_product', 0)->get();
                $orderServPay = $orderServs->where('meta', 0)->sum('price');
                $catServices = $orderServs->count();
                if ($orderServPay >= $profesionalbonus->limit && $profesionalbonus->mountpay > 0) {
                    /*$filteredPayments = $professionalPayments->filter(function ($payment) {
                        return $payment->type == 'Bono servicios';
                    });*/
                    $professionalPaymentService = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional_id)->whereDate('date', $data)->where('type', 'Bono servicios')->first();
                    if ($professionalPaymentService == null) {
                        $professionalPaymentService = new ProfessionalPayment();
                    }
                        $retentionAmount = $retentionP ? $profesionalbonus->mountpay * $retentionP / 100 : 0;
                        $professionalPaymentService->branch_id = $branch->id;
                        $professionalPaymentService->professional_id = $professional->id;
                        $professionalPaymentService->date = $data;
                        $professionalPaymentService->amount = $profesionalbonus->mountpay - $retentionAmount;
                        $professionalPaymentService->type = 'Bono servicios';
                        $professionalPaymentService->cant = $catServices;
                        $professionalPaymentService->save();
                        $bonus[] = [
                            'name' => $professional->name,
                            'image_url' => $professional->image_url,
                            'bonus' => 'Bono servicios',
                            'amount' => round($profesionalbonus->mountpay-$retentionAmount, 2),
                        ];
                        $finance = new Finance();
                        $finance->control = $control++;
                        $finance->operation = 'Gasto';
                        $finance->amount = $profesionalbonus->mountpay-$retentionAmount;
                        $finance->comment = 'Gasto por pago de bono de servicios a ' . $professional->name;
                        $finance->branch_id = $branch->id;
                        $finance->type = 'Sucursal';
                        $finance->expense_id = 5;
                        $finance->data = $data;
                        $finance->file = '';
                        $finance->save();
                        if($retentionP){
                            Log::info('Entra a retencion bono de servicios'.$professional->name.$retentionAmount);
                            $retention = new Retention();
                            $retention->branch_id = $branch->id;
                            $retention->professional_id = $professional->id;
                            $retention->data = $data;
                            $retention->retention = round($retentionAmount, 2);
                            $retention->type = 'BonoService';
                            $retention->save();
                        }
                    //}
                }  
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
                            $retention->retention = round($percentWinSum * $retentionP / 100, 2);
                            $retention->save();
                        }
                    }
                }          
            //}
            DB::commit();
            return $bonus;
        } catch (Exception $e) {
            DB::rollback();
            // Manejo de la excepción en el servicio, puedes lanzar una excepción personalizada
            throw new \RuntimeException("Error al ejecutar el MetaServie(store): " . $e->getMessage());
        }
    }

    public function store_box_close($branch, $data, $professional_id)
    {        
        DB::beginTransaction();
        try{
            $professional = Professional::findOrfail($professional_id);
        // Eliminar los registros que coincidan
        Finance::where('branch_id', $branch->id)
        ->whereDate('data', $data)
        ->where('operation', 'Gasto')
        ->where(function($query) use ($professional){
            $query->where('comment', 'like', '%Gasto por pago de bono de convivencias a '.$professional->name.'%')
                ->orWhere('comment', 'like', '%Gasto por pago de bono de servicios a '.$professional->name.'%');
        })
        ->delete();

        //Retention
        Retention::where('branch_id', $branch->id)->where('professional_id', $professional_id)
        ->whereDate('data', $data)->where(function($query) {
            $query->where('type', 'Services')
                ->orWhere('type', 'BonoConvivencia')
                ->orWhere('type', 'BonoService');
        })->delete();

        $subquery = ProfessionalPayment::where('branch_id', $branch->id)->whereDate('date', $data)->where('professional_id', $professional_id)->where(function($query) {
            $query->where('type', 'Bono convivencias')
                ->orWhere('type', 'Bono servicios');
        })->delete();

        
        // Calcular la suma de los montos
        //$totalAmount = $subquery->sum('amount');

        // Eliminar los registros
        //$subquery->delete();
        /*$box = Box::whereDate('data', Carbon::now())->where('branch_id', $branch->id)->first();
        if ($box != null) {
            // Si la diferencia es positiva, se resta de box->existence
            // Si es negativa, se suma a box->existence
            $box->existence += $totalAmount;
            $box->save(); // Guardar los cambios en $box
        }*/
        $idService=null;
        $bonus = [];
        $percentWinSum = 0;


        //$finance = Finance::where('branch_id', $branch->id)->where('expense_id', 5)->whereDate('data', Carbon::now())orderBy('control', 'desc')->first();
        $finance = Finance::orderBy('control', 'desc')->first();
        if ($finance !== null) {
            $control = $finance->control + 1;
        } else {
            $control = 1;
        }
        //foreach ($professionals as $professional) {
            Log::info($professional->id);
            $cars = Car::whereHas('reservation', function ($query) use ($branch, $data) {
                $query->where('branch_id', $branch->id)->whereDate('data', $data);
            })
                ->with(['clientProfessional.client', 'reservation'])
                ->whereHas('clientProfessional', function ($query) use ($professional) {
                    $query->where('professional_id', $professional->id);
                })
                ->where('pay', 1)
                ->get();
                Log::info('Carros pagados:'.$professional->name);
                Log::info($cars);
            //retention
            $retentionP = $professional->retention;
            $carIdsPay = $cars->pluck('id');
            $rules =  BranchRuleProfessional::where('professional_id', $professional_id)->whereHas('branchRule', function ($query) use ($branch, $data) {
                $query->where('branch_id', $branch->id)->where('estado', 0)->whereDate('data', $data);
            })->get();

            //$professionalPaymentsServices = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->where('type', 'Bono servicios')->get()->first();

            if ($rules->isEmpty()) {
                $idService = BranchServiceProfessional::where('professional_id', $professional_id)->whereHas('branchService.branch', function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id);
                })->where('meta', 1)->first();
                Log::info('Servicio Meta');
                Log::info($idService);
                if ($idService != null) {
                    $orders = Order::where('branch_service_professional_id', $idService->id)->whereIn('car_id', $carIdsPay)->limit(4)->get();
                    Log::info('Ordenes de los carros');
                    Log::info($orders);
                    if (!$orders->isEmpty()) {
                        $cant = $orders->count();
                        $amount = $orders->first()->price * $cant;
                        /*$filteredPayments = $professionalPayments->filter(function ($payment) {
                            return $payment->type == 'Bono convivencias';
                        })->first();*/
                        Log::info('Cantidad a pagar');
                        Log::info($amount);
                        $professionalPayment = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', $data)->where('type', 'Bono convivencias')->first();
                        if ($professionalPayment == null) {
                            $professionalPayment = new ProfessionalPayment();
                            $retentionAmount = $retentionP ? $amount * $retentionP / 100 : 0;
                            $professionalPayment->branch_id = $branch->id;
                            $professionalPayment->professional_id = $professional_id;
                            $professionalPayment->date = $data;
                            $professionalPayment->amount = $amount - $retentionAmount;
                            $professionalPayment->type = 'Bono convivencias';
                            $professionalPayment->cant = $cant;
                            $professionalPayment->save();
                        }
                            $bonus[] = [
                                'name' => $professional->name,
                                'image_url' => $professional->image_url,
                                'bonus' => 'Bono convivencias',
                                'amount' => round($amount - $retentionAmount, 2),
                            ];
                            $finance = new Finance();
                            $finance->control = $control++;
                            $finance->operation = 'Gasto';
                            $finance->amount = $amount - $retentionAmount;
                            $finance->comment = 'Gasto por pago de bono de convivencias a ' . $professional->name;
                            $finance->branch_id = $branch->id;
                            $finance->type = 'Sucursal';
                            $finance->expense_id = 5;
                            $finance->data = $data;
                            $finance->file = '';
                            $finance->save();
                            /*if($retentionP){
                                Log::info('Entra a retencion bono de convivencias'.$professional->name.$retentionAmount);
                                $retention = new Retention();
                                $retention->branch_id = $branch->id;
                                $retention->professional_id = $professional->id;
                                $retention->data = $data;
                                $retention->retention = round($retentionAmount, 2);
                                $retention->type = 'BonoConvivencia';
                                $retention->save();
                            }*/

                            foreach($orders as $order){
                                $order->meta = 1;
                                $order->percent_win = 0;
                                $order->save();
                            }
                        //}
                    }
                }
            }


            $profesionalbonus = BranchProfessional::where('professional_id', $professional_id)->where('branch_id', $branch->id)->first();

            //Venta de productos y servicios
            $orderServs = Order::whereIn('car_id', $carIdsPay)->where('is_product', 0)->get();
            $orderServPay = $orderServs->where('meta', 0)->sum('price');
            $catServices = $orderServs->count();
            if ($orderServPay >= $profesionalbonus->limit && $profesionalbonus->mountpay > 0) {
                /*$filteredPayments = $professionalPayments->filter(function ($payment) {
                    return $payment->type == 'Bono servicios';
                });*/
                $professionalPaymentService = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional_id)->whereDate('date', $data)->where('type', 'Bono servicios')->first();
                if ($professionalPaymentService == null) {
                    $professionalPaymentService = new ProfessionalPayment();
                }
                    $retentionAmount = $retentionP ? $profesionalbonus->mountpay * $retentionP / 100 : 0;
                    $professionalPaymentService->branch_id = $branch->id;
                    $professionalPaymentService->professional_id = $professional->id;
                    $professionalPaymentService->date = $data;
                    $professionalPaymentService->amount = $profesionalbonus->mountpay - $retentionAmount;
                    $professionalPaymentService->type = 'Bono servicios';
                    $professionalPaymentService->cant = $catServices;
                    $professionalPaymentService->save();
                    $bonus[] = [
                        'name' => $professional->name,
                        'image_url' => $professional->image_url,
                        'bonus' => 'Bono servicios',
                        'amount' => round($profesionalbonus->mountpay-$retentionAmount, 2),
                    ];
                    $finance = new Finance();
                    $finance->control = $control++;
                    $finance->operation = 'Gasto';
                    $finance->amount = $profesionalbonus->mountpay-$retentionAmount;
                    $finance->comment = 'Gasto por pago de bono de servicios a ' . $professional->name;
                    $finance->branch_id = $branch->id;
                    $finance->type = 'Sucursal';
                    $finance->expense_id = 5;
                    $finance->data = $data;
                    $finance->file = '';
                    $finance->save();
                    if($retentionP){
                        Log::info('Entra a retencion bono de servicios'.$professional->name.$retentionAmount);
                        $retention = new Retention();
                        $retention->branch_id = $branch->id;
                        $retention->professional_id = $professional->id;
                        $retention->data = $data;
                        $retention->retention = round($retentionAmount, 2);
                        $retention->type = 'BonoService';
                        $retention->save();
                    }
                //}
            } 
             //retenciones de servicios
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
                             $retention->data = $data;
                             $retention->retention = round($percentWinSum * $retentionP / 100, 2);
                             $retention->save();
                         }
                     }
                 }           
        //}
        DB::commit();
        return $bonus;
        } catch (Exception $e) {
            DB::rollback();
            // Manejo de la excepción en el servicio, puedes lanzar una excepción personalizada
            throw new \RuntimeException("Error al ejecutar el MetaServie(store): " . $e->getMessage());
        }
    }
    
    public function bonus($branch_id)
    {      
        try{  
        $idService=null;
        $bonus = [];
        $order_id = [];
        $percentWinSum = 0;
        $professionals = Professional::whereHas('branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->whereHas('charge', function ($query) {
            $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
        })->select('id', 'name', 'image_url', 'retention')->get();

        Log::info('Profesionales:'.$professionals);
        foreach ($professionals as $professional) {
            $order_id = [];
            Log::info($professional->id);
            $cars = Car::whereHas('reservation', function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id)->whereDate('data', Carbon::now());
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
            $rules =  BranchRuleProfessional::where('professional_id', $professional->id)->whereHas('branchRule', function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id)->where('estado', 0)->whereDate('data', Carbon::now());
            })->get();

            if ($rules->isEmpty()) {
                Log::info('Calcular bono de Servicios metas');
                $idService = BranchServiceProfessional::where('professional_id', $professional->id)->whereHas('branchService.branch', function ($query) use ($branch_id) {
                    $query->where('branch_id', $branch_id);
                })->where('meta', 1)->first();
                if ($idService != null) {
                    $orders = Order::where('branch_service_professional_id', $idService->id)->whereIn('car_id', $carIdsPay)->limit(4)->get();  
                                    
                    $order_id = $orders->pluck('id')->values();
                    if (!$orders->isEmpty()) {
                        $cant = $orders->count();
                        $amount = $orders->first()->price * $cant;
                        $professionalPayment = ProfessionalPayment::where('branch_id', $branch_id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->where('type', 'Bono convivencias')->first();
                        if ($professionalPayment == null) {
                            $professionalPayment = new ProfessionalPayment();
                        }
                            $retentionAmount = $retentionP ? $amount * $retentionP / 100 : 0;
                            $bonus[] = [
                                'name' => $professional->name,
                                'professional_id' => $professional->id,
                                'image_url' => $professional->image_url,
                                'bonus' => 'Bono convivencias',
                                'amount' => round($amount - $retentionAmount, 2),
                                'branch_id' => $branch_id,
                                'order_id' => $orders->pluck('id')->values(),
                                'cant' => $cant,
                                'retention' => round($retentionAmount, 2)
                            ];
                        //}
                    }
                }
            }


            $profesionalbonus = BranchProfessional::where('professional_id', $professional->id)->where('branch_id', $branch_id)->first();
            Log::info('$order_id');
            Log::info($order_id != null);
            //Venta de productos y servicios
            if ($order_id != null){
                Log::info('Tiene servicios metas');
                $orderServs = Order::whereIn('car_id', $carIdsPay)->where('is_product', 0)->whereNotIn('id', $order_id)->get();
            }else{
                Log::info('No tiene servicios metas');
                $orderServs = Order::whereIn('car_id', $carIdsPay)->where('is_product', 0)->get(); 
            }
            Log::info('orders a contemplar:'.$orderServs);
            $orderServPay = $orderServs->where('meta', 0)->sum('price');
            $catServices = $orderServs->count();
            if ($orderServPay >= $profesionalbonus->limit && $profesionalbonus->mountpay > 0) {
                Log::info('Calcular bono de Servicios');
                /*$filteredPayments = $professionalPayments->filter(function ($payment) {
                    return $payment->type == 'Bono servicios';
                });*/
                $professionalPaymentService = ProfessionalPayment::where('branch_id', $branch_id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->where('type', 'Bono servicios')->first();
                if ($professionalPaymentService == null) {
                    $professionalPaymentService = new ProfessionalPayment();
                }
                    $retentionAmount = $retentionP ? $profesionalbonus->mountpay * $retentionP / 100 : 0;
                    $bonus[] = [
                        'name' => $professional->name,
                        'professional_id' => $professional->id,
                        'image_url' => $professional->image_url,
                        'bonus' => 'Bono servicios',
                        'amount' => round($profesionalbonus->mountpay-$retentionAmount, 2),
                        'branch_id' => $branch_id,
                        'order_id' => '',
                        'cant' => $catServices,
                        'retention' => round($retentionAmount, 2)
                    ];
                //}
            }            
        }
        return $bonus;
    } catch (Exception $e) {
        Log::info($e->getMessage());
            // Manejo de la excepción en el servicio, puedes lanzar una excepción personalizada
            throw new \RuntimeException("Error al ejecutar el MetaServie(bonus): " . $e->getMessage());
        }
    }
}
