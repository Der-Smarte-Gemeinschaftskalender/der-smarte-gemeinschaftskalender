<?php

namespace App\Models;

use Illuminate\Http\UploadedFile;
use rdx\graphqlquery\Query;
use GuzzleHttp\Client;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;


class Mobilizon
{
    protected $client;
    protected $debug = false;
    private static $instance = null;
    public $user_id = null;
    public $user = null;
    public $person_id = null;
    public $person = null;
    private $accessToken = null;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    public function __construct() {}

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone() {}

    /**
     * prevent from being unserialized (which would create a second instance of it)
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }

    public static function getInstance(bool $useWithoutLogin = false, $user = null, $createNew = false): Mobilizon
    {
        if (self::$instance == null || $createNew) {
            self::$instance = new Mobilizon();
            self::$instance->client = new Client([
                'base_uri' => config('app.mobilizon_url'),
                'timeout'  => 15.0,
            ]);
            if ($useWithoutLogin) {
                return self::$instance;
            }

            if (!$user) {
                $user = auth()->user();
            }

            if (isset($user->mobilizon_access_token) && $user->mobilizon_access_token != '') {
                $decodedToken = self::$instance->decodeJwtToken($user->mobilizon_access_token);
                if (!$user->mobilizon_access_token || !$decodedToken || $decodedToken['exp'] < time()) {

                    $login = self::$instance->login($user);

                    $newAccessToken = $login['data']['login']['accessToken'];

                    self::$instance->setAccessToken($newAccessToken, $user);
                } else {
                    self::$instance->setAccessToken($user->mobilizon_access_token, $user);
                }
                self::$instance->getUserId();
                self::$instance->getPersonId();
            } else {
                $login = self::$instance->login($user);
                
                if (self::$instance->hasError($login)) {
                    Log::error('Mobilizon Login fehlgeschlagen für Benutzer ID ' . $user->id, [
                        'response' => $login
                    ]);

                    return self::$instance;
                }
   
                $newAccessToken = $login['data']['login']['accessToken'];
                self::$instance->setAccessToken($newAccessToken, $user);
                self::$instance->getUserId();
                self::$instance->getPersonId();
            }
        }
        return self::$instance;
    }

    public static function getInstanceAdmin(bool $useWithoutLogin = false, $user = null, $createNew = false): Mobilizon
    {
        $user = User::find(1);
        return self::getInstance($useWithoutLogin, $user, $createNew);
    }

    private function decodeJwtToken(string|null $jwt): array|null
    {
        if (!$jwt) {
            return null;
        }
        list($header, $payload, $signature) = explode('.', $jwt);
        $jsonToken = base64_decode($payload);
        $arrayToken = json_decode($jsonToken, true);
        return $arrayToken;
    }

    private function login($user): array
    {
        $query = Query::mutation("Login");
        $query->field("login")->attributes(["email" => $user->mobilizon_email, "password" => $user->mobilizon_password]);
        $query->login->fields(['accessToken', 'refreshToken']);

        return $this->requestWithoutMedia($query->build(), false);
    }

    public function createUser(string $email, string $password): array
    {
        $query = Query::mutation("CreateUser");
        $query->field("createUser")->attributes(["email" => $email, "password" => $password, "locale" => "de"]);
        $query->createUser->fields(['role', 'id', 'email']);

        return $this->requestWithoutMedia($query->build(), false);
    }

    public function updatePerson($name): array
    {
        $query = Query::mutation("UpdatePerson");
        $query->field("updatePerson")->attributes(["id" => $this->person_id, "name" => $name]);
        $query->updatePerson->fields(['id', 'name', 'preferredUsername']);
        if ($this->debug) var_dump($query->build());

        try {
            $response = $this->requestWithoutMedia($query->build());
            $this->person = $response['data']['updatePerson'] ?? null;
            return $response;
        } catch (Throwable $e) {
            Log::error($e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    private function getUserId(): void
    {
        $query = Query::query("LoggedUser");
        $query->field("loggedUser")->fields(['id', 'email']);
        if ($this->debug) var_dump($query->build());

        $response = $this->requestWithoutMedia($query->build());
        $this->user_id = $response['data']['loggedUser']['id'] ?? null;
        $this->user = $response['data']['loggedUser'] ?? null;
    }


    private function getPersonId(): void
    {
        $query = Query::query("LoggedPerson");
        $query->field("LoggedPerson")->fields(['id', 'name', 'preferredUsername']);
        if ($this->debug) var_dump($query->build());

        $response = $this->requestWithoutMedia($query->build());
        $this->person_id = $response['data']['LoggedPerson']['id'] ?? null;
        $this->person = $response['data']['LoggedPerson'] ?? null;
    }

    private function setAccessToken(string $accessToken, User $user): void
    {
        $user->mobilizon_access_token = $accessToken;
        $user->save();
        if (auth()->check() && auth()->user()->id === $user->id) {
            auth()->user()->mobilizon_access_token = $accessToken;
        }
        $this->accessToken = $accessToken;
    }

    public function getGroups(): array
    {
        $query = Query::query("LoggedUserMemberships");
        $query->field("loggedUser")->field('id');
        $query->loggedUser->fields(['memberships']);
        $query->loggedUser->memberships->attributes(["limit" => 1000, 'page' => 1, 'name' => ''])->field('elements')->fields(['parent', 'role', 'id']);
        $query->loggedUser->memberships->elements->parent->fields(['id', 'name', 'preferredUsername']);
        if ($this->debug) var_dump($query->build());

        return $this->requestWithoutMedia($query->build());
    }

    public function createGroup($group): array
    {
        $query = Query::mutation("CreateGroup");
        $query->field("createGroup")->attributes($group);
        $query->createGroup->fields(['id', 'name', 'preferredUsername']);

        if ($this->debug) var_dump($query->build());

        return $this->requestWithoutMedia($query->build());
    }

    public function getGroup($groupId)
    {
        $query = Query::query("GetGroup");
        $query->field("getGroup")->attribute('id', $groupId);
        $query->getGroup->fields(['id', 'name', 'preferredUsername', 'summary', 'avatar', 'physicalAddress', 'members']);
        $query->getGroup->avatar->fields(['url', 'alt', 'name']);
        $query->getGroup->physicalAddress->fields(['street', 'postalCode', 'locality', 'region', 'country', 'geom']);
        $query->getGroup->members->fields(['total', 'elements']);
        $query->getGroup->members->attribute('limit', 50);
        $query->getGroup->members->elements->fields(['id', 'insertedAt', 'role', 'actor']);
        $query->getGroup->members->elements->actor->fields(['id', 'name', 'preferredUsername', 'suspended']);

        return $this->requestWithoutMedia($query->build())['data']['getGroup'] ?? null;
    }

    public function getUserGroup($preferredUsername)
    {
        $query = Query::query("Group");
        $query->field("group")->attribute('preferredUsername', $preferredUsername);
        $query->group->fields(['id', 'name', 'preferredUsername', 'summary', 'avatar', 'physicalAddress', 'members']);
        $query->group->avatar->fields(['url', 'alt', 'name']);
        $query->group->physicalAddress->fields(['street', 'postalCode', 'locality', 'region', 'country', 'geom']);
        $query->group->members->fields(['total', 'elements']);
        $query->group->members->attribute('limit', 50);
        $query->group->members->elements->fields(['id', 'insertedAt', 'role', 'actor']);
        $query->group->members->elements->actor->fields(['id', 'name', 'preferredUsername', 'suspended']);

        return $this->requestWithoutMedia($query->build())['data']['group'] ?? null;
    }

    public function inviteToGroup($groupId, $username): array
    {
        $query = Query::mutation("InviteMember");
        $query->field("inviteMember")->attributes(['groupId' => $groupId, 'targetActorUsername' => $username]);
        $query->inviteMember->fields(['id', 'role', 'insertedAt', 'actor', 'parent']);
        $query->inviteMember->parent->fields(['name']);
        $query->inviteMember->actor->fields(['id', 'user']);
        $query->inviteMember->actor->user->fields(['id']);
        if ($this->debug) var_dump($query->build());

        return $this->requestWithoutMedia($query->build());
    }

    public function rejectGroupInvitation($membershipId): array
    {
        $query = Query::mutation("RejectInvitation");
        $query->field("rejectInvitation")->attributes(['id' => $membershipId]);
        $query->rejectInvitation->fields(['id', 'role']);
        if ($this->debug) var_dump($query->build());

        return $this->requestWithoutMedia($query->build());
    }

    public function acceptGroupInvitation($membershipId): array
    {
        $query = Query::mutation("AcceptInvitation");
        $query->field("acceptInvitation")->attributes(['id' => $membershipId]);
        $query->acceptInvitation->fields(['id', 'role', 'invitedBy']);
        $query->acceptInvitation->invitedBy->fields(['id']);
        if ($this->debug) var_dump($query->build());

        return $this->requestWithoutMedia($query->build());
    }

    public function updateGroupMember($membershipId, $role): array
    {
        $query = Query::mutation("UpdateMember");
        $query->field("updateMember")->attributes(['memberId' => $membershipId, 'role' => $role]);
        $query->updateMember->fields(['id', 'role']);
        if ($this->debug) var_dump($query->build());

        return $this->requestWithoutMedia($query->build());
    }

    public function removeGroupMember($membershipId): array
    {
        $query = Query::mutation("RemoveMember");
        $query->field("removeMember")->attributes(['memberId' => $membershipId]);
        $query->removeMember->fields(['id', 'actor', 'parent']);
        $query->removeMember->parent->fields(['name']);
        $query->removeMember->actor->fields(['id', 'user']);
        $query->removeMember->actor->user->fields(['id']);
        if ($this->debug) var_dump($query->build());

        return $this->requestWithoutMedia($query->build());
    }

    public function leaveGroupAsMember($groupId): array
    {
        $query = Query::mutation("LeaveGroup");
        $query->field("leaveGroup")->attributes(['groupId' => $groupId]);
        $query->leaveGroup->fields(['id']);
        if ($this->debug) var_dump($query->build());

        return $this->requestWithoutMedia($query->build());
    }

    public function getGroupsAsArray(): array
    {
        $groups = $this->getGroups();
        $groupsArray = [];

        if (isset($groups['data']['loggedUser']['memberships']['elements'])) {
            foreach ($groups['data']['loggedUser']['memberships']['elements'] as $group) {
                if ($group['role'] === 'ADMINISTRATOR') {
                    $groupsArray[] = $group['parent'];
                }
            }
        }
        return $groupsArray;
    }

    public function adminGetAllGroupsArray(): array
    {
        $query = Query::query("groups");
        $query->field("groups")->field('elements');
        $query->groups->attributes(["limit" => 1000, 'page' => 1, 'name' => '']);
        $query->groups->elements->fields(['id', 'name', 'preferredUsername']);
        if ($this->debug) var_dump($query->build());

        $groups = $this->requestWithoutMedia($query->build());
        $groupsArray = [];

        if (isset($groups['data']['groups']['elements'])) {
            foreach ($groups['data']['groups']['elements'] as $group) {
                $groupsArray[$group['id']] = $group;
            }
        }
        return $groupsArray;
    }

    public function getEvent(string $uuid): array
    {
        $query = Query::query("event");
        $query->field("event")->attribute("uuid", $uuid);
        $query->event->fields(['beginsOn', 'endsOn', 'description', 'title', 'category', 'tags', 'externalParticipationUrl', 'joinOptions', 'joinOptions', 'language', 'onlineAddress', 'status', 'url', 'physicalAddress', 'picture']);
        $query->event->physicalAddress->fields(['street', 'postalCode', 'locality', 'region', 'country', 'geom']);
        $query->event->tags->fields(['slug', 'title']);
        $query->event->picture->fields(['url']);

        if ($this->debug) var_dump($query->build());

        return $this->requestWithoutMedia($query->build());
    }

    public function getEventImage(string $uuid): array
    {
        $query = Query::query("event");
        $query->field("event")->attribute("uuid", $uuid);
        $query->event->fields(['picture']);
        $query->event->picture->fields(['url', 'alt', 'name']);
        if ($this->debug) var_dump($query->build());

        return $this->requestWithoutMedia($query->build());
    }

    public function validateUser(string $token): array
    {
        $query = Query::mutation("ValidateUser");
        $query->field("validateUser")->attributes(["token" => $token]);
        $query->validateUser->fields(['accessToken', 'refreshToken']);

        return $this->requestWithoutMedia($query->build(), false);
    }

    public function registerPerson(array $data): array
    {
        $query = Query::mutation("RegisterPerson");
        $query->field("createPerson")->attributes($data);
        $query->createPerson->fields(['id', 'name', 'preferredUsername']);

        return $this->requestWithoutMedia($query->build());
    }

    public function deletePerson($userId)
    {
        $query = Query::mutation("DeletePerson");
        $query->field("deletePerson")->attributes(["id" => $userId]);
        $query->deletePerson->fields(['id', 'preferredUsername']);

        return $this->requestWithoutMedia($query->build());
    }

    public function deleteAccount($userId, $password)
    {
        $query = Query::mutation("DeleteAccount");
        $query->field("deleteAccount")->attributes(["userId" => $userId, "password" => $password]);
        $query->deleteAccount->fields(['id']);

        return $this->requestWithoutMedia($query->build());
    }

    public function createEvent(array $event, bool $fileExists = false): array
    {
        if ($fileExists) {
            $file = $event['picture']['media']['file'];
            $event['picture']['media']['file'] = 'image1';

            $query = Query::mutation("CreateEvent");
            $query->field("createEvent")->attributes($event);
            $query->createEvent->fields(['id', 'uuid', 'picture']);
            $query->createEvent->picture->fields(['url', 'alt', 'name']);

            return $this->requestWithMedia($query->build(), $event, $file);
        }

        $query = Query::mutation("CreateEvent");
        $query->field("createEvent")->attributes($event);
        $query->createEvent->fields(['id', 'uuid']);

        return $this->requestWithoutMedia($query->build());
    }

    public function updateEvent(array $event, bool $fileExists = false): array
    {
        if ($fileExists) {
            $file = $event['picture']['media']['file'];
            $event['picture']['media']['file'] = 'image1';

            $query = Query::mutation("UpdateEvent");
            $query->field("updateEvent")->attributes($event);
            $query->updateEvent->fields(['id', 'uuid', 'picture']);
            $query->updateEvent->picture->fields(['url', 'alt', 'name']);

            return $this->requestWithMedia($query->build(), $event, $file);
        }

        $query = Query::mutation("UpdateEvent");
        $query->field("updateEvent")->attributes($event);
        $query->updateEvent->fields(['id', 'uuid', 'picture']);
        $query->updateEvent->picture->fields(['url', 'alt', 'name']);

        return $this->requestWithoutMedia($query->build());
    }

    public function deleteEvent(int $eventId): array
    {
        $query = Query::mutation("DeleteEvent");
        $query->field("deleteEvent")->attributes(["eventId" => $eventId]);
        $query->deleteEvent->fields(['id']);
        if ($this->debug) var_dump($query->build());

        return $this->requestWithoutMedia($query->build());
    }

    public function searchEvents(array $fieldsToSearch, $limit = null): array
    {
        $query = Query::query("searchEvents");
        $query->field("searchEvents")
            ->attribute('sortBy', Query::enum('START_TIME_ASC'))
            ->attribute('limit', $limit)
            ->attributes($fieldsToSearch);
        $query->searchEvents->fields(['total', 'elements']);
        $query->searchEvents->elements->fields(['title', 'beginsOn', 'endsOn', 'uuid', 'attributedTo', 'category']);
        $query->searchEvents->elements->attributedTo->fields(['name', 'id']);
        if ($this->debug) var_dump($query->build());

        return $this->requestWithoutMedia($query->build());
    }

public function findProfileByPreferredUsername(
    string $preferredUsername,
    bool $local = true
): int {
    $query = Query::query('ListProfiles');

    $query->field('persons')
        ->attributes([
            'preferredUsername' => $preferredUsername,
            'local' => $local,
            'page' => 1,
            'limit' => 1,
        ]);

    $query->persons->fields(['total']);

    if ($this->debug) {
        var_dump($query->build());
    }

    $response = $this->requestWithoutMedia($query->build());
    return (int) ($response['data']['persons']['total'] ?? 0);
}


    public function hasError($response)
    {
        return isset($response['errors']) || isset($response['error']);
    }

    public function getError($response)
    {
        return $response['error'] ?? $response['errors'][0]['message'];
    }

    public function updateGroup(array $group, bool $fileExists = false): array
    {
        if ($fileExists) {
            $file = $group['avatar']['media']['file'];
            $group['avatar']['media']['alt'] = "Avatar for " . $group['name'];
            $group['avatar']['media']['file'] = 'image1';

            $query = Query::mutation("UpdateGroup");
            $query->field("updateGroup")->attributes($group);
            $query->updateGroup->fields(['id', 'avatar']);
            $query->updateGroup->avatar->fields(['url', 'name']);



            return $this->requestWithMedia($query->build(), $group, $file);
        }

        $query = Query::mutation("UpdateGroup");
        $query->field("updateGroup")->attributes($group);
        $query->updateGroup->fields(['id', 'avatar']);
        $query->updateGroup->avatar->fields(['url', 'name']);

        return $this->requestWithoutMedia($query->build());
    }

    public function adminUpdateUser($fields): array
    {
        $query = Query::mutation("AdminUpdateUser");
        $query->field("adminUpdateUser")->attributes($fields);
        $query->adminUpdateUser->fields(['id', 'email', 'role', 'disabled', 'confirmedAt']);
        if ($this->debug) var_dump($query->build());

        return $this->requestWithoutMedia($query->build());
    }

    private function requestWithMedia(string $queryString, array $variables, UploadedFile $file): array
    {
        try {
            $response = $this->client->request("POST", "/api", [
                'headers' => [
                    "Authorization" => "bearer " . $this->accessToken,
                    "Accept" => "application/json",
                ],
                'multipart' => [
                    [
                        'name' => 'query',
                        'contents' => $queryString,
                    ],
                    [
                        'name' => 'variables',
                        'contents' => json_encode(['input' => $variables]),
                    ],
                    [
                        'name' => 'image1',
                        'contents' => fopen($file->getRealPath(), 'r'),
                        'filename' => $file->getClientOriginalName(),
                        'headers' => [
                            'Content-Type' => $file->getMimeType() ?: 'application/octet-stream',
                        ]
                    ],
                ],
            ]);

            return json_decode((string)$response->getBody(), true);
        } catch (Throwable $e) {
            Log::error($e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    private function requestWithoutMedia(string $queryString, $token = true): array
    {
        $headers = [
            "Content-Type" => "application/json",
            "Accept" => "application/json"
        ];
        if ($token) $headers["Authorization"] = "bearer " . $this->accessToken;

        try {
            $response = $this->client->request("POST", "/api", [
                "http_errors" => false,
                "headers" => $headers,
                "body" => json_encode(["query" => $queryString])
            ]);

            return json_decode((string)$response->getBody(), true);
        } catch (Throwable $e) {
            Log::error($e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
