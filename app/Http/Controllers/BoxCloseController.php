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
use App\Models\CashierSale;
use App\Models\CloseBox;
use App\Models\Finance;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Professional;
use App\Models\ProfessionalPayment;
use App\Models\Retention;
use App\Services\CashierBoxClosingService;
use App\Services\MetaService;
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
    private MetaService $metaService;
    private CashierBoxClosingService $cashierBoxClosingService;
    public function __construct(SendEmailService $sendEmailService, TraceService $traceService, MetaService $metaService, CashierBoxClosingService $cashierBoxClosingService)
    {

        $this->sendEmailService = $sendEmailService;
        $this->traceService = $traceService;
        $this->metaService = $metaService;
        $this->cashierBoxClosingService = $cashierBoxClosingService;
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

        DB::beginTransaction();
        try {
            Log::info("Cierre de caja Del sistema");
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
            $userId = $request->user()->id;
            $idService = null;
            $totalBonus = 0;
            Log::info($data);
            $box = Box::whereDate('data', Carbon::now())->where('branch_id', $request->branch_id)->first();
            if (!$box) {
                $box = new Box();
                $box->existence = 0;
                $box->data = Carbon::now();
                $box->branch_id = $request->branch_id;
                $box->save();
            }
            $branch = Branch::where('id', $request->branch_id)->with('business')->first();
            $boxClose = BoxClose::where('box_id', $box->id)->first();
            if (!$boxClose) {
                $boxClose = new BoxClose();
            }
            /*$bonus = ProfessionalPayment::where('branch_id', $branch->id)->whereDate('date', Carbon::now())->where(function($query) {
                $query->where('type', 'Bono convivencias')
                    ->orWhere('type', 'Bono servicios');
            })->get()->map(function ($payment) {
                return [
                    'name' => $payment->professional->name,
                    'image_url' => $payment->professional->image_url,
                    'bonus' => $payment->type,
                    'amount' => round($payment->amount),
                ];
            }); */ 
            $totalAmount = ProfessionalPayment::where('branch_id', $branch->id)->whereDate('date', Carbon::now())->where(function($query) {
                $query->where('type', 'Bono convivencias')
                    ->orWhere('type', 'Bono servicios');
            })->sum('amount');  
            $bonus = $this->metaService->store($branch);
            $bonusCollection = collect($bonus);

            // Calcular la suma de 'amount'
            $totalBonus = $bonusCollection->sum('amount');
            Log::info('$totalBonus Bonussssssss');
            Log::info($totalBonus);

            if ($totalBonus) {      
                Log::info('Entra a descontar los bonos de la existencia');
                // Calcular la suma de los montos
                //$totalAmount = $subquery->sum('amount');
                $difference = $totalBonus - $totalAmount;
                Log::info('Diferencia de bono ierre de caja'.$difference);
                  // Ajustar la existencia de $box según la diferencia
                    // Si la diferencia es positiva, se resta de box->existence
                    // Si es negativa, se suma a box->existence
                    $box->existence -= $difference;
                    $box->save(); // Guardar los cambios en $box
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
            $boxClose->user_id = $userId;
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
            DB::commit();
            //$professionals = $professionals->toArray();
            Log::info("Generar PDF");
            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('mails.cierrecaja', ['data' => $boxClose, 'box' => $box, 'branch' => $branch, 'totalBonus' => $totalBonus]);
            $reporte = $pdf->output(); // Convertir el PDF en una cadena
            $emails = Professional::whereHas('charge', function ($query)  use ($branch) {
                $query->where('name', 'Administrador')
                    ->orWhere('name', 'Encargado')
                    ->orWhere('name', 'Administrador de Sucursal')
                    ->orWhere('name', 'Coordinador');
            })->whereHas('branches', function ($query) use ($branch) {
                $query->where('branches.id', $branch->id);
            })/*whereIn('charge_id', [3, 4, 5, 12])*/
                ->pluck('email');
            $emailassociated = $branch->associates()->pluck('email');
            $emailArray = $emailassociated->toArray();
            $mergedEmails = $emails->merge($emailArray);
            Log::info('$mergedEmails correos a enviar cierre de caja');
            Log::info($mergedEmails);
            foreach ($mergedEmails as $email) {
                try {
                    $this->sendEmailService->emailBoxClosure($email, $reporte, $branch->business['name'], $branch['name'], $box['data'], $box['cashFound'], $box['existence'], $box['extraction'], $data['totalTip'], $data['totalProduct'], $data['totalService'], $data['totalCash'], $data['totalCreditCard'], $data['totalDebit'], $data['totalTransfer'], $data['totalOther'], $data['totalMount'], $data['totalCardGif'], $totalBonus);
                } catch (\Swift_TransportException $e) {
                    Log::error("Error al enviar correo a $email: " . $e->getMessage());
                } catch (\Exception $e) {
                    Log::error("Error general al enviar correo a $email: " . $e->getMessage());
                }
            }
            // Supongamos que tienes 5 direcciones de correo electrónico en un array
            /*$this->sendEmailService->emailBoxClosure($mergedEmails, $reporte, $branch->business['name'], $branch['name'], $box['data'], $box['cashFound'], $box['existence'], $box['extraction'], $data['totalTip'], $data['totalProduct'], $data['totalService'], $data['totalCash'], $data['totalCreditCard'], $data['totalDebit'], $data['totalTransfer'], $data['totalOther'], $data['totalMount'], $data['totalCardGif'], $totalBonus);*/

            return response()->json(['msg' => 'Cierre de caja realizado correctamente', 'bonus' => $bonus], 200);
        } catch (TransportException $e) {

            return response()->json(['msg' => 'Cierre de caja realizado correctamente.Error al enviar el correo electrónico '], 200);
        } catch (\Throwable $th) {
            Log::info('BoxCloseController->store');
            Log::error($th);

            DB::rollback();
            return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }

    public function store_cashier(Request $request)
    {

        DB::beginTransaction();
        try {
            Log::info("Cierre de caja parcial");
            $request->validate([
                'editedCloseBox' => 'required|array',
                'cashierData' => 'required|array',
                'car_ids'  => 'required|array',
                'branch_id' => 'required|integer',
                'nameProfessional' => 'required|string',
            ]);
            
            // Obtener los datos del request
            $editedCloseBox = $request->input('editedCloseBox');
            $cashierData = $request->input('cashierData');
            $car_ids = $request->input('car_ids');
            $branchId = $request->input('branch_id');
            $nameProfessional = $request->input('nameProfessional');
    
            // Log para depuración
            Log::info('Datos recibidos para cerrar caja:', [
                'editedCloseBox' => $editedCloseBox,
                'cashierData' => $cashierData,
                'car_ids' => $car_ids,
                'branch_id' => $branchId,
                'nameProfessional' => $nameProfessional,
            ]);
            
            $userId = $request->user()->id;
            $idService = null;
            $totalBonus = 0;
            $box = Box::whereDate('data', Carbon::now())->where('branch_id', $request->branch_id)->first();
            if (!$box) {
                $box = new Box();
                $box->existence = 0;
                $box->data = Carbon::now();
                $box->branch_id = $request->branch_id;
                $box->save();
            }
            $branch = Branch::where('id', $request->branch_id)->with('business')->first();
            $boxClose = BoxClose::where('box_id', $box->id)->first();
            if (!$boxClose) {
                $boxClose = new BoxClose();
            }
            /*$bonus = ProfessionalPayment::where('branch_id', $branch->id)->whereDate('date', Carbon::now())->where(function($query) {
                $query->where('type', 'Bono convivencias')
                    ->orWhere('type', 'Bono servicios');
            })->get()->map(function ($payment) {
                return [
                    'name' => $payment->professional->name,
                    'image_url' => $payment->professional->image_url,
                    'bonus' => $payment->type,
                    'amount' => round($payment->amount),
                ];
            }); */ 
            /*$totalAmount = ProfessionalPayment::where('branch_id', $branch->id)->whereDate('date', Carbon::now())->where(function($query) {
                $query->where('type', 'Bono convivencias')
                    ->orWhere('type', 'Bono servicios');
                })->sum('amount');  
                $bonus = $this->metaService->store($branch);
                $bonusCollection = collect($bonus);

                // Calcular la suma de 'amount'
                $totalBonus = $bonusCollection->sum('amount');
                Log::info('$totalBonus Bonussssssss');
                Log::info($totalBonus);

                if ($totalBonus) {      
                    Log::info('Entra a descontar los bonos de la existencia');
                    // Calcular la suma de los montos
                    //$totalAmount = $subquery->sum('amount');
                    $difference = $totalBonus - $totalAmount;
                    Log::info('Diferencia de bono ierre de caja'.$difference);
                    // Ajustar la existencia de $box según la diferencia
                        // Si la diferencia es positiva, se resta de box->existence
                        // Si es negativa, se suma a box->existence
                        $box->existence -= $difference;
                        $box->save(); // Guardar los cambios en $box
            }*/
            Log::info($box->id);
            $boxClose->box_id = $box->id;
            $boxClose->totalMount = $editedCloseBox['totalMount'];
            $boxClose->totalService = $editedCloseBox['totalService'];
            $boxClose->totalProduct = $editedCloseBox['totalProduct'];
            $boxClose->totalTip = $editedCloseBox['totalTip'];
            $boxClose->totalCash = $editedCloseBox['totalCash'];
            $boxClose->totalCreditCard = $editedCloseBox['totalCreditCard'];
            $boxClose->totalDebit = $editedCloseBox['totalDebit'];
            $boxClose->totalTransfer = $editedCloseBox['totalTransfer'];
            $boxClose->totalOther = $editedCloseBox['totalOther'];
            $boxClose->totalCardGif = $editedCloseBox['totalCardGif'];
            $boxClose->user_id = $userId;
            $boxClose->type = 'Parcial';
            $boxClose->data = Carbon::now();
            $boxClose->save();

            $trace = [
                'branch' => $branch->name,
                'cashier' => $request->nameProfessional,
                'client' => '',
                'amount' => $cashierData['totalMount'],
                'operation' => 'Cierre de Caja Parcial',
                'details' => 'Ingreso diario Parcial',
                'description' => ''
            ];
            $this->traceService->store($trace);
            $cashierData['branch_id'] = $branchId;
            $cashierData['user_id'] = $userId;
            $cashierData['data'] = Carbon::now();
            $this->cashierBoxClosingService->upsertCashierBoxClosing($cashierData);

            // Actualizar los registros en la tabla cars
            Car::whereIn('id', $car_ids)
            ->update(['user_id' => $userId]);
            Log::info('$trace');
            Log::info($trace);
            DB::commit();
            //$professionals = $professionals->toArray();
            /*Log::info("Generar PDF");
            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('mails.cierrecaja', ['data' => $boxClose, 'box' => $box, 'branch' => $branch, 'totalBonus' => $totalBonus]);
            $reporte = $pdf->output(); // Convertir el PDF en una cadena
            $emails = Professional::whereHas('charge', function ($query)  use ($branch) {
                $query->where('name', 'Administrador')
                    ->orWhere('name', 'Encargado')
                    ->orWhere('name', 'Administrador de Sucursal')
                    ->orWhere('name', 'Coordinador');
            })->whereHas('branches', function ($query) use ($branch) {
                $query->where('branches.id', $branch->id);
            })/*whereIn('charge_id', [3, 4, 5, 12])*/
                /*->pluck('email');
            $emailassociated = $branch->associates()->pluck('email');
            $emailArray = $emailassociated->toArray();
            $mergedEmails = $emails->merge($emailArray);
            Log::info('$mergedEmails correos a enviar cierre de caja');
            Log::info($mergedEmails);
            foreach ($mergedEmails as $email) {
                try {
                    $this->sendEmailService->emailBoxClosure($email, $reporte, $branch->business['name'], $branch['name'], $box['data'], $box['cashFound'], $box['existence'], $box['extraction'], $data['totalTip'], $data['totalProduct'], $data['totalService'], $data['totalCash'], $data['totalCreditCard'], $data['totalDebit'], $data['totalTransfer'], $data['totalOther'], $data['totalMount'], $data['totalCardGif'], $totalBonus);
                } catch (\Swift_TransportException $e) {
                    Log::error("Error al enviar correo a $email: " . $e->getMessage());
                } catch (\Exception $e) {
                    Log::error("Error general al enviar correo a $email: " . $e->getMessage());
                }
            }
            // Supongamos que tienes 5 direcciones de correo electrónico en un array
            /*$this->sendEmailService->emailBoxClosure($mergedEmails, $reporte, $branch->business['name'], $branch['name'], $box['data'], $box['cashFound'], $box['existence'], $box['extraction'], $data['totalTip'], $data['totalProduct'], $data['totalService'], $data['totalCash'], $data['totalCreditCard'], $data['totalDebit'], $data['totalTransfer'], $data['totalOther'], $data['totalMount'], $data['totalCardGif'], $totalBonus);*/

            return response()->json(['msg' => 'Cierre de caja realizado correctamente'/*, 'bonus' => $bonus*/], 200);
        } catch (TransportException $e) {

            return response()->json(['msg' => 'Cierre de caja realizado correctamente.Error al enviar el correo electrónico '], 200);
        } catch (\Throwable $th) {
            Log::info('BoxCloseController->store');
            Log::error($th);

            //DB::rollback();
            return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }

    public function box_close_new_ANTERIOR(Request $request)
    {

        DB::beginTransaction();
        try {
            Log::info("Cierre de caja Forzado");
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'data' => 'required|date'
            ]);
            $idService = null;
            $totalBonus = 0;
            Log::info($data);
            $box = Box::whereDate('data', $data['data'])->where('branch_id', $data['branch_id'])->first();
            if (!$box) {
                $box = new Box();
                $box->existence = 0;
                $box->data = Carbon::now();
                $box->branch_id = $request->branch_id;
                $box->save();
            }
            $branch = Branch::where('id', $data['branch_id'])->with('business')->first();
            $boxClose = BoxClose::where('box_id', $box->id)->first();
            if (!$boxClose) {
                $boxClose = new BoxClose();
            }
            $totalAmount = ProfessionalPayment::where('branch_id', $data['branch_id'])->whereDate('date', $data['data'])->where(function($query) {
                $query->where('type', 'Bono convivencias')
                    ->orWhere('type', 'Bono servicios');
            })->sum('amount');  
            $professionals = Professional::whereHas('branches', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id);
            })->whereHas('charge', function ($query) {
                $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
            })->select('id', 'name', 'image_url', 'retention')->get();
            foreach ($professionals as $professional) {                
            $bonus[] = $this->metaService->store_box_close($branch, $data['data'], $professional->id);
            }
            $bonusCollection = collect($bonus);

            // Calcular la suma de 'amount'
            $totalBonus = $bonusCollection->sum('amount');
            Log::info('$totalBonus Bonussssssss');
            Log::info($totalBonus);

            if ($totalBonus) {      
                Log::info('Entra a descontar los bonos de la existencia');
                // Calcular la suma de los montos
                //$totalAmount = $subquery->sum('amount');
                $difference = $totalBonus - $totalAmount;
                Log::info('Diferencia de bono cierre de caja'.$difference);
                  // Ajustar la existencia de $box según la diferencia
                    // Si la diferencia es positiva, se resta de box->existence
                    // Si es negativa, se suma a box->existence
                    $box->existence -= $difference;
                    $box->save(); // Guardar los cambios en $box
            }
            Log::info($box->id);
            $payments = Payment::where('branch_id', $data['branch_id'])->whereDate('created_at', $data['data'])->get();
            $cars_id = $payments->pluck('car_id');
            $cars = Car::whereIn('id', $cars_id)->get();
            $orders = Order::whereIn('car_id', $cars_id)->get();
            $cashiers = CashierSale::where('branch_id', $data['branch_id'])->whereDate('data', $data['data'])->get();
            $services = $orders->where('is_product', 0)->sum('price');
            $products = $orders->where('is_product', 1)->sum('price') + $cashiers->sum('price');
            $total = $services + $products;
            $boxClose->box_id = $box->id;
            $boxClose->totalMount = $total;
            $boxClose->totalService = $services;
            $boxClose->totalProduct = $products;
            $boxClose->totalTip = $cars->sum('tip');
            $boxClose->totalCash = $payments->sum('cash');
            $boxClose->totalCreditCard = $payments->sum('creditCard');
            $boxClose->totalDebit = $payments->sum('debit');
            $boxClose->totalTransfer = $payments->sum('transfer');
            $boxClose->totalOther = $payments->sum('other');
            $boxClose->totalCardGif = $payments->sum('cardGif');
            $boxClose->data = $data['data'];
            $boxClose->save();
            DB::commit();
            //$professionals = $professionals->toArray();
            Log::info('Cierre de la caja');
            Log::info($boxClose);
            Log::info('Caja');
            Log::info($box);
            Log::info('Bonos');
            Log::info($totalBonus);
            Log::info("Generar PDF");
            
            return response()->json(['msg' => 'Cierre de caja realizado correctamente', 'bonus' => $bonus], 200);
        } catch (TransportException $e) {

            return response()->json(['msg' => 'Cierre de caja realizado correctamente.Error al enviar el correo electrónico '], 200);
        } catch (\Throwable $th) {
            Log::info('BoxCloseController->store');
            Log::error($th);

            DB::rollback();
            return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }
    
      public function box_close_new(Request $request)
    {

        DB::beginTransaction();
        try {
            Log::info("Cierre de caja Forzado");
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'data' => 'required|date'
            ]);
            $idService = null;
            $totalBonus = 0;
            Log::info($data);
            $box = Box::whereDate('data', $data['data'])->where('branch_id', $data['branch_id'])->first();
            if (!$box) {
                $box = new Box();
                $box->existence = 0;
                $box->data = Carbon::now();
                $box->branch_id = $request->branch_id;
                $box->save();
            }
            $branch = Branch::where('id', $data['branch_id'])->with('business')->first();
            $boxClose = BoxClose::where('box_id', $box->id)->first();
            if (!$boxClose) {
                $boxClose = new BoxClose();
            }
            $totalAmount = ProfessionalPayment::where('branch_id', $data['branch_id'])->whereDate('date', $data['data'])->where(function($query) {
                $query->where('type', 'Bono convivencias')
                    ->orWhere('type', 'Bono servicios');
            })->sum('amount');  
            $professionals = Professional::whereHas('branches', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id);
            })->whereHas('charge', function ($query) {
                $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
            })->select('id', 'name', 'image_url', 'retention')->get();
            foreach ($professionals as $professional) {                
            $bonus[] = $this->metaService->store_box_close($branch, $data['data'], $professional->id);
            }
            $bonusCollection = collect($bonus);

            // Aplanar el array para acceder directamente a los valores de 'amount'
            $flattenedBonus = $bonusCollection->flatMap(function ($item) {
                return collect($item); // Asegurarse de que cada elemento sea una colección
            });

            // Calcular la suma de 'amount'
            $totalBonus = $flattenedBonus->sum('amount');
            Log::info('$totalBonus Bonussssssss');
            Log::info($totalBonus);

            if ($totalBonus) {      
                Log::info('Entra a descontar los bonos de la existencia');
                // Calcular la suma de los montos
                //$totalAmount = $subquery->sum('amount');
                $difference = $totalBonus - $totalAmount;
                Log::info('Diferencia de bono cierre de caja'.$difference);
                  // Ajustar la existencia de $box según la diferencia
                    // Si la diferencia es positiva, se resta de box->existence
                    // Si es negativa, se suma a box->existence
                    $box->existence -= $difference;
                    $box->save(); // Guardar los cambios en $box
            }
            Log::info($box->id);
            $payments = Payment::where('branch_id', $data['branch_id'])->whereDate('created_at', $data['data'])->get();
            $cars_id = $payments->pluck('car_id');
            $cars = Car::whereIn('id', $cars_id)->get();
            $orders = Order::whereIn('car_id', $cars_id)->get();
            $cashiers = CashierSale::where('branch_id', $data['branch_id'])->whereDate('data', $data['data'])->get();
            $services = $orders->where('is_product', 0)->sum('price');
            $products = $orders->where('is_product', 1)->sum('price') + $cashiers->sum('price');
            $totalMount = $services + $products + $cars->sum('tip');
            $boxClose->box_id = $box->id;
            $boxClose->totalMount = $totalMount;
            $boxClose->totalService = $services;
            $boxClose->totalProduct = $products;
            $boxClose->totalTip = $cars->sum('tip');
            $boxClose->totalCash = $payments->sum('cash');
            $boxClose->totalCreditCard = $payments->sum('creditCard');
            $boxClose->totalDebit = $payments->sum('debit');
            $boxClose->totalTransfer = $payments->sum('transfer');
            $boxClose->totalOther = $payments->sum('other');
            $boxClose->totalCardGif = $payments->sum('cardGif');
            $boxClose->data = $data['data'];
            $boxClose->save();

            DB::commit();
            //$professionals = $professionals->toArray();
            Log::info('Cierre de la caja');
            Log::info($boxClose);
            Log::info('Caja');
            Log::info($box);
            Log::info('Bonos');
            Log::info($totalBonus);
            Log::info("Generar PDF");
            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('mails.cierrecaja', ['data' => $boxClose, 'box' => $box, 'branch' => $branch, 'totalBonus' => $totalBonus]);
            $reporte = $pdf->output(); // Convertir el PDF en una cadena
            $this->sendEmailService->emailBoxClosure('yasmany891230@gmail.com', $reporte, $branch->business['name'], $branch['name'], $box['data'], $box['cashFound'], $box['existence'], $box['extraction'], $cars->sum('tip'), $products, $services, $payments->sum('cash'), $payments->sum('creditCard'), $payments->sum('debit'), $payments->sum('transfer'), $payments->sum('other'), $totalMount, $payments->sum('cardGif'), $totalBonus);
            /*$emails = Professional::whereHas('charge', function ($query)  use ($branch) {
                $query->where('name', 'Administrador')
                    ->orWhere('name', 'Encargado')
                    ->orWhere('name', 'Administrador de Sucursal')
                    ->orWhere('name', 'Coordinador');
            })->whereHas('branches', function ($query) use ($branch) {
                $query->where('branches.id', $branch->id);
            })/*whereIn('charge_id', [3, 4, 5, 12])*/
                /*->pluck('email');
            $emailassociated = $branch->associates()->pluck('email');
            $emailArray = $emailassociated->toArray();
            $mergedEmails = $emails->merge($emailArray);*/
            //Log::info('$mergedEmails correos a enviar cierre de caja');
            //Log::info($mergedEmails);
            /*foreach ($mergedEmails as $email) {
                try {
                    $this->sendEmailService->emailBoxClosure($email, $reporte, $branch->business['name'], $branch['name'], $box['data'], $box['cashFound'], $box['existence'], $box['extraction'], $data['totalTip'], $data['totalProduct'], $data['totalService'], $data['totalCash'], $data['totalCreditCard'], $data['totalDebit'], $data['totalTransfer'], $data['totalOther'], $data['totalMount'], $data['totalCardGif'], $totalBonus);
                } catch (\Swift_TransportException $e) {
                    Log::error("Error al enviar correo a $email: " . $e->getMessage());
                } catch (\Exception $e) {
                    Log::error("Error general al enviar correo a $email: " . $e->getMessage());
                }
            }*/
            // Supongamos que tienes 5 direcciones de correo electrónico en un array
            //$this->sendEmailService->emailBoxClosure($mergedEmails, $reporte, $branch->business['name'], $branch['name'], $box['data'], $box['cashFound'], $box['existence'], $box['extraction'], $data['totalTip'], $data['totalProduct'], $data['totalService'], $data['totalCash'], $data['totalCreditCard'], $data['totalDebit'], $data['totalTransfer'], $data['totalOther'], $data['totalMount'], $data['totalCardGif'], $totalBonus);

            return response()->json(['msg' => 'Cierre de caja realizado correctamente', 'bonus' => $bonus], 200);
        } catch (TransportException $e) {

            return response()->json(['msg' => 'Cierre de caja realizado correctamente.Error al enviar el correo electrónico '], 200);
        } catch (\Throwable $th) {
            Log::info('BoxCloseController->store');
            Log::error($th);

            DB::rollback();
            return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }

    public function store1(Request $request)
    {

            DB::beginTransaction();
        try {
            Log::info("Editar");
            $data = $request->validate([
                //'box_id' => 'required|numeric',
                'data' => 'required|date',
                'branch_id' => 'required|numeric',
                'professional_id' => 'required|numeric'
            ]);
            $idService = null;
            $totalBonus = 0;
            Log::info($data);
            $box = Box::whereDate('data', $data['data'])->where('branch_id', $data['branch_id'])->first();
            if (!$box) {
                $box = new Box();
                $box->existence = 0;
                $box->data = Carbon::now();
                $box->branch_id = $request->branch_id;
                $box->save();
            }
            $branch = Branch::where('id', $data['branch_id'])->with('business')->first();
            $boxClose = BoxClose::where('box_id', $box->id)->first();
            if (!$boxClose) {
                $boxClose = new BoxClose();
            }
            $totalAmount = ProfessionalPayment::where('branch_id', $branch->id)->whereDate('date', $data['data'])->where('professional_id', $data['professional_id'])->where(function($query) {
                $query->where('type', 'Bono convivencias')
                    ->orWhere('type', 'Bono servicios');
            })->sum('amount');  
            $bonus = $this->metaService->store1($branch, $data['data'], $data['professional_id']);
            $bonusCollection = collect($bonus);

            // Calcular la suma de 'amount'
            $totalBonus = $bonusCollection->sum('amount');
            Log::info('$totalBonus Bonussssssss');
            Log::info($totalBonus);

            if ($totalBonus) {      
                Log::info('Entra a descontar los bonos de la existencia');
                // Calcular la suma de los montos
                //$totalAmount = $subquery->sum('amount');
                $difference = $totalBonus - $totalAmount;
                Log::info('Diferencia de bono ierre de caja'.$difference);
                  // Ajustar la existencia de $box según la diferencia
                    // Si la diferencia es positiva, se resta de box->existence
                    // Si es negativa, se suma a box->existence
                    $box->existence -= $difference;
                    $box->save(); // Guardar los cambios en $box
            }
            DB::commit();
            return response()->json(['msg' => 'Pago de bonos realizado correctamente', 'bonus' => $bonus], 200);
        } catch (TransportException $e) {

            return response()->json(['msg' => 'Cierre de caja realizado correctamente.Error al enviar el correo electrónico '], 200);
        } catch (\Throwable $th) {
            Log::info('BoxCloseController->store1');
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

    public function bonus(Request $request)
    {
        Log::info("Mostrar los bonos");
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);

            $bonus = $this->metaService->bonus($data['branch_id']);
            $bonus = collect($bonus)->map(function ($bono) {
                $professionalPayment = ProfessionalPayment::where('branch_id', $bono['branch_id'])
                    ->where('professional_id', $bono['professional_id'])
                    ->whereDate('date', Carbon::now())
                    ->where('type', $bono['bonus'])
                    ->first();
            
                // Agregar nueva columna 'pay'
                $bono['pay'] = $professionalPayment != null;
                
                return $bono;
            })->toArray();
            return response()->json(['bonus' => $bonus], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }

    public function bonu_payment(Request $request)
    {
        Log::info("Pagar un bono");
        DB::beginTransaction();
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric|exists:branches,id',
                'professional_id' => 'required|numeric|exists:professionals,id',
                'order_id' => 'nullable',
                'name' => 'required|max:250',
                'amount' => 'required',
                'type' => 'required',
                'cant' => 'required',
                'retention' => 'required',
            ]);

            Log::info('Datos del Bono');
            Log::info($data);
            $totalAmount = 0;
            
            //$finance = Finance::where('branch_id', $branch->id)->where('expense_id', 5)->whereDate('data', Carbon::now())orderBy('control', 'desc')->first();
            $finance = Finance::orderBy('control', 'desc')->first();
            if ($finance !== null) {
                $control = $finance->control + 1;
            } else {
                $control = 1;
            }
            //return 1;
            $professional = Professional::find($data['professional_id']);
            $box = Box::whereDate('data', Carbon::now())->where('branch_id', $data['branch_id'])->first();
            Log::info('Caja');
            Log::info($box);
            Log::info('Valor de type en $data: ' . $data['type']);
            Log::info('Tipo de dato de $data["type"]: ' . gettype($data['type']));
            Log::info('Valor de type en $data: ' . strval($data['type']));
            if(strval($data['type']) === "Bono convivencias"){
                Log::info('Entro a convivencias'.Carbon::now()->toDateString());
                $subquery = ProfessionalPayment::where('branch_id', $data['branch_id'])->where('professional_id', $data['professional_id'])->whereDate('date', Carbon::now())->where('type', $data['type'])->first();
                Log::info('Paso a Subquery');
                Log::info($subquery);
                if ($subquery != null) {
                     // Calcular la suma de los montos
                $totalAmount = $subquery->amount;
                Log::info('Paso a totalAmount');
                Log::info($totalAmount);
                // Eliminar los registros
                $subquery->delete();
                }
                Log::info('Comprobacion de a Subquery');
                if ($totalAmount != 0) {
                    Log::info('Paso a totalAmount con datos');
                     // Calcular la diferencia entre el monto actual y el nuevo monto
                     $difference = $data['amount'] - $totalAmount;
                     Log::info('Diferencia de bono Convivencia'.$difference);
                     // Ajustar la existencia de $box según la diferencia
                    if ($box != null) {
                        // Si la diferencia es positiva, se resta de box->existence
                        // Si es negativa, se suma a box->existence
                        $box->existence -= $difference;
                        $box->save(); // Guardar los cambios en $box
                    }
                }
                else {
                    Log::info('Paso a totalAmount igual a 0');
                    if ($box != null) {
                        Log::info('Entra a la caja a descontar existencia');
                        // Si la diferencia es positiva, se resta de box->existence
                        // Si es negativa, se suma a box->existence
                        $box->existence -= $data['amount'];
                        $box->save(); // Guardar los cambios en $box
                    }
                }
                //if ($professionalPayment == null) {
                $professionalPayment = new ProfessionalPayment();
                //}
                $professionalPayment->branch_id = $data['branch_id'];
                $professionalPayment->professional_id = $professional->id;
                $professionalPayment->date = Carbon::now();
                $professionalPayment->amount = $data['amount'];
                $professionalPayment->type = $data['type'];
                $professionalPayment->cant = $data['cant'];
                $professionalPayment->save();
                $finance = Finance::where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now())->where('comment', 'Gasto por pago de bono de convivencias a ' . $professional->name)->first();
                if ($finance !=null) {                               
                    // Actualizar el monto de finance
                    $finance->amount = $data['amount'];
                    $finance->save();
                }else{
                    $finance = new Finance();
                    $finance->control = $control++;
                    $finance->operation = 'Gasto';
                    $finance->amount = $data['amount'];
                    $finance->comment = 'Gasto por pago de bono de convivencias a ' . $professional->name;
                    $finance->branch_id = $data['branch_id'];
                    $finance->type = 'Sucursal';
                    $finance->expense_id = 5;
                    $finance->data = Carbon::now();
                    $finance->file = '';
                    $finance->save();  
                    
                    /*// Restar el monto a la existencia de $box
                    if ($box != null) {
                        $box->existence -= $finance->amount;
                        $box->save(); // Guardar los cambios en $box
                    }*/
                }   
                //Retention                         
                $retention = Retention::where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now())->where('professional_id', $data['professional_id'])->where('type', 'BonoConvivencia')->first();
                if ($retention == null) {
                    $retention = new Retention();
                }                                
                //if($retentionP){
                    Log::info('Entra a retencion bono de convivencias'.$professional->name);
                    $retention->branch_id = $data['branch_id'];
                    $retention->professional_id = $professional->id;
                    $retention->data = Carbon::now();
                    $retention->retention = $data['retention'];
                    $retention->type = 'BonoConvivencia';
                    $retention->save();
                //}
                

                // Ejemplo de una acción, como actualizar un campo
                Order::whereIn('id', $data['order_id'])->update(['meta' => 1, 'percent_win' => 0]);
            }
            if (strval($data['type']) === "Bono servicios"){
                $subquery = ProfessionalPayment::where('branch_id', $data['branch_id'])->where('professional_id', $data['professional_id'])->whereDate('date', Carbon::now()->toDateString())->where('type', $data['type'])->first();
                Log::info('Paso a Subquery');
                Log::info($subquery);
                if ($subquery != null) {
                     // Calcular la suma de los montos
                $totalAmount = $subquery->amount;
                Log::info('Paso a totalAmount');
                Log::info($totalAmount);
                // Eliminar los registros
                $subquery->delete();
                }

                if ($totalAmount) {
                     // Calcular la diferencia entre el monto actual y el nuevo monto
                     $difference = $data['amount'] - $totalAmount;
                     Log::info('Diferencia de bono Servicio'.$difference);
                     // Ajustar la existencia de $box según la diferencia
                    if ($box != null) {
                        // Si la diferencia es positiva, se resta de box->existence
                        // Si es negativa, se suma a box->existence
                        $box->existence -= $difference;
                        $box->save(); // Guardar los cambios en $box
                    }
                }
                else {
                    if ($box != null) {
                        // Si la diferencia es positiva, se resta de box->existence
                        // Si es negativa, se suma a box->existence
                        $box->existence -= $data['amount'];
                        $box->save(); // Guardar los cambios en $box
                    }
                }
                    $professionalPaymentService = new ProfessionalPayment();
                //}
                $professionalPaymentService->branch_id = $data['branch_id'];
                $professionalPaymentService->professional_id = $professional->id;
                $professionalPaymentService->date = Carbon::now();
                $professionalPaymentService->amount = $data['amount'];
                $professionalPaymentService->type = $data['type'];
                $professionalPaymentService->cant = $data['cant'];
                $professionalPaymentService->save();

                $finance = Finance::where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now())->where('comment', 'Gasto por pago de bono de servicios a ' . $professional->name)->first();
                if ($finance !=null) {
                    // Actualizar el monto de finance
                    $finance->amount = $data['amount'];
                    $finance->save();
                }else {
                    $finance = new Finance();
                    $finance->control = $control++;
                    $finance->operation = 'Gasto';
                    $finance->amount = $data['amount'];
                    $finance->comment = 'Gasto por pago de bono de servicios a ' . $professional->name;
                    $finance->branch_id = $data['branch_id'];
                    $finance->type = 'Sucursal';
                    $finance->expense_id = 5;
                    $finance->data = Carbon::now();
                    $finance->file = '';
                    $finance->save();

                    // Restar el monto a la existencia de $box
                    /*if ($box != null) {
                        $box->existence -= $finance->amount;
                        $box->save(); // Guardar los cambios en $box
                    }*/
                }
                $retention = Retention::where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now())->where('professional_id', $data['professional_id'])->where('type', 'BonoService')->first();
                if ($retention == null) {
                    $retention = new Retention();
                }
                    //if($retentionP){
                        Log::info('Entra a retencion bono de servicios'.$professional->name);
                        $retention->branch_id = $data['branch_id'];
                        $retention->professional_id = $professional->id;
                        $retention->data = Carbon::now();
                        $retention->retention = $data['retention'];
                        $retention->type = 'BonoService';
                        $retention->save();
                    //}
                
            }
            DB::commit();
            return response()->json(['msg' => 'Pago realizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info('BoxCloseController->bonu_payment');
            Log::error($th);

            DB::rollback();
            return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }

    public function box_close_month_ANTERIOR()
    {
        try {
            // Obtener la fecha actual
            $now = Carbon::now();

            // Obtener el mes y año del mes anterior
            //$mesAnterior = $now->month;
            //$añoAnterior = $now->year;
            $mesAnterior = $now->subMonth()->month; // Devuelve el mes anterior
            $añoAnterior = $now->subMonth()->year; // Devuelve el año anterior
            //$boxCloseData = [];
            $professionalsData = [];

            $ingreso = 0;
            $gasto = 0;
            $branches = Branch::all();
            foreach ($branches as $branch) {
                Finance::where('branch_id', $branch->id)
                ->whereYear('data', $añoAnterior)->whereMonth('data', $mesAnterior)
                ->where('operation', 'Gasto')
                ->where('comment', 'like', '%Gasto por pago de bono de productos%')
                ->delete();
                 //Retention
                Retention::where('branch_id', $branch->id)
                ->whereYear('data', $añoAnterior)->whereMonth('data', $mesAnterior)->where('type', 'Products')->delete();

                ProfessionalPayment::where('branch_id', $branch->id)->whereYear('date', $añoAnterior)->whereMonth('date', $mesAnterior)->where('type', 'Bono productos')->delete();

                $boxCloseData = [];
                $winProduct = 0;
                $professionalsData = [];
                $ingreso = 0;
                $gasto = 0;
                $boxClose = BoxClose::whereHas('box', function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id);
                })->whereYear('data', $añoAnterior)->whereMonth('data', $mesAnterior)->selectRaw('
                SUM(totalMount) as totalMount,
                SUM(totalService) as totalService,
                SUM(totalProduct) as totalProduct,
                SUM(totalTip) as totalTip,
                SUM(totalCash) as totalCash,
                SUM(totalDebit) as totalDebit,
                SUM(totalCreditCard) as totalCreditCard,
                SUM(totalTransfer) as totalTransfer,
                SUM(totalOther) as totalOther,
                SUM(totalCardGif) as totalCardGif
            ')->first();
                /*$boxCloseArray = [
                    'totalMount' => $boxClose->totalMount ?? 0,
                    'totalService' => $boxClose->totalService ?? 0,
                    'totalProduct' => $boxClose->totalProduct ?? 0,
                    'totalTip' => $boxClose->totalTip ?? 0,
                    'totalCash' => $boxClose->totalCash ?? 0,
                    'totalDebit' => $boxClose->totalDebit ?? 0,
                    'totalCreditCard' => $boxClose->totalCreditCard ?? 0,
                    'totalTransfer' => $boxClose->totalTransfer ?? 0,
                    'totalOther' => $boxClose->totalOther ?? 0,
                    'totalcardGif' => $boxClose->totalcardGif ?? 0,
                    'branch_name' => $branch->name,
                    'ingreso' => round($ingreso, 2),
                    'gasto' => round($gasto, 2),
                    'utilidad' => round($ingreso - $gasto, 2)
                ];

                // Agregar al array de resultados
                $boxCloseData[] = $boxCloseArray;*/
                $professionals = Professional::whereHas('branches', function ($query) use ($branch) {
                        $query->where('branch_id', $branch->id);
                    })->whereHas('charge', function ($query) {
                        $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
                    })->select('id', 'name', 'surname', 'retention')->get();
                    foreach ($professionals as $professional) {
                        $cars = Car::whereHas('reservation', function ($query) use ($branch, $añoAnterior, $mesAnterior) {
                            $query->where('branch_id', $branch->id)->whereYear('data', $añoAnterior)->whereMonth('data', $mesAnterior);
                        })
                            ->with(['clientProfessional.client', 'reservation'])
                            ->whereHas('clientProfessional', function ($query) use ($professional) {
                                $query->where('professional_id', $professional->id);
                            })
                            ->where('pay', 1)
                            ->get();
                        $carIdsPay = $cars->pluck('id');
                        $products = Order::whereIn('car_id', $carIdsPay)
                            ->where('is_product', 1)
                            ->groupBy('product_store_id')
                            ->selectRaw('product_store_id, SUM(cant) as total_cant, SUM(percent_win) as total_percent_win')
                            ->get();
                    $venta = $products->sum('total_cant');
                    $percent_win = $products->sum('total_percent_win');
                    if ($venta <= 24) {
                        $winProduct = $percent_win * 0.15;
                    } else if ($venta > 24 && $venta <= 49) {
                        $winProduct = $percent_win * 0.25;
                    } else {
                        $winProduct = $percent_win * 0.50;
                    }
                    Log::info('Bono producto'.$winProduct.$professional->name);
                    // Agregar los datos del profesional al arreglo solo si $winProduct es mayor que 0
                    if ($winProduct > 0) {
                        $retention = $professional->retention;
                    if ($retention) {
                        $resultRetention = round($winProduct * $retention / 100, 2);
                        $winProduct = $winProduct - $resultRetention;
                        $retention = new Retention();
                        $retention->branch_id = $branch->id;
                        $retention->professional_id = $professional->id;
                        $retention->data = Carbon::now();
                        $retention->retention = $resultRetention;
                        $retention->type = 'Products';
                        $retention->save();
                    }
                        $professionalData = [
                            'name' => $professional->name,
                            'winProduct' => $winProduct,
                        ];

                        $finance = Finance::orderBy('control', 'desc')->first();
                        if ($finance !== null) {
                            $control = $finance->control + 1;
                        } else {
                            $control = 1;
                        }
                        Log::info('Bono de Producto'.$winProduct.$professional->name);
                        //$professionalPayment = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->where('type', 'Bono productos')->first();
                        //if ($filteredPayments->isEmpty()) {
                            $professionalPayment = new ProfessionalPayment();
                            $professionalPayment->branch_id = $branch->id;
                            $professionalPayment->professional_id = $professional->id;
                            $professionalPayment->date = Carbon::now();
                            $professionalPayment->amount = $winProduct;
                            $professionalPayment->type = 'Bono productos';
                            $professionalPayment->cant = $venta;
                            $professionalPayment->save();
        
        
                            $finance = new Finance();
                            $finance->control = $control++;
                            $finance->operation = 'Gasto';
                            $finance->amount = $winProduct;
                            $finance->comment = 'Gasto por pago de bono de productos a ' . $professional->name;
                            $finance->branch_id = $branch->id;
                            $finance->type = 'Sucursal';
                            $finance->expense_id = 5;
                            $finance->data = Carbon::now();
                            $finance->file = '';
                            $finance->save();
                        //}

                        // Agregar los datos del profesional al arreglo general
                        $professionalsData[] = $professionalData;
                    }
                }

                /*$cashiers = Professional::whereHas('branches', function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id);
                    })->whereHas('charge', function ($query) {
                        $query->where('name', 'Cajero (a)');
                    })->select('id', 'name', 'retention')->get();
                    foreach ($cashiers as $cashier) {
                    Log::info('Cajero:'.$cashier->name.'->ID:'.$cashier->id.' de la sucursal'.$branch->name);
                    $productSales = CashierSale::where('branch_id', $branch->id)->whereYear('data', $añoAnterior)->whereMonth('data', $mesAnterior)->where('professional_id', $cashier->id)->where('pay', 1)->get();
                    Log::info('Productos Vendidos');
                    Log::info($productSales);
                    //Comprobar venta de productos de los cajeros
                    $ventaCashier = $productSales->sum('cant');
                    Log::info('Cantidad Productos Vendidos');
                    Log::info($ventaCashier);
                    $percent_winCashier = $productSales->sum('percent_wint');
                    Log::info('Porciento de Ganancia');
                    Log::info($percent_winCashier);
                    if ($ventaCashier <= 24) {
                        $winProductCashier = $percent_winCashier * 0.15;
                    } else if ($ventaCashier > 24 && $ventaCashier <= 49) {
                        $winProductCashier = $percent_winCashier * 0.25;
                    } else {
                        $winProductCashier = $percent_winCashier * 0.50;
                    }
                    Log::info('Bono producto Cashier'.$winProductCashier.$cashier->name);
                    // Agregar los datos del profesional al arreglo solo si $winProduct es mayor que 0
                    if ($winProductCashier > 0) {
                        $professionalData = [
                            'name' => $cashier->name,
                            'winProduct' => $winProductCashier,
                        ];

                        $finance = Finance::orderBy('control', 'desc')->first();
                        if ($finance !== null) {
                            $control = $finance->control + 1;
                        } else {
                            $control = 1;
                        }
                        Log::info('Bono de Producto Cashier'.$winProductCashier.$cashier->name);
                        //$professionalPayment = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->where('type', 'Bono productos')->first();
                        //if ($filteredPayments->isEmpty()) {
                            $professionalPayment = new ProfessionalPayment();
                            $professionalPayment->branch_id = $branch->id;
                            $professionalPayment->professional_id = $cashier->id;
                            $professionalPayment->date = Carbon::now();
                            $professionalPayment->amount = $winProductCashier;
                            $professionalPayment->type = 'Bono productos';
                            $professionalPayment->cant = $ventaCashier;
                            $professionalPayment->save();
        
        
                            $finance = new Finance();
                            $finance->control = $control++;
                            $finance->operation = 'Gasto';
                            $finance->amount = $winProductCashier;
                            $finance->comment = 'Gasto por pago de bono de productos a ' . $cashier->name;
                            $finance->branch_id = $branch->id;
                            $finance->type = 'Sucursal';
                            $finance->expense_id = 5;
                            $finance->data = Carbon::now();
                            $finance->file = '';
                            $finance->save();
                    }
                
                }*/
                

                $finances = Finance::Where('branch_id', $branch->id)->whereYear('data', $añoAnterior)->whereMonth('data', $mesAnterior)->get();
                if (!$finances->isEmpty()) {

                    foreach ($finances as $finance) {
                        if ($finance->operation == 'Gasto') {
                            $gasto += $finance->amount;
                        } else {
                            $ingreso += $finance->amount;
                        }
                    }
                }

                Log::info('Ingreso Sucursal'.$branch->name);
                Log::info($ingreso);
                Log::info('Gasto Sucursal'.$branch->name);
                Log::info($gasto);
                     Log::info("Generar PDF");
                    $boxData = $now->format('Y-m-d');
           $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('mails.cierrecajamensual', ['branchBusinessName' => $branch->business['name'], 'branchName' => $branch->name, 'boxData' => $boxData, 'totalTip' => $boxClose->totalTip, 'totalProduct' => $boxClose->totalProduct, 'totalService' => $boxClose->totalService, 'totalCash' => $boxClose->totalCash, 'totalCreditCard' => $boxClose->totalCreditCard, 'totalDebit' => $boxClose->totalDebit, 'totalTransfer' => $boxClose->totalTransfer, 'totalOther' => $boxClose->totalOther, 'totalMount' => $boxClose->totalMount, 'totalCardGif' => $boxClose->totalCardGif, 'ingreso' =>  round($ingreso, 2), 'gasto' => round($gasto, 2), 'utilidad' => round($ingreso - $gasto, 2), 'professionalBonus' => $professionalsData]);
            $reporte = $pdf->output();
                //Aqui hacer la logicac de enviar el correo
                $emails = Professional::whereHas('charge', function ($query)  use ($branch) {
                    $query->where('name', 'Administrador')
                        ->orWhere('name', 'Administrador de Sucursal');
                })->whereHas('branches', function ($query) use ($branch) {
                    $query->where('branches.id', $branch->id);
                })
                    ->pluck('email');
                    $emailassociated = [];
                    $emailArray = [];
                    $mergedEmails = [];
                $emailassociated = $branch->associates()->pluck('email');
                $emailArray = $emailassociated->toArray();
                $mergedEmails = $emails->merge($emailArray);
            //  $mergedEmails = ['richardleyvap1991@gmail.com','yasmany891230@gmail.com'];
                Log::info($mergedEmails);    
                foreach ($mergedEmails as $email) {
                    try {
                        $this->sendEmailService->emailBoxClosureMonthly(
                            $email,
                            $reporte,
                            $branch->business['name'],
                            $branch->name,
                            $now->format('Y-m-d'),
                            0,
                            0,
                            0,
                            $boxClose->totalTip,
                            $boxClose->totalProduct,
                            $boxClose->totalService,
                            $boxClose->totalCash,
                            $boxClose->totalCreditCard,
                            $boxClose->totalDebit,
                            $boxClose->totalTransfer,
                            $boxClose->totalOther,
                            $boxClose->totalMount,
                            $boxClose->totalCardGif,
                            round($ingreso, 2),
                            round($gasto, 2),
                            round($ingreso - $gasto, 2),
                            $professionalsData
                        );
                    } catch (\Swift_TransportException $e) {
                        Log::error("Error al enviar correo a $email: " . $e->getMessage());
                    } catch (\Exception $e) {
                        Log::error("Error general al enviar correo a $email: " . $e->getMessage());
                    }
                }
                /*$this->sendEmailService->emailBoxClosureMonthly('yasmany891230@gmail.com', '', $branch->business['name'], $branch->name, $añoAnterior . '-' . $mesAnterior, 0, 0, 0, $boxClose->totalTip, $boxClose->totalProduct, $boxClose->totalService, $boxClose->totalCash, $boxClose->totalCreditCard, $boxClose->totalDebit, $boxClose->totalTransfer, $boxClose->totalOther, $boxClose->totalMount, $boxClose->totalCardGif, round($ingreso, 2), round($gasto, 2), round($ingreso - $gasto, 2), $professionalsData);*/
            }
            return response()->json(['msg' => 'Cierre de caja mensual efectuado correctamente'], 200);
        } catch (TransportException $e) {
            Log::info($e);
            return response()->json(['msg' => 'Cierre de caja realizado correctamente.Error al enviar el correo electrónico '], 200);
        } catch (\Throwable $th) {
            Log::error($th);

            DB::rollback();
            return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }
    
    public function box_close_month(Request $request)
    {
        $codigo = $request->query('codigo');  // Captura el parámetro "codigo" de la URL

        // Log para verificar el valor de código

        if ($codigo != 'P{\nkNgP9hjm/L*~Sks25h^C30_|17') {
            Log::info("Código no coincide");
            return response()->json(['msg' => 'Código inválido'], 403);
        }
        try {
            // Obtener la fecha actual
            $now = Carbon::now();

            // Obtener el mes y año del mes anterior
            //$mesAnterior = $now->month;
            //$añoAnterior = $now->year;
            // $mesAnterior = $now->subMonth()->month; 
            // $añoAnterior = $now->subMonth()->year; 
            
              $mesAnterior = $now->copy()->subMonth()->month;  
             $añoAnterior = $now->copy()->subMonth()->year; 
            //$boxCloseData = [];
            $professionalsData = [];

            $ingreso = 0;
            $gasto = 0;
            $branches = Branch::all();
            foreach ($branches as $branch) {
                Finance::where('branch_id', $branch->id)
                ->whereYear('data', $añoAnterior)->whereMonth('data', $mesAnterior)
                ->where('operation', 'Gasto')
                ->where('comment', 'like', '%Gasto por pago de bono de productos%')
                ->delete();
                 //Retention
                Retention::where('branch_id', $branch->id)
                ->whereYear('data', $añoAnterior)->whereMonth('data', $mesAnterior)->where('type', 'Products')->delete();

                ProfessionalPayment::where('branch_id', $branch->id)->whereYear('date', $añoAnterior)->whereMonth('date', $mesAnterior)->where('type', 'Bono productos')->delete();

                $boxCloseData = [];
                $winProduct = 0;
                $professionalsData = [];
                $ingreso = 0;
                $gasto = 0;
                $boxClose = BoxClose::whereHas('box', function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id);
                })->whereYear('data', $añoAnterior)->whereMonth('data', $mesAnterior)->selectRaw('
                SUM(totalMount) as totalMount,
                SUM(totalService) as totalService,
                SUM(totalProduct) as totalProduct,
                SUM(totalTip) as totalTip,
                SUM(totalCash) as totalCash,
                SUM(totalDebit) as totalDebit,
                SUM(totalCreditCard) as totalCreditCard,
                SUM(totalTransfer) as totalTransfer,
                SUM(totalOther) as totalOther,
                SUM(totalCardGif) as totalCardGif
            ')->first();
                /*$boxCloseArray = [
                    'totalMount' => $boxClose->totalMount ?? 0,
                    'totalService' => $boxClose->totalService ?? 0,
                    'totalProduct' => $boxClose->totalProduct ?? 0,
                    'totalTip' => $boxClose->totalTip ?? 0,
                    'totalCash' => $boxClose->totalCash ?? 0,
                    'totalDebit' => $boxClose->totalDebit ?? 0,
                    'totalCreditCard' => $boxClose->totalCreditCard ?? 0,
                    'totalTransfer' => $boxClose->totalTransfer ?? 0,
                    'totalOther' => $boxClose->totalOther ?? 0,
                    'totalcardGif' => $boxClose->totalcardGif ?? 0,
                    'branch_name' => $branch->name,
                    'ingreso' => round($ingreso, 2),
                    'gasto' => round($gasto, 2),
                    'utilidad' => round($ingreso - $gasto, 2)
                ];

                // Agregar al array de resultados
                $boxCloseData[] = $boxCloseArray;*/
                $professionals = Professional::whereHas('branches', function ($query) use ($branch) {
                        $query->where('branch_id', $branch->id);
                    })->whereHas('charge', function ($query) {
                        $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
                    })->select('id', 'name', 'surname', 'retention')->get();
                    foreach ($professionals as $professional) {
                        $cars = Car::whereHas('reservation', function ($query) use ($branch, $añoAnterior, $mesAnterior) {
                            $query->where('branch_id', $branch->id)->whereYear('data', $añoAnterior)->whereMonth('data', $mesAnterior);
                        })
                            ->with(['clientProfessional.client', 'reservation'])
                            ->whereHas('clientProfessional', function ($query) use ($professional) {
                                $query->where('professional_id', $professional->id);
                            })
                            ->where('pay', 1)
                            ->get();
                        $carIdsPay = $cars->pluck('id');
                        $products = Order::whereIn('car_id', $carIdsPay)
                            ->where('is_product', 1)
                            ->groupBy('product_store_id')
                            ->selectRaw('product_store_id, SUM(cant) as total_cant, SUM(percent_win) as total_percent_win')
                            ->get();
                    $venta = $products->sum('total_cant');
                    $percent_win = $products->sum('total_percent_win');
                    if ($venta <= 24) {
                        $winProduct = $percent_win * 0.15;
                    } else if ($venta > 24 && $venta <= 49) {
                        $winProduct = $percent_win * 0.25;
                    } else {
                        $winProduct = $percent_win * 0.50;
                    }
                    Log::info('Bono producto'.$winProduct.$professional->name);
                    // Agregar los datos del profesional al arreglo solo si $winProduct es mayor que 0
                    if ($winProduct > 0) {
                        $retention = $professional->retention;
                    if ($retention) {
                        $resultRetention = round($winProduct * $retention / 100, 2);
                        $winProduct = $winProduct - $resultRetention;
                        $retention = new Retention();
                        $retention->branch_id = $branch->id;
                        $retention->professional_id = $professional->id;
                        $retention->data = Carbon::now();
                        $retention->retention = $resultRetention;
                        $retention->type = 'Products';
                        $retention->save();
                    }
                        $professionalData = [
                            'name' => $professional->name,
                            'winProduct' => $winProduct,
                        ];

                        $finance = Finance::orderBy('control', 'desc')->first();
                        if ($finance !== null) {
                            $control = $finance->control + 1;
                        } else {
                            $control = 1;
                        }
                        Log::info('Bono de Producto'.$winProduct.$professional->name);
                        //$professionalPayment = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->where('type', 'Bono productos')->first();
                        //if ($filteredPayments->isEmpty()) {
                            $professionalPayment = new ProfessionalPayment();
                            $professionalPayment->branch_id = $branch->id;
                            $professionalPayment->professional_id = $professional->id;
                            $professionalPayment->date = Carbon::now();
                            $professionalPayment->amount = $winProduct;
                            $professionalPayment->type = 'Bono productos';
                            $professionalPayment->cant = $venta;
                            $professionalPayment->save();
        
        
                            $finance = new Finance();
                            $finance->control = $control++;
                            $finance->operation = 'Gasto';
                            $finance->amount = $winProduct;
                            $finance->comment = 'Gasto por pago de bono de productos a ' . $professional->name;
                            $finance->branch_id = $branch->id;
                            $finance->type = 'Sucursal';
                            $finance->expense_id = 5;
                            $finance->data = Carbon::now();
                            $finance->file = '';
                            $finance->save();
                        //}

                        // Agregar los datos del profesional al arreglo general
                        $professionalsData[] = $professionalData;
                    }
                }

                /*$cashiers = Professional::whereHas('branches', function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id);
                    })->whereHas('charge', function ($query) {
                        $query->where('name', 'Cajero (a)');
                    })->select('id', 'name', 'retention')->get();
                    foreach ($cashiers as $cashier) {
                    Log::info('Cajero:'.$cashier->name.'->ID:'.$cashier->id.' de la sucursal'.$branch->name);
                    $productSales = CashierSale::where('branch_id', $branch->id)->whereYear('data', $añoAnterior)->whereMonth('data', $mesAnterior)->where('professional_id', $cashier->id)->where('pay', 1)->get();
                    Log::info('Productos Vendidos');
                    Log::info($productSales);
                    //Comprobar venta de productos de los cajeros
                    $ventaCashier = $productSales->sum('cant');
                    Log::info('Cantidad Productos Vendidos');
                    Log::info($ventaCashier);
                    $percent_winCashier = $productSales->sum('percent_wint');
                    Log::info('Porciento de Ganancia');
                    Log::info($percent_winCashier);
                    if ($ventaCashier <= 24) {
                        $winProductCashier = $percent_winCashier * 0.15;
                    } else if ($ventaCashier > 24 && $ventaCashier <= 49) {
                        $winProductCashier = $percent_winCashier * 0.25;
                    } else {
                        $winProductCashier = $percent_winCashier * 0.50;
                    }
                    Log::info('Bono producto Cashier'.$winProductCashier.$cashier->name);
                    // Agregar los datos del profesional al arreglo solo si $winProduct es mayor que 0
                    if ($winProductCashier > 0) {
                        $professionalData = [
                            'name' => $cashier->name,
                            'winProduct' => $winProductCashier,
                        ];

                        $finance = Finance::orderBy('control', 'desc')->first();
                        if ($finance !== null) {
                            $control = $finance->control + 1;
                        } else {
                            $control = 1;
                        }
                        Log::info('Bono de Producto Cashier'.$winProductCashier.$cashier->name);
                        //$professionalPayment = ProfessionalPayment::where('branch_id', $branch->id)->where('professional_id', $professional->id)->whereDate('date', Carbon::now())->where('type', 'Bono productos')->first();
                        //if ($filteredPayments->isEmpty()) {
                            $professionalPayment = new ProfessionalPayment();
                            $professionalPayment->branch_id = $branch->id;
                            $professionalPayment->professional_id = $cashier->id;
                            $professionalPayment->date = Carbon::now();
                            $professionalPayment->amount = $winProductCashier;
                            $professionalPayment->type = 'Bono productos';
                            $professionalPayment->cant = $ventaCashier;
                            $professionalPayment->save();
        
        
                            $finance = new Finance();
                            $finance->control = $control++;
                            $finance->operation = 'Gasto';
                            $finance->amount = $winProductCashier;
                            $finance->comment = 'Gasto por pago de bono de productos a ' . $cashier->name;
                            $finance->branch_id = $branch->id;
                            $finance->type = 'Sucursal';
                            $finance->expense_id = 5;
                            $finance->data = Carbon::now();
                            $finance->file = '';
                            $finance->save();
                    }
                
                }*/
                

                $finances = Finance::Where('branch_id', $branch->id)->whereYear('data', $añoAnterior)->whereMonth('data', $mesAnterior)->get();
                if (!$finances->isEmpty()) {

                    foreach ($finances as $finance) {
                        if ($finance->operation == 'Gasto') {
                            $gasto += $finance->amount;
                        } else {
                            $ingreso += $finance->amount;
                        }
                    }
                }

                Log::info('Ingreso Sucursal'.$branch->name);
                Log::info($ingreso);
                Log::info('Gasto Sucursal'.$branch->name);
                Log::info($gasto);
                     Log::info("Generar PDF");
                    $boxData = $now->format('Y-m-d');
           $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('mails.cierrecajamensual', ['branchBusinessName' => $branch->business['name'], 'branchName' => $branch->name, 'boxData' => $boxData, 'totalTip' => $boxClose->totalTip, 'totalProduct' => $boxClose->totalProduct, 'totalService' => $boxClose->totalService, 'totalCash' => $boxClose->totalCash, 'totalCreditCard' => $boxClose->totalCreditCard, 'totalDebit' => $boxClose->totalDebit, 'totalTransfer' => $boxClose->totalTransfer, 'totalOther' => $boxClose->totalOther, 'totalMount' => $boxClose->totalMount, 'totalCardGif' => $boxClose->totalCardGif, 'ingreso' =>  round($ingreso, 2), 'gasto' => round($gasto, 2), 'utilidad' => round($ingreso - $gasto, 2), 'professionalBonus' => $professionalsData]);
            $reporte = $pdf->output();
                //Aqui hacer la logicac de enviar el correo
                $emails = Professional::whereHas('charge', function ($query)  use ($branch) {
                    $query->where('name', 'Administrador')
                        ->orWhere('name', 'Administrador de Sucursal');
                })->whereHas('branches', function ($query) use ($branch) {
                    $query->where('branches.id', $branch->id);
                })
                    ->pluck('email');
                    $emailassociated = [];
                    $emailArray = [];
                    $mergedEmails = [];
                $emailassociated = $branch->associates()->pluck('email');
                $emailArray = $emailassociated->toArray();
                $mergedEmails = $emails->merge($emailArray);
            //  $mergedEmails = ['richardleyvap1991@gmail.com','yasmany891230@gmail.com'];
                Log::info($mergedEmails);    
                foreach ($mergedEmails as $email) {
                    try {
                        $this->sendEmailService->emailBoxClosureMonthly(
                            $email,
                            $reporte,
                            $branch->business['name'],
                            $branch->name,
                            $now->format('Y-m-d'),
                            0,
                            0,
                            0,
                            $boxClose->totalTip,
                            $boxClose->totalProduct,
                            $boxClose->totalService,
                            $boxClose->totalCash,
                            $boxClose->totalCreditCard,
                            $boxClose->totalDebit,
                            $boxClose->totalTransfer,
                            $boxClose->totalOther,
                            $boxClose->totalMount,
                            $boxClose->totalCardGif,
                            round($ingreso, 2),
                            round($gasto, 2),
                            round($ingreso - $gasto, 2),
                            $professionalsData
                        );
                    } catch (\Swift_TransportException $e) {
                        Log::error("Error al enviar correo a $email: " . $e->getMessage());
                    } catch (\Exception $e) {
                        Log::error("Error general al enviar correo a $email: " . $e->getMessage());
                    }
                }
                /*$this->sendEmailService->emailBoxClosureMonthly('yasmany891230@gmail.com', '', $branch->business['name'], $branch->name, $añoAnterior . '-' . $mesAnterior, 0, 0, 0, $boxClose->totalTip, $boxClose->totalProduct, $boxClose->totalService, $boxClose->totalCash, $boxClose->totalCreditCard, $boxClose->totalDebit, $boxClose->totalTransfer, $boxClose->totalOther, $boxClose->totalMount, $boxClose->totalCardGif, round($ingreso, 2), round($gasto, 2), round($ingreso - $gasto, 2), $professionalsData);*/
            }
            return response()->json(['msg' => 'Cierre de caja mensual efectuado correctamente'], 200);
        } catch (TransportException $e) {
            Log::info($e);
            return response()->json(['msg' => 'Cierre de caja realizado correctamente.Error al enviar el correo electrónico '], 200);
        } catch (\Throwable $th) {
            Log::error($th);

            DB::rollback();
            return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
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
    
 
    
    
    public function box_close_automatic_ANTERIOR(Request $request)
    {
        $codigo = $request->query('codigo');  // Captura el parámetro "codigo" de la URL

        // Log para verificar el valor de código

        if ($codigo != 'P{\nkNgP9hjm/L*~Sks25h^C30_|17') {
            Log::info("Código no coincide");
            return response()->json(['msg' => 'Código inválido'], 403);
        }
        try {
            Log::info("Cierre de caja Forzado Automático");

            // Obtener la fecha del día anterior
            $yesterday = Carbon::yesterday()->toDateString();
            
            $totalBonuses = [];         
            $branches = Branch::all(); // Obtener todas las sucursales

            DB::beginTransaction();
            foreach ($branches as $branch) {
                $box = Box::whereDate('data', $yesterday)
                    ->where('branch_id', $branch->id)
                    ->first();
                Log::info($yesterday);
                Log::info('Caja');             
                Log::info($branch->name);             
                Log::info($box);             
                //Log::info($box->id);             
                if ($box) {
                    $boxClose = BoxClose::where('box_id', $box->id)->first();
                }else {
                    $boxClose = [];
                }
            if (!$boxClose && $box) 
            {
                Log::info("Entra a realizar cierre de caja automático");
                Log::info($branch->name);
                     // Cálculo de totalAmount
                $totalAmount = ProfessionalPayment::where('branch_id', $branch->id)
                    ->whereDate('date', $yesterday)
                    ->where(function($query) {
                        $query->where('type', 'Bono convivencias')
                            ->orWhere('type', 'Bono servicios');
                    })
                    ->sum('amount');

                $professionals = Professional::whereHas('branches', function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id);
                })->whereHas('charge', function ($query) {
                    $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
                })->select('id', 'name', 'image_url', 'retention')->get();

                $bonus = [];
                foreach ($professionals as $professional) {
                    $bonus[] = $this->metaService->store_box_close($branch, $yesterday, $professional->id);
                }

                $bonusCollection = collect($bonus);
                $flattenedBonus = $bonusCollection->flatMap(function ($item) {
                    return collect($item);
                });

                $totalBonus = $flattenedBonus->sum('amount');
                Log::info('$totalBonus Bonussssssss (Automatico)');
                Log::info($totalBonus);

                if ($totalBonus) {
                    Log::info('Entra a descontar los bonos de la existencia (Automatico)');
                    $difference = $totalBonus - $totalAmount;
                    Log::info('Diferencia de bono cierre de caja (Automatico) '.$difference);
                    $box->existence -= $difference;
                    $box->save();
                } 

                // Calcular montos para el cierre de caja
                $payments = Payment::where('branch_id', $branch->id)
                    ->whereDate('created_at', $yesterday)
                    ->get();

                $cars_id = $payments->pluck('car_id');
                $cars = Car::whereIn('id', $cars_id)->get();
                $orders = Order::whereIn('car_id', $cars_id)->get();
                $cashiers = CashierSale::where('branch_id', $branch->id)
                    ->whereDate('data', $yesterday)
                    ->get();

                $services = $orders->where('is_product', 0)->sum('price');
                $products = $orders->where('is_product', 1)->sum('price') + $cashiers->sum('price');

                $totalMount = $services + $products + $cars->sum('tip');

                // Guardar los datos en BoxClose
                $boxClose = new BoxClose();
                $boxClose->box_id = $box->id;
                $boxClose->totalMount = $totalMount;
                $boxClose->totalService = $services;
                $boxClose->totalProduct = $products;
                $boxClose->totalTip = $cars->sum('tip');
                $boxClose->totalCash = $payments->sum('cash');
                $boxClose->totalCreditCard = $payments->sum('creditCard');
                $boxClose->totalDebit = $payments->sum('debit');
                $boxClose->totalTransfer = $payments->sum('transfer');
                $boxClose->totalOther = $payments->sum('other');
                $boxClose->totalCardGif = $payments->sum('cardGif');
                $boxClose->data = $yesterday; // Usar la fecha del día anterior
                $boxClose->save();

                Log::info("Generar PDF Automatico");
                $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('mails.cierrecaja', ['data' => $boxClose, 'box' => $box, 'branch' => $branch, 'totalBonus' => $totalBonus]);
                $reporte = $pdf->output(); // Convertir el PDF en una cadena
                $emails = Professional::whereHas('charge', function ($query)  use ($branch) {
                    $query->where('name', 'Administrador')
                        ->orWhere('name', 'Encargado')
                        ->orWhere('name', 'Administrador de Sucursal')
                        ->orWhere('name', 'Coordinador');
                })->whereHas('branches', function ($query) use ($branch) {
                    $query->where('branches.id', $branch->id);
                })/*whereIn('charge_id', [3, 4, 5, 12])*/
                    ->pluck('email');
                $emailassociated = $branch->associates()->pluck('email');
                $emailArray = $emailassociated->toArray();
                $mergedEmails = $emails->merge($emailArray);
                Log::info('$mergedEmails correos a enviar cierre de caja');
                Log::info($mergedEmails);
                foreach ($mergedEmails as $email) {
                    try {
                        $this->sendEmailService->emailBoxClosure(
                            $email, 
                            $reporte, 
                            $branch->business['name'], 
                            $branch['name'], 
                            $box['data'], 
                            $box['cashFound'], 
                            $box['existence'], 
                            $box['extraction'], 
                            $cars->sum('tip'), 
                            $products, 
                            $services, 
                            $payments->sum('cash'), 
                            $payments->sum('creditCard'), 
                            $payments->sum('debit'), 
                            $payments->sum('transfer'), 
                            $payments->sum('other'), 
                            $totalMount, 
                            $payments->sum('cardGif'), 
                            $totalBonus
                        );
                    } catch (\Swift_TransportException $e) {
                        Log::error("Error al enviar correo a $email: " . $e->getMessage());
                    } catch (\Exception $e) {
                        Log::error("Error general al enviar correo a $email: " . $e->getMessage());
                    }
                }

               /*// Generar el PDF
                $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])
                    ->setPaper('a4', 'patriot')
                    ->loadView('mails.cierrecaja', [
                        'data' => $boxClose,
                        'box' => $box,
                        'branch' => $branch,
                        'totalBonus' => $totalBonus
                    ]);

                $reporte = $pdf->output(); // Convertir el PDF en una cadena

                // Intentar enviar el correo y manejar cualquier error
                try {
                    $this->sendEmailService->emailBoxClosure(
                        'yasmany891230@gmail.com', 
                        $reporte, 
                        $branch->business['name'], 
                        $branch['name'], 
                        $box['data'], 
                        $box['cashFound'], 
                        $box['existence'], 
                        $box['extraction'], 
                        $cars->sum('tip'), 
                        $products, 
                        $services, 
                        $payments->sum('cash'), 
                        $payments->sum('creditCard'), 
                        $payments->sum('debit'), 
                        $payments->sum('transfer'), 
                        $payments->sum('other'), 
                        $totalMount, 
                        $payments->sum('cardGif'), 
                        $totalBonus
                    );
                } catch (\Exception $emailException) {
                    Log::error('Error al enviar el correo: ' . $emailException->getMessage());
                    // Puedes optar por continuar o no, dependiendo de la lógica de tu aplicación
                    // Aquí podrías registrar el error en la base de datos si es necesario
                }*/
                $totalBonuses[] = $totalBonus; // Guardar el total de bonos por sucursal
                Log::info('Cierre de la caja para sucursal (Automatico): ' . $branch->id);
                Log::info($boxClose);
            }//if

        }//for
                
            DB::commit();
            return response()->json(['msg' => 'Cierre de caja realizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info('BoxCloseController->box_close_automatic');
            Log::error($th->getMessage());

            DB::rollback();
            return response()->json(['msg' => $th->getMessage() . ' Error interno del servidor'], 500);
        }
    } 
    
        public function box_close_automatic(Request $request)
    {
        $codigo = $request->query('codigo'); // Captura el parámetro "codigo" de la URL

        if ($codigo != 'P{\nkNgP9hjm/L*~Sks25h^C30_|17') {
            Log::info("Código no coincide");
            return response()->json(['msg' => 'Código inválido'], 403);
        }

        try {
            Log::info("Cierre de caja Forzado Automático");

            // Obtener la fecha del día anterior
            $yesterday = Carbon::yesterday()->toDateString();

            $totalBonuses = [];
            $branches = Branch::where('id', '!=', 20)->get(); // Obtener todas las sucursales

            foreach ($branches as $branch) {
                DB::beginTransaction();
                try {
                    $box = Box::whereDate('data', $yesterday)
                        ->where('branch_id', $branch->id)
                        ->first();

                    if (!$box) {
                        $box = new Box();
                        $box->existence = 0; // Ajustar según tu lógica
                        $box->data = $yesterday;
                        $box->branch_id = $branch->id;
                        $box->save();
                    }

                    $boxClose = BoxClose::where('box_id', $box->id)->first();
                    if (!$boxClose) {
                        $boxClose = new BoxClose(); // Crear uno nuevo si no existe
                    }

                    $totalAmount = ProfessionalPayment::where('branch_id', $branch->id)
                        ->whereDate('date', $yesterday)
                        ->where(function ($query) {
                            $query->where('type', 'Bono convivencias')
                                ->orWhere('type', 'Bono servicios');
                        })
                        ->sum('amount');

                    $professionals = Professional::whereHas('branches', function ($query) use ($branch) {
                        $query->where('branch_id', $branch->id);
                    })->whereHas('charge', function ($query) {
                        $query->where('name', 'Barbero')->orWhere('name', 'Barbero y Encargado');
                    })->select('id', 'name', 'image_url', 'retention')->get();

                    $bonus = [];
                    foreach ($professionals as $professional) {
                        $bonus[] = $this->metaService->store_box_close($branch, $yesterday, $professional->id);
                    }

                    $bonusCollection = collect($bonus);
                    $flattenedBonus = $bonusCollection->flatMap(function ($item) {
                        return collect($item);
                    });

                    $totalBonus = $flattenedBonus->sum('amount');

                    if ($totalBonus) {
                        $difference = $totalBonus - $totalAmount;
                        $box->existence -= $difference;
                        $box->save();
                    }

                    $payments = Payment::where('branch_id', $branch->id)
                        ->whereDate('created_at', $yesterday)
                        ->get();

                    $cars_id = $payments->pluck('car_id');
                    $cars = Car::whereIn('id', $cars_id)->get();
                    $orders = Order::whereIn('car_id', $cars_id)->get();
                    $cashiers = CashierSale::where('branch_id', $branch->id)
                        ->whereDate('data', $yesterday)
                        ->get();

                    $services = $orders->where('is_product', 0)->sum('price');
                    $products = $orders->where('is_product', 1)->sum('price') + $cashiers->sum('price');

                    $totalMount = $services + $products + $cars->sum('tip');

                    // Guardar los datos en BoxClose
                    $boxClose->box_id = $box->id;
                    $boxClose->totalMount = $totalMount;
                    $boxClose->totalService = $services;
                    $boxClose->totalProduct = $products;
                    $boxClose->totalTip = $cars->sum('tip');
                    $boxClose->totalCash = $payments->sum('cash');
                    $boxClose->totalCreditCard = $payments->sum('creditCard');
                    $boxClose->totalDebit = $payments->sum('debit');
                    $boxClose->totalTransfer = $payments->sum('transfer');
                    $boxClose->totalOther = $payments->sum('other');
                    $boxClose->totalCardGif = $payments->sum('cardGif');
                    $boxClose->data = $yesterday;
                    $boxClose->save();

                    DB::commit();

                    Log::info("Generar PDF Automatico");
                $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('mails.cierrecaja', ['data' => $boxClose, 'box' => $box, 'branch' => $branch, 'totalBonus' => $totalBonus]);
                $reporte = $pdf->output(); // Convertir el PDF en una cadena
                $emails = Professional::whereHas('charge', function ($query)  use ($branch) {
                    $query->where('name', 'Administrador')
                        ->orWhere('name', 'Encargado')
                        ->orWhere('name', 'Administrador de Sucursal')
                        ->orWhere('name', 'Coordinador');
                })->whereHas('branches', function ($query) use ($branch) {
                    $query->where('branches.id', $branch->id);
                })/*whereIn('charge_id', [3, 4, 5, 12])*/
                    ->pluck('email');
                $emailassociated = $branch->associates()->pluck('email');
                $emailArray = $emailassociated->toArray();
                $mergedEmails = $emails->merge($emailArray);
                Log::info('$mergedEmails correos a enviar cierre de caja');
                Log::info($mergedEmails);
                foreach ($mergedEmails as $email) {
                    try {
                        $this->sendEmailService->emailBoxClosure(
                            $email, 
                            $reporte, 
                            $branch->business['name'], 
                            $branch['name'], 
                            $box['data'], 
                            $box['cashFound'], 
                            $box['existence'], 
                            $box['extraction'], 
                            $cars->sum('tip'), 
                            $products, 
                            $services, 
                            $payments->sum('cash'), 
                            $payments->sum('creditCard'), 
                            $payments->sum('debit'), 
                            $payments->sum('transfer'), 
                            $payments->sum('other'), 
                            $totalMount, 
                            $payments->sum('cardGif'), 
                            $totalBonus
                        );
                    } catch (\Swift_TransportException $e) {
                        Log::error("Error al enviar correo a $email: " . $e->getMessage());
                    } catch (\Exception $e) {
                        Log::error("Error general al enviar correo a $email: " . $e->getMessage());
                    }
                }

                } catch (\Throwable $th) {
                    DB::rollBack();
                    Log::error("Error al procesar la sucursal {$branch->name}: " . $th->getMessage());
                }
            }

            return response()->json(['msg' => 'Cierre de caja realizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error("Error general en el cierre de caja: " . $th->getMessage());
            return response()->json(['msg' => 'Error interno del servidor'], 500);
        }
    }

}
