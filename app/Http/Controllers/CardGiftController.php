<?php

namespace App\Http\Controllers;

use App\Models\CardGift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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
            return response()->json(['cardGifts' => CardGift::with(['business'])->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::info($th);
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
                'business_id' => 'required|numeric',
                'value' => 'nullable|numeric',
                'name' => 'required|string'
            ]);
            
            /*do {
                // Genera un código alfanumérico aleatorio
                $codigo = Str::random(8);
        
                // Verifica si el código ya existe en la base de datos
            } while (CardGift::where('code', $codigo)->exists());*/
            $cardGift = new CardGift();
            $cardGift->business_id = $data['business_id'];
            $cardGift->value = $data['value'];
            $cardGift->name = $data['name'];
            $cardGift->save();
            Log::info($cardGift);
            $filename = "cardgifts/default.jpg"; 
            if ($request->hasFile('image_cardgift')) {
               $filename = $request->file('image_cardgift')->storeAs('cardgifts',$cardGift->id.'.'.$request->file('image_cardgift')->extension(),'public');
            }
            $cardGift->image_cardgift = $filename;
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
                'business_id' => 'required|numeric'
            ]);
            $cardGifts = CardGift::Where('business_id', $data['business_id'])->with(['business'])->get()->map(function ($query){
                return [
                    'id' => $query->id,
                    'name' => $query->state,
                    'value' => $query->value,
                    'businesName' => $query->business->name,
                    'business_id' => $query->business_id,
                    'image_cardgift' => $query->image_cardgift
                ];
            });
            Log::info($cardGifts);
            return response()->json(['cardGifts' => $cardGifts], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las tarjeta de regalo"], 500);
        }
    }

    /*public function show_value(Request $request)
    {
        try {
            $data = $request->validate([
                'code' => 'required'
            ]);
            $cardGifts = CardGift::where('code', $data['code'])->first()->value('value');
            Log::info($cardGifts);
            return response()->json($cardGifts, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las tarjeta de regalo"], 500);
        }
    }*/

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {

        Log::info("Editar");
        $data = $request->validate([
            'value' => 'nullable|numeric',
            'name' => 'nullable|string',
            'id' => 'required|numeric'
        ]);
        $cardGift = CardGift::find($data['id']);
        if ($request->hasFile('image_cardgift')) {
            if($cardGift->image_cardgift != 'cardgifts/default.jpg'){
            $destination = public_path("storage\\" . $cardGift->image_cardgift);
            if (File::exists($destination)) {
                File::delete($destination);
            }              
                $cardGift->image_cardgift = $request->file('image_cardgift')->storeAs('cardgifts',$cardGift->id.'.'.$request->file('image_cardgift')->extension(),'public');
            }
        }
        $cardGift->value = $data['value'];
        $cardGift->name = $data['name'];
        $cardGift->save();
        return response()->json(['msg' => 'Tarjeta de regalo creada correctamente'], 200);
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
            $cardGift = CardGift::find($data['id']);
            if ($cardGift->image_cardgift != "cardgifts/default.jpg") {
                $destination=public_path("storage\\".$cardGift->image_cardgift);
                    if (File::exists($destination)) {
                        File::delete($destination);
                    }
                }
                CardGift::destroy($data['id']);

            return response()->json(['msg' => 'Tarjeta de Regalo eliminada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error al eliminar la Tarjeta de Regalo'], 500);
        }
    }
    
}
