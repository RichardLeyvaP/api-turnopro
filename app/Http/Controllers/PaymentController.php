<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {

            Log::info("Editar");
            $data = $request->validate([
                'car_id' => 'required|numeric',
                'cash' => 'nullable|numeric',
                'creditCard' => 'nullable|numeric',
                'debit' => 'nullable|numeric',
                'transfer' => 'nullable|numeric',
                'other' => 'nullable|numeric'
            ]);

            $car = Car::find($data['car_id']);
            $payment = Payment::where('car_id', $data['car_id'])->first();
            if (!$payment) {
                $payment = new Payment();
            }
            Log::info($car->id);
            $payment->car_id = $car->id;
            $payment->cash = $data['cash'];
            $payment->creditCard = $data['creditCard'];
            $payment->debit = $data['debit'];
            $payment->transfer = $data['transfer'];
            $payment->other = $data['other'];
            $payment->save();

            $car->pay = 1;
            $car->active = 0;
            $car->save();

            return response()->json(['msg' => 'Pago realizado correctamente correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => $th->getMessage().'Error al realizar el pago'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        //
    }
}
