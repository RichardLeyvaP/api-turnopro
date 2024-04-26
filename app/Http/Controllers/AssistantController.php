<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Professional;
use App\Models\Tail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AssistantController extends Controller
{
    public function professional_branch_notif_queque(Request $request)
    {
        Log::info('Dada una sucursal y un professional devuelve las notificaciones');
        try {
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'branch_id' => 'required|numeric',
           ]);
           
           $branch = Branch::find($data['branch_id']);
           $professional = Professional::find($data['professional_id']);
            $notifications = $branch->notifications()
    ->where('professional_id', $professional->id)
    ->whereDate('created_at', Carbon::now())
    ->get()
    ->map(function ($query) {
        return [
            'id' => $query->id,
            'professional_id' => $query->professional_id,
            'branch_id' => $query->branch_id,
            'tittle' => $query->tittle,
            'description' => $query->description,
            'state' => $query->state,
            'type' => $query->type,
            'created_at' => Carbon::parse($query->created_at)->format('Y-m-d h:i:s A'),
            'updated_at' => Carbon::parse($query->updated_at)->format('Y-m-d h:i:s A')
        ];
    })
    ->sortByDesc(function ($notification) {
        return $notification['created_at'];
    })
    ->values();
    //cola
    $branch_id = $branch->id;
    $professional_id = $professional->id;
    Log::info('Dada una sucursal y un professional devuelve la cola del día');
    $tails = Tail::whereHas('reservation', function ($query) use ($branch_id){
        $query->where('branch_id', $branch_id);
    })->whereHas('reservation.car.clientProfessional', function ($query) use($professional_id){
        $query->where('professional_id', $professional_id);
    })->whereNot('attended', [2])->get();
    $branchTails = $tails->map(function ($tail) use ($data){   
        $reservation =  $tail->reservation;
            $client = $reservation->car->clientProfessional->client;
            $professional = $reservation->car->clientProfessional->professional;
        return [
            'reservation_id' => $reservation->id,
            'car_id' => $reservation->car_id,
            'start_time' => Carbon::parse($reservation->start_time)->format('H:i:s'),
            'final_hour' => Carbon::parse($reservation->final_hour)->format('H:i:s'),
            'total_time' => $reservation->total_time,
            'client_name' => $client->name." ".$client->surname,
            'client_image' => $client->client_image ? $client->client_image : "comments/default_profile.jpg",
            'professional_name' => $professional->name." ".$professional->surname,
            'client_id' => $client->id,
            'professional_id' => $data['professional_id'],
            'attended' => $tail->attended, 
            'updated_at' => $tail->updated_at->format('Y-m-d H:i:s'),
            'clock' => $tail->clock, 
            'timeClock' => $tail->timeClock, 
            'detached' => $tail->detached, 
            'total_services' => Order::whereHas('car.reservation')->whereRelation('car', 'id', '=', $reservation->car_id)->where('is_product', false)->count()
           
        ];
    })->sortBy('start_time')->values();         
           return response()->json(['notifications' => $notifications, 'tail' => $branchTails], 200, [], JSON_NUMERIC_CHECK);
       } catch (\Throwable $th) {
           return response()->json(['msg' => $th->getMessage()."Error al mostrar las notifocaciones"], 500);
       }
    }
}
