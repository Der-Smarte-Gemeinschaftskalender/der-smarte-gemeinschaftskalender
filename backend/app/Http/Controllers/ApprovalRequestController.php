<?php

namespace App\Http\Controllers;

use App\Mail\SendApprovalRequestEmail;
use App\Mail\SendApprovalRequestHandledEmail;
use App\Models\ApprovalRequest;
use App\Models\CreatedEvent;
use App\Models\OrganisationStatus;
use App\Models\Mobilizon;
use App\Models\MobilizonTag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Http;
use Log;
use Throwable;

class ApprovalRequestController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('is_admin')
        ];
    }

    public function indexEvents(Request $request): JsonResponse
    {
        $requests = ApprovalRequest::with(['createdBy'])
            ->where('status', 'pending')
            ->whereIn('requestable_type', ['SingleEvent', 'CreatedEvent'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }


    public function indexOrganisations(Request $request): JsonResponse
    {   
        $requests = ApprovalRequest::with(['createdBy'])
            ->where('status', 'pending')
            ->where('requestable_type', 'Organisation')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    public function show(ApprovalRequest $approvalRequest): JsonResponse
    {
        return response()->json($approvalRequest->load(['createdBy', 'handledBy']));
    }

    public function serveFile(ApprovalRequest $approvalRequest): mixed
    {
        $filePath = $approvalRequest->payload['_file_path'] ?? null;

        if (!$filePath || !Storage::exists($filePath)) {
            abort(404, 'File not found');
        }

        return response()->file(Storage::path($filePath));
    }

    public function serveExternalFile(ApprovalRequest $approvalRequest): mixed
    {
        $payload = $approvalRequest->payload;

        $externalFileUrl = $payload['mobilizon_fields']['picture']['url'] ?? null;

        if (!$externalFileUrl) {
            return response()->json(['error' => 'Datei-URL nicht gefunden'], 404);
        }


        try {
            $imageResponse = Http::retry(3, 200)
                ->timeout(5)
                ->get($externalFileUrl);

            if ($imageResponse->failed()) {
                return response()->json(['error' => 'Fehler beim Abrufen der externen Datei'], 404);
            }

            $contentType = $imageResponse->header('Content-Type') ?? 'image/jpeg';
            $fileName = basename(parse_url($externalFileUrl, PHP_URL_PATH)) ?: 'event_image.jpg';


            return response()->stream(function () use ($imageResponse) {
                    echo $imageResponse->body();
                }, 200, [
                    'Content-Type' => $contentType,
                    'Content-Disposition' => 'inline; filename="' . $fileName . '"',
                ]);
        } catch (\Exception $e) {
            Log::error('Error fetching external file: ' . $e->getMessage());
            return response()->json(['error' => 'Fehler beim Abrufen der externen Datei'], 500);
        }
    }

    public function approve(ApprovalRequest $approvalRequest, Request $request, array $additionalPayload = []): JsonResponse
    {
        if ($approvalRequest->status !== 'pending') {
            return response()->json(['error' => 'Anfrage wurde bereits bearbeitet'], 400);
        }

        try {
            
            // Special handling for organisation status change requests
            $additionalPayload = [];
            if ($approvalRequest->requestable_type === 'Organisation' 
                && $approvalRequest->request_type === 'changeOrganisationStatus') {
                
                $additionalPayload = array_merge($additionalPayload, [
                    'status' => 'ACTIVE',
                ]);
            }

            $result = $this->executeApprovalRequest($approvalRequest, $additionalPayload);
            
            if ($result['success']) {
                $approvalRequest->update([
                    'status' => 'approved',
                    'handled_by_user_id' => auth()->id(),
                    'admin_comment' => $request->input('comment'),
                ]);

                // Send email to user about approval
                try {
                    Mail::to($approvalRequest->createdBy->email)->send(new SendApprovalRequestHandledEmail($approvalRequest));
                } catch (Throwable $e) {
                    Log::error('Error sending approval handled email: ' . $e->getMessage());
                }

                return response()->json([
                    'message' => 'Anfrage erfolgreich genehmigt',
                    'approval_request' => $approvalRequest,
                ]);
            } else {
                return response()->json([
                    'error' => 'Fehler bei der Genehmigung der Anfrage',
                    'details' => $result['error']
                ], 500);
            }
        } catch (Throwable $e) {
            Log::error("Error approving request: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to approve request',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function reject(ApprovalRequest $approvalRequest, Request $request): JsonResponse
    {
        if ($approvalRequest->status !== 'pending') {
            return response()->json(['error' => 'Anfrage wurde bereits bearbeitet'], 400);
        }

        try {
            $approvalRequest->update([
                'status' => 'rejected',
                'handled_by_user_id' => auth()->id(),
                'admin_comment' => $request->input('comment'),
            ]);

            // Special handling for organisation status change requests
            if ($approvalRequest->requestable_type === 'Organisation' 
                && $approvalRequest->request_type === 'changeOrganisationStatus') {
                
                $this->executeApprovalRequest($approvalRequest, [ 'status' => 'REQUEST_DENIED' ]);
            }

            // Send email to user about rejection
            try {
                Mail::to($approvalRequest->createdBy->email)->send(new SendApprovalRequestHandledEmail($approvalRequest));
            } catch (Throwable $e) {
                Log::error('Error sending rejection email: ' . $e->getMessage());
            }

            // Clean up stored file if present
            $this->clearUpStoredFile($approvalRequest);

            return response()->json([
                'message' => 'Anfrage erfolgreich abgelehnt',
                'approval_request' => $approvalRequest,
            ]);
        } catch (Throwable $e) {
            Log::error("Error rejecting request: " . $e->getMessage());
            return response()->json([
                'error' => 'Fehler beim Ablehnen der Anfrage',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    private function executeApprovalRequest(ApprovalRequest $approvalRequest, array $additionalPayload = []): array
    {
        try {
            $payload = $approvalRequest->payload;

            $controller = $this->getEventController($approvalRequest->requestable_type);
            $method = $approvalRequest->request_type;

            if ($controller === null || !method_exists($controller, $method)) {
                return ['success' => false, 'error' => 'Invalid requestable type or method'];
            }

            // Reconstruct Request with user and file (if exists)
            $mockRequest = $this->mockRequestWithFile($payload, $approvalRequest, $additionalPayload);

            // Disable strict mode temporarily (to prevent creating another approval request)
            $originalStrictMode = config('dsg.strict_mode');
            config(['dsg.strict_mode' => false]);

            // Create a new Mobilizon instance for the user
            Mobilizon::getInstance(false, $approvalRequest->createdBy, true);

            try {
                $response = null;
                $responseData = null;

                switch ($approvalRequest->requestable_type) {
                    case 'Organisation':
                        if  ($method === 'changeOrganisationStatus') {
                            //get organisation status from requestable id
                            $orgnisationStatus = OrganisationStatus::find($approvalRequest->requestable_id);

                            if (!$orgnisationStatus) {
                                return [
                                    'success' => false, 
                                    'error' => 'Organisation nicht gefunden'
                                ];
                            }

                            $response = $controller->{$method}(
                                $mockRequest,
                                $orgnisationStatus
                            );
                        }
                        else {
                            $response = $controller->{$method}($mockRequest);
                        }

                        $responseData = json_decode($response->getContent(), true);

                        if ($response->getStatusCode() === 200) {
                            $this->clearUpStoredFile($approvalRequest);

                            return [
                                'success' => true, 
                                'organisation_id' => $responseData['organisation']['id']
                            ];
                        }
                        
                        return [
                            'success' => false,
                            'error' => $responseData['error'] ?? 'Failed to process organisation',
                            'details' => $responseData['details'] ?? null,
                        ];
                        break;
             
                    case 'CreatedEvent':
                        $createdEventId = $payload['_created_event_id'] ?? $approvalRequest->requestable_id;
                    
                        if (!$createdEventId) {
                            return [
                                'success' => false, 
                                'error' => 'Created event ID not found in payload'
                            ];
                        }

                        $createdEvent = CreatedEvent::find($createdEventId);
                        if (!$createdEvent) {
                            return [
                                'success' => false, 
                                'error' => 'Created event not found'
                            ];
                        }
                    
                        $response = $method === 'destroy' 
                            ? $controller->{$method}($createdEvent) 
                            : $controller->{$method}($createdEvent, $mockRequest);
                        $responseData = json_decode($response->getContent(), true);  
                        break;

                    case 'SingleEvent':
                    //case 'SeriesEvent':
                    //case 'UploadedEvent':
                    //case 'ImportedEvent':  
                        $response = $controller->{$method}($mockRequest);
                        $responseData = json_decode($response->getContent(), true);
                        break;
                    default:
                        return [
                            'success' => false, 
                            'error' => 'Unsupported requestable type'
                        ];
                }
               
                // This part is for events only
                $responseKey = match ($approvalRequest->requestable_type) {
                    'CreatedEvent'  => 'createdEvent',
                    'SingleEvent'   => 'singleEvent',
                    //'SeriesEvent'   => 'seriesEvent',
                    //'UploadedEvent' => 'uploadedEvent',
                    //'ImportedEvent' => 'importedEvent',
                    default         => null,
                };

                if ($response->getStatusCode() === 200) {
                    $this->clearUpStoredFile($approvalRequest);

                    // Update requestable_id on approval request if it was a creation
                    if (!in_array($method, ['store']) && isset($responseData[$responseKey]['id'])) {
                        $eventId = $responseData[$responseKey]['id'];
                        $approvalRequest->update([
                            'requestable_id' => $eventId,
                        ]);
                    }

                    return ['success' => true, 'event_id' => $approvalRequest->requestable_id];
                }

                return [
                    'success' => false,
                    'error' => $responseData['error'] ?? 'Unknown error',
                ];
            } 
            finally {
                // Restore original strict mode setting
                config(['dsg.strict_mode' => $originalStrictMode]);

                // Restore Mobilizon instance
                Mobilizon::getInstance(false, null, true);
            }
        } 
        catch (Throwable $e) {
            Log::error('Error executing approval request', ['exception' => $e]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function clearUpStoredFile(ApprovalRequest $approvalRequest): void
    {
        if (isset($approvalRequest->payload['_file_path'])) {
            Storage::delete($approvalRequest->payload['_file_path']);
        }
    }


    private function getEventController(string $requestableType): ?object
    {
        $targetEventType = match ($requestableType) {
            'CreatedEvent'  => \App\Http\Controllers\CreatedEventController::class,
            'SingleEvent'   => \App\Http\Controllers\SingleEventController::class,
            'SeriesEvent'   => \App\Http\Controllers\SeriesEventController::class,
            'UploadedEvent' => \App\Http\Controllers\UploadedEventController::class,
            'ImportedEvent' => \App\Http\Controllers\ImportedEventController::class,
            'Organisation'  => \App\Http\Controllers\OrganisationController::class,
            default => null,
        };

        if ($targetEventType === null) {
            return null;
        }

        return app($targetEventType);
    }

    private function mockRequestWithFile(array $payload, ApprovalRequest $approvalRequest, array $additionalPayload = []): Request
    {
        $payload = array_merge($payload, $additionalPayload);
        $mockRequest = new Request($payload);

        $user = User::find($approvalRequest->created_by_user_id);
        $mockRequest->setUserResolver(function () use ($user) {
            return $user;
        });

        if ($approvalRequest->requestable_type === 'Organisation' 
            && $approvalRequest->request_type === 'changeOrganisationStatus') {
            
            return $mockRequest;
        }

        if (isset($payload['_file_path']) && Storage::exists($payload['_file_path'])) {
            $uploadedFile = new UploadedFile(
                Storage::path($payload['_file_path']),
                $payload['_file_original_name'] ?? 'file',
                null,
                null,
                true
            );

            // Handle different file types based on requestable type
            if ($approvalRequest->requestable_type === 'Organisation') {
                // Organisation uses avatar.media.file
                $mockRequest->files->set('avatar', [
                    'media' => ['file' => $uploadedFile],
                ]);
            } else {
                // Events use mobilizon_fields.picture.media.file
                $mockRequest->files->set('mobilizon_fields', [
                    'picture' => [
                        'media' => ['file' => $uploadedFile],
                    ],
                ]);
            }
        }

        return $mockRequest;
    }
}
