<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Tail;

class TailService {

    public function cola_branch_data($branch_id){
        $tails = Tail::with(['reservation.car.clientProfessional.professional.branchServices' => function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        }])->where('attended', 0)->get();
        $branchTails = $tails->map(function ($tail){
            return [
                'reservation_id' => $tail->reservation->id,
                'start_time' => $tail->reservation->start_time,
                'final_hour' => $tail->reservation->final_hour,
                'total_time' => $tail->reservation->total_time,
                'client_name' => $tail->reservation->car->clientProfessional->client->name." ".$tail->reservation->car->clientProfessional->client->surname." ".$tail->reservation->car->clientProfessional->client->second_surname,
                'professional_name' => $tail->reservation->car->clientProfessional->professional->name." ".$tail->reservation->car->clientProfessional->professional->surname." ".$tail->reservation->car->clientProfessional->professional->second_surname,
                'client_id' => $tail->reservation->car->clientProfessional->client_id,
                'professional_id' => $tail->reservation->car->clientProfessional->professional_id
            ];
        })->sortBy('start_time')->values();

        return $branchTails;
    }

    public function cola_branch_professional($branch_id, $professional_id){
        $tails = Tail::with(['reservation.car.clientProfessional.professional.branchServices' => function ($query) use ($branch_id){
            $query->where('branch_id', $branch_id);
        }])->whereHas('reservation.car.clientProfessional', function ($query) use($professional_id){
            $query->where('professional_id', $professional_id);
        })->where('attended', 0)->get();
        $branchTails = $tails->map(function ($tail){
            return [
                'reservation_id' => $tail->reservation->id,
                'start_time' => $tail->reservation->start_time,
                'final_hour' => $tail->reservation->final_hour,
                'total_time' => $tail->reservation->total_time,
                'client_name' => $tail->reservation->car->clientProfessional->client->name." ".$tail->reservation->car->clientProfessional->client->surname." ".$tail->reservation->car->clientProfessional->client->second_surname,
                'professional_name' => $tail->reservation->car->clientProfessional->professional->name." ".$tail->reservation->car->clientProfessional->professional->surname." ".$tail->reservation->car->clientProfessional->professional->second_surname,
                'client_id' => $tail->reservation->car->clientProfessional->client_id,
                'professional_id' => $tail->reservation->car->clientProfessional->professional_id
            ];
        })->sortBy('start_time')->values();

        return $branchTails;
    }

    public function tail_attended($reservation_id, $attended){
        $tail = Tail::where('reservation_id', $reservation_id)->first();
        $tail->attended = $attended;
        $tail->save();
    }

}