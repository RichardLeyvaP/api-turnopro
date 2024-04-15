<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Branch;
use App\Models\Car;
use App\Models\CardGift;
use App\Models\CardGiftUser;
use App\Services\TraceService;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private TraceService $traceService;
    
    public function __construct(TraceService $traceService)
    {
         $this->traceService = $traceService;
    }

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
                'cardGift' => 'nullable|numeric',
                'code' => 'nullable'
            ]);
            Log::info($data);
            $car = Car::find($data['car_id']);            
           $branch = Branch::where('id', $request->branch_id)->first();
            Log::info($branch);
            $payment = Payment::where('car_id', $data['car_id'])->first();
            if (!$payment) {
                $payment = new Payment();
            }
            Log::info($data['cardGift']);
            if ($data['cardGift'] != 0) {
                Log::info($data['code']);
                $cardGiftUser = CardGiftUser::where('code',$data['code'])->first();
                Log::info('tarjeta asognada');
                Log::info($cardGiftUser);
                Log::info('carro');
                Log::info($car->id);
                Log::info("ver si es cero al pagar");
                Log::info($cardGiftUser->exist - $data['cardGift']);
                if($cardGiftUser->exist - $data['cardGift'] <= 0){
                    $cardGiftUser->state = "Redimida";
                }
                $cardGiftUser->exist = $cardGiftUser->exist - $data['cardGift'];
                $cardGiftUser->save();
            }
            //Log::info($cardGiftUser);
            $payment->car_id = $car->id;
            $payment->cash = $data['cash'];
            $payment->creditCard = $data['creditCard'];
            $payment->debit = $data['debit'];
            $payment->transfer = $data['transfer'];
            $payment->other = $data['other'];
            $payment->cardGif = $data['cardGift'];
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
                $box->data = Carbon::now();         
                $box->branch_id = $branch->id;
            }else{                
                $box->existence = $box->existence + $data['cash'];
            }
            $box->save();
            $trace = [
                'branch' => $branch->name,
                'cashier' => $request->nameProfessional,
                'client' => $car->clientProfessional->client->name.' '.$car->clientProfessional->client->surname.' '.$car->clientProfessional->client->second_surname,
                'amount' => $data['cash']+$data['creditCard']+$data['debit']+$data['transfer']+$data['other']+$data['cardGift'],
                'operation' => 'Paga Carro',
                'details' => 'Carro: '.$car->id,
                'description' => $car->clientProfessional->professional->name.' '.$car->clientProfessional->professional->surname.' '.$car->clientProfessional->professional->second_surname,
            ];
            $this->traceService->store($trace);
            Log::info('$trace');
            Log::info($trace);
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
