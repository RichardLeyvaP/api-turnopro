<?php

namespace App\Http\Controllers;

use App\Models\CardGift;
use App\Models\CardGiftUser;
use App\Models\Client;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CardGiftUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return response()->json(['cardGiftUsers' => CardGiftUser::with(['cardGift', 'user'])->get()], 200, [], JSON_NUMERIC_CHECK);
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
        Log::info("Asignar Productos a un almacen");
        Log::info($request);
        try {
            $data = $request->validate([
                'user_id' => 'required|numeric',
                'card_gift_id' => 'required|numeric',
                'expiration_date' => 'required|date',
                //'number_notification' => 'nullable|numeric'
            ]);
            $user = User::find($data['user_id']);
            do {
                // Genera un código alfanumérico aleatorio
                $codigo = Str::random(8);
        
                // Verifica si el código ya existe en la base de datos
            } while (CardGiftUser::where('code', $codigo)->exists());
            $cardGift = CardGift::find($data['card_gift_id']);
            Log::info($cardGift);
            $cardGiftUser = new CardGiftUser();
            $cardGiftUser->user_id = $data['user_id'];
            $cardGiftUser->card_gift_id = $data['card_gift_id'];
            $cardGiftUser->issue_date = Carbon::now();
            $cardGiftUser->expiration_date = $data['expiration_date'];            
            $cardGiftUser->state = 'Activa';
            $cardGiftUser->code = $codigo;
            $cardGiftUser->exist = $cardGift->value;
            $cardGiftUser->save();

            ///Aqui enviar codido por correo $user->email

            return response()->json(['msg' => 'Tarjeta de regalo correctamente al almacén'], 200);
        } catch (\Throwable $th) {
            Log::error($th);
        return response()->json(['msg' =>$th->getMessage().'Error interno'], 500);
        }
}

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {             
            Log::info("Dado una cargo devuelve los permisos");
            $request->validate([
                'card_gift_id' => 'required|numeric'
            ]);
         // Retrieve all CardGift instances with the specified business_id
             $cardGifts = CardGiftUser::with(['cardGift', 'user.professional', 'user.client'])->where('card_gift_id', $request->card_gift_id)->get()->map(function ($query){
                return [
                    'id' => $query->id,
                    'code' => $query->code,
                    'issue_date' => $query->issue_date,
                    'exist' => $query->exist,
                    'expiration_date' =>$query->expiration_date,
                    'value' => $query->cardGift->value,
                    'name' => $query->cardGift->name,
                    'state' => $query->state,
                    'image_cardgift' => $query->cardGift->image_cardgift,
                    'userName' => $query->user->client ? $query->user->client->name.' '.$query->user->client->surname.' '. $query->user->client->second_surname : $query->user->professional->name.' '.$query->user->professional->surname.' '.$query->user->professional->second_surname,
                    'image_url' => $query->user->client ? $query->user->client->client_image : $query->user->professional->image_url
                ];
             });
             
                return response()->json(['cardgiftUser' => $cardGifts],200, [], JSON_NUMERIC_CHECK); 
          
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error interno del servidor"], 500);
        }
    }

    public function show_value(Request $request)
    {
        try {
            $data = $request->validate([
                'code' => 'required'
            ]);
            $cardGiftUser = CardGiftUser::where('code', $data['code'])->get()->value('exist');
            Log::info($cardGiftUser);
            return response()->json($cardGiftUser, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            Log::info($th);
            return response()->json(['msg' => $th->getMessage()."Error al mostrar las tarjeta de regalo"], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CardGift $cardGift)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CardGift $cardGift)
    {
        //
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
            
                CardGiftUser::destroy($data['id']);

            return response()->json(['msg' => 'Tarjeta desasignada correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['msg' => 'Error del sistema'], 500);
        }
    }
}
