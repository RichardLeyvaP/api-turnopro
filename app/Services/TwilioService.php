<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class TwilioService
{
    protected $client;

    public function __construct()
    {
        $sid = env('TWILIO_ACCOUNT_SID');
        $token = env('TWILIO_AUTH_TOKEN');

        Log::info('TWILIO_ACCOUNT_SID: ' . $sid);  // Verifica si se carga correctamente el SID
        Log::info('TWILIO_AUTH_TOKEN: ' . $token); // Verifica si se carga correctamente el token

        if (empty($sid) || empty($token)) {
            throw new \Exception('Las credenciales de Twilio no están configuradas correctamente.');
        }

        $this->client = new Client($sid, $token);
    }

    /**
     * Enviar un SMS.
     *
     * @param string $to Número de teléfono del destinatario
     * @param string $message Mensaje a enviar
     * @return void
     */
    public function sendSms($to, $message)
    {
        $this->client->messages->create($to, [
            'from' => env('TWILIO_PHONE_NUMBER'),
            'body' => $message,
        ]);
    }
}
