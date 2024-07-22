<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStore;
use App\Models\Reservation;
use App\Models\Service;
use App\Traits\ProductExitTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService {

    use ProductExitTrait;
    
    public function product_order_store($data){
            $car = Car::findOrFail($data['car_id']);
            $productStore = ProductStore::with('product')->where('id', $data['product_id'])->first();
                $product = $productStore->product()->first();
                $sale_price = $product->sale_price;
                $percent_wint = $sale_price - $product->purchase_price;
                $car->amount = $car->amount + $sale_price * $data['cant'];
                $car->save();
                $car_id = $car->id;
                    //rebajar la existencia
                $productstore = ProductStore::find($data['product_id']);
                $productstore->product_quantity = 1;
                $productstore->product_exit = $productstore->product_exit - $data['cant'];
                $productstore->save();
                //todo pendiente para revisar importante
                //$this->actualizarProductExit($productstore->product_id, $productstore->store_id);            
                 $order = new Order();
                 $order->car_id = $data['car_id'];
                 $order->product_store_id = $data['product_id'];
                 $order->branch_service_professional_id = null;
                 $order->data = Carbon::now();
                 $order->is_product = true;
                 $order->price = $sale_price*$data['cant'];               
                 $order->cant = $data['cant'];               
                 $order->request_delete = false;
                 $order->percent_win = $percent_wint*$data['cant'];
                 $order->save();
        return $order;
    }
    public function service_order_store1($data){
            $car = Car::findOrFail($data['car_id']);
            $branchServiceprofessional = BranchServiceProfessional::with('branchService.service')->where('id', $data['service_id'])->first();
            
                $service = $branchServiceprofessional->branchService->service;
                $percent = $branchServiceprofessional->percent;
                    $car->amount = $car->amount + $service->price_service;
                $car->save();
                $car_id = $car->id;
       
                 $order = new Order();
                 $order->car_id = $car_id;
                 $order->product_store_id = null;
                 $order->branch_service_professional_id = $data['service_id'];
                 $order->data = Carbon::now();
                 $order->percent_win = $service->price_service*$percent/100;
                 $order->is_product = false;
                 $order->price = $service->price_service;   
                 $order->request_delete = false;
                 $order->save();
                return $order;
    }

    public function service_order_store($data){
        $car = Car::findOrFail($data['car_id']);
        $branchServiceprofessional = BranchServiceProfessional::with('branchService.service')->where('id', $data['service_id'])->first();
            $service = $branchServiceprofessional->branchService->service;
            $percent = $branchServiceprofessional->percent;
            $duration = $service->duration_service;
            $car->amount = $car->amount + $service->price_service;
            $car->save();
            $car_id = $car->id;

             $order = new Order();
             $order->car_id = $car_id;
             $order->product_store_id = null;
             $order->branch_service_professional_id = $data['service_id'];
             $order->data = Carbon::now();
             $order->percent_win = $service->price_service*$percent/100;
             $order->is_product = false;
             $order->price = $service->price_service;   
             $order->request_delete = false;
             $order->save();
             $reservation = $car->reservation;
             $tiempoGuardado = Carbon::createFromFormat('H:i:s', $reservation->total_time);

            $tiempoGuardado->addMinutes($duration);


            $tiempoGuardado = $tiempoGuardado->toTimeString();

            $reservation->final_hour = Carbon::parse($reservation->final_hour)->addMinutes($duration)->toTimeString();
            $reservation->total_time = $tiempoGuardado;
            $reservation->save();

            //aumentar tiempo al reloj
            $tail = $reservation->tail;
            $timeClock = $tail->timeClock + $duration*60;
            $tail->timeClock = $timeClock;
            $tail->save();

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
        ->map(function ($product) use ($data){
            foreach ($product->productStores as $productStore) {
                $total = $productStore->orders->whereBetween('data', [$data['startDate'], $data['endDate']])->sum('price');
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
            $query->select('percent_win');
        })->get()->map(function ($service) use ($data){
            Log::info($service);
           foreach ($service->branchServices as $branchService) {
                foreach($branchService->branchServiceProfessionals as $branchServiceProfessional){
                $totalService = $branchServiceProfessional->orders->whereBetween('data', [$data['startDate'], $data['endDate']])->sum('percent_win');
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
