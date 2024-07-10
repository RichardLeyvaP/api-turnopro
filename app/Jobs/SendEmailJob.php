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

    /*protected $recipient;
    protected $data_reservation;
    protected $start_time;
    protected $client_id;
    protected $branch_id;
    protected $type;
    protected $name_professional;
    protected $client_email; // Nuevo parámetro
    protected $branchBusinessName; // Nuevo parámetro
    protected $branchName; // Nuevo parámetro
    protected $boxData; // Nuevo parámetro
    protected $boxCashFound; // Nuevo parámetro
    protected $boxExistence; // Nuevo parámetro
    protected $boxExtraction; // Nuevo parámetro
    protected $totalTip; // Nuevo parámetro
    protected $totalProduct; // Nuevo parámetro
    protected $totalService; // Nuevo parámetro
    protected $totalCash; // Nuevo parámetro
    protected $totalCreditCard; // Nuevo parámetro
    protected $totalDebit; // Nuevo parámetro
    protected $totalTransfer; // Nuevo parámetro
    protected $totalOther; // Nuevo parámetro
    protected $totalMount; // Nuevo parámetro
    protected $client_name; 
    protected $code;
    protected $value_card;
    protected $expiration_date;
    protected $usser;
    protected $pass;

    public function __construct(
        $recipient,
        $data_reservation,
        $start_time,
        $client_id,
        $branch_id,
        $type,
        $name_professional,
        $client_email,
        $branchBusinessName,
        $branchName,
        $boxData,
        $boxCashFound,
        $boxExistence,
        $boxExtraction,
        $totalTip,
        $totalProduct,
        $totalService,
        $totalCash,
        $totalCreditCard,
        $totalDebit,
        $totalTransfer,
        $totalOther,
        $totalMount,
        $client_name, $code, $value_card,$expiration_date, //giftCard
        $usser, $pass
    ) {
        $this->recipient = $recipient;
        $this->data_reservation = $data_reservation;
        $this->start_time = $start_time;
        $this->client_id = $client_id;
        $this->branch_id = $branch_id;
        $this->type = $type;
        $this->name_professional = $name_professional;
        $this->client_email = $client_email; // Nuevo parámetro
        $this->branchBusinessName = $branchBusinessName; // Nuevo parámetro
        $this->branchName = $branchName; // Nuevo parámetro
        $this->boxData = $boxData; // Nuevo parámetro
        $this->boxCashFound = $boxCashFound; // Nuevo parámetro
        $this->boxExistence = $boxExistence; // Nuevo parámetro
        $this->boxExtraction = $boxExtraction; // Nuevo parámetro
        $this->totalTip = $totalTip; // Nuevo parámetro
        $this->totalProduct = $totalProduct; // Nuevo parámetro
        $this->totalService = $totalService; // Nuevo parámetro
        $this->totalCash = $totalCash; // Nuevo parámetro
        $this->totalCreditCard = $totalCreditCard; // Nuevo parámetro
        $this->totalDebit = $totalDebit; // Nuevo parámetro
        $this->totalTransfer = $totalTransfer; // Nuevo parámetro
        $this->totalOther = $totalOther; // Nuevo parámetro
        $this->totalMount = $totalMount; // Nuevo parámetro
        $this->client_name = $client_name;
        $this->code = $code;
        $this->value_card = $value_card;
        $this->expiration_date = $expiration_date;
        $this->usser = $usser;
        $this->pass = $pass;
    }*/
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