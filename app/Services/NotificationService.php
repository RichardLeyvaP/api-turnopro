<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class NotificationService
{


    public function whatsapp_notification(Request $request)
    {
        $twilioSid = env('TWILIO_SID');
        $twilioTemplateSid = env('TWILIO_TEMPLATE_SID');
        $twilioToken = env('TWILIO_AUTH_TOKEN');
        $twilioWhatsAppNumber = env('TWILIO_WHATSAPP_NUMBER');
        $recipientNumber = $request->telefone_client;
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
                    "from" => $twilioWhatsAppNumber, // Número de WhatsApp de Twilio
                    "template_sid" => $twilioTemplateSid, // SID de la plantilla
                    "contentSid" => $twilioTemplateSid, // SID de la plantilla
                    "contentVariables" => json_encode([
                        "1" => "3" // Parámetro dinámico
                    ]),
                ]
            );
        
            return back()->with(['success' => 'WhatsApp message sent successfully!']);
        } catch (Exception $e) {
            Log::error('Error sending WhatsApp message: ' . $e->getMessage());
            return back()->with(['error' => $e->getMessage()]);
        }
    }

    public function sendWhatsApp($phone, $name)
    {

        $twilioSid = env('TWILIO_SID');
        $twilioTemplateSid = env('TWILIO_TEMPLATE_SID');
        $twilioToken = env('TWILIO_AUTH_TOKEN');
        $twilioWhatsAppNumber = env('TWILIO_WHATSAPP_NUMBER');
        $recipientNumber = $phone;

        if (empty($recipientNumber)) {
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
                    "from" => $twilioWhatsAppNumber, // Número de WhatsApp de Twilio
                    "template_sid" => $twilioTemplateSid, // SID de la plantilla
                    "contentSid" => $twilioTemplateSid, // SID de la plantilla
                    "contentVariables" => json_encode([
                        "1" => $name, // Nombre del cliente
                    ]),
                ]
            );

            return true; // Indica que el envío fue exitoso
        } catch (Exception $e) {
            Log::error('Error enviando mensaje de WhatsApp: ' . $e->getMessage());
            return false; // Indica que el envío falló
        }
    }

    function sendWhatsAppRemember($phone, $name, $branch)
    {
        $twilioSid = env('TWILIO_SID');
        $twilioTemplateSid2 = env('TWILIO_TEMPLATE_SID2');
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
                    "from" => $twilioWhatsAppNumber, // Número de WhatsApp de Twilio
                    "template_sid" => $twilioTemplateSid2, // SID de la plantilla
                    "contentSid" => $twilioTemplateSid2, // SID de la plantilla
                    "contentVariables" => json_encode([
                        "1" => $name, // Nombre del cliente
                        "2" => $branch, // Nombre de la barbería
                        "3" => "https://reservasbh.simplifies.cl/", // Enlace de reserva
                    ]),
                ]
            );

            return true;
        } catch (Exception $e) {
            Log::error('Error sending WhatsApp message: ' . $e->getMessage());
            return false;
        }
    }
}
