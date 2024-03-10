<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Car;
use App\Models\Order;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Professional;
use App\Models\Service;
use Illuminate\Support\Facades\Log;

class BranchService
{

    public function branch_winner_date($branch_id)
    {

        $cars = Car::whereHas('clientProfessional.professional.branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->whereHas('orders', function ($query) {
            $query->whereDate('data', Carbon::now());
        })->get();
        $totalservices = 0;
        $totalproducts = 0;
        $seleccionado = 0;
        $aleatorio = 0;
        $totalClients = 0;
        $totalClients = $cars->count();
        $products = Product::withCount(['orders' => function ($query) {
            $query->whereDate('data', Carbon::now());
        }])->whereHas('productStores.store.branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->orderByDesc('orders_count')->first();
        $services = Service::withCount(['orders' => function ($query) {
            $query->whereDate('data', Carbon::now());
        }])->whereHas('branchServices', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->orderByDesc('orders_count')->first();
        foreach ($cars as $car) {
            if ($car->select_professional == 1) {
                $seleccionado = $seleccionado + $car->orders->where('is_product', 0)->count();
            } else {
                $aleatorio = $aleatorio + $car->orders->where('is_product', 0)->count();
            }
            $totalservices = $totalservices + count($car->orders->where('is_product', 0));
            $totalproducts = $totalproducts + count($car->orders->where('is_product', 1));
        }
        $orders = Order::whereHas('branchServiceProfessional.branchService', function ($query) use ($branch_id) {
            $query->whereHas('service', function ($query) {
                $query->where('type_service', 'Especial');
            })->where('branch_id', $branch_id);
        })->whereDate('data', Carbon::now())->get();
        return $result = [
            'Monto Generado' => round($cars->sum('amount') + ($cars->sum('technical_assistance') * 5000), 2),
            'Propina' => round($cars->sum('tip'), 2),
            'Producto mas Vendido' => $products->orders_count ? $products->name : null,
            'Cantidad del Producto' => $products->orders_count ? $products->orders_count : 0,
            'Total de Productos Vendidos' => $totalproducts,
            'Servicio mas Brindado' => $services->orders_count ? $services->name : null,
            'Cantidad del Servicio' => $services->orders_count ? $services->orders_count : 0,
            'Total de Servicios Brindados' => $totalservices,
            'Servicios Seleccionados' => $seleccionado,
            'Servicios Aleatorios' => $aleatorio,
            'Servicios Especiales' => $orders->count(),
            'Monto Servicios Especiales' => round($orders->sum('price'), 2),
            'Clientes Atendidos' => $totalClients
        ];
    }

    public function branch_winner_month($branch_id, $month, $year)
    {
        $cars = Car::whereHas('clientProfessional.professional.branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->whereHas('orders', function ($query) use ($month, $year) {
            $query->whereMonth('data', $month)->whereYear('data', $year);
        })->get();
        $totalservices = 0;
        $totalproducts = 0;
        $seleccionado = 0;
        $aleatorio = 0;
        $totalClients = 0;
        $totalClients = $cars->count();
        $products = Product::withCount(['orders' => function ($query) use ($month, $year) {
            $query->whereMonth('data', $month)->whereYear('data', $year);
        }])->whereHas('productStores.store.branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->orderByDesc('orders_count')->first();
        $services = Service::withCount(['orders' => function ($query) use ($month, $year) {
            $query->whereMonth('data', $month)->whereYear('data', $year);
        }])->whereHas('branchServices', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->orderByDesc('orders_count')->first();
        foreach ($cars as $car) {
            if ($car->select_professional == 1) {
                $seleccionado = $seleccionado + $car->orders->where('is_product', 0)->count();
            } else {
                $aleatorio = $aleatorio + $car->orders->where('is_product', 0)->count();
            }
            $totalservices = $totalservices + count($car->orders->where('is_product', 0));
            $totalproducts = $totalproducts + count($car->orders->where('is_product', 1));
        }
        $orders = Order::whereHas('branchServiceProfessional.branchService', function ($query) use ($branch_id) {
            $query->whereHas('service', function ($query) {
                $query->where('type_service', 'Especial');
            })->where('branch_id', $branch_id);
        })->whereMonth('data', $month)->whereYear('data', $year)->get();
        return $result = [
            'Monto Generado' => round($cars->sum('amount') + ($cars->sum('technical_assistance') * 5000), 2),
            'Propina' => round($cars->sum('tip'), 2),
            'Producto mas Vendido' => $products->orders_count ? $products->name : null,
            'Cantidad del Producto' => $products->orders_count ? $products->orders_count : 0,
            'Total de Productos Vendidos' => $totalproducts,
            'Servicio mas Brindado' => $services->orders_count ? $services->name : null,
            'Cantidad del Servicio' => $services->orders_count ? $services->orders_count : 0,
            'Total de Servicios Brindados' => $totalservices,
            'Servicios Seleccionados' => $seleccionado,
            'Servicios Aleatorios' => $aleatorio,
            'Servicios Especiales' => $orders->count(),
            'Monto Servicios Especiales' => round($orders->sum('price'), 2),
            'Clientes Atendidos' => $totalClients
        ];
    }

    public function branch_winner_periodo($branch_id, $startDate, $endDate)
    {
        $cars = Car::whereHas('clientProfessional.professional.branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->whereHas('orders', function ($query) use ($startDate, $endDate) {
            $query->whereBetWeen('data', [$startDate, $endDate]);
        })->get();
        $totalClients = 0;
        $totalservices = 0;
        $totalproducts = 0;
        $seleccionado = 0;
        $aleatorio = 0;
        $totalClients = $cars->count();
        $products = Product::withCount(['orders' => function ($query) use ($startDate, $endDate) {
            $query->whereBetWeen('data', [$startDate, $endDate]);
        }])->whereHas('productStores.store.branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->orderByDesc('orders_count')->first();
        Log::info("obtener los servicios");
        $services = Service::withCount(['orders' => function ($query) use ($startDate, $endDate) {
            $query->whereBetWeen('data', [$startDate, $endDate]);
        }])->whereHas('branchServices', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->orderByDesc('orders_count')->first();
        foreach ($cars as $car) {
            if ($car->select_professional == 1) {
                $seleccionado = $seleccionado + $car->orders->where('is_product', 0)->count();
            } else {
                $aleatorio = $aleatorio + $car->orders->where('is_product', 0)->count();
            }
            $totalservices = $totalservices + count($car->orders->where('is_product', 0));
            $totalproducts = $totalproducts + count($car->orders->where('is_product', 1));
        }
        $orders = Order::whereHas('branchServiceProfessional.branchService', function ($query) use ($branch_id) {
            $query->whereHas('service', function ($query) {
                $query->where('type_service', 'Especial');
            })->where('branch_id', $branch_id);
        })->whereBetWeen('data', [$startDate, $endDate])->get();
        //Log::info($services);
        return $result = [
            'Monto Generado' => round($cars->sum('amount') + ($cars->sum('technical_assistance') * 5000), 2),
            'Propina' => round($cars->sum('tip'), 2),
            'Producto mas Vendido' => $products->orders_count ? $products->name : null,
            'Cantidad del Producto' => $products->orders_count ? $products->orders_count : 0,
            'Total de Productos Vendidos' => $totalproducts,
            'Servicio mas Brindado' => $services->orders_count ? $services->name : null,
            'Cantidad del Servicio' => $services->orders_count ? $services->orders_count : 0,
            'Total de Servicios Brindados' => $totalservices,
            'Servicios Seleccionados' => $seleccionado,
            'Servicios Aleatorios' => $aleatorio,
            'Servicios Especiales' => $orders->count(),
            'Monto Servicios Especiales' => round($orders->sum('price'), 2),
            'Clientes Atendidos' => $totalClients
        ];
    }

    public function branch_winner_periodo_icon($branch_id, $startDate, $endDate)
    {
        $cars = Car::whereHas('clientProfessional.professional.branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->whereHas('orders', function ($query) use ($startDate, $endDate) {
            $query->whereBetWeen('data', [$startDate, $endDate]);
        })->get();
        $totalClients = 0;
        $totalservices = 0;
        $totalproducts = 0;
        $seleccionado = 0;
        $aleatorio = 0;
        $transformedResult = [];
        $totalClients = $cars->count();
       if(!$totalClients = $cars->count()){
        return $transformedResult;
       }
        $products = Product::withCount(['orders' => function ($query) use ($startDate, $endDate) {
            $query->whereBetWeen('data', [$startDate, $endDate]);
        }])->whereHas('productStores.store.branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->orderByDesc('orders_count')->first();
        Log::info("obtener los servicios");
        $services = Service::withCount(['orders' => function ($query) use ($startDate, $endDate) {
            $query->whereBetWeen('data', [$startDate, $endDate]);
        }])->whereHas('branchServices', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->orderByDesc('orders_count')->first();
        foreach ($cars as $car) {
            if ($car->select_professional == 1) {
                $seleccionado = $seleccionado + $car->orders->where('is_product', 0)->count();
            } else {
                $aleatorio = $aleatorio + $car->orders->where('is_product', 0)->count();
            }
            $totalservices = $totalservices + count($car->orders->where('is_product', 0));
            $totalproducts = $totalproducts + count($car->orders->where('is_product', 1));
        }
        $orders = Order::whereHas('branchServiceProfessional.branchService', function ($query) use ($branch_id) {
            $query->whereHas('service', function ($query) {
                $query->where('type_service', 'Especial');
            })->where('branch_id', $branch_id);
        })->whereBetWeen('data', [$startDate, $endDate])->get();
        //Log::info($services);
        $result = [
            'Monto Generado' => round($cars->sum('amount') + ($cars->sum('technical_assistance') * 5000), 2),
            'Propina' => round($cars->sum('tip'), 2),
            'Producto mas Vendido' => $products->orders_count ? $products->name : null,
            'Cantidad del Producto' => $products->orders_count ? $products->orders_count : 0,
            'Total de Productos Vendidos' => $totalproducts ? $totalproducts : 0,
            'Servicio mas Brindado' => $services->orders_count ? $services->name : null,
            'Cantidad del Servicio' => $services->orders_count ? $services->orders_count : 0,
            'Total de Servicios Brindados' => $totalservices,
            'Servicios Seleccionados' => $seleccionado,
            'Servicios Aleatorios' => $aleatorio,
            'Servicios Especiales' => $orders->count(),
            'Monto Servicios Especiales' => round($orders->sum('price'), 2),
            'Clientes Atendidos' => $totalClients
        ];
        $iconColorMapping = [
            'Monto Generado' => ['icon' => 'mdi-wallet', 'color' => 'green'],
            'Propina' => ['icon' => 'mdi-cash', 'color' => 'blue'],
            'Producto mas Vendido' => ['icon' => 'mdi-cart', 'color' => 'red'],
            'Cantidad del Producto' => ['icon' => 'mdi-format-list-numbered', 'color' => 'orange'],
            'Total de Productos Vendidos' => ['icon' => 'mdi-cash-register', 'color' => 'purple'],
            'Servicio mas Brindado' => ['icon' => 'mdi-wrench', 'color' => 'pink'],
            'Cantidad del Servicio' => ['icon' => 'mdi-counter', 'color' => 'teal'],
            'Total de Servicios Brindados' => ['icon' => 'mdi-hammer-screwdriver', 'color' => 'cyan'],
            'Servicios Seleccionados' => ['icon' => 'mdi-check-circle', 'color' => 'lime'],
            'Servicios Aleatorios' => ['icon' => 'mdi-shuffle', 'color' => 'amber'],
            'Servicios Especiales' => ['icon' => 'mdi-star', 'color' => 'yellow'],
            'Monto Servicios Especiales' => ['icon' => 'mdi-lightning-bolt', 'color' => 'deep_orange'],
            'Clientes Atendidos' => ['icon' => 'mdi-account-multiple', 'color' =>'indigo'],
        ];

        $transformedResult = [];

        foreach ($result as $key => $value) {
            $transformedResult[$key] = [
                'value' => $value,
                'icon' => $iconColorMapping[$key]['icon'] ?? 'default_icon',
                'color' => $iconColorMapping[$key]['color'] ?? 'default_color',
            ];
        }

        return $transformedResult;
    }

    public function branch_winner_month_icon($branch_id, $month, $year)
    {
        $cars = Car::whereHas('clientProfessional.professional.branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->whereHas('orders', function ($query) use ($month, $year) {
            $query->whereMonth('data', $month)->whereYear('data', $year);
        })->get();
        $totalservices = 0;
        $totalproducts = 0;
        $seleccionado = 0;
        $aleatorio = 0;
        $totalClients = 0;
        $transformedResult = [];
        $totalClients = $cars->count();
       if(!$totalClients = $cars->count()){
        return $transformedResult;
       }
        $products = Product::withCount(['orders' => function ($query) use ($month, $year) {
            $query->whereMonth('data', $month)->whereYear('data', $year);
        }])->whereHas('productStores.store.branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->orderByDesc('orders_count')->first();
        $services = Service::withCount(['orders' => function ($query) use ($month, $year) {
            $query->whereMonth('data', $month)->whereYear('data', $year);
        }])->whereHas('branchServices', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->orderByDesc('orders_count')->first();
        foreach ($cars as $car) {
            if ($car->select_professional == 1) {
                $seleccionado = $seleccionado + $car->orders->where('is_product', 0)->count();
            } else {
                $aleatorio = $aleatorio + $car->orders->where('is_product', 0)->count();
            }
            $totalservices = $totalservices + count($car->orders->where('is_product', 0));
            $totalproducts = $totalproducts + count($car->orders->where('is_product', 1));
        }
        $orders = Order::whereHas('branchServiceProfessional.branchService', function ($query) use ($branch_id) {
            $query->whereHas('service', function ($query) {
                $query->where('type_service', 'Especial');
            })->where('branch_id', $branch_id);
        })->whereMonth('data', $month)->whereYear('data', $year)->get();
        $result = [
            'Monto Generado' => round($cars->sum('amount') + ($cars->sum('technical_assistance') * 5000), 2),
            'Propina' => round($cars->sum('tip'), 2),
            'Producto mas Vendido' => $products->orders_count ? $products->name : null,
            'Cantidad del Producto' => $products->orders_count ? $products->orders_count : 0,
            'Total de Productos Vendidos' => $totalproducts ? $totalproducts : 0,
            'Servicio mas Brindado' => $services->orders_count ? $services->name : null,
            'Cantidad del Servicio' => $services->orders_count ? $services->orders_count : 0,
            'Total de Servicios Brindados' => $totalservices,
            'Servicios Seleccionados' => $seleccionado,
            'Servicios Aleatorios' => $aleatorio,
            'Servicios Especiales' => $orders->count(),
            'Monto Servicios Especiales' => round($orders->sum('price'), 2),
            'Clientes Atendidos' => $totalClients
        ];
        $iconColorMapping = [
            'Monto Generado' => ['icon' => 'mdi-wallet', 'color' => 'green'],
            'Propina' => ['icon' => 'mdi-cash', 'color' => 'blue'],
            'Producto mas Vendido' => ['icon' => 'mdi-cart', 'color' => 'red'],
            'Cantidad del Producto' => ['icon' => 'mdi-format-list-numbered', 'color' => 'orange'],
            'Total de Productos Vendidos' => ['icon' => 'mdi-cash-register', 'color' => 'purple'],
            'Servicio mas Brindado' => ['icon' => 'mdi-wrench', 'color' => 'pink'],
            'Cantidad del Servicio' => ['icon' => 'mdi-counter', 'color' => 'teal'],
            'Total de Servicios Brindados' => ['icon' => 'mdi-hammer-screwdriver', 'color' => 'cyan'],
            'Servicios Seleccionados' => ['icon' => 'mdi-check-circle', 'color' => 'lime'],
            'Servicios Aleatorios' => ['icon' => 'mdi-shuffle', 'color' => 'amber'],
            'Servicios Especiales' => ['icon' => 'mdi-star', 'color' => 'yellow'],
            'Monto Servicios Especiales' => ['icon' => 'mdi-lightning-bolt', 'color' => 'deep_orange'],
            'Clientes Atendidos' => ['icon' => 'mdi-account-multiple', 'color' =>'indigo'],
        ];

        $transformedResult = [];

        foreach ($result as $key => $value) {
            $transformedResult[$key] = [
                'value' => $value,
                'icon' => $iconColorMapping[$key]['icon'] ?? 'default_icon',
                'color' => $iconColorMapping[$key]['color'] ?? 'default_color',
            ];
        }

        return $transformedResult;
    }

    public function branch_winner_date_icon($branch_id)
    {

        $cars = Car::whereHas('clientProfessional.professional.branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->whereHas('orders', function ($query) {
            $query->whereDate('data', Carbon::now());
        })->get();
        
        $totalservices = 0;
        $totalproducts = 0;
        $seleccionado = 0;
        $aleatorio = 0;
        $totalClients = 0;
        
        $transformedResult = [];
        $totalClients = $cars->count();
       if(!$totalClients = $cars->count()){
        return $transformedResult;
       }
        $products = Product::withCount(['orders' => function ($query) {
            $query->whereDate('data', Carbon::now());
        }])->whereHas('productStores.store.branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->orderByDesc('orders_count')->first();
        $services = Service::withCount(['orders' => function ($query) {
            $query->whereDate('data', Carbon::now());
        }])->whereHas('branchServices', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->orderByDesc('orders_count')->first();
        foreach ($cars as $car) {
            if ($car->select_professional == 1) {
                $seleccionado = $seleccionado + $car->orders->where('is_product', 0)->count();
            } else {
                $aleatorio = $aleatorio + $car->orders->where('is_product', 0)->count();
            }
            $totalservices = $totalservices + count($car->orders->where('is_product', 0));
            $totalproducts = $totalproducts + count($car->orders->where('is_product', 1));
        }
        $orders = Order::whereHas('branchServiceProfessional.branchService', function ($query) use ($branch_id) {
            $query->whereHas('service', function ($query) {
                $query->where('type_service', 'Especial');
            })->where('branch_id', $branch_id);
        })->whereDate('data', Carbon::now())->get();
        $result = [
            'Monto Generado' => round($cars->sum('amount') + ($cars->sum('technical_assistance') * 5000), 2),
            'Propina' => round($cars->sum('tip'), 2),
            'Producto mas Vendido' => $products->orders_count ? $products->name : null,
            'Cantidad del Producto' => $products->orders_count ? $products->orders_count : 0,
            'Total de Productos Vendidos' => $totalproducts ? $totalproducts : 0,
            'Servicio mas Brindado' => $services->orders_count ? $services->name : null,
            'Cantidad del Servicio' => $services->orders_count ? $services->orders_count : 0,
            'Total de Servicios Brindados' => $totalservices,
            'Servicios Seleccionados' => $seleccionado,
            'Servicios Aleatorios' => $aleatorio,
            'Servicios Especiales' => $orders->count(),
            'Monto Servicios Especiales' => round($orders->sum('price'), 2),
            'Clientes Atendidos' => $totalClients
        ];
        $iconColorMapping = [
            'Monto Generado' => ['icon' => 'mdi-wallet', 'color' => 'green'],
            'Propina' => ['icon' => 'mdi-cash', 'color' => 'blue'],
            'Producto mas Vendido' => ['icon' => 'mdi-cart', 'color' => 'red'],
            'Cantidad del Producto' => ['icon' => 'mdi-format-list-numbered', 'color' => 'orange'],
            'Total de Productos Vendidos' => ['icon' => 'mdi-cash-register', 'color' => 'purple'],
            'Servicio mas Brindado' => ['icon' => 'mdi-wrench', 'color' => 'pink'],
            'Cantidad del Servicio' => ['icon' => 'mdi-counter', 'color' => 'teal'],
            'Total de Servicios Brindados' => ['icon' => 'mdi-hammer-screwdriver', 'color' => 'cyan'],
            'Servicios Seleccionados' => ['icon' => 'mdi-check-circle', 'color' => 'lime'],
            'Servicios Aleatorios' => ['icon' => 'mdi-shuffle', 'color' => 'amber'],
            'Servicios Especiales' => ['icon' => 'mdi-star', 'color' => 'yellow'],
            'Monto Servicios Especiales' => ['icon' => 'mdi-cash', 'color' => 'blue'],
            'Clientes Atendidos' => ['icon' => 'mdi-account-multiple', 'color' =>'indigo'],
        ];


        foreach ($result as $key => $value) {
            $transformedResult[$key] = [
                'value' => $value,
                'icon' => $iconColorMapping[$key]['icon'] ?? 'default_icon',
                'color' => $iconColorMapping[$key]['color'] ?? 'default_color',
            ];
        }

        return $transformedResult;
    }

    public function company_close_car_month($month, $year, $data)
    {
        $branches = Branch::where('business_id', $data['business_id'])->get();
        $result = [];
        $i = 0;
        $total_company = 0;
        $total_branch = 0;
        $total_product = 0;
        $total_service = 0;
        $technical_assistance = 0;
        $total_tip = 0;
        foreach ($branches as $branch) {
            $cars = Car::whereHas('clientProfessional.professional.branches', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id);
            })->whereHas('orders', function ($query) use ($month, $year) {
                $query->whereMonth('data', $month)->whereYear('data', $year);
            })->get()->map(function ($car) {
                $products = $car->orders->where('is_product', 1)->sum('price');
                $services = $car->orders->where('is_product', 0)->sum('price');
                return [
                    'earnings' => $car->amount,
                    'technical_assistance' => $car->technical_assistance * 5000,
                    'tip' => $car->tip,
                    'product' => $products,
                    'service' => $services,
                    'total' => $car->amount + $car->tip + $car->technical_assistance * 5000
                ];
            });
            $result[$i]['name'] = $branch->name;
            $result[$i]['earnings'] = round($cars->sum('earnings'), 2);
            $result[$i]['technical_assistance'] = round($cars->sum('technical_assistance'), 2);
            $result[$i]['tip'] = round($cars->sum('tip'), 2);
            $result[$i]['serviceMount'] = round($cars->sum('service'), 2);
            $result[$i]['productMount'] = round($cars->sum('product'), 2);
            $result[$i++]['total'] = round($cars->sum('total'), 2);
            $total_tip += round($cars->sum('tip'), 2);
            $total_branch += round($cars->sum('earnings'), 2);
            $total_company += round($cars->sum('total'), 2);
            $total_product += round($cars->sum('product'), 2);
            $total_service += round($cars->sum('service'), 2);
            $technical_assistance += round($cars->sum('technical_assistance'), 2);
        } //foreach
        $result[$i]['name'] = 'Total';
        $result[$i]['tip'] = $total_tip;
        $result[$i]['earnings'] = $total_branch;
        $result[$i]['technical_assistance'] = $technical_assistance;
        $result[$i]['serviceMount'] = $total_service;
        $result[$i]['productMount'] = $total_product;
        $result[$i++]['total'] = $total_company;
        return $result;
    }

    public function company_close_car_date($data)
    {
        $branches = Branch::where('business_id', $data['business_id'])->get();
        $result = [];
        $i = 0;
        $total_company = 0;
        $total_branch = 0;
        $total_product = 0;
        $total_service = 0;
        $technical_assistance = 0;
        $total_tip = 0;
        foreach ($branches as $branch) {
            $cars = Car::whereHas('clientProfessional.professional.branches', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id);
            })->whereHas('orders', function ($query) {
                $query->whereDate('data', Carbon::now()->toDateString());
            })->get()->map(function ($car) {
                $products = $car->orders->where('is_product', 1)->sum('price');
                $services = $car->orders->where('is_product', 0)->sum('price');
                return [
                    'earnings' => $car->amount,
                    'technical_assistance' => $car->technical_assistance * 5000,
                    'tip' => $car->tip,
                    'product' => $products,
                    'service' => $services,
                    'total' => $car->amount + $car->tip + $car->technical_assistance * 5000
                ];
            });
            $result[$i]['name'] = $branch->name;
            $result[$i]['earnings'] = round($cars->sum('earnings'), 2);
            $result[$i]['technical_assistance'] = round($cars->sum('technical_assistance'), 2);
            $result[$i]['tip'] = round($cars->sum('tip'), 2);
            $result[$i]['serviceMount'] = round($cars->sum('service'), 2);
            $result[$i]['productMount'] = round($cars->sum('product'), 2);
            $result[$i++]['total'] = round($cars->sum('total'), 2);
            $total_tip += round($cars->sum('tip'), 2);
            $total_branch += round($cars->sum('earnings'), 2);
            $total_company += round($cars->sum('total'), 2);
            $total_product += round($cars->sum('product'), 2);
            $total_service += round($cars->sum('service'), 2);
            $technical_assistance += round($cars->sum('technical_assistance'), 2);
        } //foreach
        $result[$i]['name'] = 'Total';
        $result[$i]['tip'] = $total_tip;
        $result[$i]['earnings'] = $total_branch;
        $result[$i]['technical_assistance'] = $technical_assistance;
        $result[$i]['serviceMount'] = $total_service;
        $result[$i]['productMount'] = $total_product;
        $result[$i++]['total'] = $total_company;
        return $result;
    }

    public function company_close_car_periodo($startDate, $endDate, $data)
    {
        $branches = Branch::where('business_id', $data['business_id'])->get();
        $result = [];
        $i = 0;
        $total_company = 0;
        $total_branch = 0;
        $total_product = 0;
        $total_service = 0;
        $technical_assistance = 0;
        $total_tip = 0;
        foreach ($branches as $branch) {
            $cars = Car::whereHas('clientProfessional.professional.branches', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id);
            })->whereHas('orders', function ($query) use ($startDate, $endDate) {
                $query->whereBetWeen('data', [$startDate, $endDate]);
            })->get()->map(function ($car) {
                $products = $car->orders->where('is_product', 1)->sum('price');
                $services = $car->orders->where('is_product', 0)->sum('price');
                return [
                    'earnings' => $car->amount,
                    'technical_assistance' => $car->technical_assistance * 5000,
                    'tip' => $car->tip,
                    'product' => $products,
                    'service' => $services,
                    'total' => $car->amount + $car->tip + $car->technical_assistance * 5000
                ];
            });
            $result[$i]['name'] = $branch->name;
            $result[$i]['earnings'] = round($cars->sum('earnings'), 2);
            $result[$i]['technical_assistance'] = round($cars->sum('technical_assistance'), 2);
            $result[$i]['tip'] = round($cars->sum('tip'), 2);
            $result[$i]['serviceMount'] = round($cars->sum('service'), 2);
            $result[$i]['productMount'] = round($cars->sum('product'), 2);
            $result[$i++]['total'] = round($cars->sum('total'), 2);
            $total_tip += round($cars->sum('tip'), 2);
            $total_branch += round($cars->sum('earnings'), 2);
            $total_company += round($cars->sum('total'), 2);
            $total_product += round($cars->sum('product'), 2);
            $total_service += round($cars->sum('service'), 2);
            $technical_assistance += round($cars->sum('technical_assistance'), 2);
        } //foreach
        $result[$i]['name'] = 'Total';
        $result[$i]['tip'] = $total_tip;
        $result[$i]['earnings'] = $total_branch;
        $result[$i]['technical_assistance'] = $technical_assistance;
        $result[$i]['serviceMount'] = $total_service;
        $result[$i]['productMount'] = $total_product;
        $result[$i++]['total'] = $total_company;
        return $result;
    }

