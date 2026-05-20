<?php

namespace App\Mail;

use App\Models\ApprovalRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\EmailTemplateController;
use App\Enums\EmailTemplateType;

class SendApprovalRequestCreatedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $requestTypeName;
    public int $requestId;
    public string $mailBody;
    public string $mailSubject;

    public function __construct(ApprovalRequest $approvalRequest)
    {
        $templateController = new EmailTemplateController();
        $this->mailBody = $templateController->getEventDefaultTemplateBody(EmailTemplateType::APPROVAL_REQUEST_SENT);
        $this->mailSubject = $templateController->getEventDefaultTemplateSubject(EmailTemplateType::APPROVAL_REQUEST_SENT);

        $this->requestTypeName = $this->getRequestTypeName($approvalRequest->requestable_type, $approvalRequest->request_type);
        $this->requestId = $approvalRequest->id;
        $variables = [
            ':appName' => config('app.name', 'Der Smarte Gemeinschaftskalender'),
            ':requestTypeName' => $this->requestTypeName,
        ];
        $this->mailBody = str_replace(array_keys($variables), array_values($variables), $this->mailBody);
        $this->mailSubject = str_replace(array_keys($variables), array_values($variables), $this->mailSubject);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.approvalRequestCreatedUserMail',
            with: [
                'mailBody' => $this->mailBody,
                'requestId' => $this->requestId,
                'requestTypeName' => $this->requestTypeName,
            ]
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
