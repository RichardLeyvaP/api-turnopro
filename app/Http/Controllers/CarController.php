<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Branch;
use App\Models\BranchProfessional;
use App\Models\Business;
use App\Models\Car;
use App\Models\CashierBoxClosing;
use App\Models\CashierSale;
use App\Models\ClientProfessional;
use App\Models\Comment;
use App\Models\CourseProfessional;
use App\Models\CourseStudent;
use App\Models\Finance;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStore;
use App\Models\Professional;
use App\Models\ProfessionalPayment;
use App\Models\Payment;
use App\Models\ProductSale;
use App\Models\Reservation;
use App\Models\Retention;
use App\Models\Service;
use App\Models\WorkerPurchase;
use App\Services\CarService;
use App\Services\ProfessionalPaymentService;
use App\Services\TraceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CarController extends Controller
{
    private CarService $carService;
    private TraceService $traceService;
    private ProfessionalPaymentService $professionalPaymentService;

    public function __construct(CarService $carService, TraceService $traceService, ProfessionalPaymentService $professionalPaymentService)
    {
        $this->carService = $carService;
        $this->traceService = $traceService;
        $this->professionalPaymentService = $professionalPaymentService;
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

    public function car_products_services_ANTERIOR(Request $request)
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

                $products = Product::with([
                    'orders' => function ($query) use ($data) {
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

                $productsAnt = Product::with([
                    'orders' => function ($query) use ($data, $fechaFormateada) {
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
                $products = Product::with([
                    'orders' => function ($query) use ($data) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(price) as total_price')
                            ->groupBy('product_id')
                            ->whereDate('data', Carbon::now())
                            ->whereHas('car.reservation', function ($query) use ($data) {
                                $query->whereDate('data', Carbon::now())/*->where('branch_id', $data['branch_id'])*/;
                            })
                            ->where('is_product', 1);
                    },
                    'cashiersales' => function ($query) {
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

                $productsAnt = Product::with([
                    'orders' => function ($query) use ($fechaFormateada) {
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

                $ordersAct = Order::whereHas('car', function ($query) use ($data) {  // Cambiado a car directamente
                    $query->whereHas('reservation', function ($query) use ($data) {
                        $query->whereDate('data', Carbon::now())
                            ->where('branch_id', $data['branch_id']);
                    })
                        ->where('pay', 1);  // Condición para pay en cars
                })->get();
                $ordersAnt = Order::whereHas('car', function ($query) use ($data, $fechaFormateada) {  // Cambiado a car directamente
                    $query->whereHas('reservation', function ($query) use ($data, $fechaFormateada) {
                        $query->whereDate('data', $fechaFormateada)
                            ->where('branch_id', $data['branch_id']);
                    })
                        ->where('pay', 1);  // Condición para pay en cars
                })->get();

                $products = Product::with([
                    'orders' => function ($query) use ($data) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(price) as total_price')
                            ->groupBy('product_id')
                            ->whereDate('data', Carbon::now())
                            ->whereHas('car', function ($query) use ($data) {
                                $query->whereHas('reservation', function ($query) use ($data) {
                                    $query->whereDate('data', Carbon::now())
                                        ->where('branch_id', $data['branch_id']);
                                })
                                ->where('pay', 1);
                            })
                            ->where('is_product', 1);
                    },
                    'cashiersales' => function ($query) use ($data) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(price) as total_price, product_id')
                            ->groupBy('product_id')
                            ->where('cashiersales.branch_id', $data['branch_id'])
                            ->whereDate('data', Carbon::now())
                            ->where('pay', 1);
                    },
                    'workerPurchases' => function ($query) use ($data) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(total) as total_price, product_id')
                            ->groupBy('product_id')
                            ->where('branch_id', $data['branch_id'])
                            ->whereDate('data', Carbon::now())
                            ->where('status', 1); // Solo compras aprobadas (status = 1)
                    }
                ])->get()->filter(function ($product) {
                    return !$product->orders->isEmpty() || !$product->cashiersales->isEmpty() || !$product->workerPurchases->isEmpty();
                })->values()->sortByDesc(function ($product) {
                    return $product->orders->sum('total_cant') + $product->cashiersales->sum('total_cant') + $product->workerPurchases->sum('total_cant');
                })->map(function ($product) {
                    return [
                        'name' => $product->name,
                        'total_cant' => $product->orders->sum('total_cant') + $product->cashiersales->sum('total_cant') + $product->workerPurchases->sum('total_cant'),
                        'total_price' => $product->orders->sum('total_price') + $product->cashiersales->sum('total_price') + $product->workerPurchases->sum('total_price')
                    ];
                });
                
                $productsAnt = Product::with([
                    'orders' => function ($query) use ($data, $fechaFormateada) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(price) as total_price')
                            ->groupBy('product_id')
                            ->whereDate('data', $fechaFormateada)
                            ->whereHas('car', function ($query) use ($data, $fechaFormateada) {
                                $query->whereHas('reservation', function ($query) use ($data, $fechaFormateada) {
                                    $query->whereDate('data', $fechaFormateada)
                                        ->where('branch_id', $data['branch_id']);
                                })
                                ->where('pay', 1);
                            })
                            ->where('is_product', 1);
                    },
                    'cashiersales' => function ($query) use ($data, $fechaFormateada) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(price) as total_price, product_id')
                            ->groupBy('product_id')
                            ->where('cashiersales.branch_id', $data['branch_id'])
                            ->whereDate('data', $fechaFormateada)
                            ->where('pay', 1);
                    },
                    'workerPurchases' => function ($query) use ($data, $fechaFormateada) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(total) as total_price, product_id')
                            ->groupBy('product_id')
                            ->where('branch_id', $data['branch_id'])
                            ->whereDate('data', $fechaFormateada)
                            ->where('status', 1); // Solo compras aprobadas (status = 1)
                    }
                ])->get()->filter(function ($product) {
                    return !$product->orders->isEmpty() || !$product->cashiersales->isEmpty() || !$product->workerPurchases->isEmpty();
                })->values()->sortByDesc(function ($product) {
                    return $product->orders->sum('total_cant') + $product->cashiersales->sum('total_cant') + $product->workerPurchases->sum('total_cant');
                })->map(function ($product) {
                    return [
                        'name' => $product->name,
                        'total_cant' => $product->orders->sum('total_cant') + $product->cashiersales->sum('total_cant') + $product->workerPurchases->sum('total_cant'),
                        'total_price' => $product->orders->sum('total_price') + $product->cashiersales->sum('total_price') + $product->workerPurchases->sum('total_price')
                    ];
                });
                
                $resultPproduct[] = [
                    'cant' => $products->sum('total_price'),
                    'products' => $products,
                    'cantAnt' => $productsAnt->sum('total_price'),
                    'productsAnt' => $productsAnt,
                ];
                //Servicios
                $services = Service::has('orders')
                    ->withCount(['orders' => function ($query) use ($data) {
                        $query->whereHas('car', function ($query) use ($data) {  // Cambiado a car directamente
                            $query->whereHas('reservation', function ($query) use ($data) {
                                $query->whereDate('data', Carbon::now())
                                    ->where('branch_id', $data['branch_id']);
                            })
                                ->where('pay', 1);  // Condición para pay en cars
                        })->where('is_product', 0);
                    }])->orderByDesc('orders_count')->get();
                //$mostSoldService = $services->first();

                $servicesAnt = Service::has('orders')
                    ->withCount(['orders' => function ($query) use ($data, $fechaFormateada) {
                        $query->whereHas('car', function ($query) use ($data, $fechaFormateada) {  // Cambiado a car directamente
                            $query->whereHas('reservation', function ($query) use ($data, $fechaFormateada) {
                                $query->whereDate('data', $fechaFormateada)
                                    ->where('branch_id', $data['branch_id']);
                            })
                                ->where('pay', 1);  // Condición para pay en cars
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
                $ordersAct = Order::whereHas('car', function ($query) {  // Cambiado a car directamente
                    $query->whereHas('reservation', function ($query) {
                        $query->whereDate('data', Carbon::now());
                    })
                        ->where('pay', 1);  // Condición para pay en cars
                })->get();
                $ordersAnt = Order::whereHas('car', function ($query) use ($fechaFormateada) {  // Cambiado a car directamente
                    $query->whereHas('reservation', function ($query) use ($fechaFormateada) {
                        $query->whereDate('data', $fechaFormateada);
                    })
                        ->where('pay', 1);  // Condición para pay en cars
                })->get();
                $products = Product::with([
                    'orders' => function ($query) use ($data) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(price) as total_price')
                            ->groupBy('product_id')
                            ->whereDate('data', Carbon::now())
                            ->whereHas('car', function ($query) use ($data) {  // Cambiado a car directamente
                                $query->whereHas('reservation', function ($query) use ($data) {
                                    $query->whereDate('data', Carbon::now());
                                })
                                    ->where('pay', 1);  // Condición para pay en cars
                            })
                            ->where('is_product', 1);
                    },
                    'cashiersales' => function ($query) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(price) as total_price, product_id')
                            ->groupBy('product_id')
                            ->whereDate('data', Carbon::now())
                            ->where('pay', 1);
                    },
                    'workerPurchases' => function ($query) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(total) as total_price, product_id')
                            ->groupBy('product_id')
                            ->whereDate('data', Carbon::now())
                            ->where('status', 1); // Solo compras aprobadas (status = 1)
                    }
                ])->get()->filter(function ($product) {
                    return !$product->orders->isEmpty() || !$product->cashiersales->isEmpty() || !$product->workerPurchases->isEmpty();
                })->values()->sortByDesc(function ($product) {
                    return $product->orders->sum('total_cant') + $product->cashiersales->sum('total_cant') + $product->workerPurchases->sum('total_cant');
                })->map(function ($product) {
                    return [
                        'name' => $product->name,
                        'total_cant' => $product->orders->sum('total_cant') + $product->cashiersales->sum('total_cant') + $product->workerPurchases->sum('total_cant'),
                        'total_price' => $product->orders->sum('total_price') + $product->cashiersales->sum('total_price') + $product->workerPurchases->sum('total_price'),
                    ];
                });

                $productsAnt = Product::with([
                    'orders' => function ($query) use ($fechaFormateada) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(price) as total_price')
                            ->groupBy('product_id')
                            ->whereDate('data', $fechaFormateada)
                            ->whereHas('car', function ($query) use ($fechaFormateada) {  // Cambiado a car directamente
                                $query->whereHas('reservation', function ($query) use ($fechaFormateada) {
                                    $query->whereDate('data', $fechaFormateada);
                                })
                                    ->where('pay', 1);  // Condición para pay en cars
                            })
                            ->where('is_product', 1);
                    },
                    'cashiersales' => function ($query) use ($fechaFormateada) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(price) as total_price, product_id')
                            ->groupBy('product_id')
                            ->whereDate('data', $fechaFormateada)
                            ->where('pay', 1);
                    },
                    'workerPurchases' => function ($query) use ($fechaFormateada) {
                        $query->selectRaw('SUM(cant) as total_cant, SUM(total) as total_price, product_id')
                            ->groupBy('product_id')
                            ->whereDate('data', $fechaFormateada)
                            ->where('status', 1); // Solo compras aprobadas (status = 1)
                    }
                ])->get()->filter(function ($product) {
                    return !$product->orders->isEmpty() || !$product->cashiersales->isEmpty() || !$product->workerPurchases->isEmpty();
                })->values()->sortByDesc(function ($product) {
                    return $product->orders->sum('total_cant') + $product->cashiersales->sum('total_cant') + $product->workerPurchases->sum('total_cant');
                })->map(function ($product) {
                    return [
                        'name' => $product->name,
                        'total_cant' => $product->orders->sum('total_cant') + $product->cashiersales->sum('total_cant') + $product->workerPurchases->sum('total_cant'),
                        'total_price' => $product->orders->sum('total_price') + $product->cashiersales->sum('total_price') + $product->workerPurchases->sum('total_price'),
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
                    ->withCount(['orders' => function ($query) {
                        $query->whereHas('car', function ($query) {  // Cambiado a car directamente
                            $query->whereHas('reservation', function ($query) {
                                $query->whereDate('data', Carbon::now());
                            })
                                ->where('pay', 1);  // Condición para pay en cars
                        })
                            ->where('is_product', 0);
                    }])->orderByDesc('orders_count')->get();
                //$mostSoldService = $services->first();

                $servicesAnt = Service::has('orders')
                    ->withCount(['orders' => function ($query) use ($fechaFormateada) {
                        $query->whereHas('car', function ($query) use ($fechaFormateada) {  // Cambiado a car directamente
                            $query->whereHas('reservation', function ($query) use ($fechaFormateada) {
                                $query->whereDate('data', $fechaFormateada);
                            })
                                ->where('pay', 1);  // Condición para pay en cars
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
                })->where('pay', 1)->get()->map(function ($car) {
                    return [
                        'date' => $car->reservations->data,
                        'earnings' => $car->amount + ($car->technical_assistance * 5000) + $car->tip
                    ];
                });
                //Log::info('Resultados de los carros');
                //Log::info($cars);
                $sales = CashierSale::where('branch_id', $data['branch_id'])
                    ->whereDate('data', '>=', $start)
                    ->whereDate('data', '<=', $end)
                    ->where('pay', 1)
                    ->get()
                    ->map(function ($sale) {
                        return [
                            'date' => $sale->data,
                            'earnings' => $sale->price
                        ];
                    });
                    // Agregar worker purchases
                    $workerPurchases = WorkerPurchase::where('branch_id', $data['branch_id'])
                    ->whereDate('data', '>=', $start)
                    ->whereDate('data', '<=', $end)
                    ->where('status', 1) // Solo compras aprobadas
                    ->get()
                    ->map(function ($purchase) {
                        return [
                            'date' => $purchase->data,
                            'earnings' => $purchase->total,
                            'type' => 'worker_purchase' // Agregado tipo para identificar la fuente
                        ];
                    });

                    // Combinar los resultados de las reservas, ventas en efectivo y compras de trabajadores
                    $combinedEarnings = $cars->concat($sales)->concat($workerPurchases);
                //Log::info('Resultados de los carros y la venta de productos');
                //Log::info($combinedEarnings);
                // Inicializar el array de resultados
                $dates = [];

                for ($date = $start, $i = 0; $date->lte($end); $date->addDay(), $i++) {
                    $matchingResult = $combinedEarnings->where('date', $date->toDateString())->sum('earnings');
                    $dates[$i] = $matchingResult ? $matchingResult : 0;
                }
                return $dates;
            } else {
                $cars = Car::whereHas('reservations', function ($query) use ($start, $end) {
                    $query->whereDate('data', '>=', $start)->whereDate('data', '<=', $end);
                })->where('pay', 1)->get()->map(function ($car) {
                    return [
                        'date' => $car->reservations->data,
                        'earnings' => $car->amount + ($car->technical_assistance * 5000) + $car->tip
                    ];
                });
                //Log::info('Resultados de los carros empresa');
                //Log::info($cars);
                $sales = CashierSale::whereDate('data', '>=', $start)
                    ->whereDate('data', '<=', $end)
                    ->where('pay', 1)
                    ->get()
                    ->map(function ($sale) {
                        return [
                            'date' => $sale->data,
                            'earnings' => $sale->price
                        ];
                    });
                $workerPurchases = WorkerPurchase::whereDate('data', '>=', $start)
                ->whereDate('data', '<=', $end)
                ->where('status', 1) // Solo compras aprobadas
                ->get()
                ->map(function ($purchase) {
                    return [
                        'date' => $purchase->data,
                        'earnings' => $purchase->total,
                        'type' => 'worker_purchase' // Agregado tipo para identificar la fuente
                    ];
                });

                // Combinar los resultados de las reservas, ventas en efectivo y compras de trabajadores
                $combinedEarnings = $cars->concat($sales)->concat($workerPurchases);

                //Log::info('Resultados de los carros y la venta de productos empresa');
                //Log::info($combinedEarnings);

                // Inicializar el array de resultados
                $dates = [];

                for ($date = $start, $i = 0; $date->lte($end); $date->addDay(), $i++) {
                    $matchingResult = $combinedEarnings->where('date', $date->toDateString())->sum('earnings');
                    $dates[$i] = $matchingResult ? $matchingResult : 0;
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
            $ingreso = 0;
            $gasto = 0;
            $ingresoA = 0;
            $gastoA = 0;
            $utilidadServices = 0;
            $utilidadServicesA = 0;
            // Obtener la fecha de finalización del mes anterior
            $final_mes_anterior = Carbon::now()->subMonth()->endOfMonth();
            if ($data['branch_id'] != 0) {
                Log::info("branch");
                $cars = Car::whereHas('reservation', function ($query) use ($data, $startOfMonth, $endOfMonth) {
                    $query->where('branch_id', $data['branch_id'])->whereDate('data', '>=', $startOfMonth)->whereDate('data', '<=', $endOfMonth);
                })->where('pay', 1);
                $carIds = $cars->pluck('id');

                ///Nuevo
                $products = Product::with([
                    'orders' => function ($query) use ($carIds) {
                        $query->selectRaw('product_id, SUM(cant) as total_cant, SUM(percent_win) as utilidadOrder, SUM(price) as total_price')
                            ->groupBy('product_id')
                            ->whereIn('car_id', $carIds)
                            ->where('is_product', 1);
                    },
                    'cashiersales' => function ($query) use ($data, $startOfMonth, $endOfMonth) {
                        $query->selectRaw('product_id, SUM(cant) as total_sales, SUM(percent_wint) as utilidadCash, SUM(price) as total_pricesales')
                            ->groupBy('product_id')
                            ->where('cashiersales.branch_id', $data['branch_id'])
                            ->whereDate('data', '>=', $startOfMonth)
                            ->whereDate('data', '<=', $endOfMonth);
                    },
                    'workerPurchases' => function ($query) use ($data, $startOfMonth, $endOfMonth) {
                        $query->selectRaw('product_id, SUM(cant) as total_worker, SUM(percent_wint) as utilidadWorker, SUM(total) as total_worker_price')
                            ->groupBy('product_id')
                            ->where('branch_id', $data['branch_id'])
                            ->whereDate('data', '>=', $startOfMonth)
                            ->whereDate('data', '<=', $endOfMonth)
                            ->where('status', 1); // Solo compras aprobadas
                    }
                ])->get()->filter(function ($product) {
                    return !$product->orders->isEmpty() || !$product->cashiersales->isEmpty() || !$product->workerPurchases->isEmpty();
                })->map(function ($product) {
                    $totalOrders = $product->orders->sum('total_cant');
                    $totalSales = $product->cashiersales->sum('total_sales');
                    $totalWorker = $product->workerPurchases->sum('total_worker');
                    
                    $utilidadOrders = $product->orders->sum('utilidadOrder');
                    $utilidadSales = $product->cashiersales->sum('utilidadCash');
                    $utilidadWorker = $product->workerPurchases->sum('utilidadWorker');
                    
                    $totalPriceOrders = $product->orders->sum('total_price');
                    $totalPriceSales = $product->cashiersales->sum('total_pricesales');
                    $totalPriceWorker = $product->workerPurchases->sum('total_worker_price');
                
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'total_quantity' => $totalOrders + $totalSales + $totalWorker,
                        'utilidad' => $utilidadOrders + $utilidadSales + $utilidadWorker,
                        'price' => $totalPriceOrders + $totalPriceSales + $totalPriceWorker,
                        'sales' => $totalPriceSales + $totalPriceWorker
                    ];
                })->sortByDesc('total_quantity')->values();
                
                $totalUtilidadProducts = $products->sum('utilidad');
                $totalPriceProducts = $products->sum('price');
                $productSales = $products->sum('sales');
                ///endNuevo
                //$cashierSale = CashierSale::where('branch_id', $data['branch_id'])->whereDate('data', '>=', $startOfMonth)->whereDate('data', '<=', $endOfMonth)->where('pay', 1);
                //$cashierSaleAmount = $cashierSale->sum('price');
                $carsDetail = $cars->get()->map(function ($car) {
                    $products = $car->orders->where('is_product', 1)->sum('price');
                    $services = $car->orders->where('is_product', 0)->sum('price');
                    $noMetas = $car->orders->where('is_product', 0)->where('meta', 0);
                    $utilidadServices = $noMetas->sum('price') - $noMetas->sum('percent_win');
                    return [
                        'productsAmount' => $products,
                        'servicesAmount' => $services,
                        'earnings' => $car->amount,
                        'technical_assistance' => $car->technical_assistance * 5000,
                        'tip' => $car->tip,
                        'total' => $car->amount + $car->technical_assistance * 5000,
                        'utilidadService' => $utilidadServices
                    ];
                });

                $resultDetails[] = [
                    'productsAmount' => round($totalPriceProducts, 2),
                    'servicesAmount' => round($carsDetail->sum('servicesAmount'), 2),
                    'earnings' => round($carsDetail->sum('earnings'), 2),
                    'technical_assistance' => round($carsDetail->sum('technical_assistance'), 2),
                    'tip' => round($carsDetail->sum('tip'), 2),
                    'total' => round($carsDetail->sum('total') + $productSales, 2),
                    'utilidad' => round($carsDetail->sum('utilidadService') + $totalUtilidadProducts + ($carsDetail->sum('tip') * 0.10), 2),
                    'type' => false
                ];
                $carsAnt = Car::whereHas('reservation', function ($query) use ($data, $inicio_mes_anterior, $final_mes_anterior) {
                    $query->where('branch_id', $data['branch_id'])->whereDate('data', '>=', $inicio_mes_anterior)->whereDate('data', '<=', $final_mes_anterior);
                })->where('pay', 1);

                $carIdsAnt = $carsAnt->pluck('id');
                ///Nuevo
                $productsAnt = Product::with([
                    'orders' => function ($query) use ($carIdsAnt) {
                        $query->selectRaw('product_id, SUM(cant) as total_cant, SUM(percent_win) as utilidadOrder, SUM(price) as total_price')
                            ->groupBy('product_id')
                            ->whereIn('car_id', $carIdsAnt)
                            ->where('is_product', 1);
                    },
                    'cashiersales' => function ($query) use ($data, $inicio_mes_anterior, $final_mes_anterior) {
                        $query->selectRaw('product_id, SUM(cant) as total_sales, SUM(percent_wint) as utilidadCash, SUM(price) as total_pricesales')
                            ->groupBy('product_id')
                            ->where('cashiersales.branch_id', $data['branch_id'])
                            ->whereDate('data', '>=', $inicio_mes_anterior)
                            ->whereDate('data', '<=', $final_mes_anterior);
                    },
                    'workerPurchases' => function ($query) use ($data, $inicio_mes_anterior, $final_mes_anterior) {
                        $query->selectRaw('product_id, SUM(cant) as total_worker, SUM(percent_wint) as utilidadWorker, SUM(total) as total_worker_price')
                            ->groupBy('product_id')
                            ->where('branch_id', $data['branch_id'])
                            ->whereDate('data', '>=', $inicio_mes_anterior)
                            ->whereDate('data', '<=', $final_mes_anterior)
                            ->where('status', 1); // Solo compras aprobadas
                    }
                ])->get()->filter(function ($product) {
                    return !$product->orders->isEmpty() || !$product->cashiersales->isEmpty() || !$product->workerPurchases->isEmpty();
                })->map(function ($product) {
                    // Misma lógica de cálculo que para el mes actual
                    $totalOrders = $product->orders->sum('total_cant');
                    $totalSales = $product->cashiersales->sum('total_sales');
                    $totalWorker = $product->workerPurchases->sum('total_worker');
                    
                    $utilidadOrders = $product->orders->sum('utilidadOrder');
                    $utilidadSales = $product->cashiersales->sum('utilidadCash');
                    $utilidadWorker = $product->workerPurchases->sum('utilidadWorker');
                    
                    $totalPriceOrders = $product->orders->sum('total_price');
                    $totalPriceSales = $product->cashiersales->sum('total_pricesales');
                    $totalPriceWorker = $product->workerPurchases->sum('total_worker_price');
                
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'total_quantity' => $totalOrders + $totalSales + $totalWorker,
                        'utilidad' => $utilidadOrders + $utilidadSales + $utilidadWorker,
                        'price' => $totalPriceOrders + $totalPriceSales + $totalPriceWorker,
                        'sales' => $totalPriceSales + $totalPriceWorker
                    ];
                })->sortByDesc('total_quantity')->values();
                $totalUtilidadProductsAnt = $productsAnt->sum('utilidad');
                $totalPriceProductsAnt = $productsAnt->sum('price');
                $productSalesAnt = $productsAnt->sum('sales');
                ///endNuevo
                $carsDetailAnt = $carsAnt->get()->map(function ($car) {
                    $products = $car->orders->where('is_product', 1)->sum('price');
                    $services = $car->orders->where('is_product', 0)->sum('price');
                    $noMetas = $car->orders->where('is_product', 0)->where('meta', 0);
                    $utilidadServices = $noMetas->sum('price') - $noMetas->sum('percent_win');
                    return [
                        'productsAmount' => $products,
                        'servicesAmount' => $services,
                        'earnings' => $car->amount,
                        'technical_assistance' => $car->technical_assistance * 5000,
                        'tip' => $car->tip,
                        'total' => $car->amount + $car->technical_assistance * 5000,
                        'utilidadService' => $utilidadServices
                    ];
                });
                $resultDetailsAnt[] = [
                    'productsAmount' => round($totalPriceProductsAnt, 2),
                    'servicesAmount' => round($carsDetailAnt->sum('servicesAmount'), 2),
                    'earnings' => round($carsDetailAnt->sum('earnings'), 2),
                    'technical_assistance' => round($carsDetailAnt->sum('technical_assistance'), 2),
                    'tip' => round($carsDetailAnt->sum('tip'), 2),
                    'total' => round($carsDetailAnt->sum('total') + $productSalesAnt, 2),
                    'utilidad' => round($carsDetailAnt->sum('utilidadService') + $totalUtilidadProductsAnt + ($carsDetailAnt->sum('tip') * 0.10), 2),
                    'type' => false
                ];
                $cars = $resultDetails[0]['total'];
                $carsAnt = $resultDetailsAnt[0]['total'];

                return response()->json(['cars' => $cars, 'carsDetail' => $resultDetails, 'carsDetailAnt' => $resultDetailsAnt, 'carsAnt' => $carsAnt], 200);
            } else {
                Log::info("businesss");

                /*$financesA = Finance::whereDate('data', '>=', $inicio_mes_anterior)->whereDate('data', '<=', $final_mes_anterior)->get();

                $ingresoA = $financesA->where('operation', 'Ingreso')->sum('amount');
                $gastoA = $financesA->where('operation', 'Gasto')->sum('amount');*/
                $carsAnt = Car::whereHas('reservations', function ($query) use ($inicio_mes_anterior, $final_mes_anterior) {
                    $query->whereDate('data', '>=', $inicio_mes_anterior)->whereDate('data', '<=', $final_mes_anterior);
                })->where('pay', 1);
                $carIdsAnt = $carsAnt->pluck('id');
                //Nuevo Anterior
                $productsAnt = Product::with([
                    'orders' => function ($query) use ($carIdsAnt) {
                        $query->selectRaw('product_id, SUM(cant) as total_cant, SUM(percent_win) as utilidadOrder, SUM(price) as total_price')
                            ->groupBy('product_id')
                            ->whereIn('car_id', $carIdsAnt)
                            ->where('is_product', 1);
                    },
                    'cashiersales' => function ($query) use ($inicio_mes_anterior, $final_mes_anterior) {
                        $query->selectRaw('product_id, SUM(cant) as total_sales, SUM(percent_wint) as utilidadCash, SUM(price) as total_pricesales')
                            ->groupBy('product_id')
                            ->whereDate('data', '>=', $inicio_mes_anterior)
                            ->whereDate('data', '<=', $final_mes_anterior);
                    },
                    'workerPurchases' => function ($query) use ($inicio_mes_anterior, $final_mes_anterior) {
                        $query->selectRaw('product_id, SUM(cant) as total_worker, SUM(percent_wint) as utilidadWorker, SUM(total) as total_worker_price')
                            ->groupBy('product_id')
                            ->whereDate('data', '>=', $inicio_mes_anterior)
                            ->whereDate('data', '<=', $final_mes_anterior)
                            ->where('status', 1); // Solo compras aprobadas
                    }
                ])->get()->filter(function ($product) {
                    return !$product->orders->isEmpty() || !$product->cashiersales->isEmpty() || !$product->workerPurchases->isEmpty();
                })->map(function ($product) {
                    // Misma lógica de cálculo que para el mes actual
                    $totalOrders = $product->orders->sum('total_cant');
                    $totalSales = $product->cashiersales->sum('total_sales');
                    $totalWorker = $product->workerPurchases->sum('total_worker');
                    
                    $utilidadOrders = $product->orders->sum('utilidadOrder');
                    $utilidadSales = $product->cashiersales->sum('utilidadCash');
                    $utilidadWorker = $product->workerPurchases->sum('utilidadWorker');
                    
                    $totalPriceOrders = $product->orders->sum('total_price');
                    $totalPriceSales = $product->cashiersales->sum('total_pricesales');
                    $totalPriceWorker = $product->workerPurchases->sum('total_worker_price');
                
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'total_quantity' => $totalOrders + $totalSales + $totalWorker,
                        'utilidad' => $utilidadOrders + $utilidadSales + $utilidadWorker,
                        'price' => $totalPriceOrders + $totalPriceSales + $totalPriceWorker,
                        'sales' => $totalPriceSales + $totalPriceWorker
                    ];
                })->sortByDesc('total_quantity')->values();

                // Totales
                $totalUtilidadProductsAnt = $productsAnt->sum('utilidad');
                $totalPriceProductsAnt = $productsAnt->sum('price');
                $productSalesAnt = $productsAnt->sum('sales');
                //endNuevo Anterior
                /*$cashierSaleA = CashierSale::whereDate('data', '>=', $inicio_mes_anterior)->whereDate('data', '<=', $final_mes_anterior)->where('pay', 1);
                $productSaleA = ProductSale::whereDate('data', '>=', $inicio_mes_anterior)->whereDate('data', '<=', $final_mes_anterior);
                $cashierSaleAmountA = $cashierSaleA->sum('price');
                $productSaleAmountA = $productSaleA->sum('price');*/
                $couseStudentA = CourseStudent::whereHas('course', function ($query) use ($inicio_mes_anterior) {
                    $query->whereDate('startDate', '>=', $inicio_mes_anterior);
                })->where('payment_status', 1);
                $amountCourseA = $couseStudentA->sum('total_payment');
                $carsDetailAnt = $carsAnt->get()->map(function ($car) {
                    $products = $car->orders->where('is_product', 1)->sum('price');
                    $services = $car->orders->where('is_product', 0)->sum('price');
                    $noMetas = $car->orders->where('is_product', 0)->where('meta', 0);
                    $utilidadServices = $noMetas->sum('price') - $noMetas->sum('percent_win');
                    return [
                        'productsAmount' => $products,
                        'servicesAmount' => $services,
                        'earnings' => $car->amount,
                        'technical_assistance' => $car->technical_assistance * 5000,
                        'tip' => $car->tip,
                        'total' => $car->amount + $car->technical_assistance * 5000,
                        'utilidadService' => $utilidadServices
                    ];
                });
                $resultDetailsAnt[] = [
                    'productsAmount' => round($totalPriceProductsAnt, 2),
                    'servicesAmount' => round($carsDetailAnt->sum('servicesAmount'), 2),
                    'earnings' => round($carsDetailAnt->sum('earnings'), 2),
                    'academia' => round($amountCourseA, 2),
                    'technical_assistance' => round($carsDetailAnt->sum('technical_assistance'), 2),
                    'tip' => round($carsDetailAnt->sum('tip'), 2),
                    'total' => round($carsDetailAnt->sum('total') + $productSalesAnt + $amountCourseA, 2),
                    'utilidad' => round($carsDetailAnt->sum('utilidadService') + $totalUtilidadProductsAnt + ($carsDetailAnt->sum('tip') * 0.10) + $amountCourseA, 2),
                    'type' => true
                ];
                /*$finances = Finance::whereDate('data', '>=', $startOfMonth)->whereDate('data', '<=', $endOfMonth)->get();

                $ingreso = $finances->where('operation', 'Ingreso')->sum('amount');
                $gasto = $finances->where('operation', 'Gasto')->sum('amount');*/
                $cars = Car::whereHas('reservations', function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->whereDate('data', '>=', $startOfMonth)->whereDate('data', '<=', $endOfMonth);
                })->where('pay', 1);
                $carIds = $cars->pluck('id');
                //Nuevo
                $products = Product::with([
                    'orders' => function ($query) use ($carIds) {
                        $query->selectRaw('product_id, SUM(cant) as total_cant, SUM(percent_win) as utilidadOrder, SUM(price) as total_price')
                            ->groupBy('product_id')
                            ->whereIn('car_id', $carIds)
                            ->where('is_product', 1);
                    },
                    'cashiersales' => function ($query) use ($startOfMonth, $endOfMonth) {
                        $query->selectRaw('product_id, SUM(cant) as total_sales, SUM(percent_wint) as utilidadCash, SUM(price) as total_pricesales')
                            ->groupBy('product_id')
                            ->whereDate('data', '>=', $startOfMonth)
                            ->whereDate('data', '<=', $endOfMonth);
                    },
                    'workerPurchases' => function ($query) use ($startOfMonth, $endOfMonth) {
                        $query->selectRaw('product_id, SUM(cant) as total_worker, SUM(percent_wint) as utilidadWorker, SUM(total) as total_worker_price')
                            ->groupBy('product_id')
                            ->whereDate('data', '>=', $startOfMonth)
                            ->whereDate('data', '<=', $endOfMonth)
                            ->where('status', 1); // Solo compras aprobadas
                    }
                ])->get()->filter(function ($product) {
                    return !$product->orders->isEmpty() || !$product->cashiersales->isEmpty() || !$product->workerPurchases->isEmpty();
                })->map(function ($product) {
                    $totalOrders = $product->orders->sum('total_cant');
                    $totalSales = $product->cashiersales->sum('total_sales');
                    $totalWorker = $product->workerPurchases->sum('total_worker');
                    
                    $utilidadOrders = $product->orders->sum('utilidadOrder');
                    $utilidadSales = $product->cashiersales->sum('utilidadCash');
                    $utilidadWorker = $product->workerPurchases->sum('utilidadWorker');
                    
                    $totalPriceOrders = $product->orders->sum('total_price');
                    $totalPriceSales = $product->cashiersales->sum('total_pricesales');
                    $totalPriceWorker = $product->workerPurchases->sum('total_worker_price');
                
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'total_quantity' => $totalOrders + $totalSales + $totalWorker,
                        'utilidad' => $utilidadOrders + $utilidadSales + $utilidadWorker,
                        'price' => $totalPriceOrders + $totalPriceSales + $totalPriceWorker,
                        'sales' => $totalPriceSales + $totalPriceWorker
                    ];
                })->sortByDesc('total_quantity')->values();

                // Totales
                $totalUtilidadProducts = $products->sum('utilidad');
                $totalPriceProducts = $products->sum('price');
                $productSales = $products->sum('sales');
                //endNuevo
                // $cashierSale = CashierSale::whereDate('data', '>=', $startOfMonth)->whereDate('data', '<=', $endOfMonth)->where('pay', 1);
                //$cashierSaleAmount = $cashierSale->sum('price');
                //$productSale = ProductSale::whereDate('data', '>=', $startOfMonth)->whereDate('data', '<=', $endOfMonth);
                //$productSaleAmount = $productSale->sum('price');
                $couseStudent = CourseStudent::whereHas('course', function ($query) use ($startOfMonth) {
                    $query->whereDate('startDate', '>=', $startOfMonth);
                })->where('payment_status', 1);
                $amountCourse = $couseStudent->sum('total_payment');
                $carsDetail = $cars->get()->map(function ($car) {
                    $products = $car->orders->where('is_product', 1)->sum('price');
                    $services = $car->orders->where('is_product', 0)->sum('price');
                    $noMetas = $car->orders->where('is_product', 0)->where('meta', 0);
                    $utilidadServices = $noMetas->sum('price') - $noMetas->sum('percent_win');
                    return [
                        'productsAmount' => $products,
                        'servicesAmount' => $services,
                        'earnings' => $car->amount,
                        'technical_assistance' => $car->technical_assistance * 5000,
                        'tip' => $car->tip,
                        'total' => $car->amount + $car->technical_assistance * 5000,
                        'utilidadService' => $utilidadServices
                    ];
                });
                $resultDetails[] = [
                    'productsAmount' => round($totalPriceProducts, 2),
                    'servicesAmount' => round($carsDetail->sum('servicesAmount'), 2),
                    'earnings' => round($carsDetail->sum('earnings'), 2),
                    'academia' => round($amountCourse, 2),
                    'technical_assistance' => round($carsDetail->sum('technical_assistance'), 2),
                    'tip' => round($carsDetail->sum('tip'), 2),
                    'total' => round($carsDetail->sum('total') + $productSales + $amountCourse, 2),
                    'utilidad' => round($carsDetail->sum('utilidadService') + $totalUtilidadProducts + ($carsDetail->sum('tip') * 0.10) + $amountCourse, 2),
                    'type' => true
                ];
                $cars = $resultDetails[0]['total'];
                $carsAnt = $resultDetailsAnt[0]['total'];
                return response()->json(['cars' => $cars, 'carsDetail' => $resultDetails, 'carsDetailAnt' => $resultDetailsAnt, 'carsAnt' => $carsAnt], 200);
            }
            //return response()->json($branches->sum(), 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar las reservaciones"], 500);
        }
    }

    //Nuevo metodos 
    public function cars_sum_amount_mounth_NUEVO_MAL(Request $request)
    {
        try {
            Log::info("Entra a buscar una las ganancias del mes");

            // Validación de los datos de entrada
            $data = $request->validate([
                'business_id' => 'required|numeric',
                'branch_id' => 'nullable'
            ]);

            $startOfMonth = now()->startOfMonth();
            $endOfMonth = now()->endOfMonth();
            $inicio_mes_anterior = now()->subMonth()->startOfMonth();
            $final_mes_anterior = now()->subMonth()->endOfMonth();

            // Definir condiciones para la consulta
            // Si se especifica branch_id
            if ($data['branch_id'] != 0) {
                Log::info("branch");
                // Detalles del mes actual
                $resultDetails = $this->calculateDetails($data, $startOfMonth, $endOfMonth);

                // Detalles del mes anterior
                $resultDetailsAnt = $this->calculateDetails($data, $inicio_mes_anterior, $final_mes_anterior);

                $cars = $resultDetails['total'];
                $carsAnt = $resultDetailsAnt['total'];

                return response()->json([
                    'cars' => $cars,
                    'carsDetail' => [
                        [
                            'productsAmount' => $resultDetails['productsAmount'],
                            'servicesAmount' => $resultDetails['servicesAmount'],
                            'earnings' => $resultDetails['earnings'],
                            'technical_assistance' => $resultDetails['technical_assistance'],
                            'tip' => $resultDetails['tip'],
                            'total' => $resultDetails['total'],
                            'utilidad' => $resultDetails['utilidad'],
                            'type' => $resultDetails['type'],
                        ]
                    ],
                    'carsDetailAnt' => [
                        [
                            'productsAmount' => $resultDetailsAnt['productsAmount'],
                            'servicesAmount' => $resultDetailsAnt['servicesAmount'],
                            'earnings' => $resultDetailsAnt['earnings'],
                            'technical_assistance' => $resultDetailsAnt['technical_assistance'],
                            'tip' => $resultDetailsAnt['tip'],
                            'total' => $resultDetailsAnt['total'],
                            'utilidad' => $resultDetailsAnt['utilidad'],
                            'type' => $resultDetailsAnt['type'],
                        ]
                    ],
                    'carsAnt' => $carsAnt,
                ], 200);
            } else {
                Log::info("business");
                // Detalles del mes actual
                $resultDetails = $this->calculateDetailsNegocio($startOfMonth, $endOfMonth);

                // Detalles del mes anterior
                $resultDetailsAnt = $this->calculateDetailsNegocio($inicio_mes_anterior, $final_mes_anterior);

                // Sumamos los totales para ambos meses
                $cars = $resultDetails['total'];
                $carsAnt = $resultDetailsAnt['total'];

                // Devuelve datos del mes actual y anterior en la misma respuesta
                return response()->json([
                    'cars' => $cars,
                    'carsDetail' => [
                        [
                            'productsAmount' => $resultDetails['productsAmount'],
                            'servicesAmount' => $resultDetails['servicesAmount'],
                            'earnings' => $resultDetails['earnings'],
                            'academia' => $resultDetails['academia'],
                            'technical_assistance' => $resultDetails['technical_assistance'],
                            'tip' => $resultDetails['tip'],
                            'total' => $resultDetails['total'],
                            'utilidad' => $resultDetails['utilidad'],
                            'type' => $resultDetails['type'],
                        ]
                    ],
                    'carsDetailAnt' => [
                        [
                            'productsAmount' => $resultDetailsAnt['productsAmount'],
                            'servicesAmount' => $resultDetailsAnt['servicesAmount'],
                            'earnings' => $resultDetailsAnt['earnings'],
                            'academia' => $resultDetailsAnt['academia'],
                            'technical_assistance' => $resultDetailsAnt['technical_assistance'],
                            'tip' => $resultDetailsAnt['tip'],
                            'total' => $resultDetailsAnt['total'],
                            'utilidad' => $resultDetailsAnt['utilidad'],
                            'type' => $resultDetailsAnt['type'],
                        ]
                    ],
                    'carsAnt' => $carsAnt,
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Error interno'], 500);
        }
    }

    //metodos nuevos que se llaman en ----- public function cars_sum_amount_mounth -----
    public function calculateDetails($data, $startOfMonth, $endOfMonth)
    {
        $branchId = $data['branch_id'];
        // Consultas optimizadas para carros
        $queryCars = Car::whereHas('reservation', function ($query) use ($branchId, $startOfMonth, $endOfMonth) {
            $query->where('branch_id', $branchId)
                ->whereDate('data', '>=', $startOfMonth)->whereDate('data', '<=', $endOfMonth);
        })->where('pay', 1);

        $carsDetail = $this->getCarsDetailBranch($queryCars);

        // Productos optimizados
        $products = $this->getProductsDetailBranch($queryCars, $data, $startOfMonth, $endOfMonth);

        $totalUtilidadProducts = $products->sum('utilidad');
        $totalPriceProducts = $products->sum('price');
        $productSales = $products->sum('sales');

        return [
            'productsAmount' => round($totalPriceProducts, 2),
            'servicesAmount' => round($carsDetail->sum('servicesAmount'), 2),
            'earnings' => round($carsDetail->sum('earnings'), 2),
            'technical_assistance' => round($carsDetail->sum('technical_assistance'), 2),
            'tip' => round($carsDetail->sum('tip'), 2),
            'total' => round($carsDetail->sum('total') + $productSales, 2),
            'utilidad' => round($carsDetail->sum('utilidadService') + $totalUtilidadProducts + ($carsDetail->sum('tip') * 0.10), 2),
            'type' => false
        ];
    }

    private function getProductsDetailBranch($cars, $data, $startOfMonth, $endOfMonth)
    {
        $carIds = $cars->pluck('id');

        return Product::with([
            'orders' => function ($query) use ($carIds) {
                $query->selectRaw('product_id, SUM(cant) as total_cant, SUM(percent_win) as utilidadOrder, SUM(price) as total_price')
                    ->whereIn('car_id', $carIds)
                    ->where('is_product', 1)
                    ->groupBy('product_id');
            },
            'cashierSales' => function ($query) use ($data, $startOfMonth, $endOfMonth) {
                $query->selectRaw('product_id, SUM(cant) as total_sales, SUM(percent_wint) as utilidadCash, SUM(price) as total_pricesales')
                    ->where('cashiersales.branch_id', $data['branch_id'])
                    ->whereBetween('data', [$startOfMonth, $endOfMonth])
                    ->groupBy('product_id');
            }
        ])->get()->filter(function ($product) {
            return !$product->orders->isEmpty() || !$product->cashierSales->isEmpty();
        })->map(function ($product) {
            $totalOrders = $product->orders->sum('total_cant');
            $totalSales = $product->cashierSales->sum('total_sales');
            $utilidadOrders = $product->orders->sum('utilidadOrder');
            $utilidadSales = $product->cashierSales->sum('utilidadCash');
            $totalPriceOrders = $product->orders->sum('total_price');
            $totalPriceSales = $product->cashierSales->sum('total_pricesales');

            return [
                'id' => $product->id,
                'name' => $product->name,
                'total_quantity' => $totalOrders + $totalSales,
                'utilidad' => $utilidadOrders + $utilidadSales,
                'price' => $totalPriceOrders + $totalPriceSales,
                'sales' => $totalPriceSales
            ];
        })->sortByDesc('total_quantity')->values();
    }

    private function getCarsDetailBranch($cars)
    {
        return $cars->get()->map(function ($car) {
            $products = $car->orders->where('is_product', 1)->sum('price');
            $services = $car->orders->where('is_product', 0)->sum('price');
            $noMetas = $car->orders->where('is_product', 0)->where('meta', 0);
            $utilidadServices = $noMetas->sum('price') - $noMetas->sum('percent_win');
            return [
                'productsAmount' => $products,
                'servicesAmount' => $services,
                'earnings' => $car->amount,
                'technical_assistance' => $car->technical_assistance * 5000,
                'tip' => $car->tip,
                'total' => $car->amount + $car->technical_assistance * 5000,
                'utilidadService' => $utilidadServices
            ];
        });
    }


    public function calculateDetailsNegocio($startOfMonth, $endOfMonth)
    {
        $queryCars = Car::whereHas('reservation', function ($query) use ($startOfMonth, $endOfMonth) {
            $query->whereDate('data', '>=', $startOfMonth)->whereDate('data', '<=', $endOfMonth);
        })->where('pay', 1);

        $couseStudent = CourseStudent::whereHas('course', function ($query) use ($startOfMonth) {
            $query->whereDate('startDate', '>=', $startOfMonth);
        })->where('payment_status', 1);

        $amountCourse = $couseStudent->sum('total_payment');
        $carsDetail = $this->getCarsDetailNegocio($queryCars);

        $products = $this->getProductsDetailNegocio($queryCars, $startOfMonth, $endOfMonth);
        $totalUtilidadProducts = $products->sum('utilidad');
        $totalPriceProducts = $products->sum('price');
        $productSales = $products->sum('sales');

        return [
            'productsAmount' => round($totalPriceProducts, 2),
            'servicesAmount' => round($carsDetail->sum('servicesAmount'), 2),
            'earnings' => round($carsDetail->sum('earnings'), 2),
            'academia' => round($amountCourse, 2),
            'technical_assistance' => round($carsDetail->sum('technical_assistance'), 2),
            'tip' => round($carsDetail->sum('tip'), 2),
            'total' => round($carsDetail->sum('total') + $productSales + $amountCourse, 2),
            'utilidad' => round($carsDetail->sum('utilidadService') + $totalUtilidadProducts + ($carsDetail->sum('tip') * 0.10) + $amountCourse, 2),
            'type' => true
        ];
    }

    private function getProductsDetailNegocio($cars, $startOfMonth, $endOfMonth)
    {
        $carIds = $cars->pluck('id');

        return Product::with([
            'orders' => function ($query) use ($carIds) {
                $query->selectRaw('product_id, SUM(cant) as total_orders, SUM(percent_win) as utilidad_orders, SUM(price) as total_price_orders')
                    ->whereIn('car_id', $carIds)
                    ->where('is_product', 1)
                    ->groupBy('product_id');
            },
            'cashierSales' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->selectRaw('product_id, SUM(cant) as total_sales, SUM(percent_wint) as utilidad_cash, SUM(price) as total_price_sales')
                    ->whereBetween('data', [$startOfMonth, $endOfMonth])
                    ->groupBy('product_id');
            },
            'productSales' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->selectRaw('product_store_id, SUM(price) as total_price_sales_products')
                    ->whereBetween('data', [$startOfMonth, $endOfMonth])
                    ->groupBy('product_store_id');
            }
        ])->get()->filter(function ($product) {
            return !$product->orders->isEmpty() || !$product->cashierSales->isEmpty() || !$product->productSales->isEmpty();
        })->map(function ($product) {
            $totalOrders = $product->orders->sum('total_orders');
            $totalSales = $product->cashierSales->sum('total_sales');
            $utilidadOrders = $product->orders->sum('utilidad_orders');
            $utilidadSales = $product->cashierSales->sum('utilidad_cash');
            $totalPriceOrders = $product->orders->sum('total_price_orders');
            $totalPriceSales = $product->cashierSales->sum('total_price_sales');
            $productSalePrice = $product->productSales->sum('total_price_sales_products');

            return [
                'id' => $product->id,
                'name' => $product->name,
                'total_quantity' => $totalOrders + $totalSales,
                'utilidad' => $utilidadOrders + $utilidadSales,
                'price' => $totalPriceOrders + $totalPriceSales + $productSalePrice,
                'sales' => $totalPriceSales + $productSalePrice
            ];
        })->sortByDesc('total_quantity')->values();
    }

    private function getCarsDetailNegocio($cars)
    {
        return $cars->get()->map(function ($car) {
            $products = $car->orders->where('is_product', 1)->sum('price');
            $services = $car->orders->where('is_product', 0)->sum('price');
            $noMetas = $car->orders->where('is_product', 0)->where('meta', 0);
            $utilidadServices = $noMetas->sum('price') - $noMetas->sum('percent_win');
            return [
                'productsAmount' => $products,
                'servicesAmount' => $services,
                'earnings' => $car->amount,
                'technical_assistance' => $car->technical_assistance * 5000,
                'tip' => $car->tip,
                'total' => $car->amount + $car->technical_assistance * 5000,
                'utilidadService' => $utilidadServices
            ];
        });
    }



    public function branch_cars_ANTERIOR(Request $request)
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
                $query->where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now())->whereIn('confirmation', [2, 4]);
            })->with(['clientProfessional.client', 'clientProfessional.professional', 'payment'])->get()->map(function ($car) use ($data) {
                $client = $car->clientProfessional->client;
                $professional = $car->clientProfessional->professional;
                $products = $car->orders->where('is_product', 1)->sum('price');
                $services = $car->orders->where('is_product', 0)->sum('price');
                $tail = $car->reservation->tail;
                if ($tail == null) {
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
                /*if ($tail->aleatorie == 1) {
                    $name = '';
                    $image_url = 'professionals/default_profile.jpg';
                }
                else{*/
                $name = $professional->name;
                $image_url = $professional->image_url;
                //}
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
                    'clientName' => $client->name,
                    'phone' => $client->phone,
                    'professionalName' => $name,
                    'client_image' => $client->client_image,
                    'professional_id' => $professional->id,
                    'image_url' => $image_url,
                    'payment' => $car->payment,
                    'state' => (int)$state,
                    'updated_at' => $car->reservation->tail->updated_at ?? '2024-09-13 10:10:00'

                ];
                //}
            })->sortBy('updated_at')->sortBy('state')->values();
            $box = Box::with('boxClose')->whereDate('data', Carbon::now())->where('branch_id', $data['branch_id'])->first();
            $payments = Payment::whereDate('created_at', Carbon::now())->where('branch_id', $data['branch_id'])->get();
            $cashierSales = CashierSale::where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now())->get();
            $bonus = ProfessionalPayment::where('branch_id', $data['branch_id'])
                ->whereDate('date', Carbon::now())
                ->whereIn('type', ['Bono servicios', 'Bono convivencias'])
                ->get()->sum('amount');
            return response()->json(['cars' => $cars, 'box' => $box, 'payments' => $payments, 'cashierSales' => $cashierSales, 'bonusPay' => $bonus], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los carros"], 500);
        }
    }

    public function branch_cars_date(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'data' => 'nullable|date'
            ]);

            Log::info("Recibiendo request para branch_cars", $data);
            $userId = $request->user()->id;
            // Cargar la sucursal solo si es necesaria para el proceso
            $branch = Branch::find($data['branch_id']);
            if (!$branch) {
                return response()->json(['msg' => 'Sucursal no encontrada'], 404);
            }

            // Asignar la fecha actual si data no está presente
            $today = $data['data'] ?? Carbon::now();

            // Consultar todos los carros de la sucursal para el día actual con relaciones necesarias
            $cars = Car::whereHas('reservation', function ($query) use ($data, $today) {
                $query->where('branch_id', $data['branch_id'])
                    ->whereDate('data', $today)
                    ->whereIn('confirmation', [2]);
            })
                ->with([
                    'clientProfessional.client:id,name,phone,client_image',
                    'clientProfessional.professional:id,name,image_url',
                    'orders:id,car_id,is_product,price'
                ])
                ->get()
                ->map(function ($car) {
                    // Obtener cliente y profesional
                    $client = $car->clientProfessional->client;
                    $professional = $car->clientProfessional->professional;

                    // Calcular precios de productos y servicios directamente en la colección
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
                        'clientName' => $client->name,
                        'phone' => $client->phone ?? '',
                        'professionalName' => $professional->name,
                        'client_image' => $client->client_image ?? "comments/default_profile.jpg",
                        'professional_id' => $professional->id,
                        'image_url' => $professional->image_url ?? "professionals/default_profile.jpg",
                        'user_id' => $car->user_id,
                        'action_status' => $car->action_status,
                        'action_descriptions' => $car->action_descriptions ?? [],
                        'change_log' => $car->change_log ?? []
                    ];
                })
                ->values();

            return response()->json([
                'cars' => $cars
            ], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error("Error al mostrar los carros: " . $th->getMessage());
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los carros"], 500);
        }
    }

    public function branch_cars(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'data' => 'nullable|date'
            ]);

            Log::info("Recibiendo request para branch_cars", $data);
            $userId = $request->user()->id;
            // Cargar la sucursal solo si es necesaria para el proceso
            $branch = Branch::find($data['branch_id']);
            if (!$branch) {
                return response()->json(['msg' => 'Sucursal no encontrada'], 404);
            }

            // Asignar la fecha actual si data no está presente
            $today = $data['data'] ?? Carbon::now();

            // Consultar todos los carros de la sucursal para el día actual con relaciones necesarias
            $cars = Car::whereHas('reservation', function ($query) use ($data, $today) {
                $query->where('branch_id', $data['branch_id'])
                    ->whereDate('data', $today)
                    ->whereIn('confirmation', [2, 4]);
            })
                ->with([
                    'clientProfessional.client:id,name,phone,client_image',
                    'clientProfessional.professional:id,name,image_url',
                    'orders:id,car_id,is_product,price',
                    'reservation.tail'
                ])
                ->get()
                ->map(function ($car) {
                    // Obtener cliente y profesional
                    $client = $car->clientProfessional->client;
                    $professional = $car->clientProfessional->professional;

                    // Calcular precios de productos y servicios directamente en la colección
                    $products = $car->orders->where('is_product', 1)->sum('price');
                    $services = $car->orders->where('is_product', 0)->sum('price');

                    // Determinar estado del carro en función de la cola (tail)
                    $tail = $car->reservation->tail;
                    //Log::info($tail);
                    $state = $tail ? ($tail->attended == 2 ? 1 : ($tail->attended == 0 || $tail->attended == 3 ? 3 : 2)) : 0;

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
                        'clientName' => $client->name,
                        'phone' => $client->phone ?? '',
                        'professionalName' => $professional->name,
                        'client_image' => $client->client_image ?? "comments/default_profile.jpg",
                        'professional_id' => $professional->id,
                        'image_url' => $professional->image_url ?? "professionals/default_profile.jpg",
                        'state' => (int)$state,
                        'updated_at' => optional($tail)->updated_at ?? '2024-09-13 10:10:00',
                        'user_id' => $car->user_id,
                        'action_status' => $car->action_status,
                        'action_descriptions' => $car->action_descriptions ?? [],
                        'change_log' => $car->change_log ?? [],
                        'payment' => $car->payment ?? [],
                    ];
                })
                ->sortBy('updated_at')
                ->sortBy('state')
                ->values();
            $cashierclosebox = CashierBoxClosing::where('branch_id', $data['branch_id'])
                ->where('user_id', $userId)
                ->whereDate('data', $today)
                ->get();
            // Consultar caja, pagos y ventas de la caja
            $box = Box::with('boxClose')
                ->where('branch_id', $data['branch_id'])
                ->whereDate('data', $today)
                ->first();

            $payments = Payment::where('branch_id', $data['branch_id'])
                ->whereDate('created_at', $today)
                ->get();

            $cashierSales = CashierSale::where('branch_id', $data['branch_id'])
                ->whereDate('data', $today)
                ->get();

            $workerPurchases = WorkerPurchase::whereDate('data', $today)
                ->where('status', 1)
                ->where('branch_id', $data['branch_id'])->get();

            // Obtener bonos del día actual
            $bonus = ProfessionalPayment::where('branch_id', $data['branch_id'])
                ->whereDate('date', $today)
                ->whereIn('type', ['Bono servicios', 'Bono convivencias'])
                ->sum('amount');

            return response()->json([
                'cars' => $cars ?? [],
                'box' => $box ?? [],
                'payments' => $payments ?? [],
                'cashierSales' => $cashierSales ?? [],
                'workerPurchases' => $workerPurchases ?? [],
                'bonusPay' => $bonus ?? 0,
                'cashierclosebox' => $cashierclosebox ?? []
            ], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error("Error al mostrar los carros: " . $th->getMessage());
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar los carros"], 500);
        }
    }

    public function branch_cars2(Request $request)
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
                $query->where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now())->whereIn('confirmation', [2, 4]);
            })->with(['clientProfessional.client', 'clientProfessional.professional', 'payment'])->get()->map(function ($car) use ($data) {
                $client = $car->clientProfessional->client;
                $professional = $car->clientProfessional->professional;
                $products = $car->orders->where('is_product', 1)->sum('price');
                $services = $car->orders->where('is_product', 0)->sum('price');
                $tail = $car->reservation->tail;
                if ($tail == null) {
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
                /*if ($tail->aleatorie == 1) {
                    $name = '';
                    $image_url = 'professionals/default_profile.jpg';
                }
                else{*/
                $name = $professional->name;
                $image_url = $professional->image_url;
                //}
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
                    'clientName' => $client->name,
                    'phone' => $client->phone,
                    'professionalName' => $name,
                    'client_image' => $client->client_image,
                    'professional_id' => $professional->id,
                    'image_url' => $image_url,
                    'payment' => $car->payment,
                    'state' => (int)$state,
                    'updated_at' => $car->reservation->tail->updated_at

                ];
                //}
            })->sortBy('updated_at')->sortBy('state')->values();
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
                })->whereIn('active', [2, 3])->with(['clientProfessional.client', 'clientProfessional.professional', 'payment'])->orderByDesc('updated_at')->get()->map(function ($car) use ($data) {
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
                        'clientName' => $client->name,
                        'professionalName' => $professional->name,
                        'client_image' => $client->client_image,
                        'professional_id' => $professional->id,
                        'image_url' => $professional->image_url,
                        'nameBranch' => $branch->name,
                        'professionalRole' => $professional->getRoleForBranch($branch->id),
                        'action_descriptions' => $car->action_descriptions ?? [],
                        'change_log' => $car->change_log ?? []
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
                        'professionalName' => $professional['name'],
                        'image_url' => $professional['image_url'],
                        'clientName' => $client['name'],
                        'client_image' => $client['client_image'],
                        'category' => $order['is_product'] ? $product['productCategory']['name'] : $service['type_service'],
                        'name' => $order['is_product'] ? $product['name'] : $service['branchService']['service']['name'],
                        'image' => $order['is_product'] ? $product['image_product'] : $service['branchService']['service']['image_service'],
                        'nameBranch' => $branch->name,
                        'professionalRole' => $professional->getRoleForBranch($branch->id)
                    ];
                }
                $cashierData = [];
                $cashierSales = CashierSale::where('pay', 3)->get();
                if ($cashierSales) {
                    foreach ($cashierSales as $cashierSale) {
                        $professional = $cashierSale['professional'];
                        $product = $cashierSale['productStore']['product'];
                        $branch = $cashierSale['branch'];
                        $cashierData[] = [
                            'id' => $cashierSale['id'],
                            'professionalName' => $professional['name'],
                            'image_url' => $professional['image_url'],
                            'productName' => $product['name'],
                            'price' => intval($product['sale_price']),
                            'cant' => $cashierSale['cant'],
                            'image_product' => $product['image_product'],
                            'nameBranch' => $branch['name'],
                            'professionalRole' => $professional->getRoleForBranch($branch->id)
                        ];
                    }
                }
            } else {
                $branch = Branch::where('id', $data['branch_id'])->first();
                $cars = Car::whereHas('reservation', function ($query) use ($data) {
                    $query->where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now());
                })->whereIn('active', [2, 3])->with(['clientProfessional.client', 'clientProfessional.professional', 'payment'])->orderByDesc('updated_at')->get()->map(function ($car) use ($branch) {
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
                        'clientName' => $client->name,
                        'professionalName' => $professional->name,
                        'client_image' => $client->client_image,
                        'professional_id' => $professional->id,
                        'image_url' => $professional->image_url,
                        'nameBranch' => $branch->name,
                        'professionalRole' => $professional->getRoleForBranch($branch->id),
                        'action_descriptions' => $car->action_descriptions ?? [],
                        'change_log' => $car->change_log ?? []
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
                        'professionalName' => $professional['name'],
                        'image_url' => $professional['image_url'],
                        'clientName' => $client['name'],
                        'client_image' => $client['client_image'],
                        'category' => $order['is_product'] ? $product['productCategory']['name'] : $service['type_service'],
                        'name' => $order['is_product'] ? $product['name'] : $service['branchService']['service']['name'],
                        'image' => $order['is_product'] ? $product['image_product'] : $service['branchService']['service']['image_service'],
                        'nameBranch' => $branch->name,
                        'professionalRole' => $professional->getRoleForBranch($branch->id)
                    ];
                }
                $cashierData = [];
                $cashierSales = CashierSale::where('branch_id', $data['branch_id'])->where('pay', 3)->get();
                if ($cashierSales) {
                    foreach ($cashierSales as $cashierSale) {
                        $professional = $cashierSale['professional'];
                        $product = $cashierSale['productStore']['product'];
                        $branch = $cashierSale['branch'];
                        $cashierData[] = [
                            'id' => $cashierSale['id'],
                            'professionalName' => $professional['name'],
                            'image_url' => $professional['image_url'],
                            'productName' => $product['name'],
                            'price' => intval($product['sale_price']),
                            'cant' => $cashierSale['cant'],
                            'image_product' => $product['image_product'],
                            'nameBranch' => $branch['name'],
                            'professionalRole' => $professional->getRoleForBranch($branch->id)
                        ];
                    }
                }
            }
            return response()->json(['cars' => $cars, 'orders' => $orderData, 'cashier' => $cashierData], 200, [], JSON_NUMERIC_CHECK);
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
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar ls ordenes"], 500);
        }
    }

    public function professional_car(Request $request)
    {
        try {
            Log::info("ok97");
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
                    $retent = $retention ? $amountGenerate * $retention / 100 : 0;
                    $tips = $car->tip ? $car->tip : 0;
                    $tipspercent = $tips * 0.80;
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
                    $retentionP = Retention::where('professional_id', $data['professional_id'])
                        ->whereDate('data', $cars[0]['data'])
                        ->where('branch_id', $data['branch_id'])->get();
                    return [

                        'professional_id' => intval($cars[0]['professional_id']),
                        'branch_id' =>  intval($cars[0]['branch_id']),
                        'data' => $cars[0]['data'],
                        'day_of_week' => $cars[0]['day_of_week'], // Mantener el día de la semana
                        'attendedClient' => $cars->sum('attendedClient'),
                        'services' => $cars->sum('services'),
                        'totalGeneral' => number_format(round($cars->sum('totalGeneral'), 2), 2),
                        'totalServices' => number_format(round($cars->sum('totalServices'), 2), 2),
                        'totalProducts' => number_format(round($cars->sum('totalProducts'), 2), 2),
                        'tips' => number_format(round($cars->sum('tips'), 0), 2),
                        'tips80' => number_format(round($cars->sum('tipspercent'), 2), 2),
                        'clientAleator' => $cars->sum('clientAleator'),
                        'amountGenerate' => number_format(round($cars->sum('amountGenerate'), 2), 2),
                        'totalRetention' => $retentionP->sum('retention') ? number_format(round($retentionP->sum('retention'), 2), 2) : number_format(round($cars->sum('retention'), 2), 2),
                        'metacant' => $meta->count() ? $meta->count() : 0,
                        'metaamount' => $meta->sum('amount') ? number_format(round($meta->sum('amount'), 2), 2) : '0.00',
                        'winPay' => number_format(round($cars->sum('winPay'), 2), 2)
                    ];
                })->sortByDesc('data')->values();

            //Log::info($cars->pluck('id'));
            Log::info("ok98");
            Log::info($cars);

            return response()->json(['car' => $cars], 200);
        } catch (\Throwable $th) {
            Log::error("ok99");
            Log::error($th);
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
                    'professional_id' => intval($car->tecnico_id),
                    'branch_id' => intval($data['branch_id']),
                    'data' => $reservation->data,
                    'day_of_week' => ucfirst(mb_strtolower(Carbon::parse($reservation->data)->locale('es_ES')->isoFormat('dddd'))),
                    'attendedClient' => intval($car->technical_assistance),
                    'amountGenerate' => $car->technical_assistance * 5000
                ];
            });

            $groupedCars = $carsData->groupBy('data')->map(function ($cars) {
                return [
                    'professional_id' => intval($cars[0]['professional_id']),
                    'branch_id' => intval($cars[0]['branch_id']),
                    'data' => $cars[0]['data'],
                    'day_of_week' => $cars[0]['day_of_week'],
                    'attendedClient' => intval($cars->sum('attendedClient')),
                    'amountGenerate' => number_format(round($cars->sum('amountGenerate'), 2), 2)
                ];
            })->values();

            return response()->json(['car' => $groupedCars], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar ls ordenes"], 500);
        }
    }


    public function professional_car_notpay_ANTERIOR(Request $request)
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
                    $orderPrduct = Order::where('car_id', $car->id)
                        ->where('is_product', 1)
                        ->get();

                    $client = $car->clientProfessional->client;
                    $retention = $retention ? ($orderServ->sum('percent_win') * $retention) / 100 : 0;
                    $amountServ = $orderServ->sum('percent_win');
                    return [
                        'id' => $car->id,
                        'professional_id' => $data['professional_id'],
                        'clientName' => $client->name . ' ' . $client->surname,
                        'client_image' => $client->client_image ? $client->client_image . '?$' . Carbon::now() : 'comments/default.jpg',
                        'branch_id' => $data['branch_id'],
                        'data' => $car->reservation->data,
                        'attendedClient' => 1,
                        'services' => $orderServ->count(),
                        'products' => $orderPrduct->sum('cant'),
                        'totalServices' => round(($amountServ - $retention), 2),
                        'clientAleator' => $car->select_professional,
                        'amountGenerate' => round($car->amount, 2),
                        'tip' => $car->tip * 0.80,
                        'meta' => ($orderServ->count() == 1 && $amountServ == 0) ? 'Si' : 'No',
                        'selectable' => ($orderServ->count() == 1 && $amountServ == 0 && ($car->tip * 0.80) <= 0) ? false : true
                    ];
                })->sortBy(function ($car) {
                    return $car['data']; // Ordena por la propiedad 'reservationData'
                })
                ->values(); // Reindexar las claves de la colección;

            $cursesProf = CourseProfessional::where('professional_id', $data['professional_id'])->where('pay', 0)->get()->map(function ($courseProf) {
                $course = $courseProf->course;
                $totalPayment = $course->students()->sum('course_student.total_payment');
                return [
                    'id' => $courseProf->id,
                    'enrollment_id' => $course->enrollment_id,
                    'nameEnrollment' => $course->enrollment->name,
                    'nameCourse' => $course->name,
                    'price' => $course->price,
                    'description' => $course->description,
                    'startDate' => $course->startDate,
                    'endDate' => $course->endDate,
                    'totalPayment' => $totalPayment,
                ];
            });
            return response()->json(['cars' => $cars, 'courses' => $cursesProf], 200);
        } catch (\Throwable $th) {
            Log::error($th);
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
            $professional = Professional::where('id', $request->professional_id)->first();
            $branchProfessional = BranchProfessional::where('branch_id', $data['branch_id'])
                ->where('professional_id', $data['professional_id'])
                ->first();
            
            $retention = $professional->retention;;
            $products = $this->professionalPaymentService->calculateProductCommissionsNopay($data, $branchProfessional, $professional);
            $payments = $this->professionalPaymentService->calculatePayments($data);
            $cars = $this->professionalPaymentService->getServiceEarningsDetails($data, $retention);
            /*$cars = Car::where('professional_payment_id', null)
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
                    $orderPrduct = Order::where('car_id', $car->id)
                        ->where('is_product', 1)
                        ->get();
                    // Contar directamente las órdenes donde meta es 1
                    $metaCounterServ = $orderServ->sum(function ($order) {
                        return $order->meta == 1 ? 1 : 0;
                    });
                    $client = $car->clientProfessional->client;
                    $retention = $retention ? ($orderServ->sum('percent_win') * $retention) / 100 : 0;
                    $amountServ = $orderServ->sum('percent_win');
                    return [
                        'id' => $car->id,
                        'professional_id' => $data['professional_id'],
                        'clientName' => $client->name . ' ' . $client->surname,
                        'client_image' => $client->client_image ? $client->client_image . '?$' . Carbon::now() : 'comments/default.jpg',
                        'branch_id' => $data['branch_id'],
                        'data' => $car->reservation->data,
                        'attendedClient' => 1,
                        'services' => $orderServ->count(),
                        'products' => $orderPrduct->sum('cant'),
                        'totalServices' => round(($amountServ - $retention), 2),
                        'clientAleator' => $car->select_professional,
                        'amountGenerate' => round($car->amount, 2),
                        'tip' => $car->tip * 0.80,
                        'meta' => ($orderServ->count() == 1 && $amountServ == 0) ? 'Si' : 'No',
                        'metaService' => $metaCounterServ > 0 ? 'SI' : 'NO',
                        'selectable' => ($orderServ->count() == 1 && $amountServ == 0 && ($car->tip * 0.80) <= 0) ? false : true
                    ];
                })->sortBy(function ($car) {
                    return $car['data']; // Ordena por la propiedad 'reservationData'
                })
                ->values(); // Reindexar las claves de la colección;*/

            $cursesProf = CourseProfessional::where('professional_id', $data['professional_id'])->where('pay', 0)->get()->map(function ($courseProf) {
                $course = $courseProf->course;
                $totalPayment = $course->students()->sum('course_student.total_payment');
                return [
                    'id' => $courseProf->id,
                    'enrollment_id' => $course->enrollment_id,
                    'nameEnrollment' => $course->enrollment->name,
                    'nameCourse' => $course->name,
                    'price' => $course->price,
                    'description' => $course->description,
                    'startDate' => $course->startDate,
                    'endDate' => $course->endDate,
                    'totalPayment' => $totalPayment,
                ];
            });
            $coursesIds = $cursesProf->pluck('id')->toArray();
            return response()->json(['cars' => $cars['detailed_cars'], 'courses' => $cursesProf, 'coursesIds' => $coursesIds,'products' =>$products, 'payments' => $payments], 200);
        } catch (\Throwable $th) {
            Log::error($th);
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
                $tipspercent = $tips * 0.80;
                $totalRetention = $retention ? $amountGenerate * $retention / 100 : 0;
                Log::info($ServiceEspecial);

                Log::info('aqui imprimiendo - $amountGenerate - $totalRetention + $tipspercent)');
                Log::info($amountGenerate);
                Log::info($totalRetention);
                Log::info($tipspercent);
                $formattedDuration = '';

                if ($reservation->started_at && $reservation->finished_at) {
                    $durationInMinutes = $reservation->started_at->diffInMinutes($reservation->finished_at);
                    $formattedDuration = $this->formatDuration($durationInMinutes);
                }
                return [
                    'id' => $car->id,
                    'clientName' => $client->name . " " . $client->surname,
                    'client_image' => $client->client_image ? $client->client_image : 'comments/default.jpg',
                    'date' => $reservation->data . ' ' . $reservation->start_time,
                    'time' => $formattedDuration,
                    'servicesRealizated' => implode(', ', $serviceNames->toArray()),
                    'tips' =>  number_format(round($tips, 2), 2),
                    'tips80' =>  number_format(round($tipspercent, 2), 2),
                    'Services' => $orderServ->count(),
                    'totalServices' => number_format(round($orderServ->sum('price'), 2), 2),
                    'Products' => $orderProd->sum('cant'),
                    'totalProducts' => $orderProd->sum('price'),
                    'choice' => $car->select_professional ? 'Seleccionado' : 'aleatorio',
                    'serviceSpecial' => $ServiceEspecial->count(),
                    'SpecialAmount' => number_format(round($ServiceEspecial->sum('percent_win'), 2), 2),
                    'serviceRegular' => $ServiceRegular->count(),
                    'pay' => $car->professional_payment_id == null ? 0 : 1,
                    'totalRetention' => number_format(round($totalRetention, 2), 2),
                    'totalGeneral' => $car->amount,
                    'amountGenerate' => $orderServ->sum('percent_win'),
                    'metaCant' => $meta->count(),
                    'metaAmount' => $meta->sum('amount'),
                    'winPay' => number_format(round($amountGenerate - $totalRetention + $tipspercent, 2), 2),
                ];
            });
            return response()->json(['car' => $cars], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar ls ordenes"], 500);
        }
    }

    function formatDuration($minutes)
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = floor($minutes % 60);
        $seconds = round(($minutes - floor($minutes)) * 60);

        $result = '';

        if ($hours > 0) {
            $result .= $hours . ' hora' . ($hours > 1 ? 's' : '');
        }

        if ($hours > 0 && ($remainingMinutes > 0 || $seconds > 0)) {
            $result .= ' y ';
        }

        if ($remainingMinutes > 0) {
            $result .= $remainingMinutes . ' minuto' . ($remainingMinutes > 1 ? 's' : '');
        }

        if ($remainingMinutes > 0 && $seconds > 0) {
            $result .= ' y ';
        }

        if ($seconds > 0) {
            $result .= $seconds . ' segundo' . ($seconds > 1 ? 's' : '');
        }

        if ($result === '') {
            $result = '0 segundos';
        }

        return $result;
    }
    /*function formatDuration($minutes)
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        $result = '';

        if ($hours > 0) {
            $result .= $hours . ' hora' . ($hours > 1 ? 's' : '');
        }

        if ($hours > 0 && $remainingMinutes > 0) {
            $result .= ' y ';
        }

        if ($remainingMinutes > 0) {
            $result .= $remainingMinutes . ' minuto' . ($remainingMinutes > 1 ? 's' : '');
        }

        return $result;
    }*/

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
                        'amountTotal' => number_format(round($car->technical_assistance * 5000, 2), 2),
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
            Log::error($th);
            return response()->json(['msg' => $th->getMessage() . "Error al mostrar ls ordenes"], 500);
        }
    }

    public function car_order_delete_branch_ANTERIOR(Request $request)
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

                $profesionalName = $clientProfessional->professional->name;
                $clientName = $clientProfessional->client->name;

                $hora = $orderData->updated_at;
                if ($orderData->is_product == true) {
                    return [
                        'id' => $orderData->id,
                        'profesional_id' => $clientProfessional->professional_id,
                        'reservation_id' => $car->reservation->id,
                        'nameProfesional' => $profesionalName,
                        'nameClient' => $clientName,
                        'hora' => $hora->format('h:i A'),
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
            Log::error($th);
            return response()->json(['msg' => "Error al mostrar las ordenes"], 500);
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
                    $query->where('branch_id', $data['branch_id'])->where('confirmation', 4);
                })
                ->where('request_delete', true)
                ->whereDate('data', Carbon::now()->toDateString())
                ->orderBy('updated_at', 'desc')
                ->get();
            $car = $orderDatas->map(function ($orderData) {
                $car = $orderData->car;
                $clientProfessional = $car->clientProfessional;

                $profesionalName = $clientProfessional->professional->name;
                $clientName = $clientProfessional->client->name;

                $hora = $orderData->updated_at;
                if ($orderData->is_product == true) {
                    return [
                        'id' => $orderData->id,
                        'profesional_id' => $clientProfessional->professional_id,
                        'reservation_id' => $car->reservation->id,
                        'nameProfesional' => $profesionalName,
                        'nameClient' => $clientName,
                        'hora' => $hora->format('h:i A'),
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
            Log::error($th);
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
            Log::error($th);
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
            Log::error($th);
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
            Log::error($th);
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
            Log::error($th);
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



    public function car_services2_ANTERIOR(Request $request)
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
                $query->whereHas('clientProfessional', function ($query) use ($client) {
                    $query->where('client_id', $client->id);
                });
            })->orderByDesc('data')->limit(12)->get();
            if ($reservations->isEmpty()) {
                $result[] = [
                    'clientName' => $client->name,
                    'professionalName' => "Ninguno",
                    'imageLook' => $client->client_image ? $client->client_image . '?$' . Carbon::now() : 'clients/default_profile.jpg' . '?$' . Carbon::now(),
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

                $reservation = $reservations->sortByDesc('data')
                    ->filter(function ($query) {
                        return $query->confirmation == 2;
                    })
                    ->first();
                if ($reservation != null) {
                    $professional = $reservation->car->clientProfessional->professional()->withTrashed()->first();
                } else {
                    $professional = [];
                }
                $result[] = [
                    'clientName' => $client->name,
                    'professionalName' => $professional ? $professional->name : '',
                    'image_url' => $professional ? $professional->image_url : 'professionals/default_profile.jpg',
                    'imageLook' => $client->client_image ? $client->client_image . '?$' . Carbon::now() : 'clients/default_profile.jpg' . '?$' . Carbon::now(),
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
                    'clientName' => $client->name,
                    'professionalName' => "Ninguno",
                    'imageLook' => $client->client_image ? $client->client_image . '?$' . Carbon::now() : 'clients/default_profile.jpg' . '?$' . Carbon::now(),
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
                if ($reservation != null) {
                    $professional = $reservation->car->clientProfessional->professional()->withTrashed()->first();
                } else {
                    $professional = [];
                }
                $result[] = [
                    'clientName' => $client->name,
                    'professionalName' => $professional ? $professional->name : '',
                    'image_url' => $professional ? $professional->image_url : 'professionals/default_profile.jpg',
                    'imageLook' => $client->client_image ? $client->client_image . '?$' . Carbon::now() : 'clients/default_profile.jpg' . '?$' . Carbon::now(),
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
            $user = Auth::user();
            $professionalName = $user->professional ? $user->professional->name : $user->name;
            $professionalImage = $user->professional->image_url?? 'professionals/default.jpg';
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
            /*if ($car->pay == 1) {
                Finance::where('car_id', $data['id'])->delete();                
                $payment = Payment::where('car_id', $data['id'])->first();
                if ($car->tip && $payment->method == 'cash') {
                    $box = Box::whereDate('data', Carbon::now())->where('branch_id', $branch->id)->first();
                    $box->existence -= $car->tip;
                    $box->save();
                }
            }*/
            try {
                DB::transaction(function () use ($car, $data, $branch) {
                    if ($car->pay != 1) return;
                    $payment = Payment::where('car_id', $data['id'])->first();
                    // Eliminar finanzas relacionadas
                    Finance::where('car_id', $data['id'])->delete();
                    $box = Box::whereDate('data', Carbon::now())
                            ->where('branch_id', $branch->id)
                            ->firstOrFail();
                    if ($payment->cash > 0) {                
                        // Decrementar el monto del pago en efectivo
                        $box->decrement('existence', $payment->cash);
                        
                        // Opcional: Registrar el movimiento
                        Log::info("Decrementado {$payment->cash} de existence en caja", [
                            'box_id' => $box->id,
                            'payment_id' => $payment->id,
                            'car_id' => $data['id']
                        ]);
                    }
                    // Procesar propina en efectivo
                    /*if ($car->tip && $payment->method === 'cash') {

                        $box->decrement('existence', $car->tip);
                    }*/                    
                    $payment->delete();
                });
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                Log::error('Box record not found: ' . $e->getMessage());
                throw new \Exception('No se encontró la caja registradora para esta sucursal');
            } catch (\Exception $e) {
                Log::error('Payment processing error: ' . $e->getMessage());
                throw $e;
            }
            $active = $car->active;
            $notification = new Notification();
            if ($active == 3) {
                $notification->description = 'Carro: ' . $car->id . ' eliminado';
                $reservation = Reservation::where('car_id', $car->id)->first();
                $professional = Professional::find($data['professional_id']);
                if ($professional) {
                    $reservation->cause = 'Reservación eliminada desde la caja por: ' . $professional->name;
                } else {
                    $reservation->cause = 'Reservación eliminada desde la caja';
                }
                $reservation->save();
                $reservation->delete();
                if ($car->action_status !== 0) {                    
                $car->logChanges(
                    'Carro Eliminado',
                    $professionalName,
                    'delete',
                    $professionalImage
                );
                $car->save();
                }
            } elseif ($active == 2) {
                $notification->description = 'Carro: ' . $car->id . ' Aceptado a editar';
                if ($car->action_status !== 0) { 
                $car->addActionDescription(
                    actionType: 'approved',
                    description: 'Carro aprobado a editar',
                    nameProfessional: $professionalName,
                    image: $professionalImage
                );
                }
                $car->active = 1;
                $car->pay = 0;
                $car->tip = 0;
                $car->save();
            }
            $notification->professional_id = $data['professional_id'];
            $notification->tittle = 'Aceptada';
            $notification->type = 'Caja';
            $branch->notifications()->save($notification);
            
            // $car->delete();
            //$car->delete();
            return response()->json(['msg' => 'Carro eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => $th->getMessage() . 'Error al eliminar el carro'], 500);
        }
    }

    public function destroy_denegada(Request $request)
    {
        Log::info("Denegar solicitud de edición o eliminación");
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'professional_id' => 'nullable'
            ]);
            $user = Auth::user();
            $professionalName = $user->professional ? $user->professional->name : $user->name;
            $professionalImage = $user->professional->image_url?? 'professionals/default.jpg';
            $car = Car::find($data['id']);
            $branch = Branch::where('id', $car->reservation->branch_id)->first();
            $active = $car->active;
            
            $description = '';
            if ($active == 3){                
                $description = 'Solicitud de eliminación denegada';
            }
            if ($active == 2){                
                $description = 'Solicitud de edición denegada';
                }
            $car->addActionDescription(
                actionType: 'denied',
                description: $description,
                nameProfessional: $professionalName,
                image: $professionalImage
            );
            $car->action_status = 0;
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
            if ($active == 3) {
                $notification->description = 'Carro : ' . $car->id . ' denegado a eliminar'; # code...
            } elseif ($active == 2) {
                $notification->description = 'Carro : ' . $car->id . ' denegado a editar'; # code...
            }
            $notification->professional_id = $data['professional_id'];
            $notification->tittle = 'Denegada';
            $notification->type = 'Caja';
            $branch->notifications()->save($notification);
            //$car->delete();
            return response()->json(['msg' => 'Solicitud denegada correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => 'Error al eliminar el carro'], 500);
        }
    }

    public function update_solicitud(Request $request)
    {
        Log::info("Editar carro");
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'professional_id' => 'nullable'
            ]);
            $car = Car::find($data['id']);
            $branch = Branch::where('id', $request->branch_id)->first();
            Log::info("Request de editar car");
            Log::info($request);
            $client = $car->clientProfessional->client;
            $professional = $car->clientProfessional->professional;
            $user = Auth::user();
            $professionalImage = $user->professional ? $user->professional->image_url : 'professionals/default.jpg';
            $trace = [
                'branch' => $branch->name,
                'cashier' => $request->nameProfessional,
                'client' => $client->name,
                'amount' => $car->amount,
                'operation' => 'Hace solicitud de editar carro: ' . $car->id,
                'details' => $request->description,
                'description' => $professional->name,
                'car_id' => $data['id']
            ];
            $this->traceService->store($trace);
            $car->addActionDescription(
                actionType: 'edit',
                description: $request->description,
                nameProfessional: $request->nameProfessional,
                image: $professionalImage

            );
            $car->active = $request->active;
            $car->action_status = 1;
            $car->save();
            /* $administradores = BranchProfessional::where('branch_id', $branch->id)->whereHas('professional.charge', function ($query){
            $query->where('name', 'Administrador de Sucursal');
        })->get('professional_id');
            if(!$administradores->isEmpty()){
                foreach ($administradores as $administrador) {  */
            $notification = new Notification();
            $notification->professional_id = $data['professional_id'];
            $notification->tittle = 'Solicitud';
            $notification->description = 'Solicitud de edición del carro: ' . $car->id;
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
            $user = Auth::user();
            $professionalImage = $user->professional ? $user->professional->image_url : 'professionals/default.jpg';
            $client = $car->clientProfessional->client;
            $professional = $car->clientProfessional->professional;
            $trace = [
                'branch' => $branch->name,
                'cashier' => $request->nameProfessional,
                'client' => $client->name,
                'amount' => $car->amount,
                'operation' => 'Hace solicitud de eliminar carro: ' . $car->id,
                'details' => $request->description ?? ' ',
                'description' => $professional->name,
                'car_id' => $data['id']
            ];
            $this->traceService->store($trace);
            $car->addActionDescription(
                actionType: 'delete',
                description: $request->description ?? ' ',
                nameProfessional: $request->nameProfessional,
                image: $professionalImage
            );
            $car->active = 3;
            $car->action_status = 2;
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
