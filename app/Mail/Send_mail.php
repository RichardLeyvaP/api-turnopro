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
    
    // Propiedades de la clase
    public $logoUrl;
    public $client_name;
    public $name_professional;
    public $data;
    public $template;
    public $start_time;
    public $branch_name;
    public $file;

    // Propiedades adicionales correspondientes a la plantilla final
    public $branchBusinessName;
    public $branchName;
    public $boxData;
    public $boxCashFound;
    public $boxExistence;
    public $boxExtraction;
    public $totalTip;
    public $totalProduct;
    public $totalService;
    public $totalCash;
    public $totalCreditCard;
    public $totalDebit;
    public $totalTransfer;
    public $totalOther;
    public $totalMount;

    /**
     * Create a new message instance.
     */
    public function __construct($logoUrl, $client_name, $name_professional, $data, $template, $start_time, $branch_name, $file)
    {
        $this->logoUrl = $logoUrl;
        $this->client_name = $client_name;
        $this->name_professional = $name_professional ?? 'Profesional seleccionado';
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
            with: [
                'logoUrl' => $this->logoUrl,
                'client_name' => $this->client_name,
                'name_professional' => $this->name_professional,
                'data' => $this->data,
                'template' => $this->template,
                'start_time' => $this->start_time,
                'branch_name' => $this->branch_name,
                'file' => $this->file,

                // Propiedades adicionales
                'branchBusinessName' => $this->branchBusinessName,
                'branchName' => $this->branchName,
                'boxData' => $this->boxData,
                'boxCashFound' => $this->boxCashFound,
                'boxExistence' => $this->boxExistence,
                'boxExtraction' => $this->boxExtraction,
                'totalTip' => $this->totalTip,
                'totalProduct' => $this->totalProduct,
                'totalService' => $this->totalService,
                'totalCash' => $this->totalCash,
                'totalCreditCard' => $this->totalCreditCard,
                'totalDebit' => $this->totalDebit,
                'totalTransfer' => $this->totalTransfer,
                'totalOther' => $this->totalOther,
                'totalMount' => $this->totalMount,
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
        } else {
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

