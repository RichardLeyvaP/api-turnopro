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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class MetaService {
    public function store($branch){
        $professionals = Professional::whereHas('branches', function ($query) use ($branch){
            $query->where('branch_id', $branch->id);
        })->whereHas('charge', function ($query){
            $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
        })->select('id', 'name', 'surname')->get();
        
        
        $finance = Finance::where('branch_id', $branch->id)->where('expense_id', 5)->whereDate('data', Carbon::now())->orderByDesc('control')->first();
                            
        if($finance !== null)
            {
                $control = $finance->control+1;
            }
            else {
                $control = 1;
            }

        Log::info($professionals);
            foreach($professionals as $professional){
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
                $carIdsPay = $cars->pluck('id');
                $rules =  BranchRuleProfessional::where('professional_id', $professional->id)->whereHas('branchRule', function ($query) use ($branch){
                    $query->where('branch_id', $branch->id)->where('estado', 3)->whereDate('data', Carbon::now());
                })->get();

                $professionalPayments = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->get();

                if($rules->isEmpty()){
                    $idService = BranchServiceProfessional::where('professional_id', $professional->id)->whereHas('branchService.branch', function ($query) use ($branch){
                        $query->where('branch_id', $branch->id);
                    })->where('meta', 1)->value('id');
                    if($idService!=null){
                        $orders = Order::where('branch_service_professional_id', $idService)->whereIn('car_id', $carIdsPay)->limit(4)->get();
                        if(!$orders->isEmpty()){
                            $cant = $orders->count();
                            $amount = $orders->first()->price * $cant;
                            $filteredPayments = $professionalPayments->filter(function($payment) {
                                return $payment->type == 'Bono convivencias';
                            });
                            //$professionalPayment = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->where('type', 'Bono convivencias')->first();
                            if($filteredPayments->isEmpty()){
                               $professionalPayment = new ProfessionalPayment();
                            $professionalPayment->branch_id = $branch->id;
                            $professionalPayment->professional_id = $professional->id;
                            $professionalPayment->date = Carbon::now();
                            $professionalPayment->amount = $amount;
                            $professionalPayment->type = 'Bono convivencias';
                            $professionalPayment->cant = $cant;
                            $professionalPayment->save();

                            $finance = new Finance();
                            $finance->control = $control++;
                            $finance->operation = 'Gasto';
                            $finance->amount = $amount;
                            $finance->comment = 'Gasto por pago de bono de convivencias a '.$professional->name .' '.$professional->surname;
                            $finance->branch_id = $branch->id;
                            $finance->type = 'Sucursal';
                            $finance->expense_id = 5;
                            $finance->data = Carbon::now();                
                            $finance->file = '';
                            $finance->save(); 
                            }
                            
                        /*foreach($orders as $order){
                            $order->percent_win = $order->price;
                            $order->save();
                        }*/
                    }
                    }
                }


            $profesionalbonus = BranchProfessional::where('professional_id', $professional->id)->where('branch_id', $branch->id)->first();
            
            //Venta de productos y servicios
            $orderServs = Order::whereIn('car_id', $carIdsPay)->where('is_product', 0)->get();
            $orderServPay = $orderServs->sum('price');
            $catServices = $orderServs->count();
            if ($orderServPay >= $profesionalbonus->limit && $profesionalbonus->mountpay > 0) {
                $filteredPayments = $professionalPayments->filter(function($payment) {
                    return $payment->type == 'Bono servicios';
                });
                //$professionalPayment = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->where('type', 'Bono servicios')->first();
                if($filteredPayments->isEmpty()){
                  
                $professionalPayment = new ProfessionalPayment();
                $professionalPayment->branch_id = $branch->id;
                $professionalPayment->professional_id = $professional->id;
                $professionalPayment->date = Carbon::now();
                $professionalPayment->amount = $profesionalbonus->mountpay;
                $professionalPayment->type = 'Bono servicios';
                $professionalPayment->cant = $catServices;
                $professionalPayment->save();

                            $finance = new Finance();
                            $finance->control = $control++;
                            $finance->operation = 'Gasto';
                            $finance->amount = $profesionalbonus->mountpay;
                            $finance->comment = 'Gasto por pago de bono de servicios a '.$professional->name .' '.$professional->surname;
                            $finance->branch_id = $branch->id;
                            $finance->type = 'Sucursal';
                            $finance->expense_id = 5;
                            $finance->data = Carbon::now();                
                            $finance->file = '';
                            $finance->save();  
                }
            }
            $winProduct = 0;
            $products = Order::whereIn('car_id', $carIdsPay)
            ->where('is_product', 1)
            ->groupBy('product_store_id')
            ->selectRaw('product_store_id, SUM(cant) as total_cant, SUM(percent_win) as total_percent_win')
            ->get();
            $venta = $products->sum('total_cant');
            $percent_win = $products->sum('total_percent_win');
            Log::info('$venta');
            Log::info($venta);
            Log::info('$percent_win');
            Log::info($percent_win);
            if($venta <= 24){
                $winProduct = $percent_win*0.15;
            }else if($venta > 24 && $venta <= 49){
                $winProduct = $percent_win*0.25;
            }else{
                $winProduct = $percent_win*0.50;
            }
            Log::info('$winProduct');
            Log::info($winProduct);
            /*foreach ($products  as $product) {
                if($product->total_cant <= 24){
                    $winProduct += $product->total_percent_win*0.15;
                }
                else if ($product->total_cant < 24 && $product->total_cant <= 49) {
                    $winProduct += $product->total_percent_win*0.25;
                }
                else{
                    $winProduct += $product->total_percent_win*0.50;
                }
            }*/
            if ($winProduct > 0) {
                $filteredPayments = $professionalPayments->filter(function($payment) {
                    return $payment->type == 'Bono productos';
                });
                //$professionalPayment = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->where('type', 'Bono productos')->first();
                if($filteredPayments->isEmpty()){
                    $professionalPayment = new ProfessionalPayment();
                $professionalPayment->branch_id = $branch->id;
                $professionalPayment->professional_id = $professional->id;
                $professionalPayment->date = Carbon::now();
                $professionalPayment->amount = $winProduct;
                $professionalPayment->type = 'Bono productos';
                $professionalPayment->cant = $venta;
                $professionalPayment->save();


                            $finance = new Finance();
                            $finance->control = $control++;
                            $finance->operation = 'Gasto';
                            $finance->amount = $winProduct;
                            $finance->comment = 'Gasto por pago de bono de productos a '.$professional->name .' '.$professional->surname;
                            $finance->branch_id = $branch->id;
                            $finance->type = 'Sucursal';
                            $finance->expense_id = 5;
                            $finance->data = Carbon::now();                
                            $finance->file = '';
                            $finance->save();
                }
                
            }
            /*$mountProduct = $orderProdPay->sum('price');
            if($cantProduct <= 24){

            }*/
            }
    }
}