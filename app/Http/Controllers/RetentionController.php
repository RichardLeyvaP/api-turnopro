<?php

namespace App\Http\Controllers;

use App\Models\r;
use App\Models\Retention;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RetentionController extends Controller
{

    /**
 * Lista todas las retenciones registradas en el sistema.
 *
 * @authenticated
 *
 * @response 200 {
 *   "retentions": [
 *     {
 *       "id": 1,
 *       "branch_id": 3,
 *       "professional_id": 10,
 *       "data": "2025-11-21T18:30:00.000000Z",
 *       "retention": 15000
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function index()
    {
        try {
            $retentions = Retention::all();
            return response()->json(['retentions' => $retentions], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
 * Registra una nueva retención para un profesional en una sucursal.
 *
 * La fecha se establece automáticamente al momento de la creación (`Carbon::now()`).
 *
 * @authenticated
 * @bodyParam branch_id integer required ID de la sucursal. Example: 3
 * @bodyParam professional_id integer required ID del profesional. Example: 10
 * @bodyParam data date required Fecha de la retención (se ignora; se usa la fecha actual del sistema). Example: "2025-11-21"
 * @bodyParam retention number required Monto de la retención. Example: 15000
 *
 * @response 200 {"msg": "Retención insertada correctamente"}
 * @response 400 {"msg": ["El campo branch_id es obligatorio."]}
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'branch_id' => 'required|integer',
                'professional_id' => 'required|integer',
                'data' => 'required|date',
                'retention' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'msg' => $validator->errors()->all()
                ], 400);
            }

            $retention = new Retention();
            $retention->branch_id = $request->branch_id;
            $retention->professional_id = $request->professional_id;
            $retention->data = Carbon::now();
            $retention->retention = $request->retention;
            $retention->save();

            return response()->json(['msg' => 'Retención insertada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error interno del sistema'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        //
    }
}
