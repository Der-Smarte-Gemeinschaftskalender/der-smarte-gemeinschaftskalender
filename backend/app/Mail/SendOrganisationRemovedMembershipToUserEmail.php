<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOrganisationRemovedMembershipToUserEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $organisationName;
    public string $urlMyOrganisations;

    /**
     * Create a new message instance.
     */
    public function __construct($organisationName)
    {
        $this->organisationName = $organisationName;
        $this->urlMyOrganisations = env('APP_URL') . 'app/organisation/my-organisations';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Entfernung aus Organisation: "' . $this->organisationName . '"',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.organisationRemovedMembershipToUserMail',
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
