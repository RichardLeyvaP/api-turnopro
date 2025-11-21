<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Models\CardGift;
use App\Models\CardGiftUser;
use App\Models\Client;
use App\Models\Professional;
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
 * Obtiene todas las asignaciones de tarjetas de regalo a usuarios.
 *
 * @authenticated
 *
 * @response 200 {
 *   "cardGiftUsers": [
 *     {
 *       "id": 1,
 *       "cardGift": { ... },
 *       "user": { ... }
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error al mostrar las tarjeta de regalo"}
 */
    public function index()
    {
        try {
            return response()->json(['cardGiftUsers' => CardGiftUser::with(['cardGift', 'user'])->get()], 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
            return response()->json(['msg' => "Error al mostrar las tarjeta de regalo"], 500);
        }
    }
   
    /**
 * Asigna una tarjeta de regalo a un usuario (cliente o profesional).
 *
 * Genera un código único, asigna valor y envía correo al usuario y al administrador de sucursal.
 *
 * @authenticated
 * @bodyParam user_id integer required ID del usuario (cliente o profesional). Example: 123
 * @bodyParam card_gift_id integer required ID de la tarjeta de regalo. Example: 1
 * @bodyParam expiration_date string required Fecha de vencimiento (Y-m-d). Example: 2026-11-21
 * @bodyParam branch_id integer required ID de la sucursal (para determinar destinatario del correo). Example: 5
 *
 * @response 200 {"msg": "Tarjeta de regalo asignada correctamente"}
 * @response 200 {"msg": "Tarjeta de regalo asignada correctamente.Error al enviar el correo electrónico "}
 * @response 500 {"msg": "Error interno del servidor"}
 */
    public function store(Request $request)
    {
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
            $client_name = $user->professional ? $user->professional->name : $user->client->name;
            $code = $codigo;
            $value_card = $cardGift->value;
            $expiration_date = $data['expiration_date'];
            $image_cardgift = 'https://api2.simplifies.cl/api/images/'.$cardGift->image_cardgift;
                $branch_id = $request->branch_id;
                $emails = Professional::whereHas('charge', function ($query)  use ($branch_id) {
                    $query->Where('name', 'Administrador de Sucursal');
                })->whereHas('branches', function ($query) use ($branch_id) {
                    $query->where('branches.id', $branch_id);
                })/*whereIn('charge_id', [3, 4, 5, 12])*/
                    ->pluck('email');
                    $mergedEmails = $emails->merge($client_email);

                    foreach ($mergedEmails as $email) {
                        try {
                            $this->sendEmailService->emailGitCard($email, $client_name, $code, $value_card,$expiration_date, $image_cardgift);
                        } catch (\Swift_TransportException $e) {
                        
                        } catch (\Exception $e) {
                            
                        }
                    }
            
            return response()->json(['msg' => 'Tarjeta de regalo asignada correctamente'], 200);
        } catch (TransportException $e) {
            return response()->json(['msg' => 'Tarjeta de regalo asignada correctamente.Error al enviar el correo electrónico '], 200);
        }
          catch (\Throwable $th) {
              DB::rollback();
              return response()->json(['msg' => $th->getMessage() . 'Error interno del servidor'], 500);
        }
    }
    
    /**
 * Obtiene las tarjetas de regalo asignadas a usuarios **vinculados a una sucursal específica**.
 *
 * Solo muestra usuarios (clientes o profesionales) que pertenecen a la sucursal indicada.
 *
 * @authenticated
 * @queryParam card_gift_id integer required ID de la tarjeta de regalo. Example: 1
 * @queryParam branch_id integer required ID de la sucursal. Example: 5
 *
 * @response 200 {
 *   "cardgiftUser": [
 *     {
 *       "id": 1,
 *       "code": "aB3xK9mP",
 *       "issue_date": "2025-11-21",
 *       "exist": 10000.00,
 *       "expiration_date": "2026-11-21",
 *       "value": 10000.00,
 *       "name": "Regalo Premium",
 *       "state": "Activa",
 *       "image_cardgift": "cardgifts/1.jpg?$2025-11-21T10:30:00Z",
 *       "userName": "Yasmany",
 *       "image_url": "clients/123.jpg?$2025-11-21T10:30:00Z"
 *     }
 *   ]
 * }
 * @response 500 {"msg": "Error interno del servidor"}
 */
    public function show(Request $request)
    {
               try {             
            $request->validate([
                'card_gift_id' => 'required|numeric',
                'branch_id' => 'required|numeric'
            ]);
            $now = Carbon::now();
             $cardGifts = CardGiftUser::with(['cardGift', 'user.professional', 'user.client'])
            ->where('card_gift_id', $request->card_gift_id)
            ->where(function ($query) use ($request) {
                $query->whereDoesntHave('user.client.clientProfessionals.cars.reservation')
                    ->orWhereHas('user.client.clientProfessionals.cars.reservation', function ($query) use ($request) {
                        $query->where('branch_id', $request->branch_id);
                    });
            })
            ->where(function ($query) use ($request) {
                $query->whereDoesntHave('user.professional.branches')
                    ->orWhereHas('user.professional.branches', function ($query) use ($request) {
                        $query->where('branch_id', $request->branch_id);
                    });
            })
            ->get()
            ->map(function ($query) use($now) {
                $cardGift = $query->cardGift;
                $client = $query->user->client;
                $professional = $query->user->professional;
                return [
                    'id' => $query->id,
                    'code' => $query->code,
                    'issue_date' => $query->issue_date,
                    'exist' => $query->exist,
                    'expiration_date' => $query->expiration_date,
                    'value' => $cardGift->value,
                    'name' => $cardGift->name,
                    'state' => $query->state,
                    'image_cardgift' => $cardGift->image_cardgift . '?$' . $now,
                    'userName' => $client ? $client->name : $professional->name,
                    'image_url' => $client ? $client->client_image . '?$' . $now : $professional->image_url . '?$' . $now
                ];
            });
             
                return response()->json(['cardgiftUser' => $cardGifts],200, [], JSON_NUMERIC_CHECK); 
          
            } catch (\Throwable $th) {  
        return response()->json(['msg' => $th->getMessage()."Error interno del servidor"], 500);
        }   
    
    }

    /**
 * Obtiene las tarjetas de regalo asignadas a un usuario **y todas las tarjetas disponibles de su negocio**.
 *
 * Útil para el frontend al mostrar opciones de canje.
 *
 * @authenticated
 * @queryParam user_id integer required ID del usuario. Example: 123
 * @queryParam business_id integer required ID del negocio. Example: 1
 *
 * @response 200 {
 *   "cardgiftUser": [ ... ],
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
 * @response 500 {"msg": "Error interno del servidor"}
 */
    public function client_show(Request $request)
    {
        try {             
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
        return response()->json(['msg' => $th->getMessage()."Error interno del servidor"], 500);
        }
    }

    /**
 * Obtiene el saldo disponible de una tarjeta de regalo por su código.
 *
 * Solo devuelve el valor si la tarjeta está activa y no vencida.
 *
 * @queryParam code string required Código de la tarjeta. Example: aB3xK9mP
 *
 * @response 200 10000.00
 * @response 200 0
 * @response 500 {"msg": "Error al mostrar las tarjeta de regalo"}
 */
    public function show_value(Request $request)
    {
        try {
            $data = $request->validate([
                'code' => 'required'
            ]);
            $cardGiftUser = CardGiftUser::where('state', 'Activa')->where('code', $data['code'])->get()->value('exist');
            return response()->json($cardGiftUser ? $cardGiftUser : 0, 200, [], JSON_NUMERIC_CHECK);
        } catch (\Throwable $th) {
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
 * Elimina una asignación de tarjeta de regalo (desasigna del usuario).
 *
 * @authenticated
 * @bodyParam id integer required ID de la asignación (card_gift_user). Example: 1
 *
 * @response 200 {"msg": "Tarjeta desasignada correctamente"}
 * @response 500 {"msg": "Error del sistema"}
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
