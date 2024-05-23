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
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $retentions = Retention::all();
            return response()->json(['retentions' => $retentions], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::error($th);
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
     * Store a newly created resource in storage.
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
            Log::error($th);
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
