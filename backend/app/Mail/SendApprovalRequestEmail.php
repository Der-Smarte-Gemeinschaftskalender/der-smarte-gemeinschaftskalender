<?php

namespace App\Mail;

use App\Models\ApprovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendApprovalRequestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public ApprovalRequest $approvalRequest;
    public string $requestTypeName;
    public string $approvalUrl;

    public function __construct(ApprovalRequest $approvalRequest)
    {
        $this->approvalRequest = $approvalRequest;
        $this->requestTypeName = $this->getRequestTypeName($approvalRequest->requestable_type, $approvalRequest->request_type);
        $this->approvalUrl = $approvalRequest->requestable_type === 'Organisation'
            ? env('APP_URL') . '/app/admin/organisations/requests/' . $approvalRequest->id
            : env('APP_URL') . '/app/admin/events/requests/' . $approvalRequest->id;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Neue Genehmigungsanfrage: ' . $this->requestTypeName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.adminApprovalRequestMail',
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
            'CreatedEvent' => 'Angelegter Termin',
            'UploadedEvent' => 'Hochgeladene Termine',
            'ImportedEvent' => 'Importierte Termine',
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

        return ucfirst($type) . ' ' . $action;
    }
}
