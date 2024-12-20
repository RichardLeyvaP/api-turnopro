<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\SendEmailService; // Asegúrate de importar el servicio

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function handle(SendEmailService $emailService)
    {
        if (isset($this->data['confirm_reservation'])) {
            $emailService->confirmReservation(
                $this->data['data_reservation'],
                $this->data['start_time'],
                $this->data['client_id'],
                $this->data['branch_id'],
                $this->data['type'],
                $this->data['name_professional'],
                $this->data['recipient'],
                $this->data['id_reservation'],
                $this->data['code_reserva'],
            );
        }
        if (isset($this->data['remember_reservation'])) {
            $emailService->rememberReservation(
                $this->data['data_reservation'],
                $this->data['start_time'],
                $this->data['client_id'],
                $this->data['branch_id'],
                $this->data['type'],
                $this->data['name_professional'],
                $this->data['recipient'],
                $this->data['id_reservation'],
                $this->data['code_reserva']
            );
        }
        if (isset($this->data['email_box_closure'])) {
            $emailService->emailBoxClosure(
                $this->data['client_email'],
                $this->data['type'],
                $this->data['branchBusinessName'],
                $this->data['branchName'],
                $this->data['boxData'],
                $this->data['boxCashFound'],
                $this->data['boxExistence'],
                $this->data['boxExtraction'],
                $this->data['totalTip'],
                $this->data['totalProduct'],
                $this->data['totalService'],
                $this->data['totalCash'],
                $this->data['totalCreditCard'],
                $this->data['totalDebit'],
                $this->data['totalTransfer'],
                $this->data['totalOther'],
                $this->data['totalMount'],                
                $this->data['totalCardGif'],
                $this->data['totalBonus']
            );
        }
        if (isset($this->data['email_box_closure_monthly'])) {
            $emailService->emailBoxClosureMonthly(
                $this->data['client_email'],
                $this->data['type'],
                $this->data['branchBusinessName'],
                $this->data['branchName'],
                $this->data['boxData'],
                $this->data['boxCashFound'],
                $this->data['boxExistence'],
                $this->data['boxExtraction'],
                $this->data['totalTip'],
                $this->data['totalProduct'],
                $this->data['totalService'],
                $this->data['totalCash'],
                $this->data['totalCreditCard'],
                $this->data['totalDebit'],
                $this->data['totalTransfer'],
                $this->data['totalOther'],
                $this->data['totalMount'],
                $this->data['totalCardGif'],
                $this->data['ingreso'],
                $this->data['gasto'],
                $this->data['utilidad'],
                $this->data['professionalBonus'] // Agrega este campo
            );
        }
        if (isset($this->data['send_gift_card'])) {
            $emailService->emailGitCard(
                $this->data['client_email'],
                $this->data['client_name'],
                $this->data['code'],
                $this->data['value_card'],
                $this->data['expiration_date'],
                $this->data['image_cardgift']
            );
        }
    
        if (isset($this->data['recover_password'])) {
            $emailService->emailRecuperarPass(
                $this->data['client_email'],
                $this->data['client_name'],
                $this->data['usser'],
                $this->data['pass']
            );
        }
        // Llamada a la función de envío de correo desde el servicio
        /*$emailService->confirmReservation($this->data_reservation, $this->start_time, $this->client_id, $this->branch_id, $this->type, $this->name_professional, $this->recipient);
        /*$emailService->emailBoxClosure($this->client_email, $this->type,$this->branchBusinessName, $this->branchName, $this->boxData, $this->boxCashFound, $this->boxExistence, $this->boxExtraction, $this->totalTip, $this->totalProduct, $this->totalService, $this->totalCash, $this->totalCreditCard, $this->totalDebit, $this->totalTransfer, $this->totalOther, $this->totalMount);
        $this->$emailService->emailGitCard($this->client_email,$this->client_name, $this->code, $this->value_card, $this->expiration_date);
        $this->$emailService->emailRecuperarPass($this->client_email,$this->client_name, $this->usser, $this->pass);*/
    }
}