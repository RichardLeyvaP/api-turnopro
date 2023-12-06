<?php

namespace App\Services;

use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\Order;
use App\Models\ProductStore;

class OrderService {
    public function product_order_store($data){
        /*$clientprofessional = ClientProfessional::where('client_id',$data['client_id'])->where('professional_id',$data['professional_id'])->first();
            if (!$clientprofessional) {
                $clientprofessional = new ClientProfessional();
                $clientprofessional->client_id = $data['client_id'];
                $clientprofessional->professional_id = $data['professional_id'];
                $clientprofessional->save();
            }
            $client_professional_id = $clientprofessional->id;
            $productcar = Car::where('client_professional_id', $client_professional_id)->whereDate('updated_at', Carbon::today())->first();*/
            $car = Car::find($data['car_id'])->firts();
            $productStore = ProductStore::with('product')->where('id', $data['product_id'])->first();
                $sale_price = $productStore->product()->first()->sale_price;
                //if ($productcar) {
                    //$car = Car::find($productcar->id);
                    $car->amount = $car->amount + $sale_price;
                //}
                /*else {
                    $car = new Car();
                    $car->client_professional_id = $client_professional_id;
                    $car->amount = $sale_price;
                    $car->pay = false;
                    $car->active = false;
                }*/
                $car->save();
                $car_id = $car->id;
                    //rebajar la existencia
                $productstore = ProductStore::find($data['product_id']);
                $productstore->product_quantity = 1;
                $productstore->product_exit = $productstore->product_exit - 1;
                $productstore->save();
                             
                 $order = new Order();
                 $order->car_id = $car_id;
                 $order->product_store_id = $data['product_id'];
                 $order->branch_service_professional_id = null;
                 $order->is_product = true;
                 $order->price = $sale_price;               
                 $order->request_delete = false;
                 $order->save();
        return $order;
    }
    public function service_order_store($data){
        /*$clientprofessional = ClientProfessional::where('client_id',$data['client_id'])->where('professional_id',$data['professional_id'])->first();
            if (!$clientprofessional) {
                $clientprofessional = new ClientProfessional();
                $clientprofessional->client_id = $data['client_id'];
                $clientprofessional->professional_id = $data['professional_id'];
                $clientprofessional->save();
            }
            $client_professional_id = $clientprofessional->id;*/
            //$productcar = Car::where('client_professional_id', $client_professional_id)->whereDate('updated_at', Carbon::today())->first();
            //$car = Car::find($data['car_id'])->firts();
            $car = Car::findOrFail($data['car_id']);
            $branchServiceprofessional = BranchServiceProfessional::with('branchService.service')->where('id', $data['service_id'])->first();
                $service = $branchServiceprofessional->branchService->service;
                //if ($productcar) {
                    //$car = Car::find($productcar->id);
                    $car->amount = $car->amount + $service->price_service+$service->profit_percentaje/100;
                //}
                /*else {
                    $car = new Car();
                    $car->client_professional_id = $client_professional_id;
                    $car->amount = $service->price_service+$service->profit_percentaje/100;
                    $car->pay = false;
                    $car->active = false;
                }*/
                $car->save();
                $car_id = $car->id;
                 $order = new Order();
                 $order->car_id = $car_id;
                 $order->product_store_id = null;
                 $order->branch_service_professional_id = $data['service_id'];
                 $order->is_product = false;
                 $order->price = $service->price_service+$service->profit_percentaje/100;   
                 $order->request_delete = false;
                 $order->save();
        return $order;
    }
}
