<?php

namespace App\Services;
use App\Models\Business;
use App\Models\Car;
use App\Models\Order;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Professional;
use App\Models\Service;
use Illuminate\Support\Facades\Log;

class BusinessService
{
    public function business_winner_month($month, $year)
    {
            $business = Business::all();
           $result = [];
           $i = 0;
           $total_busine = 0;
           $total_branch = 0;
           $total_tip = 0;
           $technical_assistance = 0;
           foreach ($business as $busine) {
            $cars = Car::/*whereHas('reservation.branch', function ($query) use ($busine){
                    $query->where('business_id', $busine->id);
            })->*/whereHas('reservation', function ($query) use ($month, $year){
                $query->whereMonth('data', $month)->whereYear('data', $year);
                })->where('pay', 1)->get()->map(function ($car){
                    return [
                        'earnings' => $car->amount,
                        'technical_assistance' => $car->technical_assistance * 5000,
                        'tip' => $car->tip,
                        'total' => $car->amount + $car->tip + $car->technical_assistance * 5000
                    ];
                });
                $result[$i]['name'] = $busine->name;
                $result[$i]['earnings'] = round($cars->sum('earnings'),2);
                $result[$i]['technical_assistance'] = round($cars->sum('technical_assistance'), 2);
                $result[$i]['tip'] = round($cars->sum('tip'), 2);
                $result[$i++]['total'] = round($cars->sum('total'), 2);
                $total_tip += round($cars->sum('tip'),2);
                $total_branch += round($cars->sum('earnings'),2);
                $total_busine += round($cars->sum('total'), 2);
                $technical_assistance += round($cars->sum('technical_assistance'), 2);
            }//foreach
            $result[$i]['name'] = 'Total';
            $result[$i]['tip'] = $total_tip;
            $result[$i]['earnings'] = $total_branch;
            $result[$i]['technical_assistance'] = $technical_assistance;
            $result[$i++]['total'] = $total_busine;
          return $result;
    }

    public function business_winner_periodo($startDate ,$endDate)
    {
        $business = Business::all();
           $result = [];
           $i = 0;
           $total_busine = 0;
           $total_branch = 0;
           $total_tip = 0;
           $technical_assistance = 0;
           foreach ($business as $busine) {
            $cars = Car::/*whereHas('reservation.branch', function ($query) use ($busine){
                    $query->where('business_id', $busine->id);
            })->*/whereHas('reservation', function ($query) use ($startDate ,$endDate){
                $query->whereDate('data', '>=', $startDate)->whereDate('data', '<=', $endDate);//$query->whereBetWeen('data', [$startDate ,$endDate]);
                })->where('pay', 1)->get()->map(function ($car){
                    return [
                        'earnings' => $car->amount,
                        'technical_assistance' => $car->technical_assistance * 5000,
                        'tip' => $car->tip,
                        'total' => $car->amount + $car->technical_assistance * 5000
                    ];
                });
                $result[$i]['name'] = $busine->name;
                $result[$i]['earnings'] = round($cars->sum('earnings'),2);
                $result[$i]['technical_assistance'] = round($cars->sum('technical_assistance'), 2);
                $result[$i]['tip'] = round($cars->sum('tip'), 2);
                $result[$i++]['total'] = round($cars->sum('total'), 2);
                $total_tip += round($cars->sum('tip'),2);
                $total_branch += round($cars->sum('earnings'),2);
                $total_busine += round($cars->sum('total'), 2);
                $technical_assistance += round($cars->sum('technical_assistance'), 2);
            }//foreach
            $result[$i]['name'] = 'Total';
            $result[$i]['tip'] = $total_tip;
            $result[$i]['earnings'] = $total_branch;
            $result[$i]['technical_assistance'] = $technical_assistance;
            $result[$i++]['total'] = $total_busine;
          return $result;
    }

    public function business_winner_date()
    {
        $business = Business::all();
           $result = [];
           $i = 0;
           $total_busine = 0;
           $total_tip = 0;
           $total_branch = 0;
           $technical_assistance = 0;
           $data= Carbon::now()->toDateString();
           foreach ($business as $busine) {
            $cars = Car::/*whereHas('reservation.branch', function ($query) use ($busine){
                    $query->where('branch_id', $busine->id);
            })->*/whereHas('reservation', function ($query) use ($data){
                $query->whereDate('data', $data);
                })->where('pay', 1)->get()->map(function ($car){
                    return [
                        'earnings' => $car->amount,
                        'technical_assistance' => $car->technical_assistance * 5000,
                        'tip' => $car->tip,
                        'total' => $car->amount + $car->technical_assistance * 5000
                    ];
                });
                $result[$i]['name'] = $busine->name;
                $result[$i]['earnings'] = round($cars->sum('earnings'),2);
                $result[$i]['technical_assistance'] = round($cars->sum('technical_assistance'), 2);
                $result[$i]['tip'] = round($cars->sum('tip'), 2);
                $result[$i++]['total'] = round($cars->sum('total'), 2);
                $total_tip += round($cars->sum('tip'),2);
                $total_branch += round($cars->sum('earnings'),2);
                $total_busine += round($cars->sum('total'), 2);
                $technical_assistance += round($cars->sum('technical_assistance'), 2);
            }//foreach
            $result[$i]['name'] = 'Total';
            $result[$i]['tip'] = $total_tip;
            $result[$i]['earnings'] = $total_branch;
            $result[$i]['technical_assistance'] = $technical_assistance;
            $result[$i++]['total'] = $total_busine;
          return $result;
    }

}