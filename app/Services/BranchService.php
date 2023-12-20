<?php

namespace App\Services;
use App\Models\Branch;
use Illuminate\Support\Facades\Log;

class BranchService
{

    public function branch_winner_month($branch_id, $month)
    {
        $cars = Car::whereHas('clientProfessional', function ($query) use ($data){
            $query->whereHas('professional.branches', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            });
        })->whereHas('orders', function ($query) use ($data){
            $query->whereMonth('data', Carbon::parse($data['data'])->month);
                })->get();
       $totalClients =0;
       $totalClients = $cars->count();
        $products = Product::withCount('orders')->whereHas('productStores.orders', function ($query) use ($data){
                $query->whereMonth('data', Carbon::parse($data['data'])->month);
            })->whereHas('productStores.store.branches', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            })->orderByDesc('orders_count')->first();
          return $result = [
            'Monto Generado' => round($cars->sum('amount'),2),
            'Producto mas Vendido' => $products ? $products->name : null,
            'Cantidad del Producto' => $products ? $products->orders_count : 0,
            'Clientes Atendidos' => $totalClients
          ];
      
    }

}