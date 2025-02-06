<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class TwilioService
{
    protected $client;

    public function __construct()
    {

        /*Log::info('TWILIO_ACCOUNT_SID: ' . $sid);  // Verifica si se carga correctamente el SID
        Log::info('TWILIO_AUTH_TOKEN: ' . $token); // Verifica si se carga correctamente el token

        if (empty($sid) || empty($token)) {
            throw new \Exception('Las credenciales de Twilio no están configuradas correctamente.');
        }*/

        //$this->client = new Client($sid, $token);
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
        try {
            $this->client->messages->create($to, [
                'from' => "whatsapp:+14155238886",
                'body' => $message,
            ]);
        } catch (\Twilio\Exceptions\RestException $e) {
            Log::error('Error al enviar SMS: ' . $e->getMessage());
            throw $e;  // Rethrow or handle error appropriately
        }
    }
}
