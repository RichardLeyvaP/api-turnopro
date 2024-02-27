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

class BoxCloseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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
                'totalOther' => 'nullable|numeric'
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
            $boxClose->data = Carbon::now();
            $boxClose->save();
            Log::info("Generar PDF");
            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true, 'chroot' => storage_path()])->setPaper('a4', 'patriot')->loadView('cierrecaja', ['data' => $boxClose, 'box' => $box, 'branch' => $branch]);
            //$filename = 'reporte.pdf';
            $reporte = $pdf->stream('Cierre-caja'.$boxClose->data, array('Attachment' => 0));
            //Aqui logica de enviar correo con el pdf adjuntado
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
