<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\Send_mail;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;


class SendMailController extends Controller
{
    public function send_email(Request $request)
    {
        try {    
            $data = $request->validate([
                'email' => 'required',
            ]);         
            Log::info( "Entra a send_email");
            
            Mail::to($data['email'])->send(new Send_mail('q','w','e','r','t','t','y'));
            Log::info( "Enviado send_email");
            return response()->json(['Response' => "Email enviado correctamente"], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al enviar el Email"], 500);
        }
    }

    

public function sendMessage(Request $request)
{
    Log::info("sendMessage Whatsapp1");
   // $url = env('WHATSAPP_API_URL');
    $url = 'https://graph.facebook.com/v18.0/113984608247982/messages';
    //$token = env('WHATSAPP_TOKEN');
    $token = 'EAAagNvvUedwBO19N1wGZBfSWcl8zQEoGPd1BuWE84XnTQ5OkwOiEs0yDP7llJ6xmpD0dZA8PQNKwd6COHZC1ccWHZBygjVVmWLmxeTZBZBd9EhEC0OP6CDee2P97tp8YZAZAfGSUwF79IdXizX7RVxyv6qZB2vVSNpqXhuGnzijm1lvshF9JnZBQOni3vGHvv6P5AvVVu5KOIAfRnOoU5jmXKMbG1A15AZD';

    $response = Http::post($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ],
        'json' => [
            'messaging_product' => 'whatsapp',
            'to' => $request->input('56920258489'), // Número de teléfono del destinatario
            'type' => 'text',
            'text' => ['body' => 'Soy Richard haciendo una prueba desde el Postman..dime si te llega el mensaje Patrón..jj'] // Mensaje
            //'text' => ['body' => $request->input('message')] // Mensaje
        ],
    ]);

    Log::info("sendMessage Whatsapp2");
    Log::info((string) $response->getBody());
    return response()->json(json_decode((string) $response->getBody(), true));
}


}
