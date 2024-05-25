<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Branch;
use App\Models\BranchProfessional;
use App\Models\Business;
use App\Models\Car;
use App\Models\CashierSale;
use App\Models\ClientProfessional;
use App\Models\Comment;
use App\Models\CourseProfessional;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStore;
use App\Models\Professional;
use App\Models\ProfessionalPayment;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Service;
use App\Services\CarService;
use App\Services\TraceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CarController extends Controller
{
    private CarService $carService;
    private TraceService $traceService;

    public function __construct(CarService $carService, TraceService $traceService)
    {
        $this->carService = $carService;
        $this->traceService = $traceService;
    }

    public function index()
    {
        try {
            Log::info("Entra a buscar los carros");
            $car = Car::with('clientProfessional.client', 'clientProfessional.professional')->get();
            return response()->json(['cars' => $car], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar los carros"], 500);
        }
    }

    public function cars_sum_amount(Request $request)
    {
        try {
            Log::info("Entra a buscar una las reservaciones del dia");
            Log::info(now()->toDateString());
            $data = $request->validate([
                'business_id' => 'required|numeric',
                'branch_id' => 'nullable'
            ]);
            Log::info($data['branch_id']);
            if ($data['branch_id'] != 0) {
                Log::info("Branch");
                /*$branches = Branch::where('id', $data['branch_id'])->get()->map(function ($branch){
                    $amount = $branch->cars()->where('pay', 1)->whereHas('reservations', function ($query){
                        $query->whereDate('data', now()->toDateString());
                    })->sum('amount') + $branch->cars()->where('pay', 1)->whereHas('reservations', function ($query){
                        $query->whereDate('data', now()->toDateString());
                    })->sum('tip') + $branch->cars()->where('pay', 1)->whereHas('reservations', function ($query){
                        $query->whereDate('data', now()->toDateString());
                    })->sum('technical_assistance') * 5000;
                    return $amount;
                });*/
                $cars = Car::whereHas('reservation', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id'])->whereDate('data', now()->toDateString());
                });

                return response()->json($cars->sum('amount') + $cars->sum('technical_assistance') * 5000, 200, [], JSON_NUMERIC_CHECK);
            } else {
                Log::info("Businesssss");
                $business = Business::find($data['business_id']);
                /*$branches = $business->branches->map(function ($branch){
                $amount = $branch->cars()->where('pay', 1)->whereHas('reservations', function ($query){
                    $query->whereDate('data', now()->toDateString());
                })->sum('amount') + $branch->cars()->where('pay', 1)->whereHas('reservations', function ($query){
                    $query->whereDate('data', now()->toDateString());
                })->sum('tip') + $branch->cars()->where('pay', 1)->whereHas('reservations', function ($query){
                    $query->whereDate('data', now()->toDateString());
                })->sum('technical_assistance') * 5000;
                return $amount;
            });*/
                $cars = Car::whereHas('reservations', function ($query) {
                    $query->whereDate('data', now()->toDateString());
                });
                return response()->json($cars->sum('amount') + $cars->sum('technical_assistance') * 5000, 200, [], JSON_NUMERIC_CHECK);
            }
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las reservaciones"], 500);
        }
    }

    public function car_products_services(Request $request)
    {
        try {
            Log::info("Entra a buscar una las ganancias del mes");
            $data = $request->validate([
                'business_id' => 'required|numeric',
                'branch_id' => 'nullable'
            ]);

            $fechaActual = Carbon::now();
            // Restar un mes a la fecha actual y establecer el día como el mismo día que hoy
            $fechaMesAnterior = $fechaActual->subMonth()->day($fechaActual->day);
            // Obtener la fecha como una cadena en el formato 'Y-m-d'
            $fechaFormateada = $fechaMesAnterior->toDateString();
            if ($data['branch_id'] != 0) {
                Log::info("branch");

                $ordersAct = Order::whereHas('car.reservation', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now());
                })->get();
                $ordersAnt = Order::whereHas('car.reservation', function ($query) use ($data, $fechaFormateada) {
                    $query->where('branch_id', $data['branch_id'])->whereDate('data', $fechaFormateada);
                })->get();

                $products = Product::with(['orders' => function ($query) use ($data) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(price) as total_price')
                            ->groupBy('product_id')
                            ->whereDate('data', Carbon::now())
                            ->whereHas('car.reservation', function ($query) use ($data) {
                                $query->whereDate('data', Carbon::now())->where('branch_id', $data['branch_id']);
                            })
                            ->where('is_product', 1);
                    },
                    'cashiersales' => function ($query) use ($data) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(price) as total_price, product_id')
                            ->groupBy('product_id')
                            ->where('cashiersales.branch_id', $data['branch_id'])
                            ->whereDate('data', Carbon::now());
                    }
                ])->get()->filter(function ($product) {
                    return !$product->orders->isEmpty() || !$product->cashiersales->isEmpty();
                })->values()->sortByDesc(function ($product) {
                    return $product->orders->sum('total_cant') + $product->cashiersales->sum('total_cant');
                })->map(function ($product) {
                    return [
                        'name' => $product->name,
                        'total_cant' => $product->orders->sum('total_cant') + $product->cashiersales->sum('total_cant'),
                        'total_price' => $product->orders->sum('total_price') + $product->cashiersales->sum('total_price')
                    ];
                });

                $productsAnt = Product::with(['orders' => function ($query) use ($data, $fechaFormateada) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(price) as total_price')
                            ->groupBy('product_id')
                            ->whereDate('data', $fechaFormateada)
                            ->whereHas('car.reservation', function ($query) use ($data, $fechaFormateada) {
                                $query->whereDate('data', $fechaFormateada)->where('branch_id', $data['branch_id']);
                            })
                            ->where('is_product', 1);
                    },
                    'cashiersales' => function ($query) use ($data, $fechaFormateada) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(price) as total_price, product_id')
                            ->groupBy('product_id')
                            ->where('cashiersales.branch_id', $data['branch_id'])
                            ->whereDate('data', $fechaFormateada);
                    }])->get()->filter(function ($product) {
                        return !$product->orders->isEmpty() || !$product->cashiersales->isEmpty();
                    })->values()->sortByDesc(function ($product) {
                        return $product->orders->sum('total_cant') + $product->cashiersales->sum('total_cant');
                    })->map(function ($product) {
                        return [
                            'name' => $product->name,
                            'total_cant' => $product->orders->sum('total_cant') + $product->cashiersales->sum('total_cant'),
                            'total_price' => $product->orders->sum('total_price') + $product->cashiersales->sum('total_price')
                        ];
                    });
                $resultPproduct[] = [
                    //'name' => $mostSoldProductName,
                    'cant' => $products->sum('total_price'),
                    'products' => $products,
                    //'nameAnt' => $mostSoldProductNameAnt,
                    'cantAnt' => $productsAnt->sum('total_price'),
                    'productsAnt' => $productsAnt,
                ];
                //Servicios
                $services = Service::has('orders')
                    ->withCount(['orders' => function ($query) use ($data) {
                        $query->whereHas('car.reservation', function ($query) use ($data) {
                            $query->whereDate('data', Carbon::now())->where('branch_id', $data['branch_id']);
                        })->where('is_product', 0);
                    }])->orderByDesc('orders_count')->get();
                //$mostSoldService = $services->first();

                $servicesAnt = Service::has('orders')
                    ->withCount(['orders' => function ($query) use ($data, $fechaFormateada) {
                        $query->whereHas('car.reservation', function ($query) use ($data, $fechaFormateada) {
                            $query->whereDate('data', $fechaFormateada)->where('branch_id', $data['branch_id']);
                        })->where('is_product', 0);
                    }])
                    ->orderByDesc('orders_count')
                    ->get();
                /*$mostSoldServiceAnt = $servicesAnt->first();*/
                $resultService[] = [
                    //'name' => $mostSoldService->orders_count ? $mostSoldService->name : '--',
                    'cant' => $ordersAct->where('is_product', 0)->sum('price'),
                    'services' => $services->filter(function ($service) {
                        return $service->orders_count > 0;
                    }),
                    //'nameAnt' => $mostSoldServiceAnt->orders_count ? $mostSoldServiceAnt->name : '--',
                    'cantAnt' => $ordersAnt->where('is_product', 0)->sum('price'),
                    'servicesAnt' => $servicesAnt->filter(function ($service) {
                        return $service->orders_count > 0;
                    }),
                ];
                return response()->json(['product' => $resultPproduct, 'service' => $resultService], 200);
            } else {
                Log::info("No branch");
                $ordersAct = Order::whereHas('car.reservation', function ($query) use ($data) {
                    $query->whereDate('data', Carbon::now());
                })->get();
                $ordersAnt = Order::whereHas('car.reservation', function ($query) use ($data, $fechaFormateada) {
                    $query->whereDate('data', $fechaFormateada);
                })->get();
                $products = Product::with(['orders' => function ($query) use ($data) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(price) as total_price')
                            ->groupBy('product_id')
                            ->whereDate('data', Carbon::now())
                            ->whereHas('car.reservation', function ($query) use ($data) {
                                $query->whereDate('data', Carbon::now())/*->where('branch_id', $data['branch_id'])*/;
                            })
                            ->where('is_product', 1);
                    },
                    'cashiersales' => function ($query){
                        $query->selectRaw('SUM(cant) as total_cant, SUM(price) as total_price, product_id')
                            ->groupBy('product_id')
                            ->whereDate('data', Carbon::now());
                    }
                ])->get()->filter(function ($product) {
                    return !$product->orders->isEmpty() || !$product->cashiersales->isEmpty();
                })->values()->sortByDesc(function ($product) {
                    return $product->orders->sum('total_cant') + $product->cashiersales->sum('total_cant');
                })->map(function ($product) {
                    return [
                        'name' => $product->name,
                        'total_cant' => $product->orders->sum('total_cant') + $product->cashiersales->sum('total_cant'),
                        'total_price' => $product->orders->sum('total_price') + $product->cashiersales->sum('total_price')
                    ];
                });

                $productsAnt = Product::with(['orders' => function ($query) use ($fechaFormateada) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(price) as total_price')
                            ->groupBy('product_id')
                            ->whereDate('data', $fechaFormateada)
                            ->whereHas('car.reservation', function ($query) use ($fechaFormateada) {
                                $query->whereDate('data', $fechaFormateada)/*->where('branch_id', $data['branch_id'])*/;
                            })
                            ->where('is_product', 1);
                    },
                    'cashiersales' => function ($query) use ($fechaFormateada) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(price) as total_price, product_id')
                            ->groupBy('product_id')
                            ->whereDate('data', $fechaFormateada);
                    }])->get()->filter(function ($product) {
                        return !$product->orders->isEmpty() || !$product->cashiersales->isEmpty();
                    })->values()->sortByDesc(function ($product) {
                        return $product->orders->sum('total_cant') + $product->cashiersales->sum('total_cant');
                    })->map(function ($product) {
                        return [
                            'name' => $product->name,
                            'total_cant' => $product->orders->sum('total_cant') + $product->cashiersales->sum('total_cant'),
                            'total_price' => $product->orders->sum('total_price') + $product->cashiersales->sum('total_price')
                        ];
                    });
                    $resultPproduct[] = [
                        //'name' => $mostSoldProductName,
                        'cant' => $products->sum('total_price'),
                        'products' => $products,
                        //'nameAnt' => $mostSoldProductNameAnt,
                        'cantAnt' => $productsAnt->sum('total_price'),
                        'productsAnt' => $productsAnt,
                    ];
                //Servicios
                $services = Service::has('orders')
                    ->withCount(['orders' => function ($query) use ($data) {
                        $query->whereHas('car.reservation', function ($query) use ($data) {
                            $query->whereDate('data', Carbon::now())/*->where('branch_id', $data['branch_id'])*/;
                        })->where('is_product', 0);
                    }])->orderByDesc('orders_count')->get();
                //$mostSoldService = $services->first();

                $servicesAnt = Service::has('orders')
                    ->withCount(['orders' => function ($query) use ($data, $fechaFormateada) {
                        $query->whereHas('car.reservation', function ($query) use ($data, $fechaFormateada) {
                            $query->whereDate('data', $fechaFormateada);
                        })->where('is_product', 0);
                    }])
                    ->orderByDesc('orders_count')
                    ->get();
                //$mostSoldServiceAnt = $servicesAnt->first();*/
                $resultService[] = [
                    //'name' => $mostSoldService->orders_count ? $mostSoldService->name : '--',
                    'cant' => $ordersAct->where('is_product', 0)->sum('price'),
                    'services' => $services->filter(function ($service) {
                        return $service->orders_count > 0;
                    }),
                    //'nameAnt' => $mostSoldServiceAnt->orders_count ? $mostSoldServiceAnt->name : '--',
                    'cantAnt' => $ordersAnt->where('is_product', 0)->sum('price'),
                    'servicesAnt' => $servicesAnt->filter(function ($service) {
                        return $service->orders_count > 0;
                    }),
                ];
                return response()->json(['product' => $resultPproduct, 'service' => $resultService], 200);
            }
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las reservaciones"], 500);
        }
        //Agergar productos y servicios


    }

    public function cars_sum_amount_week(Request $request)
    {
        try {
            Log::info("Entra a buscar una las reservations del dia");
            $data = $request->validate([
                'business_id' => 'required|numeric',
                'branch_id' => 'nullable'
            ]);
            $array = [];
            $start = now()->startOfWeek(); // Start of the current week, shifted to Monday
            $end = now()->endOfWeek();   // End of the current week, shifted to Sunday
            $dates = [];
            //return [$start, $end];
            $i = 0;
            $day = 0; //en $day = 1 es Lunes,$day=2 es Martes...$day=7 es Domingo, esto e spara el front
            if ($data['branch_id'] != 0) {
                Log::info('branchesssss');
                $cars = Car::whereHas('reservations', function ($query) use ($start, $end, $data) {
                    $query->whereDate('data', '>=', $start)->whereDate('data', '<=', $end)->where('branch_id', $data['branch_id']);
                })->get()->map(function ($car) {
                    return [
                        'date' => $car->reservations->data,
                        'earnings' => $car->amount + ($car->technical_assistance * 5000)
                    ];
                });
                for ($date = $start, $i = 0; $date->lte($end); $date->addDay(), $i++) {
                    $machingResult = $cars->where('date', $date->toDateString())->sum('earnings');
                    //$dates['amount'][$i] = $machingResult ? $machingResult: 0;
                    $dates[$i] = $machingResult ? $machingResult : 0;
                }
                return $dates;
                /*for($start; $start <= $end; $start->addDay()){                            
                                $branches = Branch::where('id', $data['branch_id'])->get()->map(function ($branch) use ($start){
                                    $amount = $branch->cars()->whereHas('reservation', function ($query) use ($start){
                                        $query->whereDate('data', $start);
                                    })->sum('amount') + $branch->cars()->whereHas('reservation', function ($query) use ($start){
                                        $query->whereDate('data', $start);
                                    })->sum('tip') + $branch->cars()->whereHas('reservation', function ($query) use ($start){
                                        $query->whereDate('data', $start);
                                    })->sum('technical_assistance') * 5000;
                                    return $amount;
                });
                $array [] = $branches;
            }*/
            } else {
                $cars = Car::whereHas('reservations', function ($query) use ($start, $end) {
                    $query->whereDate('data', '>=', $start)->whereDate('data', '<=', $end);
                })->get()->map(function ($car) {
                    return [
                        'date' => $car->reservations->data,
                        'earnings' => $car->amount + ($car->technical_assistance * 5000)
                    ];
                });
                for ($date = $start, $i = 0; $date->lte($end); $date->addDay(), $i++) {
                    $machingResult = $cars->where('date', $date->toDateString())->sum('earnings');
                    //$dates['amount'][$i] = $machingResult ? $machingResult: 0;
                    $dates[$i] = $machingResult ? $machingResult : 0;
                }
                return $dates;
            }


            //Log::info($branches);

            return response()->json($array, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las reservaciones"], 500);
        }
    }

    public function cars_sum_amount_mounth(Request $request)
    {
        try {
            Log::info("Entra a buscar una las ganancias del mes");
            $data = $request->validate([
                'business_id' => 'required|numeric',
                'branch_id' => 'nullable'
            ]);
            $startOfMonth = now()->startOfMonth()->toDateString();
            $endOfMonth = now()->endOfMonth()->toDateString();
            $inicio_mes_anterior = Carbon::now()->subMonth()->startOfMonth();
            // Obtener la fecha de finalización del mes anterior
            $final_mes_anterior = Carbon::now()->subMonth()->endOfMonth();
            if ($data['branch_id'] != 0) {
                Log::info("branch");
                /*$branches = Branch::where('id', $data['branch_id'])->get()->map(function ($branch){
                    $startOfMonth = now()->startOfMonth()->toDateString();
                    $endOfMonth = now()->endOfMonth()->toDateString();
                    $amount = $branch->cars()->where('pay', 1)->whereHas('reservations', function ($query)  use ($startOfMonth, $endOfMonth){
                        $query->whereBetween('data', [$startOfMonth, $endOfMonth]);
                    })->sum('amount') + $branch->cars()->where('pay', 1)->whereHas('reservations', function ($query)  use ($startOfMonth, $endOfMonth){
                        $query->whereBetween('data', [$startOfMonth, $endOfMonth]);
                    })->sum('tip') + $branch->cars()->where('pay', 1)->whereHas('reservations', function ($query)  use ($startOfMonth, $endOfMonth){
                        $query->whereBetween('data', [$startOfMonth, $endOfMonth]);
                    })->sum('technical_assistance') * 5000;
                    return $amount;
                });*/
                $cars = Car::whereHas('reservation', function ($query) use ($data, $startOfMonth, $endOfMonth) {
                    $query->where('branch_id', $data['branch_id'])->whereDate('data', '>=', $startOfMonth)->whereDate('data', '<=', $endOfMonth);
                })->where('pay', 1);
                $carsDetail = $cars->get()->map(function ($car) {
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
                $resultDetails[] = [
                    'productsAmount' => round($carsDetail->sum('productsAmount'), 2),
                    'servicesAmount' => round($carsDetail->sum('servicesAmount'), 2),
                    'earnings' => round($carsDetail->sum('earnings'), 2),
                    'technical_assistance' => round($carsDetail->sum('technical_assistance'), 2),
                    'tip' => round($carsDetail->sum('tip'), 2),
                    'total' => round($carsDetail->sum('total'), 2),
                ];

                $carsAnt = Car::whereHas('reservation', function ($query) use ($data, $inicio_mes_anterior, $final_mes_anterior) {
                    $query->where('branch_id', $data['branch_id'])->whereDate('data', '>=', $inicio_mes_anterior)->whereDate('data', '<=', $final_mes_anterior);
                })->where('pay', 1);
                $carsDetailAnt = $carsAnt->get()->map(function ($car) {
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
                $resultDetailsAnt[] = [
                    'productsAmount' => round($carsDetailAnt->sum('productsAmount'), 2),
                    'servicesAmount' => round($carsDetailAnt->sum('servicesAmount'), 2),
                    'earnings' => round($carsDetailAnt->sum('earnings'), 2),
                    'technical_assistance' => round($carsDetailAnt->sum('technical_assistance'), 2),
                    'tip' => round($carsDetailAnt->sum('tip'), 2),
                    'total' => round($carsDetailAnt->sum('total'), 2),
                ];
                $cars = $cars->sum('amount') + $cars->sum('technical_assistance') * 5000;
                $carsAnt = $carsAnt->sum('amount') + $carsAnt->sum('technical_assistance') * 5000;

                return response()->json(['cars' => $cars, 'carsDetail' => $resultDetails, 'carsDetailAnt' => $resultDetailsAnt, 'carsAnt' => $carsAnt], 200);
            } else {
                Log::info("businesss");
                /*$business = Business::find($data['business_id']);
             $branches = $business->branches->map(function ($branch){
                $startOfMonth = now()->startOfMonth()->toDateString();
                $endOfMonth = now()->endOfMonth()->toDateString();
                $amount = $branch->cars()->where('pay', 1)->whereHas('reservations', function ($query)  use ($startOfMonth, $endOfMonth){
                    $query->whereBetween('data', [$startOfMonth, $endOfMonth]);
                })->sum('amount') + $branch->cars()->where('pay', 1)->whereHas('reservations', function ($query)  use ($startOfMonth, $endOfMonth){
                    $query->whereBetween('data', [$startOfMonth, $endOfMonth]);
                })->sum('tip') + $branch->cars()->where('pay', 1)->whereHas('reservations', function ($query)  use ($startOfMonth, $endOfMonth){
                    $query->whereBetween('data', [$startOfMonth, $endOfMonth]);
                })->sum('technical_assistance') * 5000;
                return $amount;
            });*/
                $carsAnt = Car::whereHas('reservations', function ($query) use ($inicio_mes_anterior, $final_mes_anterior) {
                    $query->whereDate('data', '>=', $inicio_mes_anterior)->whereDate('data', '<=', $final_mes_anterior);
                })->where('pay', 1);
                $carsDetailAnt = $carsAnt->get()->map(function ($car) {
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
                $resultDetailsAnt[] = [
                    'productsAmount' => round($carsDetailAnt->sum('productsAmount'), 2),
                    'servicesAmount' => round($carsDetailAnt->sum('servicesAmount'), 2),
                    'earnings' => round($carsDetailAnt->sum('earnings'), 2),
                    'technical_assistance' => round($carsDetailAnt->sum('technical_assistance'), 2),
                    'tip' => round($carsDetailAnt->sum('tip'), 2),
                    'total' => round($carsDetailAnt->sum('total'), 2),
                ];

                $cars = Car::whereHas('reservations', function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->whereDate('data', '>=', $startOfMonth)->whereDate('data', '<=', $endOfMonth);
                })->where('pay', 1);
                $carsDetail = $cars->get()->map(function ($car) {
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
                $resultDetails[] = [
                    'productsAmount' => round($carsDetail->sum('productsAmount'), 2),
                    'servicesAmount' => round($carsDetail->sum('servicesAmount'), 2),
                    'earnings' => round($carsDetail->sum('earnings'), 2),
                    'technical_assistance' => round($carsDetail->sum('technical_assistance'), 2),
                    'tip' => round($carsDetail->sum('tip'), 2),
                    'total' => round($carsDetail->sum('total'), 2),
                ];
                $carsAnt = $carsAnt->sum('amount') + $carsAnt->sum('technical_assistance') * 5000;
                $cars = $cars->sum('amount') + $cars->sum('technical_assistance') * 5000;
                return response()->json(['cars' => $cars, 'carsDetail' => $resultDetails, 'carsDetailAnt' => $resultDetailsAnt, 'carsAnt' => $carsAnt], 200);
            }
            //return response()->json($branches->sum(), 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las reservaciones"], 500);
        }
    }

    public function branch_cars(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            Log::info("Esta es request");
            Log::info($request);
            Log::info("Entra a buscar los carros");
            $branch = Branch::where('id', $data['branch_id'])->first();
            Log::info("Esta es la sucursal");
            Log::info($branch);
            $cars = Car::whereHas('reservation', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now());
            })->with(['clientProfessional.client', 'clientProfessional.professional', 'payment'])->get()->map(function ($car) use ($data) {
                $client = $car->clientProfessional->client;
                $professional = $car->clientProfessional->professional;
                $products = $car->orders->where('is_product', 1)->sum('price');
                $services = $car->orders->where('is_product', 0)->sum('price');
                if ($car->reservation->tail == null) {
                    $state = 0;
                } else {
                    $attended = $car->reservation->tail->attended;
                    if ($attended == 0 || $attended == 3) {
                        $state = 3; //En cola
                    } elseif ($attended == 2) {
                        $state = 1; // Atendido
                    } else {
                        $state = 2; // Atendiendose 
                    }
                }

                return [
                    'id' => $car->id,
                    'client_professional_id' => $car->client_professional_id,
                    'amount' => $car->amount + ($car->technical_assistance * 5000) + $car->tip,
                    'tip' => $car->tip,
                    'pay' => (int)$car->pay,
                    'active' => $car->active,
                    'product' => $products,
                    'service' => $services,
                    'technical_assistance' => $car->technical_assistance * 5000,
                    'clientName' => $client->name . ' ' . $client->surname,
                    'professionalName' => $professional->name . ' ' . $professional->surname,
                    'client_image' => $client->client_image,
                    'professional_id' => $professional->id,
                    'image_url' => $professional->image_url,
                    'payment' => $car->payment,
                    'state' => (int)$state

                ];
                //}
            })->sortBy('state')->values();
            $box = Box::with('boxClose')->whereDate('data', Carbon::now())->where('branch_id', $data['branch_id'])->first();
            $payments = Payment::whereDate('created_at', Carbon::now())->where('branch_id', $data['branch_id'])->get();
            $cashierSales = CashierSale::where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now())->get();
            return response()->json(['cars' => $cars, 'box' => $box, 'payments' => $payments, 'cashierSales' => $cashierSales], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los carros"], 500);
        }
    }

    public function branch_cars_delete(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);

            $orderData = [];
            if ($data['branch_id'] == 0) {
                $cars = Car::whereHas('reservation', function ($query) use ($data) {
                    $query->whereDate('data', Carbon::now());
                })->where('active', 3)->with(['clientProfessional.client', 'clientProfessional.professional', 'payment'])->orderByDesc('updated_at')->get()->map(function ($car) use ($data) {
                    $client = $car->clientProfessional->client;
                    $professional = $car->clientProfessional->professional;
                    $products = $car->orders->where('is_product', 1)->sum('price');
                    $services = $car->orders->where('is_product', 0)->sum('price');
                    $branch = Branch::where('id', $car->reservation->branch_id)->first();
                    return [
                        'id' => $car->id,
                        'client_professional_id' => $car->client_professional_id,
                        'amount' => $car->amount + ($car->technical_assistance * 5000) + $car->tip,
                        'tip' => $car->tip,
                        'pay' => (int)$car->pay,
                        'active' => $car->active,
                        'product' => $products,
                        'service' => $services,
                        'technical_assistance' => $car->technical_assistance * 5000,
                        'clientName' => $client->name . ' ' . $client->surname,
                        'professionalName' => $professional->name . ' ' . $professional->surname,
                        'client_image' => $client->client_image,
                        'professional_id' => $professional->id,
                        'image_url' => $professional->image_url,
                        'nameBranch' => $branch->name
                    ];
                    //}
                })->sortBy('state')->values();
                $orders = Order::with(['car.clientProfessional.professional', 'car.clientProfessional.client', 'productStore.product', 'branchServiceProfessional.branchService.service'])->whereHas('car.reservation', function ($query) use ($data) {
                    $query->whereDate('data', Carbon::now());
                })->where('request_delete', 3)->orderByDesc('updated_at')->get();

                foreach ($orders as $order) {
                    $branch = Branch::where('id', $order['car']['reservation']['branch_id'])->first();
                    $professional = $order['car']['clientProfessional']['professional'];
                    $client = $order['car']['clientProfessional']['client'];
                    $product = $order['is_product'] ? $order['productStore']['product'] : null;
                    $service = !$order['is_product'] ? $order['branchServiceProfessional'] : null;
                    $orderData[] = [
                        'id' => $order['id'],
                        'car_id' => $order['car_id'],
                        'price' => $order['price'],
                        'professionalName' => $professional['name'] . ' ' . $professional['surname'],
                        'image_url' => $professional['image_url'],
                        'clientName' => $client['name'] . ' ' . $client['surname'],
                        'client_image' => $client['client_image'],
                        'category' => $order['is_product'] ? $product['productCategory']['name'] : $service['type_service'],
                        'name' => $order['is_product'] ? $product['name'] : $service['branchService']['service']['name'],
                        'image' => $order['is_product'] ? $product['image_product'] : $service['branchService']['service']['image_service'],
                        'nameBranch' => $branch->name
                    ];
                }
            } else {
                $branch = Branch::where('id', $data['branch_id'])->first();
                $cars = Car::whereHas('reservation', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now());
                })->where('active', 3)->with(['clientProfessional.client', 'clientProfessional.professional', 'payment'])->orderByDesc('updated_at')->get()->map(function ($car) use ($branch) {
                    $client = $car->clientProfessional->client;
                    $professional = $car->clientProfessional->professional;
                    $products = $car->orders->where('is_product', 1)->sum('price');
                    $services = $car->orders->where('is_product', 0)->sum('price');
                    return [
                        'id' => $car->id,
                        'client_professional_id' => $car->client_professional_id,
                        'amount' => $car->amount + ($car->technical_assistance * 5000) + $car->tip,
                        'tip' => $car->tip,
                        'pay' => (int)$car->pay,
                        'active' => $car->active,
                        'product' => $products,
                        'service' => $services,
                        'technical_assistance' => $car->technical_assistance * 5000,
                        'clientName' => $client->name . ' ' . $client->surname,
                        'professionalName' => $professional->name . ' ' . $professional->surname,
                        'client_image' => $client->client_image,
                        'professional_id' => $professional->id,
                        'image_url' => $professional->image_url,
                        'nameBranch' => $branch->name
                    ];
                    //}
                })->sortBy('state')->values();
                $orders = Order::with(['car.clientProfessional.professional', 'car.clientProfessional.client', 'productStore.product', 'branchServiceProfessional.branchService.service'])->whereHas('car.reservation', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now());
                })->where('request_delete', 3)->orderByDesc('updated_at')->get();

                foreach ($orders as $order) {
                    $professional = $order['car']['clientProfessional']['professional'];
                    $client = $order['car']['clientProfessional']['client'];
                    $product = $order['is_product'] ? $order['productStore']['product'] : null;
                    $service = !$order['is_product'] ? $order['branchServiceProfessional'] : null;
                    $orderData[] = [
                        'id' => $order['id'],
                        'car_id' => $order['car_id'],
                        'price' => $order['price'],
                        'professionalName' => $professional['name'] . ' ' . $professional['surname'],
                        'image_url' => $professional['image_url'],
                        'clientName' => $client['name'] . ' ' . $client['surname'],
                        'client_image' => $client['client_image'],
                        'category' => $order['is_product'] ? $product['productCategory']['name'] : $service['type_service'],
                        'name' => $order['is_product'] ? $product['name'] : $service['branchService']['service']['name'],
                        'image' => $order['is_product'] ? $product['image_product'] : $service['branchService']['service']['image_service'],
                        'nameBranch' => $branch->name
                    ];
                }
            }
            return response()->json(['cars' => $cars, 'orders' => $orderData], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los carros"], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info("Guardar carro");
        Log::info($request);
        try {
            $data = $request->validate([
                'client_professional_id' => 'required|numeric',
                'amount' => 'nullable|numeric',
                'pay' => 'boolean',
                'active' => 'boolean',
                'tip' => 'nullable'
            ]);
            $car = $this->carService->store($data);

            return response()->json(['msg' => $car], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => 'Error al insertar el carro'], 500);
        }
    }

    public function car_orders(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $carOrders = Order::with([
                'car.clientProfessional',
                'productStore.product',
                'branchServiceProfessional.branchService.service'
            ])->where('car_id', $data['id'])->orderBy('data', 'desc')->get();

            $products = [];
            $services = [];

            foreach ($carOrders as $order) {
                if ($order->is_product) {
                    $product = $order->productStore->product;
                    $products[] = [
                        'id' => $order->id,
                        'name' => $product->name,
                        'reference' => $product->reference,
                        'code' => $product->code,
                        'description' => $product->description,
                        'status_product' => $product->status_product,
                        'purchase_price' => $product->purchase_price,
                        'sale_price' => $product->sale_price,
                        'image_product' => $product->image_product,
                        'is_product' => $order->is_product,
                        'price' => $order->price,
                        'request_delete' => $order->request_delete
                    ];
                } else {
                    $service = $order->branchServiceProfessional->branchService->service;
                    $services[] = [
                        'id' => $order->id,
                        'nameService' => $service->name,
                        'simultaneou' => $service->simultaneou,
                        'price_service' => $service->price_service,
                        'type_service' => $service->type_service,
                        'profit_percentaje' => $service->profit_percentaje,
                        'duration_service' => $service->duration_service,
                        'image_service' => $service->image_service,
                        'service_comment' => $service->service_comment,
                        'is_product' => $order->is_product,
                        'price' => $order->price,
                        'request_delete' => $order->request_delete
                    ];
                }
            }
            /*$orderProductsDatas = Order::with('car.clientProfessional')->whereRelation('car', 'id', '=', $data['id'])->where('is_product', true)->orderBy('data', 'desc')->get();
            $products = $orderProductsDatas->map(function ($orderData){
                    return [
                        'id' => $orderData->id,                   
                        'name' => $orderData->productStore->product->name,
                        'reference' => $orderData->productStore->product->reference,
                        'code' => $orderData->productStore->product->code,
                        'description' => $orderData->productStore->product->description,
                        'status_product' => $orderData->productStore->product->status_product,
                        'purchase_price' => $orderData->productStore->product->purchase_price,
                        'sale_price' => $orderData->productStore->product->sale_price,
                        'image_product' => $orderData->productStore->product->image_product,
                        'is_product' => $orderData->is_product,
                        'price' => $orderData->price,
                        'request_delete' => $orderData->request_delete
                    ];
               });
           $orderServicesDatas = Order::with('car.clientProfessional')->whereRelation('car', 'id', '=', $data['id'])->where('is_product', false)->orderBy('data', 'desc')->get();
           $services = $orderServicesDatas->map(function ($orderData){
              return [
                    'id' => $orderData->id,
                    'nameService' => $orderData->branchServiceProfessional->branchService->service->name,
                    'simultaneou' => $orderData->branchServiceProfessional->branchService->service->simultaneou,
                    'price_service' => $orderData->branchServiceProfessional->branchService->service->price_service,
                    'type_service' => $orderData->branchServiceProfessional->branchService->service->type_service,
                    'profit_percentaje' => $orderData->branchServiceProfessional->branchService->service->profit_percentaje,
                    'duration_service' => $orderData->branchServiceProfessional->branchService->service->duration_service,
                    'image_service' => $orderData->branchServiceProfessional->branchService->service->image_service,
                    'service_comment' => $orderData->branchServiceProfessional->branchService->service->service_comment,
                    'is_product' => $orderData->is_product,
                    'price' => $orderData->price,
                    'request_delete' => $orderData->request_delete
                    ];
                });*/
            return response()->json(['productscar' => $products, 'servicescar' => $services], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar ls ordenes"], 500);
        }
    }

    public function professional_car(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $retention = Professional::where('id', $data['professional_id'])->first()->retention;
            $cars = Car::with(['reservation', 'orders'])
                ->whereHas('reservation', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id']);
                })
                ->whereHas('clientProfessional', function ($query) use ($data) {
                    $query->where('professional_id', $data['professional_id']);
                })
                ->where('pay', 1)
                ->get()
                ->map(function ($car) use ($retention, $data) {
                    $reservation = $car->reservation;
                    $orderServ = $car->orders->where('is_product', 0);
                    $orderProd = $car->orders->where('is_product', 1);
                    $aleatorio = !$car->select_professional ? 1 : 0;
                    $amountGenerate = $orderServ->sum('percent_win');
                    $retent = $retention ? $amountGenerate * $retention /100 : 0;
                    $tips = $car->tip ? $car->tip : 0;
                    $tipspercent = $tips*0.80;
                    return [
                        'professional_id' => $data['professional_id'],
                        'branch_id' => $data['branch_id'],
                        'data' => $reservation->data,
                        'day_of_week' => ucfirst(mb_strtolower(Carbon::parse($reservation->data)->locale('es_ES')->isoFormat('dddd'))), // Obtener el día de la semana en español y en mayúscula
                        'attendedClient' => 1,
                        'services' => $orderServ->count(),
                        'totalServices' => $orderServ->sum('price'),
                        'tips' => $tips,
                        'tipspercent' => $tipspercent,
                        'totalGeneral' => $car->amount,
                        'totalProducts' => $orderProd->sum('price'),
                        'clientAleator' => $aleatorio,
                        'amountGenerate' => $amountGenerate, //ganancia total del barbero ganancias servicios
                        'retention' => $retent,
                        'winPay' => $amountGenerate - $retent + $tipspercent,
                        //'metaamount' => $meta->sum('amount')

                    ];
                })->groupBy('data')->map(function ($cars) use ($data) {
                    $meta = ProfessionalPayment::where('professional_id', $data['professional_id'])
                        ->whereDate('date', $cars[0]['data'])
                        ->where('branch_id', $data['branch_id'])
                        ->where(function ($query) {
                            $query->where('type', 'Bono convivencias')
                                ->orwhere('type', 'Bono productos')
                                ->orwhere('type', 'Bono servicios');
                        })
                        ->get();
                    return [

                        'professional_id' => intval($cars[0]['professional_id']),
                        'branch_id' =>  intval($cars[0]['branch_id']),
                        'data' => $cars[0]['data'],
                        'day_of_week' => $cars[0]['day_of_week'], // Mantener el día de la semana
                        'attendedClient' => $cars->sum('attendedClient'),
                        'services' => $cars->sum('services'),
                        'totalGeneral' => intval($cars->sum('totalGeneral')),
                        'totalServices' => $cars->sum('totalServices'),
                        'totalProducts' => $cars->sum('totalProducts'),
                        'tips' => intval($cars->sum('tips')),
                        'tips80' => intval($cars->sum('tipspercent')),
                        'clientAleator' => $cars->sum('clientAleator'),
                        'amountGenerate' => intval($cars->sum('amountGenerate')),
                        'totalRetention' => intval($cars->sum('retention')),
                        'metacant' => $meta->count() ? $meta->count() : 0,
                        'metaamount' => $meta->sum('amount') ? $meta->sum('amount') : 0,
                        'winPay' => intval($cars->sum('winPay'))
                    ];
                })->sortByDesc('data')->values();

            //Log::info($cars->pluck('id'));
            return response()->json(['car' => $cars], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error interno del sistema"], 500);
        }
    }

    public function tecnico_car(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $cars = Car::with('reservation')
                ->where('pay', 1)
                ->where('tecnico_id', $data['professional_id'])
                ->whereHas('reservation', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id']);
                })
                ->get();

            $carsData = $cars->map(function ($car) use ($data) {
                $reservation = $car->reservation;
                return [
                    'professional_id' =>intval($car->tecnico_id),
                    'branch_id' => intval($data['branch_id']),
                    'data' => $reservation->data,
                    'day_of_week' => ucfirst(mb_strtolower(Carbon::parse($reservation->data)->locale('es_ES')->isoFormat('dddd'))),
                    'attendedClient' =>intval($car->technical_assistance),
                    'amountGenerate' =>intval($car->technical_assistance * 5000)
                ];
            });

            $groupedCars = $carsData->groupBy('data')->map(function ($cars) {
                return [
                    'professional_id' => intval($cars[0]['professional_id']),
                    'branch_id' => intval($cars[0]['branch_id']),
                    'data' => $cars[0]['data'],
                    'day_of_week' => $cars[0]['day_of_week'],
                    'attendedClient' => intval($cars->sum('attendedClient')),
                    'amountGenerate' => intval($cars->sum('amountGenerate'))
                ];
            })->values();

            return response()->json(['car' => $groupedCars], 200);
            /*//$retention = number_format(Professional::where('id', $data['professional_id'])->first()->retention/100, 2);
           $cars = Car::whereHas('reservation', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
           })->where('pay', 1)->where('tecnico_id', $data['professional_id'])->get()->map(function($car) use ($data){
                //$ordersServices = count($car->orders->where('is_product', 0));
                return [
                    'professional_id' => $data['professional_id'],
                    'branch_id' => $car->reservation->branch_id,
                    'data' => $car->reservation->data,
                    'day_of_week' => ucfirst(mb_strtolower(Carbon::parse($car->reservation->data)->locale('es_ES')->isoFormat('dddd'))), // Obtener el día de la semana en español y en mayúscula
                    'attendedClient' => $car->technical_assistance,
                    //'technical_assistance' => $car->technical_assistance,
                    //'services' => $ordersServices,
                    //'totalServices' => $car->orders->sum('percent_win') + $car->tip * 0.80,
                    //'totalServicesRetention' => $retention ? ($car->orders->sum('percent_win') + $car->tip * 0.80) * $retention : 0,
                    //'clientAleator' => $car->select_professional,
                    'amountGenerate' => $car->technical_assistance * 5000
                ];
           })->groupBy('data')->map(function ($cars) {
            return [
                'professional_id' => $cars[0]['professional_id'],
                'branch_id' => $cars[0]['branch_id'],
                'data' => $cars[0]['data'],
                'day_of_week' => $cars[0]['day_of_week'], // Mantener el día de la semana
                'attendedClient' => $cars->sum('attendedClient'),
                //'services' => $cars->sum('services'),
                //'totalServices' => $cars->sum('totalServices'),
                //'totalServicesRetention' => $cars->sum('totalServicesRetention'),
                //'clientAleator' => $cars[0]['clientAleator'],
                'amountGenerate' => $cars->sum('amountGenerate')
            ];
        })->values();
           return response()->json(['car' => $cars], 200);*/
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar ls ordenes"], 500);
        }
    }

    public function professional_car_notpay(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $retention = Professional::where('id', $data['professional_id'])->value('retention');

            $cars = Car::where('professional_payment_id', null)
                ->whereHas('reservation', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id']);
                })
                ->with(['clientProfessional.client', 'reservation'])
                ->whereHas('clientProfessional', function ($query) use ($data) {
                    $query->where('professional_id', $data['professional_id']);
                })
                ->where('pay', 1)
                ->get()
                ->map(function ($car) use ($retention, $data) {
                    $orderServ = Order::where('car_id', $car->id)
                        ->where('is_product', 0)
                        ->get();

                    $client = $car->clientProfessional->client;
                    $retention = $retention ? ($orderServ->sum('percent_win') * $retention) / 100 : 0;
                    $amountServ = $orderServ->sum('percent_win');
                    return [
                        'id' => $car->id,
                        'professional_id' => $data['professional_id'],
                        'clientName' => $client->name . ' ' . $client->surname,
                        'client_image' => $client->client_image ? $client->client_image : 'comments/default.jpg',
                        'branch_id' => $data['branch_id'],
                        'data' => $car->reservation->data,
                        'attendedClient' => 1,
                        'services' => $orderServ->count(),
                        'totalServices' => intval($amountServ - $retention),
                        'clientAleator' => $car->select_professional,
                        'amountGenerate' => intval($car->amount),
                        'tip' => $car->tip * 0.80
                    ];
                });

            $cursesProf = CourseProfessional::where('professional_id', $data['professional_id'])->where('pay', 0)->get()->map(function ($courseProf) {
                $course = $courseProf->course;
                return [
                    'id' => $courseProf->id,
                    'enrollment_id' => $course->enrollment_id,
                    'nameEnrollment' => $course->enrollment->name,
                    'nameCourse' => $course->name,
                    'price' => $course->price,
                    'description' => $course->description,
                    'startDate' => $course->startDate,
                    'endDate' => $course->endDate,
                ];
            });
            /*$retention =  number_format(Professional::where('id', $data['professional_id'])->first()->retention/100, 2);
           $cars = Car::where('professional_payment_id', Null)->whereHas('reservation', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
           })->whereHas('clientProfessional', function ($query) use ($data){
            $query->where('professional_id', $data['professional_id']);
           })->where('pay', 1)->get()->map(function($car) use ($retention, $data){
                //$ordersServices = count($car->orders->where('is_product', 0));
                $orderServ = Order::where('car_id', $car->id)->where('is_product', 0)->get();
                $client = $car->clientProfessional->client;
                return [
                    'id' => $car->id,
                    'professional_id' => $data['professional_id'],
                    'clientName' => $client->name.' '.$client->surname,
                    'client_image' => $client->client_image ? $client->client_image : 'comments/default.jpg',
                    'branch_id' => $data['branch_id'],
                    'data' => $car->reservation->data,
                    'attendedClient' => 1,
                    'services' => $orderServ->count(),
                    'totalServices' => $retention ? $orderServ->sum('percent_win') - ($orderServ->sum('percent_win') * $retention) : $orderServ->sum('percent_win'),
                    'clientAleator' => $car->select_professional,
                    'amountGenerate' => $car->amount,
                    'tip' => $car->tip * 0.80
                ];
           });*/
            return response()->json(['cars' => $cars, 'courses' => $cursesProf], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar ls ordenes"], 500);
        }
    }

    public function professional_car_date(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
                'data' => 'required|date'
            ]);

            $meta = ProfessionalPayment::where('professional_id', $data['professional_id'])
                ->whereDate('date', $data['data'])
                ->where('branch_id', $data['branch_id'])
                ->where(function ($query) {
                    $query->where('type', 'Bono convivencias')
                        ->orwhere('type', 'Bono productos')
                        ->orwhere('type', 'Bono servicios');
                })
                ->get();
            $retention = Professional::where('id', $data['professional_id'])->first()->retention;
            $cars = Car::whereHas('reservation', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id'])->whereDate('data', $data['data']);
            })->whereHas('clientProfessional', function ($query) use ($data) {
                $query->where('professional_id', $data['professional_id']);
            })->where('pay', 1)->get()->map(function ($car) use ($retention, $meta) {
                $serviceNames = $car->orders->where('is_product', 0)->pluck('branchServiceProfessional.branchService.service.name')->values();
                //$ServicesSpecial = $car->orders->where('is_product', 0)->where('branchServiceProfessional.type_servie', 'Especial');
                $ServiceEspecial = Order::where('car_id', $car->id)->whereHas('branchServiceProfessional', function ($query) {
                    $query->where('type_service', 'Especial');
                })->get();
                $ServiceRegular = Order::where('car_id', $car->id)->whereHas('branchServiceProfessional', function ($query) {
                    $query->where('type_service', 'Regular');
                })->get();
                $reservation = Reservation::where('car_id', $car->id)->first();
                $orderServ = Order::where('car_id', $car->id)->where('is_product', 0)->get();
                $orderProd = Order::where('car_id', $car->id)->where('is_product', 1)->get();
                $client = $car->clientProfessional->client;

                /*$meta = $orderServ->filter(function ($order) {
                    return $order->percent_win == $order->price;
                });*/
                //$reservation = $car->reservation;
                //Log:info('$ServicesSpecial');
                $amountGenerate = $orderServ->sum('percent_win');
                    //$retent = $retention ? $amountGenerate * $retention /100 : 0;
                    $tips = $car->tip ? $car->tip : 0;
                    $tipspercent = $tips*0.80;
                $totalRetention = $retention ? $amountGenerate * $retention / 100 : 0;
                Log::info($ServiceEspecial);
                return [
                    'id' => $car->id,
                    'clientName' => $client->name . " " . $client->surname,
                    'client_image' => $client->client_image ? $client->client_image : 'comments/default.jpg',
                    'date' => $reservation->data . ' ' . $reservation->start_time,
                    'time' => $reservation->total_time,
                    'servicesRealizated' => implode(', ', $serviceNames->toArray()),
                    'tips' =>  (int)$tips,
                    'tips80' =>  $tipspercent,
                    'Services' => $orderServ->count(),
                    'totalServices' => $orderServ->sum('price'),
                    'Products' => $orderProd->sum('cant'),
                    'totalProducts' => $orderProd->sum('price'),
                    'choice' => $car->select_professional ? 'Seleccionado' : 'aleatorio',
                    'serviceSpecial' => $ServiceEspecial->count(),
                    'SpecialAmount' => $ServiceEspecial->sum('percent_win'),
                    'serviceRegular' => $ServiceRegular->count(),
                    'pay' => $car->professional_payment_id == null ? 0 : 1,
                    'totalRetention' => intval($totalRetention),
                    'totalGeneral' => $car->amount,
                    'amountGenerate' => $orderServ->sum('percent_win'),
                    'metaCant' => $meta->count(),
                    'metaAmount' => $meta->sum('amount'),
                    'winPay' => intval($amountGenerate - $totalRetention + $tipspercent),
                ];
            });
            return response()->json(['car' => $cars], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar ls ordenes"], 500);
        }
    }

    public function tecnico_car_date(Request $request)
    {
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
                'data' => 'required|date'
            ]);

            $cars = Car::with(['reservation', 'clientProfessional.client'])
                ->whereHas('reservation', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id'])
                        ->whereDate('data', $data['data']);
                })
                ->where('pay', 1)
                ->where('tecnico_id', $data['professional_id'])
                ->get()
                ->map(function ($car) {
                    $client = $car->clientProfessional->client;
                    return [
                        'id' => $car->id,
                        'clientName' => $client->name . " " . $client->surname,
                        'client_image' => $client->client_image ? $client->client_image : 'comments/default.jpg',
                        'date' => $car->reservation->data . ' ' . $car->reservation->start_time,
                        'amountTotal' => $car->technical_assistance * 5000,
                    ];
                });
            /*//$retention = Professional::where('id', $data['professional_id'])->first()->retention/100;
           $cars = Car::whereHas('reservation', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id'])->whereDate('data', $data['data']);
           })->get()->map(function($car){
            //$serviceNames = $car->orders->where('is_product', 0)->pluck('branchServiceProfessional.branchService.service.name')->values();
                /*$ServicesSpecial = Order::where('car_id', $car->id)->whereHas('branchServiceProfessional', function ($query){
                    $query->where('type_service','Especial');
                })->get();*/
            /*return [
                    'id' => $car->id,
                    'clientName' => $car->clientProfessional->client->name." ".$car->clientProfessional->client->surname,
                    'client_image' => $car->clientProfessional->client->client_image ? $car->clientProfessional->client->client_image : 'comments/default.jpg',                        
                    'data' => $car->reservation->data.' '.$car->reservation->start_time,
                    //'time' => $car->reservation->total_time,
                    ///'servicesRealizated' => implode(', ', $serviceNames->toArray()),
                    'amountTotal' => $car->technical_assistance * 5000,
                    //'amountWin' =>$retention ? $car->orders->sum('percent_win') - ($car->orders->sum('percent_win') * $retention/100) : $car->orders->sum('percent_win') + $car->tip * 0.80,
                    //'choice' => $car->select_professional ? 'Seleccionado' : 'aleatorio',
                    //'serviceSpecial' => $ServicesSpecial->count(),
                    //'SpecialAmount' => $ServicesSpecial->sum('percent_win')
                    /*'attendedClient' => 1,
                    'services' => $ordersServices,
                    'totalServices' => $car->orders->sum('percent_win'),
                    'clientAleator' => $car->select_professional,
                    'amount' => $car->amount + $car->tip*/
            /*];
           });*/
            return response()->json(['car' => $cars], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar ls ordenes"], 500);
        }
    }

    public function car_order_delete_branch(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);

            /*$orderDatas = Order::whereHas('car.reservation', function ($query) use ($data){
                    $query->where('branch_id', $data['branch_id']);
                })->where('request_delete', true)->whereDate('data', Carbon::now()->toDateString())->orderBy('updated_at', 'desc')->get();*/
            $orderDatas = Order::with(['car.reservation', 'car.clientProfessional.professional', 'car.clientProfessional.client', 'productStore.product', 'branchServiceProfessional.branchService.service'])
                ->whereHas('car.reservation', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id']);
                })
                ->where('request_delete', true)
                ->whereDate('data', Carbon::now()->toDateString())
                ->orderBy('updated_at', 'desc')
                ->get();
            $car = $orderDatas->map(function ($orderData) {
                $car = $orderData->car;
                $clientProfessional = $car->clientProfessional;

                $profesionalName = $clientProfessional->professional->name . ' ' . $clientProfessional->professional->surname . ' ' . $clientProfessional->client->second_surname;
                $clientName = $clientProfessional->client->name . ' ' . $clientProfessional->client->surname . ' ' . $clientProfessional->client->second_surname;

                $hora = $orderData->updated_at;
                if ($orderData->is_product == true) {
                    return [
                        'id' => $orderData->id,
                        'profesional_id' => $clientProfessional->professional_id,
                        'reservation_id' => $car->reservation->id,
                        'nameProfesional' => $profesionalName,
                        'nameClient' => $clientName,
                        'hora' => $hora->format('g:i A'),
                        'nameProduct' => $orderData->productStore->product->name,
                        'nameService' => null,
                        'duration_service' => null,
                        'is_product' => (int)$orderData->is_product,
                        'updated_at' => $hora->toDateString()
                    ];
                } else {
                    $branchServiceProfessional = $orderData->branchServiceProfessional;
                    $branchService = $branchServiceProfessional->branchService;
                    $service = $branchService->service;
                    return [
                        'id' => $orderData->id,
                        'profesional_id' => $clientProfessional->professional_id,
                        'reservation_id' => $car->reservation->id,
                        'nameProfesional' => $profesionalName,
                        'nameClient' => $clientName,
                        'hora' => $hora->Format('g:i A'),
                        'nameProduct' => null,
                        'nameService' => $service->name,
                        'duration_service' => $service->duration_service,
                        'is_product' => (int)$orderData->is_product,
                        'updated_at' => $hora->toDateString()
                    ];
                }
            });

            return response()->json(['carOrderDelete' => $car], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar las ordenes"], 500);
        }
    }

    public function car_order_delete_professional(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $orderDatas = Order::whereHas('car.clientProfessional', function ($query) use ($data) {
                $query->where('professional_id', $data['professional_id']);
            })->whereHas('car.reservation', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })->where('request_delete', true)->whereDate('data', Carbon::now()->toDateString())->orderBy('updated_at', 'desc')->get();

            $car = $orderDatas->map(function ($orderData) {
                $professional = $orderData->car->clientProfessional->professional;
                $client = $orderData->car->clientProfessional->client;
                if ($orderData->is_product == true) {
                    return [
                        'id' => $orderData->id,
                        'nameProfesional' => $professional->name . ' ' . $professional->surname,
                        'nameClient' => $client->name . ' ' . $client->surname,
                        'hora' => $orderData->updated_at->Format('g:i A'),
                        'nameProduct' => $orderData->productStore->product->name,
                        'nameService' => null,
                        'is_product' => $orderData->is_product,
                        'updated_at' => $orderData->updated_at->toDateString()
                    ];
                } else {
                    return [
                        'id' => $orderData->id,
                        'nameProfesional' => $professional->name . ' ' . $professional->surname,
                        'nameClient' => $client->name . ' ' . $client->surname,
                        'hora' => $orderData->updated_at->Format('g:i A'),
                        'nameProduct' => null,
                        'nameService' => $orderData->branchServiceProfessional->branchService->service->name,
                        'is_product' => (int)$orderData->is_product,
                        'updated_at' => $orderData->updated_at->toDateString()
                    ];
                }
            });

            return response()->json(['carOrderDelete' => $car], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar las ordenes"], 500);
        }
    }

    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $car = $this->carService->show($data['id']);
            //$car = Car::with('clientProfessional.client', 'clientProfessional.professional')->find($data['id']);
            //$car = Car::join('client_professional', 'client_professional.id', '=', 'cars.client_professional_id')->join('clients', 'clients.id', '=', 'client_professional.client_id')->join('professionals', 'professionals.id', '=', 'client_professional.professional_id')->where('cars.id', $data['id'])->get(['clients.name as client_name', 'clients.surname as client_surname', 'clients.second_surname as client_second_surname', 'clients.email as client_email', 'clients.phone as client_phone', 'professionals.*', 'cars.*']);
            return response()->json(['car' => $car], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el carrito"], 500);
        }
    }

    public function client_professional_reservation_show($data)
    {
        try {
            Log::info("Busca el carro si no existe lo crea");
            $car = new Car();
            $car->client_professional_id = $data['client_professional_id'];
            $car->amount = 0;
            $car->pay = $data['pay'];
            $car->active = $data['active'];
            $car->save();
            return $car->id;
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al asignar el empleado a este cliente'], 500);
        }
    }
    public function car_clientProfessionalDate(Request $request)
    {
        try {
            $data = $request->validate([
                'client_professional_id' => 'required|numeric',
                'data' => 'required|date'
            ]);
            $car = Car::where('client_professional_id', $data['client_professional_id'])->whereDate('created_at', Carbon::parse($data['data']))->find($data['id']);
            //$car = Car::join('client_professional', 'client_professional.id', '=', 'cars.client_professional_id')->join('clients', 'clients.id', '=', 'client_professional.client_id')->join('professionals', 'professionals.id', '=', 'client_professional.professional_id')->where('cars.id', $data['id'])->get(['clients.name as client_name', 'clients.surname as client_surname', 'clients.second_surname as client_second_surname', 'clients.email as client_email', 'clients.phone as client_phone', 'professionals.*', 'cars.*']);
            return response()->json(['car' => $car], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el carrito"], 500);
        }
    }

    public function reservation_services(Request $request)
    {
        try {
            Log::info("Entra a buscar las reservaciones y los servicios de un cliente con un profesional");
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'client_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);

            $client_professional_id = ClientProfessional::where('professional_id', $data['professional_id'])->where('client_id', $data['client_id'])->value('id');
            $orderServicesDatas = Order::whereHas('car.reservation')->whereRelation('car', 'client_professional_id', '=', $client_professional_id)->where('is_product', false)->orderBy('updated_at', 'desc')->get();
            $services = $orderServicesDatas->map(function ($orderData) {
                $service = $orderData->branchServiceProfessional->branchService->service;
                return [
                    'data_reservation' => $orderData->car->reservations->data,
                    'nameService' => $service->name,
                    'simultaneou' => $service->simultaneou,
                    'price_service' => $service->price_service,
                    'type_service' => $service->type_service,
                    'profit_percentaje' => $service->profit_percentaje,
                    'duration_service' => $service->duration_service,
                    'image_service' => $service->image_service
                ];
            });

            return response()->json(['services' => $services], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las reservaciones"], 500);
        }
    }
    public function car_services(Request $request)
    {
        try {
            Log::info("Entra a buscar las reservaciones y los servicios de un cliente con un profesional");
            $data = $request->validate([
                'car_id' => 'required|numeric'
            ]);

            $orderServicesDatas = Order::whereHas('car.reservation')->whereRelation('car', 'id', '=', $data['car_id'])->where('is_product', 0)->get();
            $services = $orderServicesDatas->map(function ($orderData) {
                $service = $orderData->branchServiceProfessional->branchService->service;
                return [
                    'name' => $service->name,
                    'simultaneou' => $service->simultaneou,
                    'price_service' => $service->price_service,
                    'type_service' => $service->type_service,
                    'profit_percentaje' => $service->profit_percentaje,
                    'duration_service' => $service->duration_service,
                    'image_service' => $service->image_service,
                    'description' => $service->service_comment
                ];
            });

            return response()->json(['services' => $services], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las reservaciones"], 500);
        }
    }

    public function car_services2(Request $request)
    {
        try {
            Log::info("Entra a buscar las reservaciones y los servicios de un cliente con un profesional");
            $data = $request->validate([
                'car_id' => 'required|numeric'
            ]);
            $car = Car::where('id', $data['car_id'])->first();
            $client = $car->clientProfessional->client;
            //historial
            $result = [];
            $fiel = null;
            $frecuencia = null;
            $reservations = Reservation::whereHas('car', function ($query) use ($client) {
                $query->where('pay', 1)->whereHas('clientProfessional', function ($query) use ($client) {
                    $query->where('client_id', $client->id);
                });
            })->orderByDesc('data')->limit(12)->get();
            if ($reservations->isEmpty()) {
                $result[] = [
                    'clientName' => $client->name . " " . $client->surname,
                    'professionalName' => "Ninguno",
                    'imageLook' => 'comments/default_profile.jpg',
                    'image_url' => '',
                    'cantVisit' => 0,
                    'endLook' => 'No hay comentarios',
                    'frecuencia' => "No Frecuente"
                ];
            } else {
                $countReservations = $reservations->count();
                if ($countReservations >= 12) {
                    $currentYear = Carbon::now()->year;

                    $fiel = $reservations->filter(function ($reservation) use ($currentYear) {
                        return Carbon::parse($reservation->data)->year == $currentYear;
                    })->count();
                    if ($fiel >= 12) {
                        $frecuencia = "Fiel";
                    }
                } elseif ($countReservations >= 3) {
                    $frecuencia = "Frecuente";
                } else {
                    $frecuencia = "No Frecuente";
                }

                $comment = Comment::whereHas('clientProfessional', function ($query) use ($client) {
                    $query->where('client_id', $client->id);
                })->orderByDesc('data')->orderByDesc('updated_at')->first();

                $reservation = $reservations->first();
                $professional = $reservation->car->clientProfessional->professional;
                $result[] = [
                    'clientName' => $client->name . " " . $client->surname,
                    'professionalName' => $professional->name . ' ' . $professional->surname,
                    'image_url' => $professional->image_url ? $professional->image_url : 'professionals/default_profile.jpg',
                    'imageLook' => $comment ? ($comment->client_look ? $comment->client_look : 'comments/default_profile.jpg') : 'comments/default_profile.jpg',
                    'cantVisit' => $reservations->count(),
                    'endLook' => $comment ? $comment->look : null,
                    'frecuencia' => $frecuencia,
                ];
            }
            //endhistoria
            $orderServicesDatas = Order::whereHas('car.reservation')->whereRelation('car', 'id', '=', $data['car_id'])->where('is_product', false)->get();
            $services = $orderServicesDatas->map(function ($orderData) {
                $service = $orderData->branchServiceProfessional->branchService->service;
                return [
                    'name' => $service->name,
                    'simultaneou' => $service->simultaneou,
                    'price_service' => $service->price_service,
                    'type_service' => $service->type_service,
                    'profit_percentaje' => $service->profit_percentaje,
                    'duration_service' => $service->duration_service,
                    'image_service' => $service->image_service ? $service->image_service : 'services/default.jpg',
                    'description' => $service->service_comment
                ];
            });

            return response()->json(['services' => $services, 'clientHistory' => $result], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las reservaciones"], 500);
        }
    }


    public function update(Request $request)
    {
        try {

            Log::info("Editar");
            Log::info($request);
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);

            $car = Car::find($data['id']);
            $car->pay = true;
            $car->save();
            return response()->json(['msg' => 'Carro actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al actualizar el carro'], 500);
        }
    }
    /*public function car_amount_updated($data)
    {
        try {

            Log::info("Editar");
            $car = Car::find($data['id']);
            $car->amount = $car->amount + $data['amount'];
            $car->save();
            return response()->json(['msg' => 'Carro actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => 'Error al actualizar el carro'], 500);
        }
    }*/
    public function give_tips(Request $request)
    {
        try {

            Log::info("Editar");
            Log::info($request);
            $data = $request->validate([
                'id' => 'required|numeric',
                'tip' => 'required'
            ]);

            $car = Car::find($data['id']);
            $car->tip = $data['tip'];
            $car->save();
            return response()->json(['msg' => 'Se le ha dado propina para el profesional correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al dar propina para el profesional'], 500);
        }
    }

    public function destroy(Request $request)
    {
        Log::info("Eliminar");
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'professional_id' => 'nullable'
            ]);
            $car = Car::find($data['id']);
            $branch = Branch::where('id', $car->reservation->branch_id)->first();
            $orders = Order::where('car_id', $data['id'])->where('is_product', 1)->select('product_store_id', 'cant')->get();
            if (!$orders->isEmpty()) {
                foreach ($orders as $order) {
                    $productstore = ProductStore::find($order->product_store_id);
                    $productstore->product_quantity = $order->cant;
                    $productstore->product_exit = $productstore->product_exit + $order->cant;
                    $productstore->save();
                }
            }
            //$cajeros = BranchProfessional::where('branch_id', $branch->id)->whereHas('professional.charge', function ($query){
            //$query->where('name', 'Cajero (a)');
            //})->get('professional_id');
            //if(!$cajeros->isEmpty()){
            //foreach ($cajeros as $cajero) {                    
            $notification = new Notification();
            $notification->professional_id = $data['professional_id'];
            $notification->tittle = 'Aceptada';
            $notification->description = 'Carro: ' . $car->id . ' eliminado';
            $notification->type = 'Caja';
            $branch->notifications()->save($notification);
            //}
            //}
            $car->delete();
            //$car->delete();
            return response()->json(['msg' => 'Carro eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al eliminar el carro'], 500);
        }
    }

    public function destroy_denegada(Request $request)
    {
        Log::info("Eliminar");
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'professional_id' => 'nullable'
            ]);
            $car = Car::find($data['id']);
            $branch = Branch::where('id', $car->reservation->branch_id)->first();
            $car->active = 1;
            $car->save();
            /*$cajeros = BranchProfessional::where('branch_id', $branch->id)->whereHas('professional.charge', function ($query){
            $query->where('name', 'Cajero (a)');
        })->get('professional_id');
            if(!$cajeros->isEmpty()){
                foreach ($cajeros as $cajero) {                    
                $notification = new Notification();
                $notification->professional_id = $cajero->professional_id;
                $notification->tittle = 'Denegada';
                $notification->description = 'Solicitud de eliminación del carro: '.$car->id.' denegada';
                $notification->type = 'Cajero';
                $branch->notifications()->save($notification);
                }
            }*/
            $notification = new Notification();
            $notification->professional_id = $data['professional_id'];
            $notification->tittle = 'Denegada';
            $notification->description = 'Carro : ' . $car->id . ' denegado a eliminar';
            $notification->type = 'Caja';
            $branch->notifications()->save($notification);
            //$car->delete();
            return response()->json(['msg' => 'Solicitud denegada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al eliminar el carro'], 500);
        }
    }

    public function destroy_solicitud(Request $request)
    {
        Log::info("Eliminar");
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'professional_id' => 'nullable'
            ]);
            $car = Car::find($data['id']);
            $branch = Branch::where('id', $request->branch_id)->first();

            $client = $car->clientProfessional->client;
            $professional = $car->clientProfessional->professional;
            $trace = [
                'branch' => $branch->name,
                'cashier' => $request->nameProfessional,
                'client' => $client->name . ' ' . $client->surname . ' ' . $client->second_surname,
                'amount' => $car->amount,
                'operation' => 'Hace solicitud de eliminar carro: ' . $car->id,
                'details' => '',
                'description' => $professional->name . ' ' . $professional->surname . ' ' . $professional->second_surname,
            ];
            $this->traceService->store($trace);
            $car->active = 3;
            $car->save();
            /* $administradores = BranchProfessional::where('branch_id', $branch->id)->whereHas('professional.charge', function ($query){
            $query->where('name', 'Administrador de Sucursal');
        })->get('professional_id');
            if(!$administradores->isEmpty()){
                foreach ($administradores as $administrador) {  */
            $notification = new Notification();
            $notification->professional_id = $data['professional_id'];
            $notification->tittle = 'Solicitud';
            $notification->description = 'Solicitud de eliminación del carro: ' . $car->id;
            $notification->type = 'Administrador';
            $branch->notifications()->save($notification);
            //}
            //}
            //$car->delete();
            return response()->json(['msg' => 'Carro eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al eliminar el carro'], 500);
        }
    }
}
