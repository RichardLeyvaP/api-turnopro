<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Log;

class Send_mail extends Mailable
{
    use Queueable, SerializesModels;
    public $logoUrl;
    public $client_name;
    public $data;
    public $template;
    public $start_time;
    public $branch_name;
    public $file;

    /**
     * Create a new message instance.
     */
    public function __construct($logoUrl,$client_name,$data,$template,$start_time,$branch_name,$file)
    {
        $this->logoUrl = $logoUrl;
        $this->client_name = $client_name;
        $this->data = $data;
        $this->template = $template;
        $this->start_time = $start_time;
        $this->branch_name = $branch_name;       
        $this->file = $file ?? '';       
        
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.' . $this->template,
           // view: 'mails.send_mail',
            
           
            with:  [
                'logoUrl' => $this->logoUrl,
                'client_name' => $this->client_name,
                'data' => $this->data,
                'template' => $this->template,
                'start_time' => $this->start_time,
                'branch_name' => $this->branch_name,
                'file' => $this->file,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
  public function attachments(): array
{
   

// Obtén los últimos cuatro caracteres de la cadena
$extension = substr($this->file, -4);

// Verifica si los últimos cuatro caracteres son ".pdf"
if ($extension === '.pdf') {
   if (!empty($this->file)) {
    $filePath = storage_path('app/public/pdfs/' . $this->file);

    if (file_exists($filePath)) {
        return [
            Attachment::fromPath($filePath)
        ];
    } else {
        Log::error("El archivo $this->file no se encontró en la ubicación: $filePath");
    }
}
return [];
}
else{
    //DE ESTA FORMA ES GENERANDOLO AL MOMENTO Y SIN GUARDARLO
$attachments = [];

// Adjunta el PDF si está presente en $this->pdf
if (!empty($this->file)) {
    // Adjunta el PDF desde la variable $reporte
    $attachments[] = Attachment::fromData(fn () => $this->file, 'cierrecaja.pdf')
    ->withMime('application/pdf');
}

return $attachments;

}





}
}
