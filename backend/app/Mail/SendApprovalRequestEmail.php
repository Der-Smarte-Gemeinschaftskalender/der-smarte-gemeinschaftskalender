<?php

namespace App\Mail;

use App\Models\ApprovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SendApprovalRequestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public ApprovalRequest $approvalRequest;
    public string $approvalUrl;

    public function __construct(ApprovalRequest $approvalRequest)
    {
        $this->approvalRequest = $approvalRequest;
        $this->approvalUrl = $approvalRequest->requestable_type === 'Organisation'
            ? env('APP_URL') . '/app/admin/organisations/requests/' . $approvalRequest->id
            : env('APP_URL') . '/app/admin/events/requests/' . $approvalRequest->id;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.subjects.adminApprovalRequest', [
                'requestTypeName' => $this->requestTypeName(),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.adminApprovalRequestMail',
            with: [
                'approvalRequest' => $this->approvalRequest,
                'requestTypeName' => $this->requestTypeName(),
                'approvalUrl' => $this->approvalUrl,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function requestTypeName(): string
    {
        return Str::ucfirst($this->approvalRequest->getRequestTypeLabel());
    }
}
