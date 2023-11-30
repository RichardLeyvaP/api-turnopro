<?php

namespace App\Services;

use App\Models\BranchService;
use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\Client;
use App\Models\ClientProfessional;
use App\Models\Order;
use App\Models\Professional;
use App\Models\Reservation;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservationService {
   
    public function store($data, $servs,$client_id)
    {
        Log::info("Guardar Reservacion");
        DB::beginTransaction();
        $clientprofessional = ClientProfessional::where('client_professional.client_id',$client_id)->where('client_professional.professional_id',$data['professional_id'])->first();
            if (!$clientprofessional) {
                $clientprofessional = new ClientProfessional();
                $clientprofessional->client_id = $client_id;
                $clientprofessional->professional_id = $data['professional_id'];
                $clientprofessional->save();
            }
        $client_professional_id = $clientprofessional->id;
            Log::info('7');
            $car = new Car();
            $car->client_professional_id = $client_professional_id;
            $car->amount = 0.0;
            $car->pay = false;
            $car->active = 1;
            $car->tip = 0.0;
            $car->save();
            //foreach del arreglo de services
            foreach ($servs as $serv) {
                $service_id = $serv;
                $service = Service::find($service_id);
                $branchservice = BranchService::where('branch_id', $data['branch_id'])->where('service_id', $service_id)->first();
                if (!$branchservice) {
                    $branchservice = new BranchService();
                    $branchservice->branch_id = $data['branch_id'];
                    $branchservice->service_id = $service_id;
                    $branchservice->save();
                }
                $branch_service_id = $branchservice->id;
                $branchServiceProfessional = BranchServiceProfessional::where('branch_service_id', $branch_service_id)->where('professional_id', $data['professional_id'])->first();
                if (!$branchServiceProfessional) {
                    $branchServiceProfessional = new BranchServiceProfessional();
                    $branchServiceProfessional->branch_service_id = $branch_service_id;
                    $branchServiceProfessional->professional_id = $data['professional_id'];
                    $branchServiceProfessional->save();
                }
                $branch_service_professional_id = $branchServiceProfessional->id;
                $order = new Order();
                $order->car_id = $car->id;
                $order->product_store_id = null;
                $order->branch_service_professional_id = $branch_service_professional_id;
                $order->is_product = false;
                $order->price = $service->price_service+$service->profit_percentaje/100;   
                $order->request_delete = false;
                $order->save();
                $car = Car::find($car->id);
                $car->amount = $car->amount + $order->price;
                $car->save();
                $reservation= Reservation::where('car_id', $car->id)->whereDate('data', $data)->first();
                if (!$reservation) {
                $reservation = new Reservation();
                $reservation->start_time = Carbon::parse($data['start_time'])->toTimeString();
                $reservation->final_hour = Carbon::parse($data['start_time'])->addMinutes($service->duration_service)->toTimeString();
                $reservation->total_time = sprintf('%02d:%02d:%02d', floor($service->duration_service/60),$service->duration_service%60,0);
                $reservation->data = $data['data'];
                $reservation->from_home = 1;
                $reservation->car_id = $car->id;
                $reservation->save();
                }else{
                  $reservation->final_hour = Carbon::parse($reservation->final_hour)->addMinutes($service->duration_service)->toTimeString();
                  $reservation->total_time = Carbon::parse($reservation->total_time)->addMinutes($service->duration_service)->format('H:i:s');
                  $reservation->save();
                }
            } //end foreach
            Log::info('8');
            DB::commit();
            Log::info($reservation);
        return $reservation;
    }

}
