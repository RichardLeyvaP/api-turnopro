<?php

namespace App\Services;
use App\Models\Car;
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
}