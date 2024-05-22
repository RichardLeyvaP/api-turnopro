<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Branch;
use App\Models\Car;
use App\Models\CardGift;
use App\Models\CardGiftUser;
use App\Models\CashierSale;
use App\Models\Finance;
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
            $payment->branch_id = $request->branch_id;
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
                'client' => $car->clientProfessional->client->name,
                'amount' => $data['cash']+$data['creditCard']+$data['debit']+$data['transfer']+$data['other']+$data['cardGift'],
                'operation' => 'Paga Carro',
                'details' => 'Carro: '.$car->id,
                'description' => $car->clientProfessional->professional->name,
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

    public function product_sales(Request $request)
    {
        try {

            Log::info("Editar");
            $data = $request->validate([
                'professional_id' => 'required|numeric',
                'cash' => 'nullable|numeric',
                'creditCard' => 'nullable|numeric',
                'debit' => 'nullable|numeric',
                'transfer' => 'nullable|numeric',
                'other' => 'nullable|numeric',
                'tip' => 'nullable|numeric',
                'cardGift' => 'nullable|numeric',
                'code' => 'nullable'
            ]);     
            
            $ids = $request->input('ids');
           $branch = Branch::where('id', $request->branch_id)->first();

            Log::info($data['cardGift']);
            if ($data['cardGift'] != 0) {
                Log::info($data['code']);
                $cardGiftUser = CardGiftUser::where('code',$data['code'])->first();
                Log::info('tarjeta asignada');
                Log::info($cardGiftUser);
                if($cardGiftUser->exist - $data['cardGift'] <= 0){
                    $cardGiftUser->state = "Redimida";
                }
                $cardGiftUser->exist = $cardGiftUser->exist - $data['cardGift'];
                $cardGiftUser->save();
            }
            //Log::info($cardGiftUser);
            $payment = new Payment();
            $payment->cash = $data['cash'];
            $payment->creditCard = $data['creditCard'];
            $payment->debit = $data['debit'];
            $payment->transfer = $data['transfer'];
            $payment->other = $data['other'];
            $payment->cardGif = $data['cardGift'];
            $payment->branch_id = $request->branch_id;
            $payment->save();

            CashierSale::whereIn('id', $ids)->update(['pay' => 1]);
            if($data['cash']){
                $box = Box::where('branch_id', $branch->id)->whereDate('data', Carbon::now())->first();
                if (!$box) {                
                        $box = new Box();
                        $box->existence = $data['cash'];    
                        $box->data = Carbon::now();         
                        $box->branch_id = $branch->id;
                    }else{                
                        $box->existence = $box->existence + $data['cash'];
                    }
            $box->save();
            }
            $trace = [
                'branch' => $branch->name,
                'cashier' => $request->nameProfessional,
                'client' => '',
                'amount' => $data['cash']+$data['creditCard']+$data['debit']+$data['transfer']+$data['other']+$data['cardGift']+$data['tip'],
                'operation' => 'Paga Productos vendidos',
                'details' => 'Productos vendidos: '.implode(', ', $ids),
                'description' => '',
            ];
            $this->traceService->store($trace);
            
            $finance = Finance::where('branch_id', $branch->id)->where('revenue_id', 7)->whereDate('data', Carbon::now())->orderByDesc('control')->first();
                            
            if($finance !== null)
            {
                $control = $finance->control+1;
            }
            else {
                $control = 1;
            }
            $finance = new Finance();
                            $finance->control = $control;
                            $finance->operation = 'Ingreso';
                            $finance->amount = $data['cash']+$data['creditCard']+$data['debit']+$data['transfer']+$data['other']+$data['cardGift'];
                            $finance->comment = 'Ingreso por venta de producto en la caja';
                            $finance->branch_id = $branch->id;
                            $finance->type = 'Sucursal';
                            $finance->revenue_id = 7;
                            $finance->data = Carbon::now();                
                            $finance->file = '';
                            $finance->save();
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
