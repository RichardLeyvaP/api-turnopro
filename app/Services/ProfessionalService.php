<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Professional;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
        $professionals = Professional::whereHas('branchServices', function ($query) use ($branch_id){
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
        return $professionals = Professional::whereHas('branchServices', function ($query) use ($branch_id){
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
            $query->where('professional_id', $data['professional_id']);
       })->selectRaw('DATE(updated_at) as date, SUM(amount) as earnings, SUM(amount) as total_earnings, AVG(amount) as average_earnings')->whereBetween('updated_at', [$data['startDate'], Carbon::parse($data['endDate'])->addDay()])->where('pay', 1)->groupBy('date')->get();

        for($date = $startDate; $date->lte($endDate);$date->addDay()){
            $machingResult = $cars->firstWhere('date', $date->toDateString());
            $dates[$i]['date'] = $date->toDateString();

            $day += 1;
            $dates[$i]['day_week'] = $day;
            if($day == 7)
            $day = 0;
           
            $dates[$i++]['earnings'] = $machingResult ? $machingResult->earnings: 0;
          }
           $totalEarnings = $cars->sum('total_earnings');
           $averageEarnings = $cars->avg('average_earnings');
          $result = [
            'dates' => $dates,
            'totalEarnings' => $totalEarnings,
            'averageEarnings' => $averageEarnings
          ];
           return $result;
    }

    public function professionals_ganancias_branch($data)
    {
        Log::info('Obtener los cars');
        $cars = Car::whereHas('clientProfessional', function ($query) use ($data){
            $query->where('professional_id', $data['professional_id']);
       })->whereHas('clientProfessional.professional.branchServices', function ($query) use ($data){
        $query->where('branch_id', $data['branch_id']);
       })->whereHas('orders', function ($query) use ($data){
            $query->whereBetWeen('data', [Carbon::parse($data['startDate']), Carbon::parse($data['endDate'])]);
       })->get();
       $services =0;
       $totalEspecial =0;
       $totalClients =0;
        foreach ($cars as $car) {
            $services = $services + count($car->orders->where('is_product', 0));
            $totalEspecial = $totalEspecial + Service::whereHas('branchServices.branchServiceProfessionals.orders.car', function ($query) use ($car){
                $query->where('id', $car->id);
            })->where('type_service', 'Especial')->count();
            $totalClients = $car->clientProfessional->count();  
        }
          $result = [
            'totalEarnings' => $cars->sum('amount'),
            'propina' => $cars->sum('tip'),
            'propina:80%' => $cars->sum('tip')*0.8,
            'totalServices' => $services,
            'serviceEspecial' => $totalEspecial,
            'serviceRegular' => $services - $totalEspecial,
            'clientAtendidos' => $totalClients
          ];
           return $result;

    }

}