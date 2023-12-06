<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStore;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function sales_periodo_product($data){
        Log::info("optener los productos");
        $products = Product::whereHas('stores.branches', function ($query) use ($data) {
            $query->where('branch_id', $data['branch_id']);
        })
        ->whereHas('productStores.orders', function ($query) use ($data){
            $query->whereBetween('data', [$data['startDate'], $data['endDate']]);
            $query->select('price');
        })->get()
        ->map(function ($product) {
            foreach ($product->productStores as $productStore) {
                $total = $productStore->orders->sum('price');
            }
            return [
                'nameProduct' => $product->name,
                'total_sale' => $total,
            ];
        });
    return $products;
    }

    public function sales_periodo_service($data){
        /*Log::info('services');
        /*$orders = Order::whereHas('branchServiceProfessional.branchService', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
        })->whereBetween('data', [$data['startDate'], $data['endDate']])->get()*/
        $services = Service::whereHas('branches', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
        })->whereHas('branchServices.branchServiceProfessionals.orders', function ($query) use ($data){
            $query->whereBetween('data', [$data['startDate'], $data['endDate']]);
            $query->select('price');
        })->get()->map(function ($service) {
            Log::info($service);
            foreach ($service->branchServices as $branchService) {
                Log::info($branchService);
                foreach($branchService->branchServiceProfessionals as $branchServiceProfessional){
                Log::info($branchServiceProfessional);
                $totalService = $branchServiceProfessional->orders->sum('price');
                }
            }
            return [
                'nameService' => $service->name,
                'total_sale' => $totalService,
            ];
        });
        /*Log::info('services');
        Log::info($services);
        foreach($services as $service)
        foreach ($service->branchServices as $branchService) {
            $totalService = $branchService->branchServiceProfessionals->flatMap(function ($branchServiceProfessional) use ($data){
                $branchServiceProfessional->orders->whereBetween('data', [$data['startDate'], $data['endDate']])->pluck('price');
            })->sum();

            $result [] = [
                'nameService' => $branchService->service->name,
                'total_sale' => $totalService,
            ];
        }*/
        return $services;
    }
}
