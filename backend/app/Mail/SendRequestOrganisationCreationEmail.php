<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendRequestOrganisationCreationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $requestOrganisationNamen;
    public string $urlRequestedOrganisations;

    /**
     * Create a new message instance.
     */
    public function __construct($requestOrganisationNamen)
    {
        $this->requestOrganisationNamen = $requestOrganisationNamen;
        $this->urlRequestedOrganisations = env('APP_URL') . 'app/admin/requested-organisations';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Neue Organisationsanfrage: "' . $this->requestOrganisationNamen . '"',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.adminRequestOrganisationCreationMail',
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
