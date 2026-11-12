<?php

namespace App\Mail;

use App\Enums\EmailTemplateType;
use App\Mail\Concerns\RendersEmailTemplate;
use App\Models\ApprovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendApprovalRequestCreatedEmail extends Mailable
{
    use Queueable, SerializesModels, RendersEmailTemplate;

    public ApprovalRequest $approvalRequest;

    public function __construct(ApprovalRequest $approvalRequest)
    {
        $this->approvalRequest = $approvalRequest;
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
            view: 'emails.approvalRequestCreatedUserMail',
            with: [
                'mailBody' => $this->renderTemplate()['body'],
                'requestId' => $this->approvalRequest->id,
                'requestTypeName' => $this->approvalRequest->getRequestTypeLabel(),
            ]
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
        return $this->renderEmailTemplate(EmailTemplateType::APPROVAL_REQUEST_SENT, [
            ':requestTypeName' => $this->approvalRequest->getRequestTypeLabel(),
        ]);
    }
}
