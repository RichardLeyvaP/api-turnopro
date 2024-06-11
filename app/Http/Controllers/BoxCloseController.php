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
    public function __construct(SendEmailService $sendEmailService, TraceService $traceService, MetaService $metaService)
    {

        $this->sendEmailService = $sendEmailService;
        $this->traceService = $traceService;
        $this->metaService = $metaService;
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
            $idService = null;
            Log::info($data);
            $box = Box::whereDate('data', Carbon::now())->where('branch_id', $request->branch_id)->first();
            if (!$box) {
                $box = new Box();
                $box->existence = 0;
                $box->data = Carbon::now();
                $box->branch_id = $request->branch_id;
            }
            $box->save();
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
            //$finance = Finance::where('branch_id', $branch->id)->where('revenue_id', 5)->whereDate('data', Carbon::now())orderBy('control', 'desc')->first();
            $finance = Finance::orderBy('control', 'desc')->first();
            Log::info('no existe');
            //$finance = Finance::where('branch_id', $branch->id)orderBy('control', 'desc')->first();
            if ($finance !== null) {
                $control = $finance->control + 1;
            } else {
                $control = 1;
            }
            /*$finance = new Finance();
            $finance->control = $control;
            $finance->operation = 'Ingreso';
            $finance->amount = $data['totalMount'];
            $finance->comment = 'Ingreso diario en sucursal ' . $branch->name;
            $finance->branch_id = $branch->id;
            $finance->type = 'Sucursal';
            $finance->revenue_id = 5;
            $finance->data = Carbon::now();
            $finance->file = '';
            $finance->save();*/
            //end agregar a tabla de ingresos
            //Revisar convivencias de professionales para pagar 100% del servicio
            $bonus = $this->metaService->store($branch);
            $bonusCollection = collect($bonus);

            // Calcular la suma de 'amount'
            $totalBonus = $bonusCollection->sum('amount');
            Log::info('$totalBonus Bonussssssss');
            Log::info($totalBonus);
            //$professionals = $professionals->toArray();
            Log::info("Generar PDF");
            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('mails.cierrecaja', ['data' => $boxClose, 'box' => $box, 'branch' => $branch, 'totalBonus' => $totalBonus]);
            $reporte = $pdf->output(); // Convertir el PDF en una cadena
            //Log::info($reporte);
            // Envía el correo electrónico con el PDF adjunto
            // $this->sendEmailService->emailBoxClosure('evylabrada@gmail.com', $reporte);
            $emails = Professional::whereHas('charge', function ($query)  use ($branch) {
                $query->where('name', 'Administrador')
                    ->orWhere('name', 'Encargado')
                    ->orWhere('name', 'Administrador de Sucursal')
                    ->orWhere('name', 'Coordinador');
            })->whereHas('branches', function ($query) use ($branch) {
                $query->where('branches.id', $branch->id);
            })/*whereIn('charge_id', [3, 4, 5, 12])*/
                ->pluck('email');
            //$emailassociated = Associated::all()->pluck('email');
            $emailassociated = $branch->associates()->pluck('email');
            $emailArray = $emailassociated->toArray();
            $mergedEmails = $emails->merge($emailArray);
            // Supongamos que tienes 5 direcciones de correo electrónico en un array
            $this->sendEmailService->emailBoxClosure($mergedEmails, $reporte, $branch->business['name'], $branch['name'], $box['data'], $box['cashFound'], $box['existence'], $box['extraction'], $data['totalTip'], $data['totalProduct'], $data['totalService'], $data['totalCash'], $data['totalCreditCard'], $data['totalDebit'], $data['totalTransfer'], $data['totalOther'], $data['totalMount'], $data['totalCardGif'], $totalBonus);
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

            return response()->json(['msg' => 'Cierre de caja realizado correctamente', 'bonus' => $bonus], 200);
        } catch (TransportException $e) {

            return response()->json(['msg' => 'Cierre de caja realizado correctamente.Error al enviar el correo electrónico '], 200);
        } catch (\Throwable $th) {
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

    public function box_close_month()
    {
        try {
            // Obtener la fecha actual
            $now = Carbon::now();

            // Obtener el mes y año del mes anterior
            $mesAnterior = $now->subMonth()->month;
            $añoAnterior = $now->subMonth()->year;
            //$boxCloseData = [];
            $professionalsData = [];

            $ingreso = 0;
            $gasto = 0;
            $branches = Branch::all();
            foreach ($branches as $branch) {
                $boxCloseData = [];
                $professionalsData = [];
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
                SUM(totalcardGif) as totalCardGif
            ')->first();
                $finances = Finance::Where('branch_id', $branch->id)->whereYear('data', $añoAnterior)->whereMonth('data', $mesAnterior)->get();
                if (!$finances->isEmpty()) {

                    foreach ($finances as $finance) {
                        if ($finance->operation == 'Gasto') {
                            $gasto = $gasto + $finance->amount;
                        } else {
                            $ingreso = $ingreso + $finance->amount;
                        }
                    }
                }
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

                    // Agregar los datos del profesional al arreglo solo si $winProduct es mayor que 0
                    if ($winProduct > 0) {
                        $professionalData = [
                            'name' => $professional->name,
                            'winProduct' => $winProduct,
                        ];

                        // Agregar los datos del profesional al arreglo general
                        $professionalsData[] = $professionalData;
                    }
                }

                //Aqui hacer la logicac de enviar el correo
                $emails = Professional::whereHas('charge', function ($query)  use ($branch) {
                    $query->where('name', 'Administrador')
                        ->orWhere('name', 'Administrador de Sucursal');
                })->whereHas('branches', function ($query) use ($branch) {
                    $query->where('branches.id', $branch->id);
                })/*whereIn('charge_id', [3, 4, 5, 12])*/
                    ->pluck('email');
                $emailassociated = $branch->associates()->pluck('email');
                $emailArray = $emailassociated->toArray();
                $mergedEmails = $emails->merge($emailArray);
                $this->sendEmailService->emailBoxClosureMonthly($mergedEmails, '', $branch->business['name'], $branch->name, $añoAnterior . '-' . $mesAnterior, 0, 0, 0, $boxClose->totalTip, $boxClose->totalProduct, $boxClose->totalService, $boxClose->totalCash, $boxClose->totalCreditCard, $boxClose->totalDebit, $boxClose->totalTransfer, $boxClose->totalOther, $boxClose->totalMount, $boxClose->totalCardGif, round($ingreso, 2), round($gasto, 2), round($ingreso - $gasto, 2), $professionalsData);
            }
            return response()->json(['msg' => 'Cierre de caja mensual efectuado correctamente'], 200);
        } catch (TransportException $e) {

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
}
