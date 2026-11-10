<?php

namespace App\Mail;

use App\Enums\EmailTemplateType;
use App\Mail\Concerns\RendersEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendConfirmEmail extends Mailable
{
    use Queueable, SerializesModels, RendersEmailTemplate;

    public string $verificationToken;

    /**
     * Create a new message instance.
     */
    public function __construct($verificationToken)
    {
        $this->verificationToken = $verificationToken;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->renderEmailTemplate(EmailTemplateType::VALIDATION)['subject'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.confirmEmailMail',
            with: [
                'mailBody' => $this->renderEmailTemplate(EmailTemplateType::VALIDATION)['body'],
                'verificationToken' => $this->verificationToken,
            ],
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
