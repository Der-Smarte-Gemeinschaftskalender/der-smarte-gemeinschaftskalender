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
use Illuminate\Support\Facades\Lang;

class SendApprovalRequestHandledEmail extends Mailable
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
            view: 'emails.approvalRequestHandledUserMail',
            with: [
                'mailBody' => $this->renderTemplate()['body'],
                'status' => $this->approvalRequest->status,
                'comment' => $this->approvalRequest->admin_comment,
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
        $statusKey = 'emails.handledStatus.' . $this->approvalRequest->status;

        return $this->renderEmailTemplate(EmailTemplateType::APPROVAL_REQUEST_HANDLED, [
            ':requestTypeName' => $this->approvalRequest->getRequestTypeLabel(),
            ':handledStatus' => Lang::has($statusKey) ? __($statusKey) : $this->approvalRequest->status,
        ]);
    }
}
