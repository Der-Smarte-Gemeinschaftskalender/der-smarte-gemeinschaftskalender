<?php

namespace App\Mail;

use App;
use Carbon\Carbon;
use Hash;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\EmailTemplateController;
use App\Enums\EmailTemplateType;

class SendConfirmEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $verificationToken;
    public string $mailBody;
    public string $mailSubject;

    /**
     * Create a new message instance.
     */
    public function __construct($verificationToken)
    {
        $this->verificationToken = $verificationToken;

        $templateController = new EmailTemplateController();
        $this->mailBody = $templateController->getEventDefaultTemplateBody(EmailTemplateType::VALIDATION);
        $this->mailSubject = $templateController->getEventDefaultTemplateSubject(EmailTemplateType::VALIDATION);

        $variables = [
            ':appName' => config('app.name', 'Der Smarte Gemeinschaftskalender'),
        ];
        $this->mailBody = str_replace(array_keys($variables), array_values($variables), $this->mailBody);
        $this->mailSubject = str_replace(array_keys($variables), array_values($variables), $this->mailSubject);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
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
                'mailBody' => $this->mailBody,
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
