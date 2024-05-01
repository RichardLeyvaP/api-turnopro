<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Models\Associated;
use App\Models\Box;
use App\Models\BoxClose;
use App\Models\Branch;
use App\Models\CloseBox;
use App\Models\Finance;
use App\Models\Professional;
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
                'box_id' => 'required|numeric',
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
            Log::info($data);
            $box = Box::find($data['box_id']);
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
            $finance = Finance::where('branch_id', $branch->id)->where('revenue_id', 5)->whereDate('data', Carbon::now())->first();
                            Log::info('no existe');
                $finance = Finance::where('branch_id', $branch->id)->orderByDesc('control')->first();
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
            
            Log::info("Generar PDF");
            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('mails.cierrecaja', ['data' => $boxClose, 'box' => $box, 'branch' => $branch]);
            $reporte = $pdf->output(); // Convertir el PDF en una cadena
            Log::info($reporte);
            // Envía el correo electrónico con el PDF adjunto
            // $this->sendEmailService->emailBoxClosure('evylabrada@gmail.com', $reporte);
            return $emails = Professional::whereHas('charge', function ($query) {
                $query->where('name', 'Administrador')
                    ->orWhere('name', 'Encargado')
                    ->orWhere('name', 'Administrador de Sucursal')
                    ->orWhere('name', 'Coordinador');
            })/*whereIn('charge_id', [3, 4, 5, 12])*/
                ->pluck('email');
            //$emailassociated = Associated::all()->pluck('email');
            $emailassociated = $branch->associates()->pluck('email');
            $emailArray = $emailassociated->toArray();
            $mergedEmails = $emails->merge($emailArray);
            // Supongamos que tienes 5 direcciones de correo electrónico en un array
            //todo $emails = ['correo1@example.com', 'correo2@example.com', 'correo3@example.com', 'correo4@example.com', 'correo5@example.com'];
            //$this->sendEmailService->emailBoxClosure($mergedEmails, $reporte, $branch->business['name'], $branch['name'], $box['data'], $box['cashFound'], $box['existence'], $box['extraction'], $data['totalTip'], $data['totalProduct'], $data['totalService'], $data['totalCash'], $data['totalCreditCard'], $data['totalDebit'], $data['totalTransfer'], $data['totalOther'], $data['totalMount']);
            //SendEmailJob::dispatch()->emailBoxClosure($mergedEmails, $reporte, $branch->business['name'], $branch['name'], $box['data'], $box['cashFound'], $box['existence'], $box['extraction'], $data['totalTip'], $data['totalProduct'], $data['totalService'], $data['totalCash'], $data['totalCreditCard'], $data['totalDebit'], $data['totalTransfer'], $data['totalOther'], $data['totalMount']);
            $data = [
                'email_box_closure' => true, // Indica que es un correo de cierre de caja
                'client_email' => $mergedEmails, // Correo electrónico del cliente
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
            
            SendEmailJob::dispatch($data);

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

            return response()->json(['msg' => 'Pago realizado correctamente correctamente'], 200);
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
