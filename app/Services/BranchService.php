<?php

namespace App\Services;
use App\Models\Branch;
use App\Models\Car;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Support\Facades\Log;

class BranchService
{

    public function branch_winner_date($branch_id)
    {

        $cars = Car::whereHas('clientProfessional', function ($query) use ($branch_id){
            $query->whereHas('professional.branches', function ($query) use ($branch_id){
                $query->where('branch_id', $branch_id);
            });
        })->whereHas('orders', function ($query){
            $query->whereDate('data', Carbon::now());
                })->get();
            $totalservices =0;
            $totalproducts =0;
            $seleccionado = 0;
            $aleatorio = 0;
            $totalEspecial =0;
            $montoEspecial = 0;
            $totalClients =0;
            $totalClients = $cars->count();
        $products = Product::withCount('orders')->whereHas('productStores.orders', function ($query){
                $query->whereDate('data', Carbon::now());
            })->whereHas('productStores.store.branches', function ($query) use ($branch_id){
                $query->where('branch_id', $branch_id);
            })->orderByDesc('orders_count')->first();
            $services = Service::withCount('orders')->whereHas('branchServices.branchServiceProfessionals.orders', function ($query){
                $query->whereDate('data', Carbon::now());
            })->whereHas('branchServices', function ($query) use ($branch_id){
                $query->where('branch_id', $branch_id);
            })->orderByDesc('orders_count')->first();
            foreach ($cars as $car) {   
                if ($car->select_professional == 1) {
                    $seleccionado = $seleccionado + $car->orders->where('is_product', 0)->count();
                }
                else{
                    $aleatorio = $aleatorio + $car->orders->where('is_product', 0)->count();
                }
                $totalservices = $totalservices + count($car->orders->where('is_product', 0));
                $totalproducts = $totalproducts + count($car->orders->where('is_product', 1));

                $totalEspecial = $totalEspecial + Service::whereHas('branchServices.branchServiceProfessionals.orders.car', function ($query) use ($car){
                    $query->where('id', $car->id);
                })->where('type_service', 'Especial')->count();
                $montoEspecial = $montoEspecial + Service::whereHas('branchServices.branchServiceProfessionals.orders.car', function ($query) use ($car){
                    $query->where('id', $car->id);
                })->where('type_service', 'Especial')->sum('price_service'); 
            }
          return $result = [
            'Monto Generado' => round($cars->sum('amount'),2),
            'Propina' => round($cars->sum('tip'), 2),
            'Producto mas Vendido' => $products ? $products->name : null,
            'Cantidad del Producto' => $products ? $products->orders_count : 0,
            'Total de Productos Vendidos' => $totalproducts,
            'Servicio mas Brindado' => $services ? $services->name : null,
            'Cantidad del Servicio' => $services ? $services->orders_count : 0,
            'Total de Servicios Brindados' => $totalservices,
            'Servicios Seleccionados' => $seleccionado,
            'Servicios Aleatorios' => $aleatorio,
            'Servicios Especiales' => $totalEspecial,
            'Monto Servicios Especiales' => round($montoEspecial, 2),
            'Clientes Atendidos' => $totalClients
          ];
    }

