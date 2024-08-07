<?php

namespace App\Services;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\Send_mail;
use App\Models\Branch;
use App\Models\Client;




class SendEmailService {
   

    public function confirmReservation($data_reservation,$start_time,$client_id,$branch_id,$type,$name_professional,$recipient,$id_reservation,$code_reserva)
    {
        $logoUrl = 'https://api2.simplifies.cl/api/images/image/imagen_reservas.png'; // Reemplaza esto con la lógica para obtener la URL dinámicamente
        $template = 'send_mail_reservation';        
        $client = Client::where('id', $client_id)->first();
        $branch = Branch::where('id', $branch_id)->first();
        $recipient = $recipient;

       

        if ($client) {
            $client_email = $client->email;
            $client_name = $client->name.' '.$client->surname;
        } else {
            // El cliente con id 5 no fue encontrado
            $client_email = null; // o manejar de acuerdo a tus necesidades
        }
        if ($branch) {
            $branch_name = $branch->name;
            $branch_address = $branch->address;
        } else {
            // El cliente con id 5 no fue encontrado
            $branch_name = null; // o manejar de acuerdo a tus necesidades
            $branch_address = null;
        }
              Log::info($client_email);
              $mail = new Send_mail($logoUrl, $client_name,$name_professional,$data_reservation,$template,$start_time,$branch_name,$type,'');
              $mail->id_reservation = $id_reservation;
              $mail->branch_address = $branch_address;
              $mail->code_reserva = $code_reserva;
              $this->sendEmail($client_email,$mail,'Confirmación de Reserva en Simplifies');


    }

    public function rememberReservation($data_reservation,$start_time,$client_id,$branch_id,$type,$name_professional,$recipient,$id_reservation,$code_reserva)
    {
        $logoUrl = 'https://api2.simplifies.cl/api/images/image/confirme.png'; // Reemplaza esto con la lógica para obtener la URL dinámicamente
        $template = 'send_mail_remember';        
        $client = Client::where('id', $client_id)->first();
        $branch = Branch::where('id', $branch_id)->first();
        $recipient = $recipient;

       

        if ($client) {
            $client_email = $client->email;
            $client_name = $client->name.' '.$client->surname;
        } else {
            // El cliente con id 5 no fue encontrado
            $client_email = null; // o manejar de acuerdo a tus necesidades
        }
        if ($branch) {
            $branch_name = $branch->name;
            $branch_address = $branch->address;
        } else {
            // El cliente con id 5 no fue encontrado
            $branch_name = null; // o manejar de acuerdo a tus necesidades
            $branch_address = null;
        }
              Log::info($client_email);
              $mail = new Send_mail($logoUrl, $client_name,$name_professional,$data_reservation,$template,$start_time,$branch_name,$type,'');
              $mail->id_reservation = $id_reservation;
              $mail->branch_address = $branch_address;
              $mail->code_reserva = $code_reserva;
              $this->sendEmail($client_email,$mail,'Confirmación de Reserva en Simplifies');


    }

    //este configurarlo para el envio de cierre de caja si hiciera falta
    public function emailRecuperarPass($client_email,$client_name, $usser, $pass)
    {
        $logoUrl = 'https://i.pinimg.com/originals/6a/8a/39/6a8a3944621422753697fc54d7a5d6c1.jpg'; // Reemplaza esto con la lógica para obtener la URL dinámicamente
        $template = 'restaurar_pass';
        Log::info('estoy en emailRecuperarPass($client_email,$client_name, $usser, $pass)');

              Log::info($client_email);
              $mail = new Send_mail($logoUrl, $client_name,'','$data_reservation',$template,'$start_time','$branch_name','');            
              $mail->usser = $usser;
              $mail->pass = $pass;
              Log::info('estoy en emailRecuperarPass($client_email,$client_name, $usser, $pass)-222');
              
              $this->sendEmail($client_email,$mail,'Restaurar Contraseña');

    }