    public function company_winner_month($month, $year, $data)
    {
        $branches = Branch::where('business_id', $data['business_id'])->get();
        $result = [];
        $i = 0;
        $total_company = 0;
        $total_branch = 0;
        $total_tip = 0;
        $technical_assistance = 0;
        foreach ($branches as $branch) {
            $cars = Car::whereHas('clientProfessional.professional.branches', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id);
            })->whereHas('orders', function ($query) use ($month, $year) {
                $query->whereMonth('data', $month)->whereYear('data', $year);
            })->get()->map(function ($car) {
                return [
                    'earnings' => $car->amount,
                    'technical_assistance' => $car->technical_assistance * 5000,
                    'tip' => $car->tip,
                    'total' => $car->amount + $car->tip + $car->technical_assistance * 5000
                ];
            });
            $result[$i]['name'] = $branch->name;
            $result[$i]['earnings'] = round($cars->sum('earnings'), 2);
            $result[$i]['technical_assistance'] = round($cars->sum('technical_assistance'), 2);
            $result[$i]['tip'] = round($cars->sum('tip'), 2);
            $result[$i++]['total'] = round($cars->sum('total'), 2);
            $total_tip += round($cars->sum('tip'), 2);
            $total_branch += round($cars->sum('earnings'), 2);
            $total_company += round($cars->sum('total'), 2);
            $technical_assistance += round($cars->sum('technical_assistance'), 2);
        } //foreach
        $result[$i]['name'] = 'Total';
        $result[$i]['tip'] = $total_tip;
        $result[$i]['earnings'] = $total_branch;
        $result[$i]['technical_assistance'] = $technical_assistance;
        $result[$i++]['total'] = $total_company;
        return $result;
    }

    public function company_winner_periodo($startDate, $endDate, $data)
    {
        $branches = Branch::where('business_id', $data['business_id'])->get();
        $result = [];
        $i = 0;
        $total_company = 0;
        $total_branch = 0;
        $total_tip = 0;
        $technical_assistance = 0;
        foreach ($branches as $branch) {
            $cars = Car::whereHas('clientProfessional.professional.branches', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id);
            })->whereHas('orders', function ($query) use ($startDate, $endDate) {
                $query->whereBetWeen('data', [$startDate, $endDate]);
            })->get()->map(function ($car) {
                return [
                    'earnings' => $car->amount,
                    'technical_assistance' => $car->technical_assistance * 5000,
                    'tip' => $car->tip,
                    'total' => $car->amount + $car->tip + $car->technical_assistance * 5000
                ];
            });
            $result[$i]['name'] = $branch->name;
            $result[$i]['earnings'] = round($cars->sum('earnings'), 2);
            $result[$i]['technical_assistance'] = round($cars->sum('technical_assistance'), 2);
            $result[$i]['tip'] = round($cars->sum('tip'), 2);
            $result[$i++]['total'] = round($cars->sum('total'), 2);
            $total_tip += round($cars->sum('tip'), 2);
            $total_branch += round($cars->sum('earnings'), 2);
            $total_company += round($cars->sum('total'), 2);
            $technical_assistance += round($cars->sum('technical_assistance'), 2);
        } //foreach
        $result[$i]['name'] = 'Total';
        $result[$i]['tip'] = $total_tip;
        $result[$i]['earnings'] = $total_branch;
        $result[$i]['technical_assistance'] = $technical_assistance;
        $result[$i++]['total'] = $total_company;
        return $result;
    }

    public function company_winner_date($data)
    {
        $branches = Branch::where('business_id', $data['business_id'])->get();
        $result = [];
        $i = 0;
        $total_company = 0;
        $total_tip = 0;
        $total_branch = 0;
        $technical_assistance = 0;
        $data = Carbon::now()->toDateString();
        foreach ($branches as $branch) {
            $cars = Car::whereHas('clientProfessional.professional.branches', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id);
            })->whereHas('orders', function ($query) use ($data) {
                $query->whereDate('data', $data);
            })->get()->map(function ($car) {
                return [
                    'earnings' => $car->amount,
                    'technical_assistance' => $car->technical_assistance * 5000,
                    'tip' => $car->tip,
                    'total' => $car->amount + $car->tip + $car->technical_assistance * 5000
                ];
            });
            $result[$i]['name'] = $branch->name;
            $result[$i]['earnings'] = round($cars->sum('earnings'), 2);
            $result[$i]['technical_assistance'] = round($cars->sum('technical_assistance'), 2);
            $result[$i]['tip'] = round($cars->sum('tip'), 2);
            $result[$i++]['total'] = round($cars->sum('total'), 2);
            $total_tip += round($cars->sum('tip'), 2);
            $total_branch += round($cars->sum('earnings'), 2);
            $total_company += round($cars->sum('total'), 2);
            $technical_assistance += round($cars->sum('technical_assistance'), 2);
        } //foreach
        $result[$i]['name'] = 'Total';
        $result[$i]['tip'] = $total_tip;
        $result[$i]['earnings'] = $total_branch;
        $result[$i]['technical_assistance'] = $technical_assistance;
        $result[$i++]['total'] = $total_company;
        return $result;
    }

    public function branch_professionals_winner_date($branch_id)
    {
        return $resultados = Professional::with(['orders' => function ($query) {
            $query->whereDate('data',  Carbon::now());
        }, 'clientProfessionals.cars.orders' => function ($query) {
            $query->whereDate('data', Carbon::now());
        }])->whereHas('branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })
            ->get()->map(function ($professional) {
                $totalOrdersAmount = $professional->orders ? round($professional->orders->sum('price') * 0.45, 2) : 0;
    $totalTips = $professional->orders ? round(($professional->clientProfessionals->map(function ($clientProfessional) {
        return $clientProfessional->cars->filter(function ($car) {
            return $car->orders->isNotEmpty();
        })->sum('tip') * 0.8;
    }))->sum(), 2) : 0;

    $totalCars = $professional->clientProfessionals->map(function ($clientProfessional) {
        return $clientProfessional->cars->count();
    })->sum();

    return [
        'name' => $professional->name . " " . $professional->surname . " " . $professional->second_surname,
        'amount' => $totalOrdersAmount,
        'tip' => $totalTips,
        'total' => round($totalOrdersAmount + $totalTips, 2),
        'total_cars' => $totalCars,
    ];
            })->sortByDesc('total')->values();
    }

    public function branch_professionals_winner_month($branch_id, $month, $year)
    {
        return $resultados = Professional::with(['orders' => function ($query) use ($month, $year) {
            $query->whereMonth('data', $month)->whereYear('data', $year);
        }, 'clientProfessionals.cars.orders' => function ($query) use ($month, $year) {
            $query->whereMonth('data', $month)->whereYear('data', $year);
        }])->whereHas('branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })
            ->get()->map(function ($professional) {
                return [
                    'name' => $professional->name . " " . $professional->surname . " " . $professional->second_surname,
                    'amount' => $professional->orders ? round($professional->orders->sum('price') * 0.45, 2) : 0,
                    'tip' => $professional->orders ? round(($professional->clientProfessionals->map(function ($clientProfessional) {
                        return $clientProfessional->cars->filter(function ($car) {
                            return $car->orders->isNotEmpty();
                        })->sum('tip') * 0.8;
                    }))->sum(), 2) : 0,
                    'total' => $professional->orders ? round(($professional->orders->sum('price') * 0.45) + ($professional->clientProfessionals->map(function ($clientProfessional) {
                        return $clientProfessional->cars->filter(function ($car) {
                            return $car->orders->isNotEmpty();
                        })->sum('tip') * 0.8;
                    }))->sum(), 2) : 0
                ];
            })->sortByDesc('total')->values();
    }

    public function branch_professionals_winner_periodo($branch_id, $startDate, $endDate)
    {
        return $resultados = Professional::with(['orders' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('data', [$startDate, $endDate]);
        }, 'clientProfessionals.cars.orders' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('data', [$startDate, $endDate]);
        }])->whereHas('branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })
            ->get()->map(function ($professional) {
                return [
                    'name' => $professional->name . " " . $professional->surname . " " . $professional->second_surname,
                    'amount' => $professional->orders ? round($professional->orders->sum('price') * 0.45, 2) : 0,
                    'tip' => $professional->orders ? round(($professional->clientProfessionals->map(function ($clientProfessional) {
                        return $clientProfessional->cars->filter(function ($car) {
                            return $car->orders->isNotEmpty();
                        })->sum('tip') * 0.8;
                    }))->sum(), 2) : 0,
                    'total' => $professional->orders ? round(($professional->orders->sum('price') * 0.45) + ($professional->clientProfessionals->map(function ($clientProfessional) {
                        return $clientProfessional->cars->filter(function ($car) {
                            return $car->orders->isNotEmpty();
                        })->sum('tip') * 0.8;
                    }))->sum(), 2) : 0
                ];
            })->sortByDesc('total')->values();
    }
}
