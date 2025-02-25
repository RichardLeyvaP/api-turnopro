<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class NotificationService
{

    public function sendWhatsApp($phone, $name)
    {
        $twilioSid = env('TWILIO_SID');
        $twilioToken = env('TWILIO_AUTH_TOKEN');
        $twilioWhatsAppNumber = env('TWILIO_WHATSAPP_NUMBER');
        $recipientNumber = $phone;

        if (empty($recipientNumber)) {
            Log::error('El número de teléfono es obligatorio.');
            return false; // Indica que el envío falló
        }

        // Asegúrate de que el número de teléfono esté en el formato correcto
        if (strpos($recipientNumber, 'whatsapp:') === false) {
            $recipientNumber = 'whatsapp:' . $recipientNumber;
        }

        try {
            $twilio = new Client($twilioSid, $twilioToken);

            // Enviar un mensaje usando la plantilla aprobada
            $twilio->messages->create(
                $recipientNumber,
                [
                    "from" => "whatsapp:56931435036", // Número de WhatsApp de Twilio
                    "template_sid" => "HXabc5167c48681a4eaeeff4323505064a", // SID de la nueva plantilla
                    "contentVariables" => json_encode([
                        "1" => $name, // Nombre del cliente
                        "2" => $phone, // Nombre de la barbería
                    ]),
                ]
            );

            Log::info('Mensaje enviado exitosamente a ' . $recipientNumber);
            return true; // Indica que el envío fue exitoso
        } catch (Exception $e) {
            Log::error('Error enviando mensaje de WhatsApp: ' . $e->getMessage());
            return false; // Indica que el envío falló
        }
    }

    function sendWhatsAppRemember($phone, $name, $branch)
    {
        $twilioSid = env('TWILIO_SID');
        $twilioToken = env('TWILIO_AUTH_TOKEN');
        $twilioWhatsAppNumber = env('TWILIO_WHATSAPP_NUMBER');
        $recipientNumber = $phone;
        //$message = 'Usted va ser atendido aproximadamente en 3 minutos';

        if (empty($recipientNumber)) {
            return back()->with(['error' => 'El número de teléfono es obligatorio.']);
        }

        // Asegúrate de que el número de teléfono esté en el formato correcto
        if (strpos($recipientNumber, 'whatsapp:') === false) {
            $recipientNumber = 'whatsapp:' . $recipientNumber;
        }

        try {
            $twilio = new Client($twilioSid, $twilioToken);

            // Enviar un mensaje usando la plantilla aprobada
            $twilio->messages->create(
                $recipientNumber,
                [
                    "from" => "whatsapp:56931435036", // Número de WhatsApp de Twilio
                    "template_sid" => "HXabc5167c48681a4eaeeff4323505064a", // SID de la nueva plantilla
                    "contentVariables" => json_encode([
                        "1" => $name, // Nombre del cliente
                        "2" => $branch, // Nombre de la barbería
                        "3" => "https://reservasbh.simplifies.cl/", // Enlace de reserva
                    ]),
                ]
            );

            Log::info('Message sent successfully'. $twilio);

            return true;
        } catch (Exception $e) {
            Log::error('Error sending WhatsApp message: ' . $e->getMessage());
            return false;
        }
    }
}