    //este configurarlo para el envio de cierre de caja si hiciera falta
    public function emailBoxClosure($client_email, $type,$branchBusinessName, $branchName, $boxData, $boxCashFound, $boxExistence, $boxExtraction, $totalTip, $totalProduct, $totalService, $totalCash, $totalCreditCard, $totalDebit, $totalTransfer, $totalOther, $totalMount, $totalGiftcard, $totalBonus)
    {
        $logoUrl = 'https://i.pinimg.com/originals/6a/8a/39/6a8a3944621422753697fc54d7a5d6c1.jpg'; // Reemplaza esto con la lógica para obtener la URL dinámicamente
        $template = 'cierre_de_caja';
       

              Log::info($client_email);
              $mail = new Send_mail($logoUrl, '$client_name','','$data_reservation',$template,'$start_time','$branch_name',$type);
              $mail->branchBusinessName = $branchBusinessName;
              $mail->branchName = $branchName;
              $mail->boxData = $boxData;
              $mail->boxCashFound = $boxCashFound;
              $mail->boxExistence = $boxExistence;
              $mail->boxExtraction = $boxExtraction;
              $mail->totalTip = $totalTip;
              $mail->totalProduct = $totalProduct;
              $mail->totalService = $totalService;
              $mail->totalCash = $totalCash;
              $mail->totalCreditCard = $totalCreditCard;
              $mail->totalDebit = $totalDebit;
              $mail->totalTransfer = $totalTransfer;
              $mail->totalOther = $totalOther;
              $mail->totalMount = $totalMount;
              $mail->totalGiftcard = $totalGiftcard;
              $mail->totalBonus = $totalBonus;
              $this->sendEmail($client_email,$mail,'Cierre de Caja');

    }


    //este configurarlo para el envio de cierre de caja del mes
    public function emailBoxClosureMonthly(
        $client_email,
        $type,
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
        $totalGiftcard,
        $ingreso,
        $gasto,
        $utilidad,
        $professionalBonus // Este es un array de objetos
    ) {
        $logoUrl = 'https://i.pinimg.com/originals/6a/8a/39/6a8a3944621422753697fc54d7a5d6c1.jpg'; // Reemplaza esto con la lógica para obtener la URL dinámicamente
        $template = 'cierre_de_caja_mensual'; // Asegúrate de que este es el nombre correcto de tu plantilla de correo
    
        Log::info($client_email);
        $mail = new Send_mail($logoUrl, '$client_name', '', '$data_reservation', $template, '$start_time', '$branch_name', $type);
        $mail->branchBusinessName = $branchBusinessName;
        $mail->branchName = $branchName;
        $mail->boxData = $boxData;
        $mail->boxCashFound = $boxCashFound;
        $mail->boxExistence = $boxExistence;
        $mail->boxExtraction = $boxExtraction;
        $mail->totalTip = $totalTip;
        $mail->totalProduct = $totalProduct;
        $mail->totalService = $totalService;
        $mail->totalCash = $totalCash;
        $mail->totalCreditCard = $totalCreditCard;
        $mail->totalDebit = $totalDebit;
        $mail->totalTransfer = $totalTransfer;
        $mail->totalOther = $totalOther;
        $mail->totalMount = $totalMount;
        $mail->totalGiftcard = $totalGiftcard;
        $mail->ingreso = $ingreso;
        $mail->gasto = $gasto;
        $mail->utilidad = $utilidad;
        $mail->professionalBonus = $professionalBonus; // Asegúrate de que tu plantilla maneja este array correctamente
    
        $this->sendEmail($client_email, $mail, 'Cierre de Caja Mensual');
    }
    

    //este configurarlo para el envio de cierre de caja si hiciera falta
    public function emailGitCard($client_email,$client_name, $code, $value_card,$expiration_date, $image_cardgift)
    {
        $logoUrl = 'https://i.pinimg.com/originals/6a/8a/39/6a8a3944621422753697fc54d7a5d6c1.jpg'; // Reemplaza esto con la lógica para obtener la URL dinámicamente
        $template = 'targeta_regalo';
       

              Log::info($client_email);
              $mail = new Send_mail($logoUrl, $client_name,'','$data_reservation',$template,'$start_time','$branch_name','');
             
              $mail->client_name = $client_name;
              $mail->code = $code;
              $mail->value_card = $value_card;
              $mail->expiration_date = $expiration_date;
              $mail->image_cardgift = $image_cardgift;
              $this->sendEmail($client_email,$mail,'Asignación de Targeta de Regalo');

    }

    
    public function sendEmail($client_email,$mail,$subject){
          Mail::to($client_email)
        ->send($mail->from('reservas@simplifies.cl', 'Simplifies')
                    ->subject($subject));       
      
        Log::info( "Enviado send_email");
    }

}