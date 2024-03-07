<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\BoxClose;
use App\Models\Branch;
use App\Models\CloseBox;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\SendEmailService;

use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class BoxCloseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private SendEmailService $sendEmailService;
    public function __construct(SendEmailService $sendEmailService )
    {
       
        $this->sendEmailService = $sendEmailService;
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
            $branch = Branch::whereHas('boxes', function ($query) use ($box){
                $query->where('boxes.id', $box->id);
            })->with('business')->first();
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
            Log::info("Generar PDF");
            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('mails.cierrecaja', ['data' => $boxClose, 'box' => $box, 'branch' => $branch]);
            $reporte = $pdf->output(); // Convertir el PDF en una cadena
            Log::info($reporte);
            // Envía el correo electrónico con el PDF adjunto
           // $this->sendEmailService->emailBoxClosure('evylabrada@gmail.com', $reporte);
           $this->sendEmailService->emailBoxClosure('evylabrada@gmail.com', $reporte, $branch->business['name'], $branch['name'], $box['data'], $box['cashFound'], $box['existence'], $box['extraction'], $data['totalTip'], $data['totalProduct'], $data['totalService'], $data['totalCash'], $data['totalCreditCard'], $data['totalDebit'], $data['totalTransfer'], $data['totalOther'], $data['totalMount']);



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
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => $th->getMessage().'Error al cerrar la caja el pago'], 500);
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
