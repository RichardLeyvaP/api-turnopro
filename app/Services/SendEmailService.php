<?php

namespace App\Services;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\Send_mail;
use App\Models\Branch;
use App\Models\Client;




class SendEmailService {

    public function confirmReservation($data_reservation,$start_time,$client_id,$branch_id,$type)
    {
        $logoUrl = 'https://i.pinimg.com/originals/6a/8a/39/6a8a3944621422753697fc54d7a5d6c1.jpg'; // Reemplaza esto con la lógica para obtener la URL dinámicamente
        $template = 'send_mail_reservation';
        $client = Client::where('id', $client_id)->first();
        $branch = Branch::where('id', $branch_id)->first();

       

        if ($client) {
            $client_email = $client->email;
            $client_name = $client->name.' '.$client->surname;
        } else {
            // El cliente con id 5 no fue encontrado
            $client_email = null; // o manejar de acuerdo a tus necesidades
        }
        if ($branch) {
            $branch_name = $branch->name;
        } else {
            // El cliente con id 5 no fue encontrado
            $branch_name = null; // o manejar de acuerdo a tus necesidades
        }
              Log::info($client_email);
              $mail = new Send_mail($logoUrl, $client_name,$data_reservation,$template,$start_time,$branch_name,$type);
              $this->sendEmail($client_email,$mail);


    }

    //este configurarlo para el envio de cierre de caja si hiciera falta
    public function emailBoxClosure($client_email,$type)
    {
        $logoUrl = 'https://i.pinimg.com/originals/6a/8a/39/6a8a3944621422753697fc54d7a5d6c1.jpg'; // Reemplaza esto con la lógica para obtener la URL dinámicamente
        $template = 'send_mail_reservation';
       

       

              Log::info($client_email);
              $mail = new Send_mail($logoUrl, '$client_name','$data_reservation',$template,'$start_time','$branch_name',$type);
              $this->sendEmail($client_email,$mail);


    }








    

    public function sendEmail($client_email,$mail){
          Mail::to($client_email)
        ->send($mail->from('reservas@simplifies.cl', 'Simplifies')
                    ->subject('Confirmación de Reserva en Simplifies'));       
      
        Log::info( "Enviado send_email");
    }

}