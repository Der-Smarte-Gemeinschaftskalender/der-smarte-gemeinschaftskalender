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

class SendApprovalRequestHandledEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $mailBody;
    public string $mailSubject;
    public string $status;
    public ?string $comment;

    public function __construct(ApprovalRequest $approvalRequest)
    {
        
        $templateController = new EmailTemplateController();
        $this->mailBody = $templateController->getEventDefaultTemplateBody(EmailTemplateType::APPROVAL_REQUEST_HANDLED);
        $this->mailSubject = $templateController->getEventDefaultTemplateSubject(EmailTemplateType::APPROVAL_REQUEST_HANDLED);

        $this->comment = $approvalRequest->admin_comment;
        $this->status = $approvalRequest->status;

        $requestTypeName = $this->getRequestTypeName($approvalRequest->requestable_type, $approvalRequest->request_type);
        $handledStatus = $this->status === 'approved' ? 'genehmigt' : 'abgelehnt';
        $variables = [
            ':appName' => config('app.name', 'Der Smarte Gemeinschaftskalender'),
            ':requestTypeName' => $requestTypeName,
            ':handledStatus' => $handledStatus,
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
            view: 'emails.approvalRequestHandledUserMail',
            with: [
                'mailBody' => $this->mailBody,
                'status' => $this->status,
                'comment' => $this->comment,
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
