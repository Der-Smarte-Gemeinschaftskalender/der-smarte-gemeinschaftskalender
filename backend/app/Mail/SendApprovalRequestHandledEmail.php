<?php

namespace App\Mail;

use App\Models\ApprovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendApprovalRequestHandledEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $requestTypeName;
    public string $status;
    public ?string $comment;

    public function __construct(ApprovalRequest $approvalRequest)
    {
        $this->requestTypeName = $this->getRequestTypeName($approvalRequest->requestable_type, $approvalRequest->request_type);
        $this->status = $approvalRequest->status;
        $this->comment = $approvalRequest->admin_comment;
    }

    public function envelope(): Envelope
    {
        $statusText = $this->status === 'approved' ? 'genehmigt' : 'abgelehnt';
        return new Envelope(
            subject: 'Ihre Genehmigungsanfrage wurde ' . $statusText,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.approvalRequestHandledUserMail',
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function getRequestTypeName(string $requestableType, string $requestType): string
    {
        $typeMap = [
            'SingleEvent' => 'Einzeltermin',
            'SeriesEvent' => 'Serienterm',
            'CreatedEvent' => 'angelegter Termin',
            'UploadedEvent' => 'hochgeladene Termine',
            'ImportedEvent' => 'importierte Termine',
            'Organisation' => 'Organisation',
        ];

        $actionMap = [
            'store' => 'erstellen',
            'create' => 'erstellen',
            'update' => 'aktualisieren',
            'delete' => 'löschen',
            'updateGroup' => 'aktualisieren',
            'changeOrganisationStatus' => 'erstellen',
        ];

        $type = $typeMap[$requestableType] ?? $requestableType;
        $action = $actionMap[$requestType] ?? $requestType;

        return $type . ' ' . $action;
    }
}
