<?php

namespace App\Services;
use App\Models\Branch;
use App\Models\Car;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class BranchService
{

    public function branch_winner_month($branch_id, $month)
    {
        $cars = Car::whereHas('clientProfessional', function ($query) use ($branch_id, $month){
            $query->whereHas('professional.branches', function ($query) use ($branch_id, $month){
                $query->where('branch_id', $branch_id);
            });
        })->whereHas('orders', function ($query) use ($month){
            $query->whereMonth('data', $month);
                })->get();
       $totalClients =0;
       $totalClients = $cars->count();
        $products = Product::withCount('orders')->whereHas('productStores.orders', function ($query) use ($branch_id, $month){
                $query->whereMonth('data', $month);
            })->whereHas('productStores.store.branches', function ($query) use ($branch_id){
                $query->where('branch_id', $branch_id);
            })->orderByDesc('orders_count')->first();
          return $result = [
            'Monto Generado' => round($cars->sum('amount'),2),
            'Producto mas Vendido' => $products ? $products->name : null,
            'Cantidad del Producto' => $products ? $products->orders_count : 0,
            'Clientes Atendidos' => $totalClients
          ];
      
    }

    public function branch_winner_periodo($branch_id, $startDate, $endDate)
    {
        $cars = Car::whereHas('clientProfessional', function ($query) use ($branch_id){
            $query->whereHas('professional.branches', function ($query) use ($branch_id){
                $query->where('branch_id', $branch_id);
            });
        })->whereHas('orders', function ($query) use ($startDate, $endDate){
            $query->whereBetWeen('data', [$startDate, $endDate]);
                })->get();
       $totalClients =0;
       $totalClients = $cars->count();
        $products = Product::withCount('orders')->whereHas('productStores.orders', function ($query) use ($startDate, $endDate){
                $query->whereBetWeen('data', [$startDate, $endDate]);
            })->whereHas('productStores.store.branches', function ($query) use ($branch_id){
                $query->where('branch_id', $branch_id);
            })->orderByDesc('orders_count')->first();
          return $result = [
            'Monto Generado' => round($cars->sum('amount'),2),
            'Producto mas Vendido' => $products ? $products->name : null,
            'Cantidad del Producto' => $products ? $products->orders_count : 0,
            'Clientes Atendidos' => $totalClients
          ];
    }


}