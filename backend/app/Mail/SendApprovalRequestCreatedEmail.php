<?php

namespace App\Mail;

use App\Models\ApprovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendApprovalRequestCreatedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $requestTypeName;
    public int $requestId;

    public function __construct(ApprovalRequest $approvalRequest)
    {
        $this->requestTypeName = $this->getRequestTypeName($approvalRequest->requestable_type, $approvalRequest->request_type);
        $this->requestId = $approvalRequest->id;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ihre Genehmigungsanfrage wurde eingereicht: ' . $this->requestTypeName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.approvalRequestCreatedUserMail',
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
