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
           foreach ($business as $busine) {
            $cars = Car::whereHas('clientProfessional.professional.branches', function ($query) use ($busine){
                    $query->where('business_id', $busine->id);
            })->whereHas('orders', function ($query) use ($month, $year){
                $query->whereMonth('data', $month)->whereYear('data', $year);
                })->get()->map(function ($car){
                    return [
                        'earnings' => $car->amount,
                        'tip' => $car->tip,
                        'total' => $car->amount + $car->tip
                    ];
                });
                $result[$i]['name'] = $busine->name;
                $result[$i]['earnings'] = round($cars->sum('earnings'),2);
                $result[$i]['tip'] = round($cars->sum('tip'), 2);
                $result[$i++]['total'] = round($cars->sum('total'), 2);
                $total_tip += round($cars->sum('tip'),2);
                $total_branch += round($cars->sum('earnings'),2);
                $total_busine += round($cars->sum('total'), 2);
            }//foreach
            $result[$i]['name'] = 'Total';
            $result[$i]['tip'] = $total_tip;
            $result[$i]['earnings'] = $total_branch;
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
           foreach ($business as $busine) {
            $cars = Car::whereHas('clientProfessional.professional.branches', function ($query) use ($busine){
                    $query->where('business_id', $busine->id);
            })->whereHas('orders', function ($query) use ($startDate ,$endDate){
                $query->whereBetWeen('data', [$startDate ,$endDate]);
                })->get()->map(function ($car){
                    return [
                        'earnings' => $car->amount,
                        'tip' => $car->tip,
                        'total' => $car->amount + $car->tip
                    ];
                });
                $result[$i]['name'] = $busine->name;
                $result[$i]['earnings'] = round($cars->sum('earnings'),2);
                $result[$i]['tip'] = round($cars->sum('tip'), 2);
                $result[$i++]['total'] = round($cars->sum('total'), 2);
                $total_tip += round($cars->sum('tip'),2);
                $total_branch += round($cars->sum('earnings'),2);
                $total_busine += round($cars->sum('total'), 2);
            }//foreach
            $result[$i]['name'] = 'Total';
            $result[$i]['tip'] = $total_tip;
            $result[$i]['earnings'] = $total_branch;
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
           $data= Carbon::now()->toDateString();
           foreach ($business as $busine) {
            $cars = Car::whereHas('clientProfessional.professional.branches', function ($query) use ($busine){
                    $query->where('branch_id', $busine->id);
            })->whereHas('orders', function ($query) use ($data){
                $query->whereDate('data', $data);
                })->get()->map(function ($car){
                    return [
                        'earnings' => $car->amount,
                        'tip' => $car->tip,
                        'total' => $car->amount + $car->tip
                    ];
                });
                $result[$i]['name'] = $busine->name;
                $result[$i]['earnings'] = round($cars->sum('earnings'),2);
                $result[$i]['tip'] = round($cars->sum('tip'), 2);
                $result[$i++]['total'] = round($cars->sum('total'), 2);
                $total_tip += round($cars->sum('tip'),2);
                $total_branch += round($cars->sum('earnings'),2);
                $total_busine += round($cars->sum('total'), 2);
            }//foreach
            $result[$i]['name'] = 'Total';
            $result[$i]['tip'] = $total_tip;
            $result[$i]['earnings'] = $total_branch;
            $result[$i++]['total'] = $total_busine;
      return $result;
    }

}