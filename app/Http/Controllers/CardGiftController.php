<?php

namespace App\Http\Controllers;

use App\Models\CardGift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class CardGiftController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return response()->json(['cardGifts' => CardGift::with(['branch', 'user'])->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar las tarjeta de regalo"], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            Log::info("Crear");
            $data = $request->validate([
                'branch_id' => 'required|numeric',
                'user_id' => 'nullable|numeric',
                'data' => 'nullable|numeric',
                'value' => 'nullable|numeric',
                'state' => 'nullable|string'
            ]);
            
            do {
                // Genera un código alfanumérico aleatorio
                $codigo = Str::random(8);
        
                // Verifica si el código ya existe en la base de datos
            } while (CardGift::where('code', $codigo)->exists());
            $cardGift = new CardGift();
            $cardGift->branch_id = $data['branch_id'];
            $cardGift->user_id = $data['user_id'];
            $cardGift->code = $data['code'];
            $cardGift->data = $data['data'];
            $cardGift->value = $data['value'];
            $cardGift->state = $data['state'];
            $cardGift->save();
            return response()->json(['msg' => 'Tarjeta de regajo asignadda correctamente'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
        return response()->json(['msg' => $th->getMessage().'Error al asignartar la ttarjeta de regalo'], 500);
        }
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
            return response()->json(['cardGifts' => CardGift::Where('branch_id', $data['branch_id'])->with(['branch', 'user'])->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar las tarjeta de regalo"], 500);
        }
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
    public function destroy(CardGift $cardGift)
    {
        //
    }
}