    public function branch_winner_month($branch_id, $month)
    {
        $cars = Car::whereHas('clientProfessional', function ($query) use ($branch_id){
            $query->whereHas('professional.branches', function ($query) use ($branch_id){
                $query->where('branch_id', $branch_id);
            });
        })->whereHas('orders', function ($query) use ($month){
            $query->whereMonth('data', $month);
        })->get();
            $totalservices =0;
            $totalproducts =0;
            $seleccionado = 0;
            $aleatorio = 0;
            $totalEspecial =0;
            $montoEspecial = 0;
            $totalClients =0;
       $totalClients = $cars->count();
        $products = Product::withCount('orders')->whereHas('productStores.orders', function ($query) use ($month){
            $query->whereMonth('data', $month);
        })->whereHas('productStores.store.branches', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        })->orderByDesc('orders_count')->first();
        $services = Service::withCount('orders')->whereHas('branchServices.branchServiceProfessionals.orders', function ($query) use ($month){
                $query->whereMonth('data', $month);
            })->whereHas('branchServices', function ($query) use ($branch_id){
                $query->where('branch_id', $branch_id);
            })->orderByDesc('orders_count')->first();
            foreach ($cars as $car) {   
                if ($car->select_professional == 1) {
                    $seleccionado = $seleccionado + $car->orders->where('is_product', 0)->count();
                }
                else{
                    $aleatorio = $aleatorio + $car->orders->where('is_product', 0)->count();
                }
                $totalservices = $totalservices + count($car->orders->where('is_product', 0));
                $totalproducts = $totalproducts + count($car->orders->where('is_product', 1));

                $totalEspecial = $totalEspecial + Service::whereHas('branchServices.branchServiceProfessionals.orders.car', function ($query) use ($car){
                    $query->where('id', $car->id);
                })->where('type_service', 'Especial')->count();
                $montoEspecial = $montoEspecial + Service::whereHas('branchServices.branchServiceProfessionals.orders.car', function ($query) use ($car){
                    $query->where('id', $car->id);
                })->where('type_service', 'Especial')->sum('price_service'); 
            }
          return $result = [
            'Monto Generado' => round($cars->sum('amount'),2),
            'Propina' => round($cars->sum('tip'), 2),
            'Producto mas Vendido' => $products ? $products->name : null,
            'Cantidad del Producto' => $products ? $products->orders_count : 0,
            'Total de Productos Vendidos' => $totalproducts,
            'Servicio mas Brindado' => $services ? $services->name : null,
            'Cantidad del Servicio' => $services ? $services->orders_count : 0,
            'Total de Servicios Brindados' => $totalservices,
            'Servicios Seleccionados' => $seleccionado,
            'Servicios Aleatorios' => $aleatorio,
            'Servicios Especiales' => $totalEspecial,
            'Monto Servicios Especiales' => round($montoEspecial, 2),
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
            $totalservices =0;
            $totalproducts =0;
            $seleccionado = 0;
            $aleatorio = 0;
            $totalEspecial =0;
            $montoEspecial = 0;
       $totalClients = $cars->count();
        $products = Product::withCount('orders')->whereHas('productStores.orders', function ($query) use ($startDate, $endDate){
                $query->whereBetWeen('data', [$startDate, $endDate]);
            })->whereHas('productStores.store.branches', function ($query) use ($branch_id){
                $query->where('branch_id', $branch_id);
            })->orderByDesc('orders_count')->first();
            Log::info("obtener los servicios");
            $services = Service::withCount('orders')->whereHas('branchServices.branchServiceProfessionals.orders', function ($query) use ($startDate, $endDate){
                $query->whereBetWeen('data', [$startDate, $endDate]);
            })->whereHas('branchServices', function ($query) use ($branch_id){
                $query->where('branch_id', $branch_id);
            })->orderByDesc('orders_count')->first();
            foreach ($cars as $car) {   
                if ($car->select_professional == 1) {
                    $seleccionado = $seleccionado + $car->orders->where('is_product', 0)->count();
                }
                else{
                    $aleatorio = $aleatorio + $car->orders->where('is_product', 0)->count();
                }
                $totalservices = $totalservices + count($car->orders->where('is_product', 0));
                $totalproducts = $totalproducts + count($car->orders->where('is_product', 1));

                $totalEspecial = $totalEspecial + Service::whereHas('branchServices.branchServiceProfessionals.orders.car', function ($query) use ($car){
                    $query->where('id', $car->id);
                })->where('type_service', 'Especial')->count();
                $montoEspecial = $montoEspecial + Service::whereHas('branchServices.branchServiceProfessionals.orders.car', function ($query) use ($car){
                    $query->where('id', $car->id);
                })->where('type_service', 'Especial')->sum('price_service'); 
            }
            //Log::info($services);
          return $result = [
            'Monto Generado' => round($cars->sum('amount'),2),
            'Propina' => round($cars->sum('tip'), 2),
            'Producto mas Vendido' => $products ? $products->name : null,
            'Cantidad del Producto' => $products ? $products->orders_count : 0,
            'Total de Productos Vendidos' => $totalproducts,
            'Servicio mas Brindado' => $services ? $services->name : null,
            'Cantidad del Servicio' => $services ? $services->orders_count : 0,
            'Total de Servicios Brindados' => $totalservices,
            'Servicios Seleccionados' => $seleccionado,
            'Servicios Aleatorios' => $aleatorio,
            'Servicios Especiales' => $totalEspecial,
            'Monto Servicios Especiales' => round($montoEspecial, 2),
            'Clientes Atendidos' => $totalClients
          ];
    }

    public function company_winner_month($month)
    {
           $branches = Branch::all();
           $result = [];
           $i = 0;
           $total_company = 0;
           foreach ($branches as $branch) {
            $cars = Car::whereHas('clientProfessional', function ($query) use ($branch){
                $query->whereHas('professional.branches', function ($query) use ($branch){
                    $query->where('branch_id', $branch->id);
                });
            })->whereHas('orders', function ($query) use ($month){
                $query->whereMonth('data', $month);
                })->get()->map(function ($car){
                    return [
                        'earnings' => $car->amount
                    ];
                });
                $result[$i]['name'] = $branch->name;
                $result[$i++]['earnings'] = round($cars->sum('earnings'),2);
                $total_company += round($cars->sum('earnings'),2);
            }//foreach
          return [
            'branches' => $result,
            'totalEarnings' => $total_company
          ];
    }

    public function company_winner_periodo($startDate ,$endDate)
    {
           $branches = Branch::all();
           $result = [];
           $i = 0;
           $total_company = 0;
           foreach ($branches as $branch) {
            $cars = Car::whereHas('clientProfessional', function ($query) use ($branch){
                $query->whereHas('professional.branches', function ($query) use ($branch){
                    $query->where('branch_id', $branch->id);
                });
            })->whereHas('orders', function ($query) use ($startDate ,$endDate){
                $query->whereBetWeen('data', [$startDate ,$endDate]);
                })->get()->map(function ($car){
                    return [
                        'earnings' => $car->amount
                    ];
                });
                $result[$i]['name'] = $branch->name;
                $result[$i++]['earnings'] = round($cars->sum('earnings'),2);
                $total_company += round($cars->sum('earnings'),2);
            }//foreach
          return [
            'branches' => $result,
            'totalEarnings' => $total_company
          ];
    }

    public function company_winner_date()
    {
           $branches = Branch::all();
           $result = [];
           $i = 0;
           $total_company = 0;
           $data= Carbon::now()->toDateString();
           foreach ($branches as $branch) {
            $cars = Car::whereHas('clientProfessional', function ($query) use ($branch){
                $query->whereHas('professional.branches', function ($query) use ($branch){
                    $query->where('branch_id', $branch->id);
                });
            })->whereHas('orders', function ($query) use ($data){
                $query->whereDate('data', $data);
                })->get()->map(function ($car){
                    return [
                        'earnings' => $car->amount
                    ];
                });
                $result[$i]['name'] = $branch->name;
                $result[$i++]['earnings'] = round($cars->sum('earnings'),2);
                $total_company += round($cars->sum('earnings'),2);
            }//foreach
          return [
            'branches' => $result,
            'totalEarnings' => $total_company
          ];
    }

}