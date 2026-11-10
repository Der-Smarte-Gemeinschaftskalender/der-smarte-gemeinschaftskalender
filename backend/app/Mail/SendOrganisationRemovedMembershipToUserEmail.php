<?php

namespace App\Mail;

use App\Enums\EmailTemplateType;
use App\Mail\Concerns\RendersEmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOrganisationRemovedMembershipToUserEmail extends Mailable
{
    use Queueable, SerializesModels, RendersEmailTemplate;

    public string $organisationName;
    public string $urlMyOrganisations;

    public function __construct(?string $organisationName)
    {
        $this->organisationName = (string) $organisationName;
        $this->urlMyOrganisations = env('APP_URL') . '/app/organisation/my-organisations';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->renderTemplate()['subject'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.organisationRemovedMembershipToUserMail',
            with: [
                'mailBody' => $this->renderTemplate()['body'],
                'urlMyOrganisations' => $this->urlMyOrganisations,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    /**
     * @return array{subject: string, body: string}
     */
    private function renderTemplate(): array
    {
        return $this->renderEmailTemplate(EmailTemplateType::ORGANISATION_REMOVAL, [
            ':organisationName' => $this->organisationName,
        ]);
    }
}
