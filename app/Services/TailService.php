<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Reservation;
use App\Models\Tail;
use Carbon\Carbon;

class TailService {

    public function cola_branch_data($branch_id){
        $tails = Tail::with(['reservation.car.clientProfessional.professional.branchServices' => function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        }])->whereIn('attended', [0,3])->get();
        $branchTails = $tails->map(function ($tail){
            return [
                'reservation_id' => $tail->reservation->id,
                'car_id' => $tail->reservation->car_id,
                'start_time' => Carbon::parse($tail->reservation->start_time)->format('H:i:s'),
                'final_hour' => Carbon::parse($tail->reservation->final_hour)->format('H:i:s'),
                'total_time' => $tail->reservation->total_time,
                'client_name' => $tail->reservation->car->clientProfessional->client->name." ".$tail->reservation->car->clientProfessional->client->surname." ".$tail->reservation->car->clientProfessional->client->second_surname,
                'professional_name' => $tail->reservation->car->clientProfessional->professional->name." ".$tail->reservation->car->clientProfessional->professional->surname." ".$tail->reservation->car->clientProfessional->professional->second_surname,
                'client_id' => $tail->reservation->car->clientProfessional->client_id,
                'professional_id' => $tail->reservation->car->clientProfessional->professional_id,
                'attended' => $tail->attended
            ];
        })->sortBy('start_time')->values();

        return $branchTails;
    }

    public function cola_branch_delete($branch_id){
        $tails = Tail::whereHas('reservation.car.clientProfessional.professional.branchServices', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        })->delete();
    }

    public function cola_branch_professional($branch_id, $professional_id){
        $tails = Tail::whereHas('reservation.car.clientProfessional.professional.branchServices', function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        })->whereHas('reservation.car.clientProfessional', function ($query) use($professional_id){
            $query->where('professional_id', $professional_id);
        })->whereHas('reservation.car.orders', function ($query){
            $query->where('is_product', false);
        })->whereIn('attended', [0,1,3])->get();
        $branchTails = $tails->map(function ($tail){
            return [
                'reservation_id' => $tail->reservation->id,
                'car_id' => $tail->reservation->car_id,
                'start_time' => Carbon::parse($tail->reservation->start_time)->format('H:i:s'),
                'final_hour' => Carbon::parse($tail->reservation->final_hour)->format('H:i:s'),
                'total_time' => $tail->reservation->total_time,
                'client_name' => $tail->reservation->car->clientProfessional->client->name." ".$tail->reservation->car->clientProfessional->client->surname." ".$tail->reservation->car->clientProfessional->client->second_surname,
                'professional_name' => $tail->reservation->car->clientProfessional->professional->name." ".$tail->reservation->car->clientProfessional->professional->surname." ".$tail->reservation->car->clientProfessional->professional->second_surname,
                'client_id' => $tail->reservation->car->clientProfessional->client_id,
                'professional_id' => $tail->reservation->car->clientProfessional->professional_id,
                'attended' => $tail->attended, 
                'total_services' => Order::whereHas('car.reservations')->whereRelation('car', 'id', '=', $tail->reservation->car_id)->where('is_product', false)->count()/*$tail->reservation->car->orders->map(function ($orderData){
                    $orderData->where('is_product', false);
                      })*/
                //'total_services' => count($tail->reservation->car->orders)
            ];
        })->sortBy('start_time')->values();

        return $branchTails;
    }

    public function tail_attended($reservation_id, $attended){
        $tail = Tail::where('reservation_id', $reservation_id)->first();
        $tail->attended = $attended;
        $tail->save();
    }

    public function type_of_service($branch_id, $professional_id){
        $tails = Tail::with(['reservation.car.clientProfessional.professional.branchServices' => function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        }])->whereHas('reservation.car.clientProfessional', function ($query) use($professional_id){
            $query->where('professional_id', $professional_id);
        })->whereHas('reservation.car.orders', function ($query){
            $query->where('is_product', false);
        })->where('attended', 1)->get();
        if (count($tails) > 4) {
            return false;
        }elseif (count($tails) < 1) {
            return true;
        }
        else {
            foreach ($tails as $tail) {            
            foreach ($tail->reservation->car->orders as $orderData) {
                if ($orderData->branchServiceProfessional->branchService->service->simultaneou == 1) {
                    return true;
                }
            }
        }
        }
        return false;
    }

}