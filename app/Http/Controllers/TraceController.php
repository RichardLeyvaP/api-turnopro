<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Car;
use App\Models\Trace;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TraceController extends Controller
{
    /**
 * Obtiene los registros de trazabilidad (traces) de una sucursal en un día específico.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam day string required Fecha en formato Y-m-d. Example: 2025-11-21
 *
 * @response 200 {
 *   "traces": [
 *     {
 *       "id": 123,
 *       "branch": "Centro",
 *       "cashier": "Yasmany",
 *       "client": "Juan Pérez",
 *       "amount": 10000.00,
 *       "operation": "Paga Carro",
 *       "details": "Carro: 456",
 *       "description": "Barbero",
 *       "data": "2025-11-21 10:30:00",
 *       "created_at": "2025-11-21T10:30:00.000000Z"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function traces_branch_day(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'day' => 'nullable'
            ]);
            $branch = Branch::where('id', $data['branch_id'])->first();
            $traces = Trace::where('branch', $branch->name)->whereDate('data', $data['day'])->orderByDesc('created_at')->get();
            return response()->json(['traces' => $traces], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    /**
 * Obtiene los registros de trazabilidad de una sucursal en un mes y año específicos.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam year integer required Año. Example: 2025
 * @queryParam month integer required Mes (1–12). Example: 11
 *
 * @response 200 {
 *   "traces": [ ... ]
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function traces_branch_month(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'year' => 'nullable|numeric',
                'month' => 'nullable|numeric'
            ]);
            $branch = Branch::where('id', $data['branch_id'])->first();
            $traces = Trace::where('branch', $branch->name)->whereYear('data', $data['year'])->whereMonth('data', $data['month'])->get();
            return response()->json(['traces' => $traces], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    /**
 * Obtiene los registros de trazabilidad de una sucursal en un rango de fechas.
 *
 * @authenticated
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 * @queryParam startDate string required Fecha de inicio (Y-m-d). Example: 2025-11-01
 * @queryParam endDate string required Fecha de fin (Y-m-d). Example: 2025-11-30
 *
 * @response 200 {
 *   "traces": [ ... ]
 * }
 * @response 500 {"msg": "Error interno del sistema"}
 */
    public function traces_branch_periodo(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'endDate' => 'nullable|date',
                'startDate' => 'nullable|date'
            ]);
            $branch = Branch::where('id', $data['branch_id'])->first();
            $traces = Trace::where('branch', $branch->name)->whereDate('data', '>=',$data['startDate'])->whereDate('data', '<=',$data['endDate'])->orderByDesc('data')->get();
            return response()->json(['traces' => $traces], 200,  [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }
}
