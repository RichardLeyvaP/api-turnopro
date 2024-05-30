<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Car;
use App\Models\Finance;
use App\Models\Order;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Professional;
use App\Models\ProfessionalPayment;
use App\Models\Retention;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
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
        if (!$totalClients = $cars->count()) {
            return $transformedResult;
        }
        $products = Product::with([
            'orders' => function ($query) use ($carIds) {
                $query->selectRaw('product_id, SUM(cant) as total_cant')
                    ->groupBy('product_id')
                    ->whereIn('car_id', $carIds)
                    ->where('is_product', 1);
            },
            'cashiersales' => function ($query) use ($branch_id) {
                $query->selectRaw('product_id, SUM(cant) as total_sales')
                    ->groupBy('product_id')
                    ->where('cashiersales.branch_id', $branch_id)
                    ->whereDate('data', Carbon::now());
            }
        ])->get()->filter(function ($product) {
            // Filtra productos que tienen órdenes o ventas de productos no vacías
            return !$product->orders->isEmpty() || !$product->cashiersales->isEmpty();
        })->map(function ($product) {
            // Crea una estructura de datos simplificada con la suma de las cantidades
            $totalOrders = $product->orders->sum('total_cant');
            $totalSales = $product->cashiersales->sum('total_sales');
            return [
                'id' => $product->id,
                'name' => $product->name,
                'reference' => $product->reference,
                'code' => $product->code,
                'description' => $product->description,
                'status_product' => $product->status_product,
                'purchase_price' => $product->purchase_price,
                'sale_price' => $product->sale_price,
                'image_product' => $product->image_product,
                'product_category_id' => $product->product_category_id,
                'total_quantity' => $totalOrders + $totalSales,
            ];
        })->sortByDesc('total_quantity')->values();

        $mostSoldProductName = $products ? $products->first()['name'] : '';
        $mostSoldProductQuantity = $products ? $products->first()['total_quantity'] : 0;
        $totalSoldProducts = $products->sum('total_quantity');
        /*$products = Product::with(['orders' => function ($query) use ($carIds) {
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
        });*/
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
        if (!$totalClients = $cars->count()) {
            return $transformedResult;
        }
        $products = Product::with([
            'orders' => function ($query) use ($carIds) {
                $query->selectRaw('product_id, SUM(cant) as total_cant')
                    ->groupBy('product_id')
                    ->whereIn('car_id', $carIds)
                    ->where('is_product', 1);
            },
            'cashiersales' => function ($query) use ($branch_id, $month, $year) {
                $query->selectRaw('product_id, SUM(cant) as total_sales')
                    ->groupBy('product_id')
                    ->where('cashiersales.branch_id', $branch_id)
                    ->whereMonth('data', $month)->whereYear('data', $year);
            }
        ])->get()->filter(function ($product) {
            // Filtra productos que tienen órdenes o ventas de productos no vacías
            return !$product->orders->isEmpty() || !$product->cashiersales->isEmpty();
        })->map(function ($product) {
            // Crea una estructura de datos simplificada con la suma de las cantidades
            $totalOrders = $product->orders->sum('total_cant');
            $totalSales = $product->cashiersales->sum('total_sales');
            return [
                'id' => $product->id,
                'name' => $product->name,
                'reference' => $product->reference,
                'code' => $product->code,
                'description' => $product->description,
                'status_product' => $product->status_product,
                'purchase_price' => $product->purchase_price,
                'sale_price' => $product->sale_price,
                'image_product' => $product->image_product,
                'product_category_id' => $product->product_category_id,
                'total_quantity' => $totalOrders + $totalSales,
            ];
        })->sortByDesc('total_quantity')->values();

        $mostSoldProductName = $products ? $products->first()['name'] : '';
        $mostSoldProductQuantity = $products ? $products->first()['total_quantity'] : 0;
        $totalSoldProducts = $products->sum('total_quantity');
        /*$products = Product::with(['orders' => function ($query) use ($carIds) {
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
        });*/
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
        if (!$totalClients = $cars->count()) {
            return $transformedResult;
        }
        $products = Product::with([
            'orders' => function ($query) use ($carIds) {
                $query->selectRaw('product_id, SUM(cant) as total_cant')
                    ->groupBy('product_id')
                    ->whereIn('car_id', $carIds)
                    ->where('is_product', 1);
            },
            'cashiersales' => function ($query) use ($branch_id, $startDate, $endDate) {
                $query->selectRaw('product_id, SUM(cant) as total_sales')
                    ->groupBy('product_id')
                    ->where('cashiersales.branch_id', $branch_id)
                    ->whereDate('data', '>=', $startDate)
                    ->whereDate('data', '<=', $endDate);
            }
        ])->get()->filter(function ($product) {
            // Filtra productos que tienen órdenes o ventas de productos no vacías
            return !$product->orders->isEmpty() || !$product->cashiersales->isEmpty();
        })->map(function ($product) {
            // Crea una estructura de datos simplificada con la suma de las cantidades
            $totalOrders = $product->orders->sum('total_cant');
            $totalSales = $product->cashiersales->sum('total_sales');
            return [
                'id' => $product->id,
                'name' => $product->name,
                'reference' => $product->reference,
                'code' => $product->code,
                'description' => $product->description,
                'status_product' => $product->status_product,
                'purchase_price' => $product->purchase_price,
                'sale_price' => $product->sale_price,
                'image_product' => $product->image_product,
                'product_category_id' => $product->product_category_id,
                'total_quantity' => $totalOrders + $totalSales,
            ];
        })->sortByDesc('total_quantity')->values();

        $mostSoldProductName = $products ? $products->first()['name'] : '';
        $mostSoldProductQuantity = $products ? $products->first()['total_quantity'] : 0;
        $totalSoldProducts = $products->sum('total_quantity');
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
            $query->whereDate('data', '>=', $startDate)
                ->whereDate('data', '<=', $endDate)
                ->where('branch_id', $branch_id);
        })->where('pay', 1)->get();

        if ($cars->isEmpty()) {
            return [];
        }

        $carIds = $cars->pluck('id');
        $totalClients = $cars->count();
        $totalservices = 0;
        $montoServicios = 0;
        $utilidadServices = 0;
        $productSales = 0;
        $seleccionado = 0;
        $aleatorio = 0;

        $ingreso = 0;
        $gasto = 0;
        $finances = Finance::Where('branch_id', $branch_id)->whereDate('data', '>=', $startDate)->whereDate('data', '<=', $endDate)->get();
        if (!$finances->isEmpty()) {

            foreach ($finances as $finance) {
                if ($finance->operation == 'Gasto') {
                    $gasto = $gasto + $finance->amount;
                } else {
                    $ingreso = $ingreso + $finance->amount;
                }
            }
        }

        return $products = Product::with([
            'orders' => function ($query) use ($carIds) {
                $query->selectRaw('product_id, SUM(cant) as total_cant, SUM(percent_win) as utilidadOrder, SUM(price) as total_price')
                    ->groupBy('product_id')
                    ->whereIn('car_id', $carIds)
                    ->where('is_product', 1);
            },
            'cashiersales' => function ($query) use ($branch_id, $startDate, $endDate) {
                $query->selectRaw('product_id, SUM(cant) as total_sales, SUM(percent_wint) as utilidadCash, SUM(price) as total_pricesales')
                    ->groupBy('product_id')
                    ->where('cashiersales.branch_id', $branch_id)
                    ->whereDate('data', '>=', $startDate)
                    ->whereDate('data', '<=', $endDate);
            }
        ])->get()->filter(function ($product) {
            return !$product->orders->isEmpty() || !$product->cashiersales->isEmpty();
        })->map(function ($product) {
            $totalOrders = $product->orders->sum('total_cant');
            $totalSales = $product->cashiersales->sum('total_sales'); // Cambio aquí
            $utilidadOrders = $product->orders->sum('utilidadOrder');
            $utilidadSales = $product->cashiersales->sum('utilidadCash');
            $totalPriceOrders = $product->orders->sum('total_price');
            $totalPriceSales = $product->cashiersales->sum('total_pricesales');
            return [
                'id' => $product->id,
                'name' => $product->name,
                'total_quantity' => $totalOrders + $totalSales,
                'utilidad' => $utilidadOrders + $utilidadSales,
                'price' => $totalPriceOrders + $totalPriceSales,
                'sales' => $totalPriceSales
            ];
        })->sortByDesc('total_quantity')->values();

        $mostSoldProductName = $products->isNotEmpty() ? $products->first()['name'] : '';
        //$mostSoldProductQuantity = $products->isNotEmpty() ? $products->first()['total_quantity'] : 0;
        $totalSoldProducts = $products->sum('total_quantity');
        $totalUtilidadProducts = $products->sum('utilidad');
        $totalPriceProducts = $products->sum('price');
        $productSales = $products->sum('sales');
        $TotalRetention = Retention::where('branch_id', $branch_id)
            ->whereDate('data', Carbon::now())
            ->sum('retention');

        $services =  Service::withCount(['orders' => function ($query) use ($carIds) {
            $query->whereIn('car_id', $carIds)->where('is_product', 0);
        }])
            ->with(['orders' => function ($query) use ($carIds) {
                $query->whereIn('car_id', $carIds)->where('is_product', 0);
                /*->select(
                    'service_id',
                    DB::raw('SUM(price - percent_win) as total_profit')
                )
                ->groupBy('service_id');*/
            }])
            ->having('orders_count', '>', 0)
            ->orderBy('orders_count', 'desc')
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'orders_count' => $service->orders_count/*,
                'total_profit' => $service->orders->sum('total_profit')*/
                ];
            });

        foreach ($cars as $car) {
            if ($car->select_professional == 1) {
                $seleccionado++;
            } else {
                $aleatorio++;
            }
            $totalservices += $car->orders->where('is_product', 0)->count();
            $montoServicios += $car->orders->where('is_product', 0)->sum('price');
            $noMetas = $car->orders->where('is_product', 0)->where('meta', 0);
            $utilidadServices += $noMetas->sum('price') - $noMetas->sum('percent_win');
        }

        $orders = Order::whereIn('car_id', $carIds)->whereHas('branchServiceProfessional', function ($query) {
            $query->where('type_service', 'Especial');
        })->get();
        $service = $services ? $services->first() : null;
        $tips = $cars->sum('tip');
        $coffe = $tips * 0.10;
        $result = [
            'Monto Generado Servicios' => $montoServicios,
            'Utilidad de Servicios' => $utilidadServices,
            'Servicio más Brindado' => $service ? $service['name'] : '',
            'Total de Servicios Brindados' => $totalservices,
            'Monto de Productos Vendidos' => $totalPriceProducts,
            'Utilidad de Productos Vendidos' => $totalUtilidadProducts,
            'Producto más Vendido' => $mostSoldProductName,
            'Total de Productos Vendidos' => $totalSoldProducts,
            'Monto de Propina' => round($tips, 2),
            'Café 10% Propina' => round($coffe, 2),
            'Clientes Seleccionados' => $seleccionado,
            'Clientes Aleatorios' => $aleatorio,
            'Servicios Especiales' => $orders->count(),
            'Monto Servicios Especiales' => round($orders->sum('price'), 2),
            'Clientes Atendidos' => $totalClients,
            'Monto Generado' => round($cars->sum('amount') + ($cars->sum('technical_assistance') * 5000) + $productSales, 2),
            'Utilidades' => round($ingreso - $gasto, 2),
            'Total Retenciones' => round($TotalRetention),
            //'Cantidad del Producto' => $mostSoldProductQuantity,
            //'Cantidad del Servicio' => $services ? $service['orders_count'] : 0,
        ];

        $iconColorMapping = [
            'Monto Generado Servicios' => ['icon' => 'mdi-wrench', 'color' => 'pink'],
            'Utilidad de Productos Vendidos' => ['icon' => 'mdi-cash-register', 'color' => 'purple'],
            'Monto Generado' => ['icon' => 'mdi-wallet', 'color' => 'green'],
            'Utilidades' => ['icon' => 'mdi-wallet', 'color' => 'green'],
            'Monto de Propina' => ['icon' => 'mdi-cash', 'color' => 'blue'],
            'Café 10% Propina' => ['icon' => 'mdi-cash', 'color' => 'blue'],
            'Total Retenciones' => ['icon' => 'mdi-account-multiple', 'color' => 'grey'],
            'Producto más Vendido' => ['icon' => 'mdi-cart', 'color' => 'red'],
            //'Cantidad del Producto' => ['icon' => 'mdi-format-list-numbered', 'color' => 'orange'],
            'Total de Productos Vendidos' => ['icon' => 'mdi-cash-register', 'color' => 'purple'],
            'Monto de Productos Vendidos' => ['icon' => 'mdi-cash-register', 'color' => 'purple'],
            'Servicio más Brindado' => ['icon' => 'mdi-wrench', 'color' => 'pink'],
            //'Cantidad del Servicio' => ['icon' => 'mdi-counter', 'color' => 'teal'],
            'Total de Servicios Brindados' => ['icon' => 'mdi-hammer-screwdriver', 'color' => 'cyan'],
            'Utilidad de Servicios' => ['icon' => 'mdi-hammer-screwdriver', 'color' => 'cyan'],
            'Clientes Seleccionados' => ['icon' => 'mdi-check-circle', 'color' => 'lime'],
            'Clientes Aleatorios' => ['icon' => 'mdi-shuffle', 'color' => 'amber'],
            'Servicios Especiales' => ['icon' => 'mdi-star', 'color' => 'yellow'],
            'Monto Servicios Especiales' => ['icon' => 'mdi-cash', 'color' => 'blue'],
            'Clientes Atendidos' => ['icon' => 'mdi-account-multiple', 'color' => 'indigo'],
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

        if ($cars->isEmpty()) {
            return [];
        }

        $carIds = $cars->pluck('id');
        $totalClients = $cars->count();
        $totalservices = 0;
        $montoServicios = 0;
        $productSales = 0;
        $utilidadServices = 0;
        $seleccionado = 0;
        $aleatorio = 0;
        $ingreso = 0;
        $gasto = 0;
        $finances = Finance::Where('branch_id', $branch_id)->whereDate('data', Carbon::now())->get();
        if (!$finances->isEmpty()) {

            foreach ($finances as $finance) {
                if ($finance->operation == 'Gasto') {
                    $gasto = $gasto + $finance->amount;
                } else {
                    $ingreso = $ingreso + $finance->amount;
                }
            }
        }
        $products = Product::with([
            'orders' => function ($query) use ($carIds) {
                $query->selectRaw('product_id, SUM(cant) as total_cant, SUM(percent_win) as utilidadOrder, SUM(price) as total_price')
                    ->groupBy('product_id')
                    ->whereIn('car_id', $carIds)
                    ->where('is_product', 1);
            },
            'cashiersales' => function ($query) use ($branch_id) {
                $query->selectRaw('product_id, SUM(cant) as total_sales, SUM(percent_wint) as utilidadCash, SUM(price) as total_price')
                    ->groupBy('product_id')
                    ->where('cashiersales.branch_id', $branch_id)
                    ->whereDate('data', Carbon::now());
            }
        ])->get()->filter(function ($product) {
            return !$product->orders->isEmpty() || !$product->cashiersales->isEmpty();
        })->map(function ($product) {
            $totalOrders = $product->orders->sum('total_cant');
            $totalSales = $product->cashiersales->sum('total_sales');
            $utilidadOrders = $product->orders->sum('utilidadOrder');
            $UtilidadSales = $product->cashiersales->sum('utilidadCash');
            $totalPriceOrders = $product->orders->sum('total_price');
            $totalPriceSales = $product->cashiersales->sum('total_price');
            return [
                'id' => $product->id,
                'name' => $product->name,
                'total_quantity' => $totalOrders + $totalSales,
                'utilidad' => $utilidadOrders + $UtilidadSales,
                'price' => $totalPriceOrders + $totalPriceSales,
                'sales' => $totalPriceSales
            ];
        })->sortByDesc('total_quantity')->values();

        $mostSoldProductName = $products->isNotEmpty() ? $products->first()['name'] : '';
        //$mostSoldProductQuantity = $products->isNotEmpty() ? $products->first()['total_quantity'] : 0;
        $totalSoldProducts = $products->sum('total_quantity');
        $totalUtilidadProducts = $products->sum('utilidad');
        $totalPriceProducts = $products->sum('price');
        $productSales = $products->sum('sales');
        $TotalRetention = Retention::where('branch_id', $branch_id)
            ->whereDate('data', Carbon::now())
            ->sum('retention');

        $services =  Service::withCount(['orders' => function ($query) use ($carIds) {
            $query->whereIn('car_id', $carIds)->where('is_product', 0);
        }])
            ->with(['orders' => function ($query) use ($carIds) {
                $query->whereIn('car_id', $carIds)->where('is_product', 0);
                /*->select(
                        'service_id',
                        DB::raw('SUM(price - percent_win) as total_profit')
                    )
                    ->groupBy('service_id');*/
            }])
            ->having('orders_count', '>', 0)
            ->orderBy('orders_count', 'desc')
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'orders_count' => $service->orders_count/*,
                    'total_profit' => $service->orders->sum('total_profit')*/
                ];
            });

        foreach ($cars as $car) {
            if ($car->select_professional == 1) {
                $seleccionado++;
            } else {
                $aleatorio++;
            }
            $totalservices += $car->orders->where('is_product', 0)->count();
            $montoServicios += $car->orders->where('is_product', 0)->sum('price');
            $noMetas = $car->orders->where('is_product', 0)->where('meta', 0);
            $utilidadServices += $noMetas->sum('price') - $noMetas->sum('percent_win');
        }

        $orders = Order::whereIn('car_id', $carIds)->whereHas('branchServiceProfessional', function ($query) {
            $query->where('type_service', 'Especial');
        })->get();
        $service = $services ? $services->first() : null;
        $tips = $cars->sum('tip');
        $coffe = $tips * 0.10;
        $result = [
            'Monto Generado Servicios' => $montoServicios,
            'Utilidad de Servicios' => $utilidadServices,
            'Servicio más Brindado' => $service ? $service['name'] : '',
            'Total de Servicios Brindados' => $totalservices,
            'Monto de Productos Vendidos' => $totalPriceProducts,
            'Utilidad de Productos Vendidos' => $totalUtilidadProducts,
            'Producto más Vendido' => $mostSoldProductName,
            'Total de Productos Vendidos' => $totalSoldProducts,
            'Monto de Propina' => round($tips, 2),
            'Café 10% Propina' => round($coffe, 2),
            'Clientes Seleccionados' => $seleccionado,
            'Clientes Aleatorios' => $aleatorio,
            'Servicios Especiales' => $orders->count(),
            'Monto Servicios Especiales' => round($orders->sum('price'), 2),
            'Clientes Atendidos' => $totalClients,
            'Monto Generado' => round($cars->sum('amount') + ($cars->sum('technical_assistance') * 5000) +  $productSales, 2),
            'Utilidades' => round($ingreso - $gasto, 2),
            'Total Retenciones' => round($TotalRetention),
            //'Cantidad del Producto' => $mostSoldProductQuantity,
            //'Cantidad del Servicio' => $services ? $service['orders_count'] : 0,
        ];

        $iconColorMapping = [
            'Monto Generado Servicios' => ['icon' => 'mdi-wrench', 'color' => 'pink'],
            'Utilidad de Productos Vendidos' => ['icon' => 'mdi-cash-register', 'color' => 'purple'],
            'Monto Generado' => ['icon' => 'mdi-wallet', 'color' => 'green'],
            'Utilidades' => ['icon' => 'mdi-wallet', 'color' => 'green'],
            'Propina' => ['icon' => 'mdi-cash', 'color' => 'blue'],
            'Total Retenciones' => ['icon' => 'mdi-account-multiple', 'color' => 'grey'],
            //'Producto más Vendido' => ['icon' => 'mdi-cart', 'color' => 'red'],
            'Cantidad del Producto' => ['icon' => 'mdi-format-list-numbered', 'color' => 'orange'],
            'Total de Productos Vendidos' => ['icon' => 'mdi-cash-register', 'color' => 'purple'],
            'Monto Generado Productos Vendidos' => ['icon' => 'mdi-cash-register', 'color' => 'purple'],
            'Servicio más Brindado' => ['icon' => 'mdi-wrench', 'color' => 'pink'],
            //'Cantidad del Servicio' => ['icon' => 'mdi-counter', 'color' => 'teal'],
            'Total de Servicios Brindados' => ['icon' => 'mdi-hammer-screwdriver', 'color' => 'cyan'],
            'Utilidad de Servicios' => ['icon' => 'mdi-hammer-screwdriver', 'color' => 'cyan'],
            'Clientes Seleccionados' => ['icon' => 'mdi-check-circle', 'color' => 'lime'],
            'Clientes Aleatorios' => ['icon' => 'mdi-shuffle', 'color' => 'amber'],
            'Servicios Especiales' => ['icon' => 'mdi-star', 'color' => 'yellow'],
            'Monto Servicios Especiales' => ['icon' => 'mdi-cash', 'color' => 'blue'],
            'Clientes Atendidos' => ['icon' => 'mdi-account-multiple', 'color' => 'indigo'],
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

    public function branch_professionals_winner_date($branch_id)
    {
        $dates = [];
        $professionals = Professional::whereHas('charge', function ($query) {
            $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
        })->whereHas('branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->get();
        //$branches = Branch::select('id', 'name')->get();
        foreach ($professionals as $professional) {
            $payments = ProfessionalPayment::where('branch_id', $branch_id)
                ->where('professional_id', $professional->id)
                ->whereDate('date', Carbon::now())
                ->whereIn('type', ['Bono convivencias', 'Bono productos', 'Bono servicios'])
                ->get();
            //foreach ($branches as $branch) {
            $cars = Car::whereHas('reservation', function ($query) use ($branch_id) {
                $query->where('branch_id', $branch_id)->whereDate('data', Carbon::now());
            })->whereHas('clientProfessional', function ($query) use ($professional) {
                $query->where('professional_id', $professional->id);
            })->where('pay', 1)->get();
            $retention = $professional->retention;
            $winProfessional = $cars->sum(function ($car) {
                return $car->orders->sum('percent_win');
            });
            $amuntGenerate = $cars->sum(function ($car) {
                return $car->orders->sum('price');
            });
            $retentionPorcent = round(($winProfessional * $retention) / 100);
            $winTips =  round($cars->sum('tip') * 0.8, 2);
            $bonus = $payments->sum('amount');
            $dates[] =  [
                //'branchName' => $branch->name,
                'name' => $professional->name,
                'image_url' => $professional->image_url,
                'amount' => $winProfessional,
                'amountGenerate' => $amuntGenerate,
                'retention' => $retentionPorcent,
                'tip' => round($cars->sum('tip'), 2),
                'tip80' => $winTips,
                'bonus' => $bonus,
                'total' => ($winProfessional - $retentionPorcent + $winTips) + $bonus,
                'total_cars' => $cars->count()
            ];
            //})->sortByDesc('total')->values();
            //}
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

    public function branch_professionals_winner_periodo($startDate, $endDate, $branch_id)
    {
        $dates = [];
        $professionals = Professional::whereHas('charge', function ($query) {
            $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
        })->whereHas('branches', function ($query) use ($branch_id) {
            $query->where('branch_id', $branch_id);
        })->get();
        //$branches = Branch::select('id', 'name')->get();
        foreach ($professionals as $professional) {
            $payments = ProfessionalPayment::where('branch_id', $branch_id)
                ->where('professional_id', $professional->id)
                ->whereDate('date', '>=', $startDate)->whereDate('date', '<=', $endDate)
                ->whereIn('type', ['Bono convivencias', 'Bono productos', 'Bono servicios'])
                ->get();
            //foreach ($branches as $branch) {
            $cars = Car::whereHas('reservation', function ($query) use ($branch_id, $startDate, $endDate) {
                $query->where('branch_id', $branch_id)->whereDate('data', '>=', $startDate)->whereDate('data', '<=', $endDate);
            })->whereHas('clientProfessional', function ($query) use ($professional) {
                $query->where('professional_id', $professional->id);
            })->where('pay', 1)->get();
            $retention = $professional->retention;
            $winProfessional = $cars->sum(function ($car) {
                return $car->orders->sum('percent_win');
            });
            $amuntGenerate = $cars->sum(function ($car) {
                return $car->orders->sum('price');
            });
            $retentionPorcent = round(($winProfessional * $retention) / 100);
            $winTips =  round($cars->sum('tip') * 0.8, 2);
            $bonus = $payments->sum('amount');
            $dates[] =  [
                //'branchName' => $branch->name,
                'name' => $professional->name,
                'image_url' => $professional->image_url,
                'amount' => $winProfessional,
                'amountGenerate' => $amuntGenerate,
                'retention' => $retentionPorcent,
                'tip' => round($cars->sum('tip'), 2),
                'tip80' => $winTips,
                'bonus' => $bonus,
                'total' => ($winProfessional - $retentionPorcent + $winTips) + $bonus,
                'total_cars' => $cars->count()
            ];
            //})->sortByDesc('total')->values();
            //}
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
