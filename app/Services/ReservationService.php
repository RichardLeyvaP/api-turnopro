<?php

namespace App\Services;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservationService {

    private ClientProfessionalService $clientProfessionalService;
    private CarService $carService;
    private ServiceService $serviceService;
    private BranchServiceService $branchServiceService;
    private BranchServiceProfessionalService $branchServiceProfessionalService;
    private OrderService $orderService;

    public function __construct(BranchServiceProfessionalService $branchServiceProfessionalService, OrderService $orderService, ClientProfessionalService $clientProfessionalService, CarService $carService, ServiceService $serviceService, BranchServiceService $branchServiceService)
    {
        $this->branchServiceService = $branchServiceService;
        $this->branchServiceProfessionalService = $branchServiceProfessionalService;
        $this->orderService = $orderService;
        $this->clientProfessionalService = $clientProfessionalService;
        $this->carService = $carService;
        $this->serviceService = $serviceService;
    }

    public function store($data, $servs,$client_id)
    {
        Log::info("Guardar Reservacion");
        DB::beginTransaction();
            $client_professional_id = $this->clientProfessionalService->client_professional($client_id, $data['professional_id']);
           $dataCarData = [
                'amount' => 0.0,
                'client_professional_id' => $client_professional_id,
                'pay' => false,
                'active' => 1,
                'tip' => 0.0
            ];
            Log::info('7');
            $car = $this->carService->store($dataCarData);
            //foreach del arreglo de services
            foreach ($servs as $serv) {
                $service_id = $serv;
                $service = $this->serviceService->show($service_id);
                $branch_service_id = $this->branchServiceService->branch_service_show($service_id, $data['branch_id']);
                $branch_service_professional_id = $this->branchServiceProfessionalService->branch_service_professional($branch_service_id, $data['professional_id']);
            
                $dataOrderService = [
                'car_id' =>$car->id,
                'branch_service_professional_id' => $branch_service_professional_id,
                'product_store_id' => 0,
                'price' => $service->price_service+$service->profit_percentaje/100
                ];
                $order = $this->orderService->order_service_store($dataOrderService);
                $this->carService->car_amount_updated($car->id, $order->price);
                $reservation = $this->reservation_car($car->id, $data);
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

    public function reservation_car($car_id, $data)
    {
        Log::info("Dado un carro devuelve la reservacion de una fecha determinada");
        return Reservation::where('car_id', $car_id)->whereDate('data', $data)->first();
    }

}
