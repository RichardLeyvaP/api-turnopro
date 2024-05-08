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
        $cars = Car::whereHas('reservation', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id)->whereDate('data', Carbon::now());
        })->where('pay', 1)->get();
        $carIds = $cars->pluck('id');
        
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
        $products = Product::with(['orders' => function ($query) use ($carIds) {
            $query->selectRaw('SUM(cant) as total_sale_price')
                ->groupBy('product_id')
                ->whereIn('car_id', $carIds)
                ->where('is_product', 1);
        }])
        ->get()->filter(function ($product) {
            return !$product->orders->isEmpty();
        })->values()->sortByDesc(function ($product) {
            return $product->orders->sum('total_cant');
        });
        $mostSoldProduct = $products->first();

        // Obtener el nombre y la cantidad del producto más vendido
        $mostSoldProductName = $mostSoldProduct ? $mostSoldProduct->name: '';
        $mostSoldProductQuantity = $mostSoldProduct ? $mostSoldProduct->orders->sum('total_cant') : 0;
        $totalSoldProducts = $products->sum(function ($product) {
            return $product->orders->sum('total_cant');
        });
        $services = Service::withCount(['orders' => function ($query) use ($carIds) {
            $query->whereIn('car_id', $carIds)->where('is_product', 0);
        }])->orderByDesc('orders_count')->first();
        foreach ($cars as $car) {
            if ($car->select_professional == 1) {
                $seleccionado = $seleccionado + 1;
            } else {
                $aleatorio = $aleatorio + 1;
            }
            $totalservices = $totalservices + count($car->orders->where('is_product', 0));
            //$totalproducts = $totalproducts + count($car->orders->where('is_product', 1));
        }
        $orders = Order::whereIn('car_id', $carIds)->whereHas('branchServiceProfessional', function ($query) {
            $query->where('type_service', 'Especial');
        })->get();
        //Log::info($services);
        return $result = [
            'Monto Generado' => round($cars->sum('amount') + ($cars->sum('technical_assistance') * 5000), 2),
            'Propina' => round($cars->sum('tip'), 2),
            'Producto más Vendido' => $mostSoldProductName,
            'Cantidad del Producto' => $mostSoldProductQuantity,
            'Total de Productos Vendidos' => $totalSoldProducts,
            'Servicio más Brindado' => $services->orders_count ? $services->name : null,
            'Cantidad del Servicio' => $services->orders_count ? $services->orders_count : 0,
            'Total de Servicios Brindados' => $totalservices,
            'Clientes Seleccionados' => $seleccionado,
            'Clientes Aleatorios' => $aleatorio,
            'Servicios Especiales' => $orders->count(),
            'Monto Servicios Especiales' => round($orders->sum('price'), 2),
            'Clientes Atendidos' => $totalClients
        ];
    }

    public function branch_winner_month($branch_id, $month, $year)
    {
        $cars = Car::whereHas('reservation', function ($query) use ($branch_id, $month, $year) {
            $query->whereMonth('data', $month)->whereYear('data', $year)->where('branch_id', $branch_id);
        })->where('pay', 1)->get();
        $carIds = $cars->pluck('id');
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
        $products = Product::with(['orders' => function ($query) use ($carIds) {
            $query->selectRaw('SUM(cant) as total_cant')
                ->groupBy('product_id')
                ->whereIn('car_id', $carIds)
                ->where('is_product', 1);
        }])
        ->get()->filter(function ($product) {
            return !$product->orders->isEmpty();
        })->values()->sortByDesc(function ($product) {
            return $product->orders->sum('total_cant');
        });
        $mostSoldProduct = $products->first();

        // Obtener el nombre y la cantidad del producto más vendido
        $mostSoldProductName = $mostSoldProduct ? $mostSoldProduct->name: '';
        $mostSoldProductQuantity = $mostSoldProduct ? $mostSoldProduct->orders->sum('total_cant') : 0;
        $totalSoldProducts = $products->sum(function ($product) {
            return $product->orders->sum('total_cant');
        });
        $services = Service::withCount(['orders' => function ($query) use ($carIds) {
            $query->whereIn('car_id', $carIds)->where('is_product', 0);
        }])->orderByDesc('orders_count')->first();
        foreach ($cars as $car) {
            if ($car->select_professional == 1) {
                $seleccionado = $seleccionado + 1;
            } else {
                $aleatorio = $aleatorio + 1;
            }
            $totalservices = $totalservices + count($car->orders->where('is_product', 0));
            //$totalproducts = $totalproducts + count($car->orders->where('is_product', 1));
        }
        $orders = Order::whereIn('car_id', $carIds)->whereHas('branchServiceProfessional', function ($query) {
            $query->where('type_service', 'Especial');
        })->get();
        //Log::info($services);
        return $result = [
            'Monto Generado' => round($cars->sum('amount') + ($cars->sum('technical_assistance') * 5000), 2),
            'Propina' => round($cars->sum('tip'), 2),
            'Producto más Vendido' => $mostSoldProductName,
            'Cantidad del Producto' => $mostSoldProductQuantity,
            'Total de Productos Vendidos' => $totalSoldProducts,
            'Servicio más Brindado' => $services->orders_count ? $services->name : null,
            'Cantidad del Servicio' => $services->orders_count ? $services->orders_count : 0,
            'Total de Servicios Brindados' => $totalservices,
            'Clientes Seleccionados' => $seleccionado,
            'Clientes Aleatorios' => $aleatorio,
            'Servicios Especiales' => $orders->count(),
            'Monto Servicios Especiales' => round($orders->sum('price'), 2),
            'Clientes Atendidos' => $totalClients
        ];

    }

    public function branch_winner_periodo($branch_id, $startDate, $endDate)
    {
        $cars = Car::whereHas('reservation', function ($query) use ($branch_id, $startDate, $endDate) {
            $query->whereDate('data', '>=', $startDate)->whereDate('data', '<=', $endDate)->where('branch_id', $branch_id);
        })->where('pay', 1)->get();
        $carIds = $cars->pluck('id');
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
        /*$products = Product::withCount(['orders' => function ($query) use ($startDate, $endDate, $carIds) {
            $query->whereIn('car_id', $carIds)->where('is_product', 1);
        }])->orderByDesc('orders_count')->first();
        Log::info("obtener los servicios");*/
        $products = Product::with(['orders' => function ($query) use ($carIds) {
            $query->selectRaw('SUM(cant) as total_cant')
                ->groupBy('product_id')
                ->whereIn('car_id', $carIds)
                ->where('is_product', 1);
        }])
        ->get()->filter(function ($product) {
            return !$product->orders->isEmpty();
        })->values()->sortByDesc(function ($product) {
            return $product->orders->sum('total_cant');
        });
        $mostSoldProduct = $products->first();

        // Obtener el nombre y la cantidad del producto más vendido
        $mostSoldProductName = $mostSoldProduct ? $mostSoldProduct->name: '';
        $mostSoldProductQuantity = $mostSoldProduct ? $mostSoldProduct->orders->sum('total_cant') : 0;
        $totalSoldProducts = $products->sum(function ($product) {
            return $product->orders->sum('total_cant');
        });
        $services = Service::withCount(['orders' => function ($query) use ($startDate, $endDate, $carIds) {
            $query->whereIn('car_id', $carIds)->where('is_product', 0);
        }])->orderByDesc('orders_count')->first();
        foreach ($cars as $car) {
            if ($car->select_professional == 1) {
                $seleccionado = $seleccionado + 1;
            } else {
                $aleatorio = $aleatorio + 1;
            }
            $totalservices = $totalservices + count($car->orders->where('is_product', 0));
            //$totalproducts = $totalproducts + count($car->orders->where('is_product', 1));
        }
        $orders = Order::whereIn('car_id', $carIds)->whereHas('branchServiceProfessional', function ($query) {
            $query->where('type_service', 'Especial');
        })->get();
        //Log::info($services);
        return $result = [
            'Monto Generado' => round($cars->sum('amount') + ($cars->sum('technical_assistance') * 5000), 2),
            'Propina' => round($cars->sum('tip'), 2),
            'Producto más Vendido' => $mostSoldProductName,
            'Cantidad del Producto' => $mostSoldProductQuantity,
            'Total de Productos Vendidos' => $totalSoldProducts,
            'Servicio más Brindado' => $services->orders_count ? $services->name : null,
            'Cantidad del Servicio' => $services->orders_count ? $services->orders_count : 0,
            'Total de Servicios Brindados' => $totalservices,
            'Clientes Seleccionados' => $seleccionado,
            'Clientes Aleatorios' => $aleatorio,
            'Servicios Especiales' => $orders->count(),
            'Monto Servicios Especiales' => round($orders->sum('price'), 2),
            'Clientes Atendidos' => $totalClients
        ];
    }

    public function branch_winner_periodo_icon($branch_id, $startDate, $endDate)
    {
       $cars = Car::whereHas('reservation', function ($query) use ($branch_id, $startDate, $endDate) {
            $query->whereDate('data', '>=', $startDate)->whereDate('data', '<=', $endDate)->where('branch_id', $branch_id);
        })->where('pay', 1)->get();
        $carIds = $cars->pluck('id');
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
        /*$products = Product::withCount(['orders' => function ($query) use ($startDate, $endDate, $carIds) {
            $query->whereIn('car_id', $carIds)->where('is_product', 1);
        }])->orderByDesc('orders_count')->first();
        Log::info("obtener los servicios");*/
        $products = Product::with(['orders' => function ($query) use ($carIds) {
            $query->selectRaw('SUM(cant) as total_cant')
                ->groupBy('product_id')
                ->whereIn('car_id', $carIds)
                ->where('is_product', 1);
        }])
        ->get()->filter(function ($product) {
            return !$product->orders->isEmpty();
        })->values()->sortByDesc(function ($product) {
            return $product->orders->sum('total_cant');
        });
        $mostSoldProduct = $products->first();

        // Obtener el nombre y la cantidad del producto más vendido
        $mostSoldProductName = $mostSoldProduct ? $mostSoldProduct->name: '';
        $mostSoldProductQuantity = $mostSoldProduct ? $mostSoldProduct->orders->sum('total_cant') : 0;
        $totalSoldProducts = $products->sum(function ($product) {
            return $product->orders->sum('total_cant');
        });
        $services = Service::withCount(['orders' => function ($query) use ($startDate, $endDate, $carIds) {
            $query->whereIn('car_id', $carIds)->where('is_product', 0);
        }])->orderByDesc('orders_count')->first();
        foreach ($cars as $car) {
            if ($car->select_professional == 1) {
                $seleccionado = $seleccionado + 1;
            } else {
                $aleatorio = $aleatorio + 1;
            }
            $totalservices = $totalservices + count($car->orders->where('is_product', 0));
            //$totalproducts = $totalproducts + count($car->orders->where('is_product', 1));
        }
        $orders = Order::whereIn('car_id', $carIds)->whereHas('branchServiceProfessional', function ($query) {
            $query->where('type_service', 'Especial');
        })->get();
        //Log::info($services);
        $result = [
            'Monto Generado' => round($cars->sum('amount') + ($cars->sum('technical_assistance') * 5000), 2),
            'Propina' => round($cars->sum('tip'), 2),
            'Producto más Vendido' => $mostSoldProductName,
            'Cantidad del Producto' => $mostSoldProductQuantity,
            'Total de Productos Vendidos' => $totalSoldProducts,
            'Servicio más Brindado' => $services->orders_count ? $services->name : null,
            'Cantidad del Servicio' => $services->orders_count ? $services->orders_count : 0,
            'Total de Servicios Brindados' => $totalservices,
            'Clientes Seleccionados' => $seleccionado,
            'Clientes Aleatorios' => $aleatorio,
            'Servicios Especiales' => $orders->count(),
            'Monto Servicios Especiales' => round($orders->sum('price'), 2),
            'Clientes Atendidos' => $totalClients
        ];
        $iconColorMapping = [
            'Monto Generado' => ['icon' => 'mdi-wallet', 'color' => 'green'],
            'Propina' => ['icon' => 'mdi-cash', 'color' => 'blue'],
            'Producto más Vendido' => ['icon' => 'mdi-cart', 'color' => 'red'],
            'Cantidad del Producto' => ['icon' => 'mdi-format-list-numbered', 'color' => 'orange'],
            'Total de Productos Vendidos' => ['icon' => 'mdi-cash-register', 'color' => 'purple'],
            'Servicio más Brindado' => ['icon' => 'mdi-wrench', 'color' => 'pink'],
            'Cantidad del Servicio' => ['icon' => 'mdi-counter', 'color' => 'teal'],
            'Total de Servicios Brindados' => ['icon' => 'mdi-hammer-screwdriver', 'color' => 'cyan'],
            'Clientes Seleccionados' => ['icon' => 'mdi-check-circle', 'color' => 'lime'],
            'Clientes Aleatorios' => ['icon' => 'mdi-shuffle', 'color' => 'amber'],
            'Servicios Especiales' => ['icon' => 'mdi-star', 'color' => 'yellow'],
            'Monto Servicios Especiales' => ['icon' => 'mdi-cash', 'color' => 'blue'],
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

    /*public function branch_winner_month_icon($branch_id, $month, $year)
    {
        $cars = Car::whereHas('reservation', function ($query) use ($branch_id) {
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
            $query->where('branch_id', $branch_id);
        })->whereHas('branchServiceProfessional', function ($query) {
            $query->where('type_service', 'Especial');
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
    }*/

    public function branch_winner_date_icon($branch_id)
    {
        $cars = Car::whereHas('reservation', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id)->whereDate('data', Carbon::now());
        })->where('pay', 1)->get();
        $carIds = $cars->pluck('id');
        
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
        /*$products = Product::withCount(['orders' => function ($query) use ($carIds){
            $query->whereIn('car_id', $carIds)->where('is_product', 1)->whereDate('data', Carbon::now());
        }])->orderByDesc('orders_count')->first();*/
        //$carIds = $cars->pluck('car_id');
        $products = Product::with(['orders' => function ($query) use ($carIds) {
            $query->selectRaw('SUM(cant) as total_sale_price')
                ->groupBy('product_id')
                ->whereIn('car_id', $carIds)
                ->where('is_product', 1);
        }])
        ->get()->filter(function ($product) {
            return !$product->orders->isEmpty();
        })->values()->sortByDesc(function ($product) {
            return $product->orders->sum('total_cant');
        });
        $mostSoldProduct = $products->first();

        // Obtener el nombre y la cantidad del producto más vendido
        $mostSoldProductName = $mostSoldProduct ? $mostSoldProduct->name: '';
        $mostSoldProductQuantity = $mostSoldProduct ? $mostSoldProduct->orders->sum('total_cant') : 0;
        $totalSoldProducts = $products->sum(function ($product) {
            return $product->orders->sum('total_cant');
        });
        $services = Service::withCount(['orders' => function ($query) use ($carIds) {
            $query->whereIn('car_id', $carIds)->where('is_product', 0);
        }])->orderByDesc('orders_count')->first();
        foreach ($cars as $car) {
            if ($car->select_professional == 1) {
                $seleccionado = $seleccionado + 1;
            } else {
                $aleatorio = $aleatorio + 1;
            }
            $totalservices = $totalservices + count($car->orders->where('is_product', 0));
            //$totalproducts = $totalproducts + count($car->orders->where('is_product', 1));
        }
        $orders = Order::whereIn('car_id', $carIds)->whereHas('branchServiceProfessional', function ($query) {
            $query->where('type_service', 'Especial');
        })->get();
        //Log::info($services);
        $result = [
            'Monto Generado' => round($cars->sum('amount') + ($cars->sum('technical_assistance') * 5000), 2),
            'Propina' => round($cars->sum('tip'), 2),
            'Producto más Vendido' => $mostSoldProductName,
            'Cantidad del Producto' => $mostSoldProductQuantity,
            'Total de Productos Vendidos' => $totalSoldProducts,
            'Servicio más Brindado' => $services->orders_count ? $services->name : null,
            'Cantidad del Servicio' => $services->orders_count ? $services->orders_count : 0,
            'Total de Servicios Brindados' => $totalservices,
            'Clientes Seleccionados' => $seleccionado,
            'Clientes Aleatorios' => $aleatorio,
            'Servicios Especiales' => $orders->count(),
            'Monto Servicios Especiales' => round($orders->sum('price'), 2),
            'Clientes Atendidos' => $totalClients
        ];
        $iconColorMapping = [
            'Monto Generado' => ['icon' => 'mdi-wallet', 'color' => 'green'],
            'Propina' => ['icon' => 'mdi-cash', 'color' => 'blue'],
            'Producto más Vendido' => ['icon' => 'mdi-cart', 'color' => 'red'],
            'Cantidad del Producto' => ['icon' => 'mdi-format-list-numbered', 'color' => 'orange'],
            'Total de Productos Vendidos' => ['icon' => 'mdi-cash-register', 'color' => 'purple'],
            'Servicio más Brindado' => ['icon' => 'mdi-wrench', 'color' => 'pink'],
            'Cantidad del Servicio' => ['icon' => 'mdi-counter', 'color' => 'teal'],
            'Total de Servicios Brindados' => ['icon' => 'mdi-hammer-screwdriver', 'color' => 'cyan'],
            'Clientes Seleccionados' => ['icon' => 'mdi-check-circle', 'color' => 'lime'],
            'Clientes Aleatorios' => ['icon' => 'mdi-shuffle', 'color' => 'amber'],
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

    /*public function company_close_car_month($month, $year, $data)
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
            $cars = Car::whereHas('reservation', function ($query) use ($branch) {
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
    }*/

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
            $cars = Car::whereHas('reservation', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id)->whereDate('data', Carbon::now()->toDateString());
            })->get()->map(function ($car) {
                $products = $car->orders->where('is_product', 1)->sum('price');
                $services = $car->orders->where('is_product', 0)->sum('price');
                return [
                    'earnings' => $car->amount,
                    'technical_assistance' => $car->technical_assistance * 5000,
                    'tip' => $car->tip,
                    'product' => $products,
                    'service' => $services,
                    'total' => $car->amount + $car->technical_assistance * 5000
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
            $cars = Car::whereHas('reservation', function ($query) use ($branch, $startDate, $endDate) {
                $query->where('branch_id', $branch->id)->whereDate('data', '>=', $startDate)->whereDate('data', '<=', $endDate);
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

    /*public function company_winner_month($month, $year, $data)
    {
        $branches = Branch::where('business_id', $data['business_id'])->get();
        $result = [];
        $i = 0;
        $total_company = 0;
        $total_branch = 0;
        $total_tip = 0;
        $technical_assistance = 0;
        foreach ($branches as $branch) {
            $cars = Car::whereHas('reservation', function ($query) use ($branch) {
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
    }*/

    public function company_winner_periodo($startDate, $endDate, $data)
    {
        $branches = Branch::where('business_id', $data['business_id'])->get();
        $result = [];
        $i = 0;
        $total_company = 0;
        $total_branch = 0;
        $total_tip = 0;
        $total_services = 0;
        $total_products = 0;
        $technical_assistance = 0;
        foreach ($branches as $branch) {
            $cars = Car::whereHas('reservation', function ($query) use ($branch, $startDate, $endDate) {
                $query->where('branch_id', $branch->id)->whereDate('data', '>=', $startDate)->whereDate('data', '<=', $endDate);
            })->where('pay', 1)->get()->map(function ($car) {
                $products = $car->orders->where('is_product', 1)->sum('price');
                $services = $car->orders->where('is_product', 0)->sum('price');
                return [
                    'productsAmount' => $products,
                    'servicesAmount' => $services,
                    'earnings' => $car->amount,
                    'technical_assistance' => $car->technical_assistance * 5000,
                    'tip' => $car->tip,
                    'total' => $car->amount + $car->technical_assistance * 5000
                ];
            });
            $result[$i]['name'] = $branch->name;
            $result[$i]['productsAmount'] = round($cars->sum('productsAmount'), 2);
            $result[$i]['servicesAmount'] = round($cars->sum('servicesAmount'), 2);
            $result[$i]['earnings'] = round($cars->sum('earnings'), 2);
            $result[$i]['technical_assistance'] = round($cars->sum('technical_assistance'), 2);
            $result[$i]['tip'] = round($cars->sum('tip'), 2);
            $result[$i++]['total'] = round($cars->sum('total'), 2);
            $total_tip += round($cars->sum('tip'), 2);
            $total_products += round($cars->sum('productsAmount'), 2);
            $total_services += round($cars->sum('servicesAmount'), 2);
            $total_branch += round($cars->sum('earnings'), 2);
            $total_company += round($cars->sum('total'), 2);
            $technical_assistance += round($cars->sum('technical_assistance'), 2);
        } //foreach
        $result[$i]['name'] = 'Total';
        $result[$i]['tip'] = $total_tip;
        $result[$i]['productsAmount'] = $total_products;
        $result[$i]['servicesAmount'] = $total_services;
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
        $total_services = 0;
        $total_products = 0;
        $technical_assistance = 0;
        //$data = Carbon::now()->toDateString();
        foreach ($branches as $branch) {
            $cars = Car::whereHas('reservation', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id)->whereDate('data', Carbon::now()->toDateString());
            })->where('pay', 1)->get()->map(function ($car) {
                $products = $car->orders->where('is_product', 1)->sum('price');
                $services = $car->orders->where('is_product', 0)->sum('price');
                return [
                    'productsAmount' => $products,
                    'servicesAmount' => $services,
                    'earnings' => $car->amount,
                    'technical_assistance' => $car->technical_assistance * 5000,
                    'tip' => $car->tip,
                    'total' => $car->amount + $car->technical_assistance * 5000
                ];
            });
            $result[$i]['name'] = $branch->name;
            $result[$i]['productsAmount'] = round($cars->sum('productsAmount'), 2);
            $result[$i]['servicesAmount'] = round($cars->sum('servicesAmount'), 2);
            $result[$i]['earnings'] = round($cars->sum('earnings'), 2);
            $result[$i]['technical_assistance'] = round($cars->sum('technical_assistance'), 2);
            $result[$i]['tip'] = round($cars->sum('tip'), 2);
            $result[$i++]['total'] = round($cars->sum('total'), 2);
            $total_tip += round($cars->sum('tip'), 2);
            $total_products += round($cars->sum('productsAmount'), 2);
            $total_services += round($cars->sum('servicesAmount'), 2);
            $total_branch += round($cars->sum('earnings'), 2);
            $total_company += round($cars->sum('total'), 2);
            $technical_assistance += round($cars->sum('technical_assistance'), 2);
        } //foreach
        $result[$i]['name'] = 'Total';
        $result[$i]['tip'] = $total_tip;
        $result[$i]['productsAmount'] = $total_products;
        $result[$i]['servicesAmount'] = $total_services;
        $result[$i]['earnings'] = $total_branch;
        $result[$i]['technical_assistance'] = $technical_assistance;
        $result[$i++]['total'] = $total_company;
        return $result;
    }

    public function branch_professionals_winner_date()
    {
        $dates = [];
        $professionals = Professional::whereHas('charge', function($query){
            $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
        })->get();
        $branches = Branch::select('id', 'name')->get();
        foreach ($professionals as $professional) {
            foreach ($branches as $branch) {
                $cars = Car::whereHas('reservation', function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id)->whereDate('data', Carbon::now());
                })->whereHas('clientProfessional', function ($query) use ($professional){
                    $query->where('professional_id', $professional->id);
                })->where('pay', 1)->get(); 
                $retention = number_format($professional->retention/100, 2);
                $winProfessional =$cars->sum(function ($car){
                    return $car->orders->sum('percent_win');
                });
                $amuntGenerate =$cars->sum(function ($car){
                    return $car->orders->sum('price');
                });
                $retentionPorcent = round($winProfessional * $retention);
                $winTips =  round($cars->sum('tip') * 0.8, 2);
                $dates [] =  [
                    'branchName' => $branch->name,
                    'name' => $professional->name. " " .$professional->surname. " " .$professional->second_surname,
                    'amount' => $winProfessional,
                    'amountGenerate' => $amuntGenerate,
                    'retention' => $retentionPorcent,
                    'tip' => round($cars->sum('tip'), 2),
                    'tip80' => $winTips,
                    'total' => $winProfessional-$retentionPorcent+$winTips,
                    'total_cars' => $cars->count()
                ];
            //})->sortByDesc('total')->values();
            }
        }
        return $dates;
        /*return $professionals = Professional::whereHas('charge', function ($query){
            $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
        })->get()->map(function ($professional) use ($branch_id){
            $cars = Car::whereHas('reservation', function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id)->whereDate('data', Carbon::now());
            })->whereHas('clientProfessional', function ($query) use ($professional){
                $query->where('professional_id', $professional->id);
            })->get(); 
            $retention = number_format($professional->retention/100, 2);
            $winProfessional =$cars->sum(function ($car){
                return $car->orders->sum('percent_win');
            });
            $retentionPorcent = round($winProfessional * $retention);
            $winTips =  round($cars->sum('tip') * 0.8, 2);
            return [
                'name' => $professional->name . " " . $professional->surname . " " . $professional->second_surname,
                'amount' => $winProfessional,
                'retention' => $retentionPorcent,
                'tip' => round($cars->sum('tip'), 2),
                'tip80' => $winTips,
                'total' => $winProfessional-$retentionPorcent+$winTips,
                'total_cars' => $cars->count()
            ];
        })->sortByDesc('total')->values();*/
       
    }

    /*public function branch_professionals_winner_month($branch_id, $month, $year)
    {

        return $professionals = Professional::whereHas('charge', function ($query){
            $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
        })->get()->map(function ($professional) use ($month, $year, $branch_id){
            $cars = Car::whereHas('reservation', function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id);
            })->whereHas('orders', function ($query) use ($month, $year) {
                $query->whereMonth('data', $month)->whereYear('data', $year);
            })->whereHas('clientProfessional', function ($query) use ($professional){
                $query->where('professional_id', $professional->id);
            })->get(); 
            $retention = $professional->retention;
            $winProfessional =$cars->sum(function ($car){
                return $car->orders->sum('percent_win');
            });
            $retentionPorcent = round($winProfessional * ($retention /100));
            $winTips =  round($cars->sum('tip') * 0.8, 2);
            return [
                'name' => $professional->name . " " . $professional->surname . " " . $professional->second_surname,
                'amount' => $winProfessional,
                'tip' => $winTips,
                'total' => $winProfessional-$retentionPorcent+$winTips,
                'total_cars' => $cars->count()
            ];
        })->sortByDesc('total')->values();
        /*return $resultados = Professional::with(['orders' => function ($query) use ($month, $year, $branch_id) {
            $query->whereHas('branchServiceProfessional.branchService', function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id);
            })->whereMonth('data', $month)->whereYear('data', $year);
        }])
            ->get()->map(function ($professional)  use ($month, $year) {
                return [
                    'name' => $professional->name . " " . $professional->surname . " " . $professional->second_surname,
                    'amount' => $professional->orders ? round($professional->orders->sum('price') * 0.45, 2) : 0,
                    'tip' => $professional->orders ? round($professional->orders->sum('car.tip') * 0.45) : 0,
                    'total' => $professional->orders ? 
                    round(
                        (
                            $professional->orders->sum(function ($order) {
                                return $order->is_product ? $order->price : $order->percent_win;
                            }) * 0.45
                        ) +
                        (
                            $professional->orders->map(function ($order) {
                                return $order->car->sum('tip') * 0.8;
                            }))->sum(), 2) 
                    : 0,
                    'total_cars' => $professional->reservations()
                    ->whereMonth('data', $month)->whereYear('data', $year)
                    ->count(),
                ];
            })->sortByDesc('total')->values();*/
    //}*/

    public function branch_professionals_winner_periodo($startDate, $endDate)
    {
        $dates = [];
        $professionals = Professional::whereHas('charge', function($query){
            $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
        })->get();
        $branches = Branch::select('id', 'name')->get();
        foreach ($professionals as $professional) {
            foreach ($branches as $branch) {
                $cars = Car::whereHas('reservation', function ($query) use ($branch, $startDate, $endDate) {
                    $query->where('branch_id', $branch->id)->whereDate('data', '>=', $startDate)->whereDate('data', '<=', $endDate);
                })->whereHas('clientProfessional', function ($query) use ($professional){
                    $query->where('professional_id', $professional->id);
                })->where('pay', 1)->get(); 
                $retention = number_format($professional->retention/100, 2);
                $winProfessional =$cars->sum(function ($car){
                    return $car->orders->sum('percent_win');
                });
                $amuntGenerate =$cars->sum(function ($car){
                    return $car->orders->sum('price');
                });
                $retentionPorcent = round($winProfessional * $retention);
                $winTips =  round($cars->sum('tip') * 0.8, 2);
                $dates [] =  [
                    'branchName' => $branch->name,
                    'name' => $professional->name. " " .$professional->surname. " " .$professional->second_surname,
                    'amount' => $winProfessional,
                    'amountGenerate' => $amuntGenerate,
                    'retention' => $retentionPorcent,
                    'tip' => round($cars->sum('tip'), 2),
                    'tip80' => $winTips,
                    'total' => $winProfessional-$retentionPorcent+$winTips,
                    'total_cars' => $cars->count()
                ];
            //})->sortByDesc('total')->values();
            }
        }
        return $dates;
        /*return $professionals = Professional::whereHas('charge', function ($query){
            $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
        })->get()->map(function ($professional) use ($branch_id, $startDate, $endDate){
            $cars = Car::whereHas('reservation', function ($query) use ($branch_id, $startDate, $endDate) {
                $query->where('branch_id', $branch_id)->whereDate('data', '>=', $startDate)->whereDate('data', '<=', $endDate);
            })->whereHas('clientProfessional', function ($query) use ($professional){
                $query->where('professional_id', $professional->id);
            })->get(); 
            $retention = number_format($professional->retention/100, 2);
            $winProfessional =$cars->sum(function ($car){
                return $car->orders->sum('percent_win');
            });
            $retentionPorcent = round($winProfessional * $retention);
            $winTips =  round($cars->sum('tip') * 0.8, 2);
            return [
                'name' => $professional->name. " " .$professional->surname. " " .$professional->second_surname,
                'amount' => $winProfessional,
                'retention' => $retentionPorcent,
                'tip' => round($cars->sum('tip'), 2),
                'tip80' => $winTips,
                'total' => $winProfessional-$retentionPorcent+$winTips,
                'total_cars' => $cars->count()
            ];
        })->sortByDesc('total')->values();*/
        
    }
}
