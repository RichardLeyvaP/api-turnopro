<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Branch;
use App\Models\Finance;
use App\Services\TraceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BoxController extends Controller
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
 * Registra o actualiza la caja diaria de una sucursal.
 *
 * Crea un nuevo registro de caja si no existe para el día actual, o actualiza el existente.
 * Soporta actualización de efectivo encontrado y extracciones.
 *
 * @authenticated
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam cashFound number optional Monto de efectivo encontrado en la caja. Example: 500.00
 * @bodyParam extraction number optional Monto extraído de la caja (se suma al total). Example: 100.00
 * @bodyParam nameProfessional string required Nombre del cajero que realiza la operación. Example: Yasmany Sánchez
 * @bodyParam comment string optional Comentario para la extracción (obligatorio si hay extracción). Example: Pago de proveedores
 * @bodyParam file file optional Comprobante de la extracción (PDF o imagen).
 *
 * @response 200 {"msg": "Caja actualizada correctamente correctamente"}
 * @response 500 {"msg": "Error al actualizar la caja"}
 */
     public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $request->merge([
                'cashFound' => $request->cashFound === 'null' ? null : $request->cashFound
            ]);
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'cashFound' => 'nullable|numeric',
                'existence' => 'nullable|numeric',
                'extraction' => 'nullable|numeric',
            ]);

            $branch = Branch::find($data['branch_id']);
            $box = Box::whereDate('data', Carbon::now())->where('branch_id', $data['branch_id'])->first();
            $trace = [];
            if (!$box) {              
                $box = new Box();
                $box->existence = $data['cashFound'];
                $box->extraction = $data['extraction'];
            }else{                         
                $existence = 0;
                if($data['cashFound'] != 0){
                    $existence = $box->existence - $box->cashFound + $data['cashFound'];
                  }
                 else{
                     $existence = $box->existence;
                 }
                
                // extraction es un nuevo monto retirado (no total)
                $box->existence = $existence - $data['extraction'];
                //$box->existence += $data['cashFound'] - $data['extraction'];               
                $box->extraction = $box->extraction + $data['extraction'];
            }
            $box->branch_id = $branch->id;
            $box->data = Carbon::now();

            // Solo actualizamos cashFound si es un número mayor que 0
            if ($data['cashFound'] !== null && is_numeric($data['cashFound']) && $data['cashFound'] > 0) {
                $box->cashFound = $data['cashFound'];
            }

            $box->save();
            if($data['extraction'] != 0){
                $trace = [
                    'branch' => $branch->name,
                    'cashier' => $request->nameProfessional,
                    'client' => '',
                    'amount' => $data['extraction'],
                    'operation' => 'Extracción de la caja',
                    'details' => '',
                    'description' => ''
                ];                
                $this->traceService->store($trace);
                $finance = Finance::orderBy('control', 'desc')->first();
                if ($finance !== null) {
                    $control = $finance->control + 1;
                } else {
                    $control = 1;
                }
                if ($request->hasFile('file')) {
                    $filename = $request->file('file')->storeAs('finances', 'Gasto-'.Carbon::now()->format('Y-m-d').'.'.$control. '.' . $request->file('file')->extension(), 'public');
                } else {
                    $filename = '';
                }
                $comment = $request->comment;
                $finance = new Finance();
                $finance->control = $control++;
                $finance->operation = 'Gasto';
                $finance->amount = $data['extraction'];
                $finance->comment = $comment;
                $finance->branch_id = $request->branch_id;
                $finance->type = 'Sucursal';
                $finance->expense_id = 10;
                $finance->data = Carbon::now();
                $finance->file = $filename;
                $finance->save();
            }
            if($data['cashFound'] != 0){
                $trace = [
                    'branch' => $branch->name,
                    'cashier' => $request->nameProfessional,
                    'client' => '',
                    'amount' => $data['cashFound'],
                    'operation' => 'Actualización de la caja',
                    'details' => '',
                    'description' => ''
                ];
                $this->traceService->store($trace);
            }
            DB::commit();
            return response()->json(['msg' => 'Caja actualizada correctamente correctamente'], 200);
        } catch (\Throwable $th) {
            DB::rollback();
        return response()->json(['msg' => $th->getMessage().'Error al actualizar la caja'], 500);
        }
    }

    /**
 * Obtiene el estado actual de la caja de una sucursal en el día.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "box": [
 *     {
 *       "id": 123,
 *       "branch_id": 5,
 *       "data": "2025-11-21",
 *       "cashFound": 500.00,
 *       "existence": 400.00,
 *       "extraction": 100.00
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar el carrito"}
 */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $box = Box::whereDate('data', Carbon::now())->where('branch_id', $data['branch_id'])->get();
            return response()->json(['box' => $box], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el carrito"], 500);
        }
    }

    /**
 * Actualiza la caja diaria de una sucursal (mismo comportamiento que `store`).
 *
 * Crea un nuevo registro si no existe, o modifica el existente.
 *
 * @authenticated
 * @bodyParam branch_id integer required ID de la sucursal. Example: 5
 * @bodyParam cashFound number optional Monto de efectivo encontrado. Example: 500.00
 * @bodyParam extraction number optional Monto extraído. Example: 100.00
 * @bodyParam nameProfessional string required Nombre del cajero. Example: Yasmany Sánchez
 *
 * @response 200 {"msg": "Caja actualizada correctamente correctamente"}
 * @response 500 {"msg": "Error al actualizar la caja"}
 */
    public function update(Request $request)
    {
        try {

            $request->merge([
                'cashFound' => $request->cashFound === 'null' ? null : $request->cashFound
            ]);
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'cashFound' => 'nullable|numeric',
                'existence' => 'nullable|numeric',
                'extraction' => 'nullable|numeric',
            ]);

            $branch = Branch::find($data['branch_id']);
            $box = Box::whereDate('data', Carbon::now())->where('branch_id', $data['branch_id'])->first();
            $trace = [];
            if (!$box) {              
                $box = new Box();
                $box->existence = $data['cashFound'];
                $box->extraction = $data['extraction'];
            }else{                         
               $existence = 0;
                if($data['cashFound'] != 0){
                    $existence = $box->existence - $box->cashFound + $data['cashFound'];
                  }
                 else{
                     $existence = $box->existence;
                 }
                
                // extraction es un nuevo monto retirado (no total)
                $box->existence = $existence - $data['extraction'];
                //$box->existence += $data['cashFound'] - $data['extraction'];               
                $box->extraction = $box->extraction + $data['extraction'];
            }
            $box->branch_id = $branch->id;
            $box->cashFound = $data['cashFound'];
            $box->data = Carbon::now();
            $box->save();
            if($data['extraction'] != 0){
                $trace = [
                    'branch' => $branch->name,
                    'cashier' => $request->nameProfessional,
                    'client' => '',
                    'amount' => $data['extraction'],
                    'operation' => 'Extracción de la caja',
                    'details' => '',
                    'description' => ''
                ];                
                $this->traceService->store($trace);
            }
            if($data['cashFound'] != 0){
                $trace = [
                    'branch' => $branch->name,
                    'cashier' => $request->nameProfessional,
                    'client' => '',
                    'amount' => $data['cashFound'],
                    'operation' => 'Actualización de la caja',
                    'details' => '',
                    'description' => ''
                ];
                $this->traceService->store($trace);
            }
            return response()->json(['msg' => 'Caja actualizada correctamente correctamente'], 200);
        } catch (\Throwable $th) {
        return response()->json(['msg' => $th->getMessage().'Error al actualizar la caja'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Box $box)
    {
        //
    }
}
