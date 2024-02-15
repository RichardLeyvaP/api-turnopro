<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Order;
use App\Models\Professional;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use function PHPSTORM_META\map;

class ProfessionalService
{
    public function store($data)
    {
        $professional = new Professional();
        $professional->name = $data['name'];
        $professional->surname = $data['surname'];
        $professional->second_surname = $data['second_surname'];
        $professional->email = $data['email'];
        $professional->phone = $data['phone'];
        $professional->charge_id = $data['charge_id'];
        $professional->user_id = $data['user_id'];
        $professional->image_url = $data['image_url'];
        $professional->state = 0;
        $professional->save();
        return $professional;
    }

    public function professionals_branch($branch_id, $professional_id)
    {
        $professionals = Professional::whereHas('branches', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
           })->find($professional_id);
                      
           $dataUser = [];
           if ($professionals) {
                $date = Carbon::now();
                $dataUser['id'] = $professionals->id;
                $dataUser['usuario'] = $professionals->name;
                $dataUser['fecha'] = $date->toDateString();
                $dataUser['hora'] = $date->Format('g:i:s A');
           }

           return $dataUser;
    }

    public function branch_professionals($branch_id)
    {
        return $professionals = Professional::whereHas('branches', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
           })->get();
    }

    public function get_professionals_service($data)
    {
        return $professionals = Professional::whereHas('branchServices', function ($query) use ($data) {
            $query->where('branch_id', $data['branch_id'])->where('service_id', $data['service_id']);
        })->select('id', 'name','surname','second_surname')->get();
    }

    public function professionals_ganancias($data)
    {
        $startDate = Carbon::parse($data['startDate']);
           $endDate = Carbon::parse($data['endDate']);
          $dates = [];
          $i=0;
          $day = $data['day']-1;//en $day = 1 es Lunes,$day=2 es Martes...$day=7 es Domingo, esto e spara el front
        
          $cars = Car::whereHas('clientProfessional', function ($query) use ($data){
            $query->where('professional_id', $data['professional_id'])->whereHas('professional.branches', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            });
            })->whereHas('orders', function ($query) use ($data){
                $query->whereBetween('data', [$data['startDate'], Carbon::parse($data['endDate'])->addDay()]);
            })->with('orders')->get()->map(function ($car){
                return [
                    'date' => $car->orders->value('data'),
                    'earnings' => $car->amount
                ];
            });
            for($date = $startDate; $date->lte($endDate);$date->addDay()){
            $machingResult = $cars->where('date', $date->toDateString())->sum('earnings');
            $dates[$i]['date'] = $date->toDateString();

            $day += 1;
            $dates[$i]['day_week'] = $day;
            if($day == 7)
            $day = 0;
           
            $dates[$i++]['earnings'] = $machingResult ? $machingResult: 0;
          }
          $result = [
            'dates' => $dates,
            'totalEarnings' => $cars->sum('earnings'),
            'averageEarnings' => $cars->avg('earnings')
          ];
           return $result;
    }

    public function professionals_ganancias_branch_date($data)
    {
        Log::info('Obtener los cars');
        $cars = Car::whereHas('clientProfessional', function ($query) use ($data){
            $query->where('professional_id', $data['professional_id'])->whereHas('professional.branches', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            });
        })->whereHas('orders', function ($query){
            $query->whereDate('data', Carbon::now());
        })->get();
       $services =0;
       $products =0;
       $totalClients =0;
        foreach ($cars as $car) {
            $services = $services + count($car->orders->where('is_product', 0));
            $products = $products + count($car->orders->where('is_product', 1));         
        }
        $orders = Order::whereHas('branchServiceProfessional', function ($query) use ($data){
            $query->whereHas('branchService', function ($query) use ($data){
                $query->WhereHas('service', function ($query){
                    $query->where('type_service', 'Especial');
                })->where('branch_id', $data['branch_id']);
            })->where('professional_id', $data['professional_id']);
        })->whereDate('data', Carbon::now())->get();
        $totalClients = $cars->count();
          return $result = [
            'Monto Generado' => round($cars->sum('amount'), 2),
            'Propina' => round($cars->sum('tip'), 2),
            'Propina 80%' => round($cars->sum('tip')*0.8, 2),
            'Procentaje de Ganancia' =>45,
            'Servicios Realizados' => $services,  
            'Productos Vendidos' => $products,           
            'Servicios Regulares' => $services - $orders->count(),
            'Servicios Especiales' => $orders->count(),
            'Monto Especial' => round($orders->sum('price'), 2),
            'Ganancia Barbero' => round($cars->sum('amount')*0.45, 2),
            'Ganancia Total Barbero' => round($cars->sum('amount')*0.45 + $cars->sum('tip')*0.8, 2),
            'Clientes Atendidos' => $totalClients,
            'Seleccionado' => $cars->where('select_professional', 1)->count(),
            'Aleatorio' => $cars->where('select_professional', 0)->count()
          ];
    }

    public function professionals_ganancias_branch_Periodo($data, $startDate, $endDate)
    {
        Log::info('Obtener los cars');
        $cars = Car::whereHas('clientProfessional', function ($query) use ($data){
            $query->where('professional_id', $data['professional_id'])->whereHas('professional.branches', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            });
        })->whereHas('orders', function ($query) use ($startDate, $endDate){
            $query->whereBetWeen('data', [$startDate, $endDate]);
        })->get();
       $services =0;
       $products =0;
       $totalClients =0;
        foreach ($cars as $car) {
            $services = $services + count($car->orders->where('is_product', 0));
            $products = $products + count($car->orders->where('is_product', 1));         
        }
        $orders = Order::whereHas('branchServiceProfessional', function ($query) use ($data){
            $query->whereHas('branchService', function ($query) use ($data){
                $query->WhereHas('service', function ($query){
                    $query->where('type_service', 'Especial');
                })->where('branch_id', $data['branch_id']);
            })->where('professional_id', $data['professional_id']);
        })->whereBetWeen('data', [$startDate, $endDate])->get();
        $totalClients = $cars->count();
          return $result = [
            'Monto Generado' => round($cars->sum('amount'), 2),
            'Propina' => round($cars->sum('tip'), 2),
            'Propina 80%' => round($cars->sum('tip')*0.8, 2),
            'Procentaje de Ganancia' =>45,
            'Servicios Realizados' => $services,  
            'Productos Vendidos' => $products,           
            'Servicios Regulares' => $services - $orders->count(),
            'Servicios Especiales' => $orders->count(),
            'Monto Especial' => round($orders->sum('price'), 2),
            'Ganancia Barbero' => round($cars->sum('amount')*0.45, 2),
            'Ganancia Total Barbero' => round($cars->sum('amount')*0.45 + $cars->sum('tip')*0.8, 2),
            'Clientes Atendidos' => $totalClients,
            'Seleccionado' => $cars->where('select_professional', 1)->count(),
            'Aleatorio' => $cars->where('select_professional', 0)->count()
          ];
    }

    public function professionals_ganancias_branch_month($data, $mes, $year)
    {
        Log::info('Obtener los cars');
        $cars = Car::whereHas('clientProfessional', function ($query) use ($data){
            $query->where('professional_id', $data['professional_id'])->whereHas('professional.branches', function ($query) use ($data){
                $query->where('branch_id', $data['branch_id']);
            });
        })->whereHas('orders', function ($query) use ($mes, $year){
            $query->whereMonth('data', $mes)->whereYear('data', $year);
        })->get();
       $services =0;
       $products =0;
       $totalClients =0;
        foreach ($cars as $car) {
            $services = $services + count($car->orders->where('is_product', 0));
            $products = $products + count($car->orders->where('is_product', 1));         
        }
        $orders = Order::whereHas('branchServiceProfessional', function ($query) use ($data){
            $query->whereHas('branchService', function ($query) use ($data){
                $query->WhereHas('service', function ($query){
                    $query->where('type_service', 'Especial');
                })->where('branch_id', $data['branch_id']);
            })->where('professional_id', $data['professional_id']);
        })->whereMonth('data', $mes)->whereYear('data', $year)->get();
        Log::info($orders);
        $totalClients = $cars->count();
          return $result = [
            'Monto Generado' => round($cars->sum('amount'), 2),
            'Propina' => round($cars->sum('tip'), 2),
            'Propina 80%' => round($cars->sum('tip')*0.8, 2),
            'Procentaje de Ganancia' =>45,
            'Servicios Realizados' => $services,  
            'Productos Vendidos' => $products,           
            'Servicios Regulares' => $services - $orders->count(),
            'Servicios Especiales' => $orders->count(),
            'Monto Especial' => round($orders->sum('price'), 2),
            'Ganancia Barbero' => round($cars->sum('amount')*0.45, 2),
            'Ganancia Total Barbero' => round($cars->sum('amount')*0.45 + $cars->sum('tip')*0.8, 2),
            'Clientes Atendidos' => $totalClients,
            'Seleccionado' => $cars->where('select_professional', 1)->count(),
            'Aleatorio' => $cars->where('select_professional', 0)->count()
          ];
    }

}