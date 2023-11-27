<?php

namespace App\Services;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderService
{

    public function order_service_store($data)
    {
        Log::info("Reservacion de servicio prestado");
        $order = new Order();
        $order->car_id = $data['car_id'];
        $order->product_store_id = null;
        $order->branch_service_professional_id = $data['branch_service_professional_id'];
        $order->is_product = false;
        $order->price = $data['price'];   
        $order->request_delete = false;
        $order->save();
        return $order;    

    }

}