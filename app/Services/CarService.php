<?php

namespace App\Services;
use App\Models\Car;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CarService {

    public function show($id)
    {
        return Car::with('clientProfessional.client', 'clientProfessional.professional')->find($id);
    }

    public function store($data)
    {
        $car = new Car();
        $car->client_professional_id = $data['client_professional_id'];
        $car->amount = $data['amount'];
        $car->pay = $data['pay'];
        $car->active = $data['active'];
        $car->tip = $data['tip'];
        $car->save();

        return $car;
    }

    //Actualizar el monto del carro
    public function car_amount_updated($car_id, $amount)
    {
            Log::info("Actualiza el monto del carro");
            $car = $this->show($car_id);
            $car->amount = $car->amount + $amount;
            $car->save();
            return $car;
    }

    public function professionals_ganancias_periodo($data)
    {
        return $cars = Car::whereHas('clientProfessional', function ($query) use ($data){
            $query->where('professional_id', $data['professional_id']);
       })->selectRaw('DATE(updated_at) as date, SUM(amount) as earnings, SUM(amount) as total_earnings, AVG(amount) as average_earnings')->whereBetween('updated_at', [$data['startDate'], Carbon::parse($data['endDate'])->addDay()])->where('pay', 1)->groupBy('date')->get();
    }
}