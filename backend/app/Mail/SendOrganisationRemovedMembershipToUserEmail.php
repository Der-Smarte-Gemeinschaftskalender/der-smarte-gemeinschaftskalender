<?php

namespace App\Mail;

use App\Enums\EmailTemplateType;
use App\Http\Controllers\EmailTemplateController;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOrganisationRemovedMembershipToUserEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $urlMyOrganisations;
    public string $mailBody;
    public string $mailSubject;

    /**
     * Create a new message instance.
     */
    public function __construct(string $organisationName)
    {
        $templateController = new EmailTemplateController();
        $this->mailBody = $templateController->getEventDefaultTemplateBody(EmailTemplateType::ORGANISATION_REMOVAL);
        $this->mailSubject = $templateController->getEventDefaultTemplateSubject(EmailTemplateType::ORGANISATION_REMOVAL);

        // Replace variables
        $this->urlMyOrganisations = env('APP_URL') . '/app/organisation/my-organisations';
        $variables = [
            ':appName' => config('app.name'),
            ':organisationName' => $organisationName,
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
            view: 'emails.organisationRemovedMembershipToUserMail',
            with: [
                'mailBody' => $this->mailBody,
                'urlMyOrganisations' => $this->urlMyOrganisations,
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
