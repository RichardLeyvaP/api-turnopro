<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Models\CardGift;
use App\Models\CardGiftUser;
use App\Models\Client;
use App\Models\User;
use App\Services\SendEmailService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportException;

class CardGiftUserController extends Controller
{
    private SendEmailService $sendEmailService;
    public function __construct(SendEmailService $sendEmailService )
    {
       
        $this->sendEmailService = $sendEmailService;
    }
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
        Log::info("Asignar tarjeta de regalo");
        Log::info($request);
        try {
            $data = $request->validate([
                'user_id' => 'required|numeric',
                'card_gift_id' => 'required|numeric',
                'expiration_date' => 'required|date',
                //'number_notification' => 'nullable|numeric'
            ]);
            $user = User::find($data['user_id']);
            /*if ($user->professional) {                
            return $user->professional;
            }
            else{
                return $user->client;
            }*/
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
            // variablespara el correo
            $client_email = $user->professional ? $user->professional->email : $user->client->email;
            $client_name = $user->professional ? $user->professional->name.' '.$user->professional->surname.' '.$user->professional->second_surname : $user->client->name.' '.$user->client->surname.''.$user->client->second_surname;
            $code = $codigo;
            $value_card = $cardGift->value;
            $expiration_date = $data['expiration_date'];
            $image_cardgift = 'https://api2.simplifies.cl/api/images/'.$cardGift->image_cardgift;

            ///Aqui enviar codido por correo $user->email
            $this->sendEmailService->emailGitCard($client_email, $client_name, $code, $value_card,$expiration_date, $image_cardgift);
            //SendEmailJob::dispatch()->emailGitCard($client_email, $client_name, $code, $value_card,$expiration_date);
            /*$data = [
                'send_gift_card' => true, // Indica que es un correo de envío de tarjeta de regalo
                'client_email' => $client_email,
                'client_name' => $client_name,
                'code' => $code,
                'value_card' => $value_card,
                'expiration_date' => $expiration_date,
            ];
            
            SendEmailJob::dispatch($data);*/
            
            return response()->json(['msg' => 'Tarjeta de regalo asignada correctamente'], 200);
        } catch (TransportException $e) {
            Log::error($e);
            return response()->json(['msg' => 'Tarjeta de regalo asignada correctamente.Error al enviar el correo electrónico '], 200);
        }
          catch (\Throwable $th) {
              Log::error($th);
            
              DB::rollback();
              return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {             
            Log::info("Dado una cardGift devuelve los clientes que tienen asignado");
            $request->validate([
                'card_gift_id' => 'required|numeric'
            ]);
            $now = Carbon::now();
         // Retrieve all CardGift instances with the specified business_id
             $cardGifts = CardGiftUser::with(['cardGift', 'user.professional', 'user.client'])->where('card_gift_id', $request->card_gift_id)->get()->map(function ($query) use($now){
                $cardGift = $query->cardGift;
                $client = $query->user->client;
                $professional = $query->user->professional;
                return [
                    'id' => $query->id,
                    'code' => $query->code,
                    'issue_date' => $query->issue_date,
                    'exist' => $query->exist,
                    'expiration_date' =>$query->expiration_date,
                    'value' => $cardGift->value,
                    'name' => $cardGift->name,
                    'state' => $query->state,
                    'image_cardgift' => $cardGift->image_cardgift.'?$'.$now,
                    'userName' => $client ? $client->name : $professional->name,
                    'image_url' => $client ? $client->client_image.'?$'.$now : $professional->image_url.'?$'.$now
                ];
             });
             
                return response()->json(['cardgiftUser' => $cardGifts],200, [], JSON_NUMERIC_CHECK); 
          
            } catch (\Throwable $th) {  
            Log::error($th);
        return response()->json(['msg' => $th->getMessage()."Error interno del servidor"], 500);
        }
    }

    public function client_show(Request $request)
    {
        try {             
            Log::info("Dado una cliente devuelve las tarjetas que tienen asignado");
            $request->validate([
                'user_id' => 'required|numeric',
                'business_id' => 'required|numeric'
            ]);
            $now = Carbon::now();
         // Retrieve all CardGift instances with the specified business_id
             $cardGiftsUser = CardGiftUser::with(['cardGift', 'user.professional', 'user.client'])->where('user_id', $request->user_id)->get()->map(function ($query) use($now){
                $cardGift = $query->cardGift;
                $client = $query->user->client;
                $professional = $query->user->professional;
                return [
                    'id' => $query->id,
                    'code' => $query->code,
                    'issue_date' => $query->issue_date,
                    'exist' => $query->exist,
                    'expiration_date' =>$query->expiration_date,
                    'value' => $cardGift->value,
                    'name' => $cardGift->name,
                    'state' => $query->state,
                    'image_cardgift' => $cardGift->image_cardgift.'?$'.$now,
                    'userName' => $client ? $client->name : $professional->name,
                    'image_url' => $client ? $client->client_image.'?$'.$now : $professional->image_url.'?$'.$now
                ];
             });
             $cardGifts = CardGift::Where('business_id', $request->business_id)->with(['business'])->get()->map(function ($query) use($now){
                return [
                    'id' => intval($query->id),
                    'name' => $query->name,
                    'value' => $query->value,
                    'businesName' => $query->business->name,
                    'business_id' => $query->business_id,
                    'image_cardgift' => $query->image_cardgift.'?$'.$now
                ];
            });
             
                return response()->json(['cardgiftUser' => $cardGiftsUser, 'cardGifts' => $cardGifts],200, [], JSON_NUMERIC_CHECK); 
          
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
            $cardGiftUser = CardGiftUser::where('state', 'Activa')->where('code', $data['code'])->get()->value('exist');
            Log::info($cardGiftUser);
            return response()->json($cardGiftUser ? $cardGiftUser : 0, 200, [], JSON_NUMERIC_CHECK);
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
            Log::error($th);
            return response()->json(['msg' => 'Error del sistema'], 500);
        }
    }
}
