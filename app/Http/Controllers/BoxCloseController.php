<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Models\Associated;
use App\Models\Box;
use App\Models\BoxClose;
use App\Models\Branch;
use App\Models\BranchProfessional;
use App\Models\BranchRuleProfessional;
use App\Models\BranchServiceProfessional;
use App\Models\Car;
use App\Models\CloseBox;
use App\Models\Finance;
use App\Models\Order;
use App\Models\Product;
use App\Models\Professional;
use App\Models\ProfessionalPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\SendEmailService;
use App\Services\TraceService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mailer\Exception\TransportException;

class BoxCloseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private SendEmailService $sendEmailService;
    private TraceService $traceService;
    public function __construct(SendEmailService $sendEmailService, TraceService $traceService)
    {

        $this->sendEmailService = $sendEmailService;
        $this->traceService = $traceService;
    }

    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        try {

            Log::info("Editar");
            $data = $request->validate([
                //'box_id' => 'required|numeric',
                'totalMount' => 'nullable|numeric',
                'totalService' => 'nullable|numeric',
                'totalProduct' => 'nullable|numeric',
                'totalTip' => 'nullable|numeric',
                'totalCash' => 'nullable|numeric',
                'totalDebit' => 'nullable|numeric',
                'totalCreditCard' => 'nullable|numeric',
                'totalTransfer' => 'nullable|numeric',
                'totalOther' => 'nullable|numeric',
                'totalCardGif' => 'nullable|numeric'
            ]);
            $idService=null;
            Log::info($data);
            $box = Box::whereDate('data', Carbon::now())->where('branch_id', $request->branch_id)->first();
            $branch = Branch::where('id', $request->branch_id)->with('business')->first();
            $boxClose = BoxClose::where('box_id', $box->id)->first();
            if (!$boxClose) {
                $boxClose = new BoxClose();
            }

            Log::info($box->id);
            $boxClose->box_id = $box->id;
            $boxClose->totalMount = $data['totalMount'];
            $boxClose->totalService = $data['totalService'];
            $boxClose->totalProduct = $data['totalProduct'];
            $boxClose->totalTip = $data['totalTip'];
            $boxClose->totalCash = $data['totalCash'];
            $boxClose->totalCreditCard = $data['totalCreditCard'];
            $boxClose->totalDebit = $data['totalDebit'];
            $boxClose->totalTransfer = $data['totalTransfer'];
            $boxClose->totalOther = $data['totalOther'];
            $boxClose->totalCardGif = $data['totalCardGif'];
            $boxClose->data = Carbon::now();
            $boxClose->save();

            $trace = [
                'branch' => $branch->name,
                'cashier' => $request->nameProfessional,
                'client' => '',
                'amount' => $data['totalMount'],
                'operation' => 'Cierre de Caja',
                'details' => 'Ingreso diario',
                'description' => ''
            ];
            $this->traceService->store($trace);
            Log::info('$trace');
            Log::info($trace);
            //Agregar a tabla de ingresos
            $finance = Finance::where('branch_id', $branch->id)->where('revenue_id', 5)->whereDate('data', Carbon::now())->orderByDesc('control')->first();
                            Log::info('no existe');
                //$finance = Finance::where('branch_id', $branch->id)->orderByDesc('control')->first();
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
                $finance->amount = $data['totalMount'];
                $finance->comment = 'Ingreso diario';
                $finance->branch_id = $branch->id;
                $finance->type = 'Sucursal';
                $finance->revenue_id = 5;
                $finance->data = Carbon::now();                
                $finance->file = '';
                $finance->save();
            //end agregar a tabla de ingresos
            //Revisar convivencias de professionales para pagar 100% del servicio
            Log::info("LLega a professionals");
            $professionals = Professional::whereHas('branches', function ($query) use ($branch){
                $query->where('branch_id', $branch->id);
            })->whereHas('charge', function ($query){
                $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
            })->select('id')->get();
            //$professionals = $professionals->toArray();
            Log::info($professionals);
            foreach($professionals as $professional){
                Log::info($professional->id);
                $cars = Car::whereHas('reservation', function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id)->whereDate('data', Carbon::now());
                })
                ->with(['clientProfessional.client', 'reservation'])
                ->whereHas('clientProfessional', function ($query) use ($professional) {
                    $query->where('professional_id', $professional->id);
                })
                ->where('pay', 1)
                ->get();
                $carIdsPay = $cars->pluck('id');
                $rules =  BranchRuleProfessional::where('professional_id', $professional->id)->whereHas('branchRule', function ($query) use ($branch){
                    $query->where('branch_id', $branch->id)->where('estado', 3)->whereDate('data', Carbon::now());
                })->get();

                if($rules->isEmpty()){
                    $idService = BranchServiceProfessional::where('professional_id', $professional->id)->whereHas('branchService.branch', function ($query) use ($branch){
                        $query->where('branch_id', $branch->id);
                    })->where('meta', 1)->value('id');
                    if($idService!=null){
                        $orders = Order::where('branch_service_professional_id', $idService)->whereIn('car_id', $carIdsPay)->limit(4)->get();
                        if(!$orders->isEmpty()){
                            $cant = $orders->count();
                            $amount = $orders->first()->price * $cant;
                            $professionalPayment = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->where('type', 'Bono convivencias')->first();
                            if($professionalPayment == null)
                            $professionalPayment = new ProfessionalPayment();
                            $professionalPayment->branch_id = $branch->id;
                            $professionalPayment->professional_id = $professional->id;
                            $professionalPayment->date = Carbon::now();
                            $professionalPayment->amount = $amount;
                            $professionalPayment->type = 'Bono convivencias';
                            $professionalPayment->cant = $cant;
                            $professionalPayment->save();
                        /*foreach($orders as $order){
                            $order->percent_win = $order->price;
                            $order->save();
                        }*/
                    }
                    }
                }


                $profesionalbonus = BranchProfessional::where('professional_id', $professional->id)->where('branch_id', $branch->id)->first();
            
            //Venta de productos y servicios
            $orderServs = Order::whereIn('car_id', $carIdsPay)->where('is_product', 0);
            $orderServPay = $orderServs->sum('price');
            $catServices = $orderServs->count();
            if ($orderServPay >= $profesionalbonus->limit && $profesionalbonus->mountpay > 0) {
                $professionalPayment = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->where('type', 'Bono servicios')->first();
                if($professionalPayment == null)
                $professionalPayment = new ProfessionalPayment();
                $professionalPayment->branch_id = $branch->id;
                $professionalPayment->professional_id = $professional->id;
                $professionalPayment->date = Carbon::now();
                $professionalPayment->amount = $profesionalbonus->mountpay;
                $professionalPayment->type = 'Bono servicios';
                $professionalPayment->cant = $catServices;
                $professionalPayment->save();
            }
            $winProduct = 0;
            $products = Order::whereIn('car_id', $carIdsPay)
            ->where('is_product', 1)
            ->groupBy('product_store_id')
            ->selectRaw('product_store_id, SUM(cant) as total_cant, SUM(percent_win) as total_percent_win')
            ->get();
            $venta = $products->sum('total_cant');
            $percent_win = $products->sum('total_percent_win');
            Log::info('$venta');
            Log::info($venta);
            Log::info('$percent_win');
            Log::info($percent_win);
            if($venta <= 24){
                $winProduct = $percent_win*0.15;
            }else if($venta > 24 && $venta <= 49){
                $winProduct = $percent_win*0.25;
            }else{
                $winProduct = $percent_win*0.50;
            }
            Log::info('$winProduct');
            Log::info($winProduct);
            /*foreach ($products  as $product) {
                if($product->total_cant <= 24){
                    $winProduct += $product->total_percent_win*0.15;
                }
                else if ($product->total_cant < 24 && $product->total_cant <= 49) {
                    $winProduct += $product->total_percent_win*0.25;
                }
                else{
                    $winProduct += $product->total_percent_win*0.50;
                }
            }*/
            if ($winProduct > 0) {
                $professionalPayment = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->where('type', 'Bono productos')->first();
                if($professionalPayment == null)
                $professionalPayment = new ProfessionalPayment();

                $professionalPayment->branch_id = $branch->id;
                $professionalPayment->professional_id = $professional->id;
                $professionalPayment->date = Carbon::now();
                $professionalPayment->amount = $winProduct;
                $professionalPayment->type = 'Bono productos';
                $professionalPayment->cant = $venta;
                $professionalPayment->save();
            }
            /*$mountProduct = $orderProdPay->sum('price');
            if($cantProduct <= 24){

            }*/
            }



            Log::info("Generar PDF");
            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('mails.cierrecaja', ['data' => $boxClose, 'box' => $box, 'branch' => $branch]);
            $reporte = $pdf->output(); // Convertir el PDF en una cadena
            //Log::info($reporte);
            // Envía el correo electrónico con el PDF adjunto
            // $this->sendEmailService->emailBoxClosure('evylabrada@gmail.com', $reporte);
            $emails = Professional::whereHas('charge', function ($query)  use ($branch){
                $query->where('name', 'Administrador')
                    ->orWhere('name', 'Encargado')
                    ->orWhere('name', 'Administrador de Sucursal')
                    ->orWhere('name', 'Coordinador');
            })->whereHas('branches', function ($query) use ($branch){
                $query->where('branches.id', $branch->id);
            })/*whereIn('charge_id', [3, 4, 5, 12])*/
                ->pluck('email');
            //$emailassociated = Associated::all()->pluck('email');
            $emailassociated = $branch->associates()->pluck('email');
            $emailArray = $emailassociated->toArray();
            $mergedEmails = $emails->merge($emailArray);
            // Supongamos que tienes 5 direcciones de correo electrónico en un array
            $this->sendEmailService->emailBoxClosure($mergedEmails, $reporte, $branch->business['name'], $branch['name'], $box['data'], $box['cashFound'], $box['existence'], $box['extraction'], $data['totalTip'], $data['totalProduct'], $data['totalService'], $data['totalCash'], $data['totalCreditCard'], $data['totalDebit'], $data['totalTransfer'], $data['totalOther'], $data['totalMount'], $data['totalCardGif']);
            //SendEmailJob::dispatch()->emailBoxClosure($mergedEmails, $reporte, $branch->business['name'], $branch['name'], $box['data'], $box['cashFound'], $box['existence'], $box['extraction'], $data['totalTip'], $data['totalProduct'], $data['totalService'], $data['totalCash'], $data['totalCreditCard'], $data['totalDebit'], $data['totalTransfer'], $data['totalOther'], $data['totalMount']);
            /*$data = [
                'email_box_closure' => true, // Indica que es un correo de cierre de caja
                'client_email' => $mergedEmails, // Correo electrónico del cliente
                'type' => '', // Correo electrónico del cliente
                'branchBusinessName' => $branch->business['name'], // Nombre del negocio de la sucursal
                'branchName' => $branch['name'], // Nombre de la sucursal
                'boxData' => $box['data'], // Datos de la caja
                'boxCashFound' => $box['cashFound'], // Dinero encontrado en la caja
                'boxExistence' => $box['existence'], // Existencia de la caja
                'boxExtraction' => $box['extraction'], // Extracción de la caja
                'totalTip' => $data['totalTip'], // Total de propinas
                'totalProduct' => $data['totalProduct'], // Total de productos
                'totalService' => $data['totalService'], // Total de servicios
                'totalCash' => $data['totalCash'], // Total en efectivo
                'totalCreditCard' => $data['totalCreditCard'], // Total en tarjeta de crédito
                'totalDebit' => $data['totalDebit'], // Total en tarjeta de débito
                'totalTransfer' => $data['totalTransfer'], // Total en transferencias
                'totalOther' => $data['totalOther'], // Otros totales
                'totalMount' => $data['totalMount'], // Monto total
            ];
            
            SendEmailJob::dispatch($data);*/

                        //DE ESTA FORMA FUNCIONA PERO SIN UTILIZAR PLANTILLA evylabrada@gmail.com
                        /*
                        Log::info("Generar PDF");
            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('cierrecaja', ['data' => $boxClose, 'box' => $box, 'branch' => $branch]);
            $reporte = $pdf->output(); // Convertir el PDF en una cadena
            Log::info($reporte);

            // Adjuntar el PDF al correo electrónico
            Mail::send([], [], function (Message $message) use ($reporte) {
                $message->to('richardleyvap1991@gmail.com')
                        ->subject('Asunto del correo')
                        ->attachData($reporte, 'Cierre-caja.pdf');
            });
            */
            
            return response()->json(['msg' => 'Cierre de caja realizado correctamente'], 200);
        } catch (TransportException $e) {
    
            return response()->json(['msg' => 'Cierre de caja realizado correctamente.Error al enviar el correo electrónico '], 200);
  }
          catch (\Throwable $th) {
              Log::error($th);
            
              DB::rollback();
              return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(BoxClose $closeBox)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BoxClose $closeBox)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BoxClose $closeBox)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BoxClose $closeBox)
    {
        //
    }
}
