<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Business;
use App\Models\Car;
use App\Models\ClientProfessional;
use App\Models\Order;
use App\Models\Product;
use App\Models\Professional;
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
            Log::info( "Entra a buscar los carros");
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
            Log::info( "Entra a buscar una las reservaciones del dia");
            Log::info(now()->toDateString());
            $data = $request->validate([
                'business_id' => 'required|numeric',
                'branch_id' => 'nullable'
            ]);
            Log::info($data['branch_id']);
            if($data['branch_id'] !=0){
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
            
                return response()->json($cars->sum('amount')+$cars->sum('tip')+$cars->sum('technical_assistance')*5000, 200, [], JSON_NUMERIC_CHECK);
            }else{      
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
            $cars = Car::whereHas('reservations', function ($query){
                $query->whereDate('data', now()->toDateString());
            });            
            return response()->json($cars->sum('amount')+$cars->sum('tip')+$cars->sum('technical_assistance')*5000, 200, [], JSON_NUMERIC_CHECK);
            }
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las reservaciones"], 500);
        }
    }

    public function cars_sum_amount_week(Request $request)
    {
        try {             
            Log::info( "Entra a buscar una las reservations del dia");
            $data = $request->validate([
                'business_id' => 'required|numeric',
                'branch_id' => 'nullable'
            ]);           
            $array = [];
                        $start = now()->startOfWeek(); // Start of the current week, shifted to Monday
                        $end = now()->endOfWeek();   // End of the current week, shifted to Sunday
                        $dates = [];
                        //return [$start, $end];
          $i=0;
          $day = 0;//en $day = 1 es Lunes,$day=2 es Martes...$day=7 es Domingo, esto e spara el front
                        if ($data['branch_id'] !=0) {
                            Log::info('branchesssss');
                            $cars = Car::whereHas('reservations', function ($query) use ($start, $end, $data){
                                $query->whereDate('data', '>=', $start)->whereDate('data', '<=', $end)->where('branch_id', $data['branch_id']);
                            })->get()->map(function ($car){
                                    return [
                                        'date' => $car->reservations->data,
                                        'earnings' => $car->amount + $car->tip + ($car->technical_assistance * 5000)
                                    ];
                                });
                                for($date = $start, $i = 0; $date->lte($end); $date->addDay(), $i++){
                                    $machingResult = $cars->where('date', $date->toDateString())->sum('earnings');
                                    //$dates['amount'][$i] = $machingResult ? $machingResult: 0;
                                    $dates[$i] = $machingResult ? $machingResult: 0;
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
                            $cars = Car::whereHas('reservations', function ($query) use ($start, $end){
                                $query->whereBetween('data', [$start, $end]);
                            })->get()->map(function ($car){
                                    return [
                                        'date' => $car->reservations->data,
                                        'earnings' => $car->amount + $car->tip + ($car->technical_assistance * 5000)
                                    ];
                                });
                                for($date = $start, $i = 0; $date->lte($end); $date->addDay(), $i++){
                                    $machingResult = $cars->where('date', $date->toDateString())->sum('earnings');
                                    //$dates['amount'][$i] = $machingResult ? $machingResult: 0;
                                    $dates[$i] = $machingResult ? $machingResult: 0;
                                  }
                                  return $dates;
                        }
                        
        
        //Log::info($branches);
            
            return response()->json( $array, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las reservaciones"], 500);
        }
    }

    public function cars_sum_amount_mounth(Request $request)
    {
        try {             
            Log::info( "Entra a buscar una las ganancias del mes");
            $data = $request->validate([
                'business_id' => 'required|numeric',
                'branch_id' => 'nullable'
            ]);
            if($data['branch_id'] !=0){
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
                $startOfMonth = now()->startOfMonth()->toDateString();
                    $endOfMonth = now()->endOfMonth()->toDateString();
                $cars = Car::whereHas('reservation', function ($query) use ($data, $startOfMonth, $endOfMonth) {
                    $query->where('branch_id', $data['branch_id'])->whereDate('data', '>=', $startOfMonth)->whereDate('data', '<=', $endOfMonth);
                });
            
                return response()->json($cars->sum('amount')+$cars->sum('tip')+$cars->sum('technical_assistance')*5000, 200, [], JSON_NUMERIC_CHECK);
            }else{
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
            $startOfMonth = now()->startOfMonth()->toDateString();
                    $endOfMonth = now()->endOfMonth()->toDateString();
                $cars = Car::whereHas('reservations', function ($query) use ($startOfMonth, $endOfMonth){
                    $query->whereDate('data', '>=', $startOfMonth)->whereDate('data', '<=', $endOfMonth);
                });
            
                return response()->json($cars->sum('amount')+$cars->sum('tip')+$cars->sum('technical_assistance')*5000, 200, [], JSON_NUMERIC_CHECK);
            }
            //return response()->json($branches->sum(), 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las reservaciones"], 500);
        }
    }

    public function branch_cars(Request $request)
    {
        try {    
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);         
            Log::info( "Esta es request");
            Log::info( $request);
            Log::info( "Entra a buscar los carros");
            $branch = Branch::where('id',$data['branch_id'])->first();
            Log::info( "Esta es la sucursal");
            Log::info( $branch);
            $cars = $branch->cars()->with(['clientProfessional.client', 'clientProfessional.professional', 'payment'])->whereHas('reservation', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now());
            })->get()->map(function ($car) use ($data){
                $client = $car->clientProfessional->client;
                $professional = $car->clientProfessional->professional;
                $products = $car->orders->where('is_product', 1)->sum('price');
                $services = $car->orders->where('is_product', 0)->sum('price');
                return [
                    'id' => $car->id,
                    'client_professional_id' => $car->client_professional_id,
                    'amount' => $car->amount + ($car->technical_assistance * 5000) + $car->tip,
                    'tip' => $car->tip,
                    'pay' => $car->pay,
                    'active' => $car->active,
                    'product' => $products,
                    'service' => $services,
                    'technical_assistance' => $car->technical_assistance * 5000,
                    'clientName' => $client->name.' '.$client->surname.' '.$client->second_surname,
                    'professionalName' => $professional->name.' '.$professional->surname.' '.$professional->second_surname,
                    'client_image' => $client->client_image,
                    'professional_id' => $professional->id,
                    'image_url' => $professional->image_url,
                    'payment' => $car->payment

                ];
                //}
            });
            return response()->json(['cars' => $cars], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()."Error al mostrar los carros"], 500);
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
           $orderProductsDatas = Order::with('car.clientProfessional')->whereRelation('car', 'id', '=', $data['id'])->where('is_product', true)->orderBy('data', 'desc')->get();
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
                });
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
           $retention = Professional::where('id', $data['professional_id'])->first()->retention/100;
           $cars = Car::whereHas('reservation', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
           })->whereHas('clientProfessional', function ($query) use ($data){
            $query->where('professional_id', $data['professional_id']);
           })->get()->map(function($car) use ($retention){
                $ordersServices = count($car->orders->where('is_product', 0));
                return [
                    'professional_id' => $car->clientProfessional->professional->id,
                    'branch_id' => $car->reservation->branch_id,
                    'data' => $car->reservation->data,
                    'day_of_week' => ucfirst(mb_strtolower(Carbon::parse($car->reservation->data)->locale('es_ES')->isoFormat('dddd'))), // Obtener el día de la semana en español y en mayúscula
                    'attendedClient' => 1,
                    'services' => $ordersServices,
                    'totalServices' => $retention ? $car->orders->sum('percent_win') - $car->orders->sum('percent_win') * ($retention/100) : $car->orders->sum('percent_win'),
                    'clientAleator' => $car->select_professional,
                    'amountGenerate' => $car->amount + $car->tip
                ];
           })->groupBy('data')->map(function ($cars) {
            return [
                'professional_id' => $cars[0]['professional_id'],
                'branch_id' => $cars[0]['branch_id'],
                'data' => $cars[0]['data'],
                'day_of_week' => $cars[0]['day_of_week'], // Mantener el día de la semana
                'attendedClient' => $cars->sum('attendedClient'),
                'services' => $cars->sum('services'),
                'totalServices' => $cars->sum('totalServices'),
                'clientAleator' => $cars[0]['clientAleator'],
                'amountGenerate' => $cars->sum('amountGenerate')
            ];
        })->values();
           return response()->json(['car' => $cars], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Error al mostrar ls ordenes"], 500);
       }
    }

    public function professional_car_notpay(Request $request)
    {
        try {
            $data = $request->validate([
               'professional_id' => 'required|numeric',
               'branch_id' => 'required|numeric'
           ]);
           $retention = Professional::where('id', $data['professional_id'])->first()->retention/100;
           $cars = Car::where('professional_payment_id', Null)->whereHas('reservation', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id']);
           })->whereHas('clientProfessional', function ($query) use ($data){
            $query->where('professional_id', $data['professional_id']);
           })->get()->map(function($car) use ($retention){
                $ordersServices = count($car->orders->where('is_product', 0));
                return [
                    'id' => $car->id,
                    'professional_id' => $car->clientProfessional->professional->id,
                    'clientName' => $car->clientProfessional->client->name.' '.$car->clientProfessional->client->surname,
                    'client_image' => $car->clientProfessional->client->client_image ? $car->clientProfessional->client->client_image : 'comments/default.jpg',
                    'branch_id' => $car->reservation->branch_id,
                    'data' => $car->reservation->data,
                    'attendedClient' => 1,
                    'services' => $ordersServices,
                    'totalServices' => $retention ? $car->orders->sum('percent_win') - $car->orders->sum('percent_win') * ($retention/100) : $car->orders->sum('percent_win'),
                    'clientAleator' => $car->select_professional,
                    'amountGenerate' => $car->amount + $car->tip,
                    'tip' => $car->tip * 0.80
                ];
           });
           return response()->json($cars, 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Error al mostrar ls ordenes"], 500);
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
           $retention = Professional::where('id', $data['professional_id'])->first()->retention/100;
           $cars = Car::whereHas('reservation', function ($query) use ($data){
            $query->where('branch_id', $data['branch_id'])->whereDate('data', $data['data']);
           })->whereHas('clientProfessional', function ($query) use ($data){
            $query->where('professional_id', $data['professional_id']);
           })->get()->map(function($car) use ($retention){
            $serviceNames = $car->orders->where('is_product', 0)->pluck('branchServiceProfessional.branchService.service.name')->values();
                $ServicesSpecial = Order::where('car_id', $car->id)->whereHas('branchServiceProfessional', function ($query){
                    $query->where('type_service','Especial');
                })->get();
                return [
                    'id' => $car->id,
                    'clientName' => $car->clientProfessional->client->name." ".$car->clientProfessional->client->surname,
                    'client_image' => $car->clientProfessional->client->client_image ? $car->clientProfessional->client->client_image : 'comments/default.jpg',                        
                    'data' => $car->reservation->data.' '.$car->reservation->start_time,
                    'time' => $car->reservation->total_time,
                    'servicesRealizated' => implode(', ', $serviceNames->toArray()),
                    'amountTotal' => $car->orders->where('is_product', 0)->sum('price'),
                    'amountWin' => $retention ? $car->orders->sum('percent_win') - $car->orders->sum('percent_win') * ($retention/100) : $car->orders->sum('percent_win'),
                    'choice' => $car->select_professional ? 'Seleccionado' : 'aleatorio',
                    'serviceSpecial' => $ServicesSpecial->count(),
                    'SpecialAmount' => $ServicesSpecial->sum('percent_win')
                    /*'attendedClient' => 1,
                    'services' => $ordersServices,
                    'totalServices' => $car->orders->sum('percent_win'),
                    'clientAleator' => $car->select_professional,
                    'amount' => $car->amount + $car->tip*/
                ];
           });
           return response()->json(['car' => $cars], 200);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Error al mostrar ls ordenes"], 500);
       }
    }

    public function car_order_delete_branch(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
    
                $orderDatas = Order::whereHas('car.reservation', function ($query) use ($data){
                    $query->where('branch_id', $data['branch_id']);
                })->where('request_delete', true)->whereDate('data', Carbon::now()->toDateString())->orderBy('updated_at', 'desc')->get();

           $car = $orderDatas->map(function ($orderData){
            if ($orderData->is_product == true) {
                return [
                    'id' => $orderData->id,
                    'profesional_id' => $orderData->car->clientProfessional->professional->id,
                    'reservation_id' => $orderData->car->reservation->id,
                    'nameProfesional' => $orderData->car->clientProfessional->professional->name.' '.$orderData->car->clientProfessional->professional->surname.' '.$orderData->car->clientProfessional->client->second_surname,
                    'nameClient' => $orderData->car->clientProfessional->client->name.' '.$orderData->car->clientProfessional->client->surname.' '.$orderData->car->clientProfessional->client->second_surname,
                    'hora' => $orderData->updated_at->Format('g:i:s A'),                    
                    'nameProduct' => $orderData->productStore->product->name,
                    'nameService' => null,
                    'duration_service' => null,
                    'is_product' => $orderData->is_product,
                    'updated_at' => $orderData->updated_at->toDateString()
                ];
            }
            else {
                return [
                    'id' => $orderData->id,
                    'profesional_id' => $orderData->car->clientProfessional->professional->id,
                    'reservation_id' => $orderData->car->reservation->id,
                    'nameProfesional' => $orderData->car->clientProfessional->professional->name.' '.$orderData->car->clientProfessional->professional->surname.' '.$orderData->car->clientProfessional->client->second_surname,
                    'nameClient' => $orderData->car->clientProfessional->client->name.' '.$orderData->car->clientProfessional->client->surname.' '.$orderData->car->clientProfessional->client->second_surname,
                    'hora' => $orderData->updated_at->Format('g:i:s A'),
                    'nameProduct' => null,
                    'nameService' => $orderData->branchServiceProfessional->branchService->service->name,
                    'duration_service' => $orderData->branchServiceProfessional->branchService->service->duration_service,
                    'is_product' => (int)$orderData->is_product,
                    'updated_at' => $orderData->updated_at->toDateString()
                ];
            }
           });
    
            return response()->json(['carOrderDelete' => $car], 200);
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
                $orderDatas = Order::whereHas('car.clientProfessional', function ($query) use ($data){
                    $query->where('professional_id', $data['professional_id']);
                })->whereHas('car.reservation', function ($query) use ($data){
                    $query->where('branch_id', $data['branch_id']);
                })->where('request_delete', true)->whereDate('data', Carbon::now()->toDateString())->orderBy('updated_at', 'desc')->get();

           $car = $orderDatas->map(function ($orderData){
            if ($orderData->is_product == true) {
                return [
                    'id' => $orderData->id,
                    'nameProfesional' => $orderData->car->clientProfessional->professional->name.' '.$orderData->car->clientProfessional->professional->surname,
                    'nameClient' => $orderData->car->clientProfessional->client->name.' '.$orderData->car->clientProfessional->client->surname,
                    'hora' => $orderData->updated_at->Format('g:i:s A'),                    
                    'nameProduct' => $orderData->productStore->product->name,
                    'nameService' => null,
                    'is_product' => $orderData->is_product,
                    'updated_at' => $orderData->updated_at->toDateString()
                ];
            }
            else {
                return [
                    'id' => $orderData->id,
                    'nameProfesional' => $orderData->car->clientProfessional->professional->name.' '.$orderData->car->clientProfessional->professional->surname,
                    'nameClient' => $orderData->car->clientProfessional->client->name.' '.$orderData->car->clientProfessional->client->surname,
                    'hora' => $orderData->updated_at->Format('g:i:s A'),
                    'nameProduct' => null,
                    'nameService' => $orderData->branchServiceProfessional->branchService->service->name,
                    'is_product' => (int)$orderData->is_product,
                    'updated_at' => $orderData->updated_at->toDateString()
                ];
            }
           });
    
            return response()->json(['carOrderDelete' => $car], 200);
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
            return response()->json(['car' => $car], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el carrito"], 500);
        }
    }

    public function reservation_services(Request $request)
    {
        try {             
            Log::info( "Entra a buscar las reservaciones y los servicios de un cliente con un profesional");
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'client_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            
            $client_professional_id = ClientProfessional::where('professional_id', $data['professional_id'])->where('client_id', $data['client_id'])->value('id');
            $orderServicesDatas = Order::whereHas('car.reservation')->whereRelation('car', 'client_professional_id', '=', $client_professional_id)->where('is_product', false)->orderBy('updated_at', 'desc')->get();
            $services = $orderServicesDatas->map(function ($orderData){
                return [
                      'data_reservation' => $orderData->car->reservations->data,
                      'nameService' => $orderData->branchServiceProfessional->branchService->service->name,
                      'simultaneou' => $orderData->branchServiceProfessional->branchService->service->simultaneou,
                      'price_service' => $orderData->branchServiceProfessional->branchService->service->price_service,
                      'type_service' => $orderData->branchServiceProfessional->branchService->service->type_service,
                      'profit_percentaje' => $orderData->branchServiceProfessional->branchService->service->profit_percentaje,
                      'duration_service' => $orderData->branchServiceProfessional->branchService->service->duration_service,
                      'image_service' => $orderData->branchServiceProfessional->branchService->service->image_service
                      ];
                  });
            
            return response()->json(['services' => $services], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las reservaciones"], 500);
			
        }
    }
    public function car_services(Request $request)
    {
        try {             
            Log::info( "Entra a buscar las reservaciones y los servicios de un cliente con un profesional");
            $data = $request->validate([
                'car_id' => 'required|numeric'
            ]);
            
            $orderServicesDatas = Order::whereHas('car.reservation')->whereRelation('car', 'id', '=', $data['car_id'])->where('is_product', false)->get();
            $services = $orderServicesDatas->map(function ($orderData){
                return [
                     'name' => $orderData->branchServiceProfessional->branchService->service->name,
                      'simultaneou' => $orderData->branchServiceProfessional->branchService->service->simultaneou,
                      'price_service' => $orderData->branchServiceProfessional->branchService->service->price_service,
                      'type_service' => $orderData->branchServiceProfessional->branchService->service->type_service,
                      'profit_percentaje' => $orderData->branchServiceProfessional->branchService->service->profit_percentaje,
                      'duration_service' => $orderData->branchServiceProfessional->branchService->service->duration_service,
                      'image_service' => $orderData->branchServiceProfessional->branchService->service->image_service,
                      'description' => $orderData->branchServiceProfessional->branchService->service->service_comment
                      ];
                  });
            
            return response()->json(['services' => $services], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las reservaciones"], 500);
			
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
        try{
        $data = $request->validate([
            'id' => 'required|numeric'
        ]);
        
        $car = Car::find($data['id']);
        $branch = Branch::where('id', $request->branch_id)->first();
        $trace = [
            'branch' => $branch->name,
            'cashier' => $request->nameProfessional,
            'client' => $car->clientProfessional->client->name.' '.$car->clientProfessional->client->surname.' '.$car->clientProfessional->client->second_surname,
            'amount' => $car->amount,
            'operation' => 'Elimina Carro',
            'details' => '',
            'description' => ''
        ];
        $this->traceService->store($trace);
            //$car->delete();
            return response()->json(['msg' => 'Carro eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => 'Error al eliminar el carro'], 500);
        }
    }
}
