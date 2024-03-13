<?php

namespace App\Http\Controllers;

use App\Models\Associated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssociatedController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return response()->json(['associates' => Associated::all()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error interno del sistema"], 500);
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
            $data = $request->validate([
                'name' => 'required|max:50',
                'email' => 'required|max:100|email|unique:associates'
            ]);

            $associated = new Associated();
            $associated->name = $data['name'];
            $associated->email = $data['email'];
            $associated->save();

            return response()->json(['msg' => 'Asociado insertado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            return response()->json(['business' => Associated::find($data['id'])], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error interno del sistema"], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Associated $associated)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Associated $associated)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric',
                'name' => 'required|max:50',
                'email' => 'required|max:100|email'
            ]);

            $associated = Associated::find($data['id']);
            $associated->name = $data['name'];
            $associated->email = $data['email'];
            $associated->save();

            return response()->json(['msg' => 'Asociado actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
            return response()->json(['msg' => $th->getMessage().'Error interno del sistema'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            Associated::destroy($data['id']);

            return response()->json(['msg' => 'Asociado eliminado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error intern del sistema'], 500);
        }
    }
}
