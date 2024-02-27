<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BoxController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'branch_id' => 'required|numeric'
            ]);
            $box = Box::where('branch_id', $data['branch_id'])->whereDate('data', Carbon::now())->first();
            return response()->json(['box' => $box], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar el carrito"], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {

            Log::info("Editar");
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'cashFound' => 'nullable|numeric',
                'existence' => 'nullable|numeric',
                'extraction' => 'nullable|numeric',
            ]);

            $branch = Branch::find($data['branch_id']);
            $box = Box::where('branch_id', $branch->id)->whereDate('data', Carbon::now())->first();
            if (!$box) {                
                $box = new Box();
                $box->existence = $data['cashFound'];
                $box->extraction = $data['extraction'];
            }else{                
                $box->existence = $box->existence + $data['cashFound'];               
                $box->extraction = $box->extraction + $data['extraction'];
            }
            $box->branch_id = $branch->id;
            $box->cashFound = $data['cashFound'];
            $box->data = Carbon::now();
            $box->save();

            return response()->json(['msg' => 'Caja actualizada correctamente correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
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
