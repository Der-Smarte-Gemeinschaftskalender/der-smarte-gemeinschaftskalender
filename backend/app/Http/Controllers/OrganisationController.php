<?php

namespace App\Http\Controllers;

use App\Mail\SendOrganisationInviteToUserEmail;
use App\Mail\SendOrganisationRemovedMembershipToUserEmail;
use App\Models\Mobilizon;
use App\Models\MobilizonTag;
use App\Models\OrganisationStatus;
use App\Models\User;
use App\Models\ApprovalRequest;
use App\Models\ImportedEvent;
use Exception;
use Http;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Log;
use rdx\graphqlquery\Query;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\ApprovalRequestService;

class OrganisationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth', except: ['getOrganisationsWithFeaturedStatus']),
            new Middleware('is_admin', only: ['indexRequestedOrganisation', 'changeOrganisationStatus', 'indexOrganisation', 'showGroupById', 'updateFeaturedStatus']),
        ];
    }

    public function myOrganisations(): array
    {
        $mclient = Mobilizon::getInstance();
        $groups = $mclient->getGroups();
        $userIsAdmin = auth()->user()->type === 'admin';

        $groupsArray = [];
        if (isset($groups['data']['loggedUser']['memberships']['elements'])) {
            foreach ($groups['data']['loggedUser']['memberships']['elements'] as $group) {
                if ($userIsAdmin && isset($group['parent']['id'])) {
                    $groupId = $group['parent']['id'];
                    $orgStatus = OrganisationStatus::where('mobilizon_group_id', $groupId)->first();
                    $group['is_featured'] = $orgStatus?->is_featured ?? false;
                }
                array_push($groupsArray, $group);
            }
        }
        
        usort($groupsArray, fn($a, $b) => $b['role'] <=> $a['role']);
        usort($groupsArray, fn($a, $b) => ($b['is_featured'] ?? false) <=> ($a['is_featured'] ?? false));

        return ['data' => $groupsArray];
    }

    public function requestOrganisationCreation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'preferredUsername' => 'required|string',
            'summary' => 'string',
        ]);

        $systemAdmin = User::find(1);
        $adminClient = Mobilizon::getInstance(false, $systemAdmin, true);
        $existingGroups = $adminClient->adminGetAllGroupsArray();
        foreach ($existingGroups as $group) {
            if ($group['preferredUsername'] === $validated['preferredUsername']) {
                return response()->json(['error' => 'Es kann keine Organisation mit diesem Benutzernamen erstellt werden, da dieser bereits vergeben ist.'], 422);
            }
        }

    
        $userWithSameUsernameExists = (bool) $adminClient->findProfileByPreferredUsername($validated['preferredUsername']);
        if ($userWithSameUsernameExists) {
            return response()->json(['error' => 'Es kann keine Organisation mit diesem Benutzernamen erstellt werden, da dieser bereits von einem Benutzer verwendet wird.'], 422);
        }
        

        $newOrganisationStatus = new OrganisationStatus();
        $newOrganisationStatus->status = 'REQUESTED';
        $newOrganisationStatus->requested_organisation_data = $validated;
        $newOrganisationStatus->requested_by_user_id = $request->user()->id; // Authenticated user ?? manipulated user
        $newOrganisationStatus->save();

        $approvalRequestService = new ApprovalRequestService();
        $result = $approvalRequestService->createApprovalRequest($request, 'Organisation', 'changeOrganisationStatus', $newOrganisationStatus->id);  
        if ($result['success']) {
            return response()->json([
                    'message' => $result['message'],
                    'approval_request_id' => $result['approval_request_id'],
                ], 202);
        } else {
            $newOrganisationStatus->delete();
            return response()->json([
                'error' => $result['error'],
                'details' => $result['details'] ?? null,
            ], 500);
        }
    }

    public function indexRequestedOrganisationFromMe(): array
    {
        $requestedOrganisations = OrganisationStatus::where('status', 'REQUESTED')
            ->where('requested_by_user_id', auth()->id())
            ->get();
        return ['data' => $requestedOrganisations];
    }

    public function indexRequestedOrganisation(): array
    {
        $requestedOrganisations = OrganisationStatus::where('status', 'REQUESTED')
            ->with(['requested_by_user' => function ($query) {
                $query->select('id', 'mobilizon_name', 'mobilizon_preferred_username', 'email');
            }])
            ->get();
        return ['data' => $requestedOrganisations];
    }

    public function indexOrganisation(Request $request): array
    {
        $status = $request->input('status', 'ACTIVE');
        $organisations = OrganisationStatus::where('status', $status)->get();
        return ['data' => $organisations];
    }

    public function changeOrganisationStatus(Request $request, OrganisationStatus $organisation): JsonResponse
    {
        // Seems like INACTIVE wasn't set anywhere before, so nothing changes here
        $validated = $request->validate([
            'status' => 'required|string|in:REQUEST_DENIED,ACTIVE,INACTIVE',
        ]);
        $newStatus = $validated['status'];

        if ($newStatus === 'ACTIVE') {
            $systemAdmin = User::find(1);
            $adminClient = Mobilizon::getInstance(false, $systemAdmin, true);
            $existingGroups = $adminClient->adminGetAllGroupsArray();
            foreach ($existingGroups as $group) {
                if ($group['preferredUsername'] === $organisation->requested_organisation_data['preferredUsername']) {
                    return response()->json(['error' => 'Es kann keine Organisation mit diesem Benutzernamen erstellt werden, da dieser bereits vergeben ist.'], 422);
                }
            }
            $organisation->load('requested_by_user');
            try {
                $mclient = Mobilizon::getInstance(false, $organisation->requested_by_user, true);
                $createdMobilizonGroup = $mclient->createGroup(
                    $organisation->requested_organisation_data
                );

                $organisation->mobilizon_group_id = $createdMobilizonGroup['data']['createGroup']['id'];
            } catch (\Exception $e) {
                Log::error('Error creating Mobilizon group: ' . $e->getMessage());
                return response()->json(['error' => 'Organisation konnte nicht in Mobilizon von dem Benutzer erstellt werden'], 500);
            }
        }

        $organisation->status = $newStatus;
        $organisation->save();

        return response()->json([
            'organisation' => $organisation
        ], 200);
    }

    public function showGroup(Request $request)
    {
        $mclient = Mobilizon::getInstance();
        $preferredUsername = $request->input('preferred_username');
        $result = $mclient->getUserGroup($preferredUsername);
        if ($request->user()->type === 'admin' && isset($result['id'])) {
            $groupId = $result['id'];
            $orgStatus = OrganisationStatus::where('mobilizon_group_id', $groupId)->first();
            $result['is_featured'] = $orgStatus?->is_featured ?? false;
        }

        return $result;
    }

    public function showGroupById(Request $request, $group_id)
    {
        $mclient = Mobilizon::getInstance();
        $result = $mclient->getGroup($group_id);
        return $result;
    }

    public function updateGroup(Request $request): JsonResponse|array
    {
        // Strict mode: create approval request instead of updating immediately
        if (config('dsg.strict_mode')) {
            $approvalRequestService = new ApprovalRequestService();
            $result = $approvalRequestService->createApprovalRequest($request, 'Organisation', 'updateGroup', $request->input('id'));
            if ($result['success']) {
                return response()->json([
                    'message' => $result['message'],
                    'approval_request_id' => $result['approval_request_id'],
                ], 202);
            } else {
                return response()->json([
                    'error' => $result['error'],
                    'details' => $result['details'] ?? null,
                ], 500);
            }
        }

        $mclient = Mobilizon::getInstance();
        $groupData = [
            'id' => $request->input('id'),
            'name' => $request->input('name'),
            'summary' => $request->input('summary'),
        ];

        if ($request->hasFile('avatar.media.file')) {
            $groupData['avatar'] = $request->input('avatar');
            $groupData['avatar']['media']['file'] = $request->file('avatar.media.file');
        }

        if ($request->input('physicalAddress')) {
            $groupData['physicalAddress'] = $request->input('physicalAddress');
        }

        $mresponse = $mclient->updateGroup($groupData, $request->hasFile('avatar.media.file'));
        
        // Check for errors in the Mobilizon response
        if (isset($mresponse['errors']) || (isset($mresponse['data']) && isset($mresponse['data']['updateGroup']) && isset($mresponse['data']['updateGroup']['errors']))) {
            return response()->json([
                'error' => 'Failed to update group',
                'details' => $mresponse['errors'] ?? $mresponse['data']['updateGroup']['errors'] ?? null,
            ], 400);
        }
        
        return response()->json([
            'organisation' => $mresponse['data']['updateGroup']
        ], 200);
    }

    public function inviteMember(Request $request): array
    {
        $groupId = $request->input('group_id');
        $username = $request->input('username');

        $mclient = Mobilizon::getInstance();
        $mresponse = $mclient->inviteToGroup($groupId, $username);
        try {
            if (!$mclient->hasError($mresponse)) {
                $inivtedUserId = $mresponse['data']['inviteMember']['actor']['id'];
                $user = User::where('mobilizon_profile_id', $inivtedUserId)->first();
                if ($user) {
                    Mail::to($user->email)
                        ->send(new SendOrganisationInviteToUserEmail($mresponse['data']['inviteMember']['parent']['name']));
                }
            }
        } catch (Exception $e) {
            Log::error('Error sending email inviting user to organisation: ' . $e->getMessage());
        }

        return $mresponse;
    }

    public function rejectInvitation(Request $request): array
    {
        $memberShipId = $request->input('membership_id');

        $mclient = Mobilizon::getInstance();
        $mresponse = $mclient->rejectGroupInvitation($memberShipId);

        return $mresponse;
    }

    public function acceptInvitation(Request $request)
    {
        try {
            $membershipId = $request->input('membership_id');

            $mclient = Mobilizon::getInstance();
            $mresponse = $mclient->acceptGroupInvitation($membershipId);
            $invitedById = $mresponse['data']['acceptInvitation']['invitedBy']['id'];

            $invitedByUser = User::where('mobilizon_profile_id', $invitedById)->first();
            if (!$invitedByUser) {
                return ['error' => 'Invited by user not found'];
            }

            $mclientInvitedBy = Mobilizon::getInstance(false, $invitedByUser, true);
            $mresponse = $mclientInvitedBy->updateGroupMember($membershipId, Query::enum('ADMINISTRATOR'));
            return $mresponse;
        } catch (Exception $e) {
            Log::error('Error accepting invitation: ' . $e->getMessage());
            return ['error' => 'Failed to accept invitation in Mobilizon'];
        }
    }

    public function removeMember(Request $request): JsonResponse
    {
        $memberShipId = $request->input('membership_id');
        $groupId = $request->input('group_id');
        $groupPreferredUsername = $request->input('group_preferred_username');

        $mclient = Mobilizon::getInstance();
        $groupMembersResponse = $mclient->getUserGroup($groupPreferredUsername);

        $mresponse = $mclient->removeGroupMember($memberShipId);
        if ($mclient->hasError($mresponse)) {
            return response()->json([
                'error' => 'Fehler beim Entfernen des Mitglieds aus der Organisation',
                'details' => $mresponse['errors'] ?? null,
            ], 400);
        }

        try {
            $userId = $mresponse['data']['removeMember']['actor']['id'];
            $user = User::where('mobilizon_profile_id', $userId)->first();

            if ($user) {
                if (!$mclient->hasError($groupMembersResponse)) {
                    $this->migrateImportedEvents($user, $groupId, $groupMembersResponse['members']['elements'] ?? []);
                }

                Mail::to($user->email)
                    ->send(new SendOrganisationRemovedMembershipToUserEmail($mresponse['data']['removeMember']['parent']['name']));    
            }
            
        } catch (Exception $e) {
            Log::error('Error sending email removing user from organisation: ' . $e->getMessage());
            return response()->json([
                'error' => 'Mitglied wurde zwar aus der Organisation entfernt, aber es gab ein Problem beim Senden der Benachrichtigungs-E-Mail.',
                'details' => $e->getMessage(),
            ], 200);
        }

        return response()->json([
            'message' => 'Mitglied erfolgreich aus der Organisation entfernt',
        ], 200);
    }

    public function leaveOrganiation(Request $request): JsonResponse
    {
        $groupId = $request->input('group_id');
        $groupPreferredUsername = $request->input('group_preferred_username');

        $mclient = Mobilizon::getInstance();
        $groupMembersResponse = $mclient->getUserGroup($groupPreferredUsername);
        
        $mresponse = $mclient->leaveGroupAsMember($groupId);
        if ($mclient->hasError($mresponse)) {
            return response()->json([
                'error' => 'Fehler beim Verlassen der Organisation',
                'details' => $mresponse['errors'] ?? null,
            ], 400);
        }

        if (!$mclient->hasError($groupMembersResponse)) {
            $this->migrateImportedEvents($request->user(), $groupId, $groupMembersResponse['members']['elements'] ?? []);
        }
        
        return response()->json([
            'message' => 'Erfolgreich aus der Organisation ausgetreten',
        ], 200);
    }

    public function getOrganisationAvatar(Request $request): StreamedResponse|JsonResponse
    {
        $mclient = Mobilizon::getInstance();
        if (!$request->has('imageUrl')) {
            $preferredUsername = $request->input('preferred_username');
            $mresponse = $mclient->getUserGroup($preferredUsername);

            $imageUrl = $mresponse['avatar']['url'] ?? null;
        } else {
            $imageUrl = $request->input('imageUrl');
        }

        if (!$imageUrl) {
            return response()->json(['error' => 'Image not found'], 404);
        }

        try {
            $imageResponse = Http::get($imageUrl);

            if (!$imageResponse->successful()) {
                return response()->json(['error' => 'Failed to fetch image'], 502);
            }

            $contentType = $imageResponse->header('Content-Type') ?? 'image/jpeg';
            $fileName = basename(parse_url($imageUrl, PHP_URL_PATH)) ?? 'event_image.jpg';

            return response()->stream(function () use ($imageResponse) {
                echo $imageResponse->body();
            }, 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Unexpected error: ' . $e->getMessage()], 500);
        }
    }

    public function getMostUsedTags(Request $request): array
    {
        $groupId = $request->input('groupId');
        $mostUsedTags = MobilizonTag::where('mobilizon_group_id', (int)$groupId)
            ->select('title', DB::raw('count(*) as total'))
            ->groupBy('title')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        return $mostUsedTags->pluck('title')->toArray();
    }

    public function getMobilizonGroups(): array
    {
        $mclient = Mobilizon::getInstance();
        $groups = $mclient->getGroupsAsArray();

        return $groups;
    }

    public function loadMobilizonGroups(): JsonResponse
    {
        return response()->json([
            'groups' => $this->getMobilizonGroups()
        ], 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * @param int $userId The ID of the user which was removed from the group
     * @param int $groupId
     * @param array $groupMembers The group members of the group the user was removed from
     */
    public function migrateImportedEvents(User $user, int $groupId, array $groupMembers = []): void
    {
        $importedEventsWithMissingUser = ImportedEvent::where('user_id', $user->id)
            ->where('mobilizon_group_id', $groupId)
            ->get();

        if ($importedEventsWithMissingUser->isEmpty()) {
            return;
        }
            
        $otherMember = array_find($groupMembers, fn($member) => 
                $member['actor']['id'] !== $user->mobilizon_profile_id 
                && $member['role'] !== 'INVITED'
        );

        if (!$otherMember) {
            Log::warning("No other member found in the group with ID {$groupId} to migrate the imported events to.");
            return;
        }

        $otherMemberUserId = $otherMember['actor']['id'];
        $userByMember = User::where('mobilizon_profile_id', $otherMemberUserId)->first();
        if (!$userByMember) {
            Log::warning("No user found in the database with mobilizon_profile_id {$otherMemberUserId} to migrate the imported events to.");
            return;
        }
            
        foreach ($importedEventsWithMissingUser as $importedEvent) {
            $importedEvent->user_id = $userByMember->id;
            $importedEvent->save();

            $createdEvents = $importedEvent->created_events()->get();
            foreach ($createdEvents as $createdEvent) {
                $createdEvent->user_id = $userByMember->id;
                $createdEvent->save();
            }
        }
    }

    public function updateFeaturedStatus(Request $request, $mobilizonGroupId): JsonResponse
    {
        $validated = $request->validate([
            'is_featured' => 'required|boolean',
        ]);

        try {
            $organisationStatus = OrganisationStatus::firstOrCreate(
                ['mobilizon_group_id' => $mobilizonGroupId],
                ['status' => 'ACTIVE']
            );

            $organisationStatus->is_featured = $validated['is_featured'];
            $organisationStatus->save();

            return response()->json([
                'message' => 'Erfolgreich aktualisiert',
                'organisation_status' => $organisationStatus
            ], 200);

        } catch (Exception $e) {
            Log::error('Error updating featured status: ' . $e->getMessage());
            return response()->json(['error' => 'Fehler beim Aktualisieren des empfohlenen Status'], 500);
        }
    }

    public function getOrganisationsWithFeaturedStatus(): JsonResponse
    {
        $organisationStatuses = OrganisationStatus::where('status', 'ACTIVE')
            ->whereNotNull('mobilizon_group_id')
            ->where('is_featured', true)
            ->get(['mobilizon_group_id', 'is_featured']);

        return response()->json([
            'organisation_statuses' => $organisationStatuses
        ], 200);
    }
}
