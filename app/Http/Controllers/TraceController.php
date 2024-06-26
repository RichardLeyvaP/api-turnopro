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
    public function traces_branch_day(Request $request)
    {
    Log::info("Trazas por año");
        Log::info($request);
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'day' => 'nullable'
            ]);
            $branch = Branch::where('id', $data['branch_id'])->first();
            $traces = Trace::where('branch', $branch->name)->whereDate('data', $data['day'])->get();
            return response()->json(['traces' => $traces], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    public function traces_branch_month(Request $request)
    {
    Log::info("Trazas en un mes");
        Log::info($request);
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
            Log::error($th);
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    public function traces_branch_periodo(Request $request)
    {
    Log::info("Trazas en un periodo");
        Log::info($request);
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
            Log::error($th);
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }
}
