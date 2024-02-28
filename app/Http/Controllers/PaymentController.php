<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Branch;
use App\Models\Car;
use App\Models\CardGift;
use App\Models\Payment;
use Carbon\Carbon;
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
                'other' => 'nullable|numeric',
                'tip' => 'nullable|numeric',
                'cardGif' => 'nullable|numeric',
                'id' => 'nullable|numeric'
            ]);
            $car = Car::find($data['car_id']);            
           $branch = Branch::whereHas('cars', function ($query) use ($car){
                $query->where('cars.id', $car->id);
            })->first();
            Log::info($branch);
            $payment = Payment::where('car_id', $data['car_id'])->first();
            if (!$payment) {
                $payment = new Payment();
            }
            if ($data['cardGif']) {
                $cardGift = CardGift::find($data['id']);
                Log::info($car->id);
                if(!$cardGift->value - $data['cardGif']){
                    $cardGift->state = "Redimida";
                }
                $cardGift->value = $cardGift->value - $data['cardGif'];
                $cardGift->save();
            }
            Log::info($cardGift);
            $payment->car_id = $car->id;
            $payment->cash = $data['cash'];
            $payment->creditCard = $data['creditCard'];
            $payment->debit = $data['debit'];
            $payment->transfer = $data['transfer'];
            $payment->other = $data['other'];
            $payment->cardGif = $data['cardGif'];
            $payment->save();



            $car->pay = 1;
            $car->active = 0;
            $car->tip = $data['tip'];
            $car->save();
            Log::info($branch->id);
            $box = Box::where('branch_id', $branch->id)->whereDate('data', Carbon::now())->first();
            Log::info($car->id);
            if (!$box) {                
                $box = new Box();
                $box->existence = $data['cash'];              
                $box->branch_id = $branch->id;
            }else{                
                $box->existence = $box->existence + $data['cash'];
            }
            $box->save();

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
