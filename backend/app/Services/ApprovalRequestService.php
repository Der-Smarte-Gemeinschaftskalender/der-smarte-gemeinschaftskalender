<?php

namespace App\Services;

use App\Mail\SendApprovalRequestEmail;
use App\Mail\SendApprovalRequestCreatedEmail;
use App\Models\ApprovalRequest;
use App\Models\SingleEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Log;
use Throwable;

class ApprovalRequestService
{
    /**
     * Map of model classes to their corresponding controller classes and event keys
     */
    private const EVENT_CONTROLLER_MAP = [
        'CreatedEvent',
        'SingleEvent', 
        //'SeriesEvent', 
        //'UploadedEvent',
        //'ImportedEvent',
        'Organisation'
    ];

    /**
     * Create an approval request for any event type and action
     * 
     * @param Request $request The HTTP request
     * @param string $eventType The event model class (e.g., SingleEvent::class)
     * @param string $actionType The action type (create, update, delete, etc.)
     * @param int|null $requestableId The ID of the resource being updated/deleted (optional)
     * @return array ['success' => bool, 'approval_request_id' => ?int, 'error' => ?string]
     */
    public function createApprovalRequest(Request $request, string $eventType, string $actionType = 'store', ?int $requestableId = null): array
    {
        try {
            if (!in_array($eventType, self::EVENT_CONTROLLER_MAP)) {
                return [
                    'success' => false,
                    'error' => 'Unbekannter Ereignistyp für Genehmigungsanforderung: ' . $eventType
                ];
            }

            if ($eventType === 'Organisation') {
                $payload = $request->except(['avatar.media.file']);
            } else {
                $payload = $request->except(['mobilizon_fields.picture.media.file']);
            }

            // Check for existing pending approval requests if requestableId is provided
            if ($requestableId && $this->hasPendingApprovalRequests($eventType, $actionType, $requestableId, $payload)) {
                return [
                    'success' => false,
                    'error' => 'Es gibt bereits eine ausstehende Anfrage für diese' . ($eventType === 'Organisation' ? ' Organisation' : 'n Termin') . '.'
                ];
            }

            $fileKey = $eventType === 'Organisation' ? 'avatar.media.file' : 'mobilizon_fields.picture.media.file';
            if ($request->hasFile($fileKey)) {
                $file = $request->file($fileKey);
                $filename = 'approval_' . time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('approval_requests', $filename, 'local');
                $payload['_file_path'] = $path;
                $payload['_file_original_name'] = $file->getClientOriginalName();
            }

            $approvalRequest = ApprovalRequest::create([
                'requestable_type' => $eventType,
                'requestable_id' => $requestableId,
                'request_type' => $actionType,
                'payload' => $payload,
                'status' => 'pending',
                'created_by_user_id' => $request->user()->id,
            ]);

            // Send email to all admin users
            try {
                $adminUsers = User::where('type', 'admin')->get();
                foreach ($adminUsers as $adminUser) {
                    Mail::to($adminUser->email)->send(new SendApprovalRequestEmail($approvalRequest));
                }
            } catch (Throwable $e) {
                Log::error('Error sending approval request email: ' . $e->getMessage());
            }

            // Send confirmation email to the user
            try {
                Mail::to($request->user()->email)->send(new SendApprovalRequestCreatedEmail($approvalRequest));
            } catch (Throwable $e) {
                Log::error('Error sending approval request created email to user: ' . $e->getMessage());
            }

            return [
                'success' => true,
                'approval_request_id' => $approvalRequest->id,
                'message' => 'Approval requested for ' . $eventType . ' ' . $actionType
            ];
        } catch (Throwable $e) {
            Log::error("Error creating approval request: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Fehler beim Erstellen der Genehmigungsanforderung.',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if there are pending approval requests for a given event
     * @param string $eventType The event model class (e.g., SingleEvent::class)
     * @param int $eventId The ID of the event
     * @param array $payload The payload data to check against (only for Organisation creation cases)
     * 
     * @return bool True if there are pending requests, false otherwise
     */

    private function hasPendingApprovalRequests(string $eventType, string $actionType, int $eventId, array $payload): bool
    {
        
        $existsByRequestableId = ApprovalRequest::where('requestable_type', $eventType)
            ->where('requestable_id', $eventId)
            ->where('requestable_type', $eventType)
            ->where('status', 'pending')
            ->exists();

        // In case of Organisation creation, also check by preferredUsername in payload 
        // because requestable_id is always unique -> OrganisationStatus->id.
        return $existsByRequestableId || ($eventType === 'Organisation' && $actionType === 'changeOrganisationStatus'
            ? ApprovalRequest::where('requestable_type', $eventType)
                    ->where('payload->preferredUsername', $payload['preferredUsername'])
                    ->where('status', 'pending')
                    ->exists()
            : false);
    }
}
