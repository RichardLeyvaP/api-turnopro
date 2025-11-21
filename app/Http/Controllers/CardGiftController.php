<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\CardGift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class CardGiftController extends Controller
{
    /**
 * Obtiene todas las tarjetas de regalo con sus negocios asociados, y la lista de negocios con administradores.
 *
 * @authenticated
 *
 * @response 200 {
 *   "cardGifts": [
 *     {
 *       "id": 1,
 *       "name": "Regalo Premium",
 *       "value": 10000.00,
 *       "business_id": 1,
 *       "image_cardgift": "cardgifts/1.jpg?$2025-11-21T10:30:00Z",
 *       "business": { ... }
 *     }
 *   ],
 *   "business": [
 *     {
 *       "id": 1,
 *       "name": "Barbería Central",
 *       "address": "Calle Principal 123",
 *       "professional_name": "Yasmany"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar las tarjeta de regalo"}
 */
    public function index()
    {
        try {
            $now = Carbon::now();
            $cardGift = CardGift::with(['business'])->get()->map(function ($query) use ($now){
                return [
                    'id' => $query->id,
                    'name' => $query->name,
                    'value' => $query->value,
                    'business_id' => $query->business_id,
                    'image_cardgift' => $query->image_cardgift.'?$'.$now,
                    'business' => $query->business
                ];
            });
            return response()->json(['cardGifts' => $cardGift, 'business' => Business::join('professionals', 'businesses.professional_id', '=', 'professionals.id')
                ->select('businesses.id', 'businesses.name', 'businesses.address', 'professionals.name as professional_name')
                ->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar las tarjeta de regalo"], 500);
        }
    }

    /**
 * Crea una nueva tarjeta de regalo para un negocio.
 *
 * @authenticated
 * @bodyParam business_id integer required ID del negocio. Example: 1
 * @bodyParam name string required Nombre de la tarjeta. Example: Regalo Premium
 * @bodyParam value number optional Valor monetario. Example: 10000.00
 * @bodyParam image_cardgift file optional Imagen de la tarjeta de regalo.
 *
 * @response 200 {"msg": "Tarjeta de regalo asignadda correctamente"}
 * @response 500 {"msg": "Error al asignartar la ttarjeta de regalo"}
 */
    public function store(Request $request)
    {
        try {

            $data = $request->validate([
                'business_id' => 'required|numeric',
                'value' => 'nullable|numeric',
                'name' => 'required|string'
            ]);
            
            $cardGift = new CardGift();
            $cardGift->business_id = $data['business_id'];
            $cardGift->value = $data['value'];
            $cardGift->name = $data['name'];
            $cardGift->save();
            $filename = "cardgifts/default.jpg"; 
            if ($request->hasFile('image_cardgift')) {
               $filename = $request->file('image_cardgift')->storeAs('cardgifts',$cardGift->id.'.'.$request->file('image_cardgift')->extension(),'public');
            }
            $cardGift->image_cardgift = $filename;
            $cardGift->save();
            return response()->json(['msg' => 'Tarjeta de regalo asignadda correctamente'], 200);
        } catch (\Throwable $th) {
        return response()->json(['msg' => $th->getMessage().'Error al asignartar la ttarjeta de regalo'], 500);
        }
    }

    /**
 * Obtiene las tarjetas de regalo asociadas a un negocio específico.
 *
 * @authenticated
 * @queryParam business_id integer required ID del negocio. Example: 1
 *
 * @response 200 {
 *   "cardGifts": [
 *     {
 *       "id": 1,
 *       "name": "Regalo Premium",
 *       "value": 10000.00,
 *       "businesName": "Barbería Central",
 *       "business_id": 1,
 *       "image_cardgift": "cardgifts/1.jpg?$2025-11-21T10:30:00Z"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar las tarjeta de regalo"}
 */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'business_id' => 'required|numeric'
            ]);
            $now = Carbon::now();
            $cardGifts = CardGift::Where('business_id', $data['business_id'])->with(['business'])->get()->map(function ($query) use($now){
                return [
                    'id' => $query->id,
                    'name' => $query->name,
                    'value' => $query->value,
                    'businesName' => $query->business->name,
                    'business_id' => $query->business_id,
                    'image_cardgift' => $query->image_cardgift.'?$'.$now
                ];
            });
            return response()->json(['cardGifts' => $cardGifts], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las tarjeta de regalo"], 500);
        }
    }

    /**
 * Actualiza los datos de una tarjeta de regalo existente.
 *
 * @authenticated
 * @bodyParam id integer required ID de la tarjeta. Example: 1
 * @bodyParam name string optional Nuevo nombre. Example: Regalo VIP
 * @bodyParam value number optional Nuevo valor. Example: 15000.00
 * @bodyParam image_cardgift file optional Nueva imagen.
 *
 * @response 200 {"msg": "Tarjeta de regalo creada correctamente"}
 * @response 500 {"msg": "Error al asignartar la ttarjeta de regalo"}
 */
    public function update(Request $request)
    {
        try {

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
    return response()->json(['msg' => $th->getMessage().'Error al asignartar la ttarjeta de regalo'], 500);
    }
    }

    /**
 * Elimina una tarjeta de regalo del sistema.
 *
 * @authenticated
 * @bodyParam id integer required ID de la tarjeta. Example: 1
 *
 * @response 200 {"msg": "Tarjeta de Regalo eliminada correctamente"}
 * @response 500 {"msg": "Error al eliminar la Tarjeta de Regalo"}
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
