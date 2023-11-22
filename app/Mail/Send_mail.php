<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Send_mail extends Mailable
{
    use Queueable, SerializesModels;
    public $logoUrl;
    public $icon;
    public $template;

    /**
     * Create a new message instance.
     */
    public function __construct($logoUrl,$icon,$template)
    {
        $this->logoUrl = $logoUrl;
        $this->icon = $icon;
        $this->template = $template;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Send Mail',
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
                'otrosDatos' => $this->icon,
                'template' => $this->template,
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
        return [];
    }
}
