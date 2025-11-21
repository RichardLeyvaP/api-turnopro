<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Branch;
use App\Models\Car;
use App\Models\CardGift;
use App\Models\CardGiftUser;
use App\Models\CashierSale;
use App\Models\Finance;
use App\Models\Order;
use App\Services\TraceService;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
 * Registra el pago de un carro (reserva completada).
 *
 * Crea un registro de pago, actualiza el carro como pagado, registra ingresos en `Finance` y actualiza la caja.
 *
 * @authenticated
 * @bodyParam car_id integer required ID del carro a pagar. Example: 123
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam nameProfessional string required Nombre del cajero(a). Example: Yasmany
 * @bodyParam cash number optional Pago en efectivo. Example: 5000.00
 * @bodyParam creditCard number optional Pago con tarjeta de crédito. Example: 3000.00
 * @bodyParam debit number optional Pago con tarjeta de débito. Example: 2000.00
 * @bodyParam transfer number optional Pago por transferencia. Example: 4000.00
 * @bodyParam other number optional Otro método de pago. Example: 1000.00
 * @bodyParam cardGift number optional Pago con tarjeta de regalo. Example: 2500.00
 * @bodyParam tip number optional Propina. Example: 1000.00
 * @bodyParam tipByCash string optional Método de pago de la propina ("Efectivo", "Débito", etc.). Example: Efectivo
 * @bodyParam code string optional Código de tarjeta de regalo (si aplica). Example: aB3xK9mP
 *
 * @response 200 {"msg": "Pago realizado correctamente correctamente"}
 * @response 200 {"msg": "El pago ya ha sido registrado para este carro."}
 * @response 500 {"msg": "Error al realizar el pago"}
 */
    public function update(Request $request)
    {
        try {
            $payment = Payment::where('car_id', $request->car_id)->first();
            $userId = $request->user()->id;
            $user = Auth::user();
            $professionalImage = $user->professional ? $user->professional->image_url : 'professionals/default.jpg';
            if ($payment) {
                return response()->json(['msg' => 'El pago ya ha sido registrado para este carro.'], 200); 
            }
            DB::beginTransaction();
            $data = $request->validate([
                'car_id' => 'required|numeric',
                'cash' => 'nullable|numeric',
                'creditCard' => 'nullable|numeric',
                'debit' => 'nullable|numeric',
                'transfer' => 'nullable|numeric',
                'other' => 'nullable|numeric',
                'tip' => 'nullable|numeric',
                'cardGift' => 'nullable|numeric',
                'code' => 'nullable',
                'tipByCash' => 'nullable'
            ]);         
            $control = 0;
            $method = null;
            $car = Car::find($data['car_id']);            
            $branch = Branch::where('id', $request->branch_id)->first();
            $payment = new Payment();
            // Lógica basada en el valor de $data['tipByCash']
            switch ($data['tipByCash']) {
                case 'Efectivo':
                    $data['cash'] += $data['tip'];
                    $method = 'cash';
                    break;
                
                case 'Débito':
                    $data['debit'] += $data['tip'];
                    $method = 'debit';
                    break;
                
                case 'Transferencia':
                    $data['transfer'] += $data['tip'];
                    $method = 'transfer';
                    break;
                
                case 'Tarjeta de regalo':
                    $data['cardGift'] += $data['tip'];
                    $method = 'cardGift';
                    break;
                
                case 'Tarjeta de Crédito':
                    $data['creditCard'] += $data['tip'];
                    $method = 'creditCard';
                    break;

                case 'Otro Método':
                    $data['other'] += $data['tip'];
                    $method = 'other';
                    break;
            }
            if ($data['cardGift'] != 0) {
                $cardGiftUser = CardGiftUser::where('code',$data['code'])->first();
                if($cardGiftUser->exist - $data['cardGift'] <= 0){
                    $cardGiftUser->state = "Redimida";
                }
                $cardGiftUser->exist = $cardGiftUser->exist - $data['cardGift'];
                $cardGiftUser->save();
            }
            $payment->car_id = $car->id;
            $payment->cash = $data['cash'];
            $payment->creditCard = $data['creditCard'];
            $payment->debit = $data['debit'];
            $payment->transfer = $data['transfer'];
            $payment->other = $data['other'];
            $payment->cardGif = $data['cardGift'];
            $payment->branch_id = $request->branch_id;
            $payment->user_id = $userId;
            $payment->method = $method;
            $payment->save();

            $finance = Finance::orderBy('control', 'desc')->first();
            if ($finance !== null) {
                $control = $finance->control + 1;
            } else {
                $control = 1;
            }
            $client = $car->clientProfessional->client->name;
            $winProducts = Order::where('car_id', $data['car_id'])->where('is_product', 1)->sum('price');
            $services = Order::where('car_id', $data['car_id'])->where('is_product', 0)->get();
            $winServices = $services->sum('price');
            if($car->technical_assistance){
                $finance = new Finance();
                            $finance->control = $control++;
                            $finance->operation = 'Ingreso';
                            $finance->amount = $car->technical_assistance * 5000;
                            $finance->comment = 'Ingreso por pago de servicios del técnico a cliente ' . $client;
                            $finance->branch_id = $request->branch_id;
                            $finance->type = 'Sucursal';
                            $finance->revenue_id = 8;
                            $finance->data = Carbon::now();
                            $finance->file = '';
                            $finance->car_id = $data['car_id'];
                            $finance->save();
            }
            if($winProducts){
                $finance = new Finance();
                            $finance->control = $control++;
                            $finance->operation = 'Ingreso';
                            $finance->amount = $winProducts;
                            $finance->comment = 'Ingreso venta de productos a cliente ' . $client;
                            $finance->branch_id = $request->branch_id;
                            $finance->type = 'Sucursal';
                            $finance->revenue_id = 7;
                            $finance->data = Carbon::now();
                            $finance->file = '';
                            $finance->car_id = $data['car_id'];
                            $finance->save();
            }
            if($winServices){
                //Servicios
                $finance = new Finance();
                $finance->control = $control++;
                $finance->operation = 'Ingreso';
                $finance->amount = $winServices;
                $finance->comment = 'Ingreso por pago de servicios de cliente ' . $client;
                $finance->branch_id = $request->branch_id;
                $finance->type = 'Sucursal';
                $finance->revenue_id = 8;
                $finance->data = Carbon::now();
                $finance->file = '';
                $finance->car_id = $data['car_id'];
                $finance->save();
            }
            if($data['tip']){
                //Servicios
                $finance = new Finance();
                $finance->control = $control++;
                $finance->operation = 'Ingreso';
                $finance->amount = $data['tip'];
                $finance->comment = 'Ingreso por pago de propina de cliente ' . $client.' ['.$data['tipByCash'].']';
                $finance->branch_id = $request->branch_id;
                $finance->type = 'Sucursal';
                $finance->revenue_id = 8;
                $finance->data = Carbon::now();
                $finance->file = '';
                $finance->car_id = $data['car_id'];
                $finance->save();
            }
            $paymentData = [
                'cash' => $data['cash'] ?? 0,
                'creditCard' => $data['creditCard'] ?? 0,
                'debit' => $data['debit'] ?? 0,
                'transfer' => $data['transfer'] ?? 0,
                'other' => $data['other'] ?? 0,
                'cardGift' => $data['cardGift'] ?? 0,
                'tip' => $data['tip'] ?? 0,
                'tipByCash' => $data['tipByCash'] ?? null,
            ];   
            
            if ($car->action_status != 0 && !empty($car->payment)) {
                $changesDescription = $car->comparePaymentChanges($car->payment, $paymentData);
                if (!empty($changesDescription)) {
                    $car->logChanges(
                        $changesDescription,
                        $request->nameProfessional,
                        'payment',
                        $professionalImage
                    );
                }
            }
            // Guardar en el campo JSON payments del carro
            $car->payment = $paymentData;
            
            $car->pay = 1;
            $car->active = 0;
            $car->action_status = 0;
            $car->tip = $data['tip'];
            $car->save();
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
            $trace = [
                'branch' => $branch->name,
                'cashier' => $request->nameProfessional,
                'client' => $car->clientProfessional->client->name,
                'amount' => $data['cash']+$data['creditCard']+$data['debit']+$data['transfer']+$data['other']+$data['cardGift'],
                'operation' => 'Paga Carro',
                'details' => 'Carro: '.$car->id,
                'description' => $car->clientProfessional->professional->name,
                'car_id' => $data['car_id']
            ];
            $this->traceService->store($trace);
            DB::commit();
            return response()->json(['msg' => 'Pago realizado correctamente correctamente'], 200);
        } catch (\Throwable $th) {
            DB::rollback();
        return response()->json(['msg' => $th->getMessage().'Error al realizar el pago'], 500);
        }
    }

    /**
 * Registra el pago de productos vendidos desde la caja (ventas directas sin carro).
 *
 * Actualiza las ventas como pagadas, registra ingresos y actualiza la caja si hay efectivo.
 *
 * @authenticated
 * @bodyParam professional_id integer required ID del cajero(a). Example: 123
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam nameProfessional string required Nombre del cajero(a). Example: Yasmany
 * @bodyParam cash number optional Pago en efectivo. Example: 5000.00
 * @bodyParam creditCard number optional Pago con tarjeta de crédito. Example: 3000.00
 * @bodyParam debit number optional Pago con tarjeta de débito. Example: 2000.00
 * @bodyParam transfer number optional Pago por transferencia. Example: 4000.00
 * @bodyParam other number optional Otro método de pago. Example: 1000.00
 * @bodyParam cardGift number optional Pago con tarjeta de regalo. Example: 2500.00
 * @bodyParam tip number optional Propina. Example: 1000.00
 * @bodyParam code string optional Código de tarjeta de regalo (si aplica). Example: aB3xK9mP
 * @bodyParam ids array required IDs de las ventas de caja a pagar. Example: [456, 457]
 *
 * @response 200 {"msg": "Pago realizado correctamente correctamente"}
 * @response 500 {"msg": "Error al realizar el pago"}
 */
    public function product_sales(Request $request)
    {
        try {
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
           $userId = $request->user()->id;
            if ($data['cardGift'] != 0) {
                $cardGiftUser = CardGiftUser::where('code',$data['code'])->first();
                if($cardGiftUser->exist - $data['cardGift'] <= 0){
                    $cardGiftUser->state = "Redimida";
                }
                $cardGiftUser->exist = $cardGiftUser->exist - $data['cardGift'];
                $cardGiftUser->save();
            }
            $firstSaleId = $ids[0];
            $payment = new Payment();
            $payment->cash = $data['cash'];
            $payment->creditCard = $data['creditCard'];
            $payment->debit = $data['debit'];
            $payment->transfer = $data['transfer'];
            $payment->other = $data['other'];
            $payment->cardGif = $data['cardGift'];
            $payment->branch_id = $request->branch_id;
            $payment->user_id = $userId;
            $payment->cashiersale_id = $firstSaleId;
            $payment->save();

            CashierSale::whereIn('id', $ids)->update(['pay' => 1]);
            // Obtenemos todas las ventas con sus relaciones
            $cashierSales = CashierSale::with('productStore.product')
            ->whereIn('id', $ids)
            ->get();

            // Calculamos el total de ganancias
            $win = $cashierSales->sum('price');

            // Procesamos los productos vendidos
            $productSales = $cashierSales->groupBy(function ($item) {
            return $item->productStore->product->name ?? 'Producto desconocido';
            })->map(function ($group) {
            return $group->sum('cant');
            });

            // Formateamos el resultado como lo necesitas
            $formattedProducts = $productSales->map(function ($quantity, $name) {
                return "$quantity $name";
            })->implode(', ');
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
                'details' => $formattedProducts,
                'description' => '',
            ];
            $this->traceService->store($trace);
            
            $finance = Finance::orderBy('control', 'desc')->first();
                            
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
                            $finance->amount = $win;
                            $finance->comment = 'Ingreso venta de productos en la caja de la sucursal '.$branch->name;
                            $finance->branch_id = $branch->id;
                            $finance->type = 'Sucursal';
                            $finance->revenue_id = 7;
                            $finance->data = Carbon::now();                
                            $finance->file = '';
                            $finance->save();
            return response()->json(['msg' => 'Pago realizado correctamente correctamente'], 200);
        } catch (\Throwable $th) {
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
