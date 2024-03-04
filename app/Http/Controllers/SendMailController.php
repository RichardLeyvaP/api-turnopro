<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\Send_mail;
use GuzzleHttp\Client;


class SendMailController extends Controller
{
    public function send_email(Request $request)
    {
        try {    
            $data = $request->validate([
                'email' => 'required',
            ]);         
            Log::info( "Entra a send_email");
            
            Mail::to($data['email'])->send(new Send_mail);
            Log::info( "Enviado send_email");
            return response()->json(['Response' => "Email enviado correctamente"], 200);
        } catch (\Throwable $th) {  
            Log::error($th);
            return response()->json(['msg' => "Error al enviar el Email"], 500);
        }
    }

    public function sendMessage(Request $request)
    {
        $client = new Client();
        $url = env('WHATSAPP_API_URL');
        $token = env('WHATSAPP_TOKEN');

        $response = $client->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'messaging_product' => 'whatsapp',
                'to' => $request->input('to'), // Número de teléfono del destinatario
                'type' => 'text',
                'text' => ['body' => $request->input('message')] // Mensaje
            ],
        ]);

        return response()->json(json_decode((string) $response->getBody(),true));
}

}
