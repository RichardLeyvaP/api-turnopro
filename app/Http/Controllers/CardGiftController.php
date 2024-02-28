<?php

namespace App\Http\Controllers;

use App\Models\CardGift;
use Carbon\Carbon;
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
                'value' => 'nullable|numeric'
            ]);
            
            do {
                // Genera un código alfanumérico aleatorio
                $codigo = Str::random(8);
        
                // Verifica si el código ya existe en la base de datos
            } while (CardGift::where('code', $codigo)->exists());
            $cardGift = new CardGift();
            $cardGift->branch_id = $data['branch_id'];
            $cardGift->user_id = $data['user_id'];
            $cardGift->code = $codigo;
            $cardGift->data = Carbon::now();
            $cardGift->value = $data['value'];
            $cardGift->state = 'Activa';
            $cardGift->save();
            return response()->json(['msg' => 'Tarjeta de regalo asignadda correctamente'], 200);
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
            $cardGifts = CardGift::where('state', 'Activa')->Where('branch_id', $data['branch_id'])->with(['branch', 'user.client'])->get()->map(function ($query){
                return [
                    'id' => $query->id,
                    'data' => $query->data,
                    'code' => $query->code,
                    'state' => $query->state,
                    'value' => $query->value,
                    'name' => $query->user->client->name.' '.$query->user->client->surname.' '.$query->user->client->second_surname,
                    'user_id' => $query->user->id,
                    'client_image' => $query->user->client->client_image
                ];
            });
            Log::info($cardGifts);
            return response()->json(['cardGifts' => $cardGifts], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las tarjeta de regalo"], 500);
        }
    }

    public function show_value(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            $cardGifts = CardGift::find($data['id'])->value('value');
            Log::info($cardGifts);
            return response()->json($cardGifts, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las tarjeta de regalo"], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {

        Log::info("Crear");
        $data = $request->validate([
            'user_id' => 'nullable|numeric',
            'value' => 'nullable|numeric',
            'id' => 'required|numeric'
        ]);
        $cardGift = CardGift::find($data['id']);
        $cardGift->user_id = $data['user_id'];
        $cardGift->value = $data['value'];
        $cardGift->save();
        return response()->json(['msg' => 'Tarjeta de regalo asignadda correctamente'], 200);
    } catch (\Throwable $th) {
        Log::info($th);
    return response()->json(['msg' => $th->getMessage().'Error al asignartar la ttarjeta de regalo'], 500);
    }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            
            $data = $request->validate([
                'id' => 'required|numeric'
            ]);
            
                CardGift::destroy($data['id']);

            return response()->json(['msg' => 'Tarjeta de Regalo eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar la Tarjeta de Regalo'], 500);
        }
    }
    
}
