<?php

namespace App\Services;

use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\Order;
use App\Models\ProductStore;
use Carbon\Carbon;

class OrderService {
    public function product_order_store($data){
            $car = Car::findOrFail($data['car_id']);
            $productStore = ProductStore::with('product')->where('id', $data['product_id'])->first();
                $sale_price = $productStore->product()->first()->sale_price;
                    $car->amount = $car->amount + $sale_price;
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
                 $order->data = Carbon::now();
                 $order->is_product = true;
                 $order->price = $sale_price;               
                 $order->request_delete = false;
                 $order->save();
        return $order;
    }
    public function service_order_store($data){
            $car = Car::findOrFail($data['car_id']);
            $branchServiceprofessional = BranchServiceProfessional::with('branchService.service')->where('id', $data['service_id'])->first();
                $service = $branchServiceprofessional->branchService->service;
                    $car->amount = $car->amount + $service->price_service+$service->profit_percentaje/100;
                $car->save();
                $car_id = $car->id;
                 $order = new Order();
                 $order->car_id = $car_id;
                 $order->product_store_id = null;
                 $order->branch_service_professional_id = $data['service_id'];
                 $order->data = Carbon::now();
                 $order->is_product = false;
                 $order->price = $service->price_service+$service->profit_percentaje/100;   
                 $order->request_delete = false;
                 $order->save();
        return $order;
    }
}
