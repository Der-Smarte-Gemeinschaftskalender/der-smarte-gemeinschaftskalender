<?php

namespace App\Jobs;

use App\Models\CreatedEvent;
use App\Models\ImportedEvent;
use App\Models\Mobilizon;
use App\Models\MobilizonTag;
use App\Services\IcalEventService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Log;
use Throwable;

class FetchImportedEvents implements ShouldQueue
{
    use Queueable;

    protected int $eventId;


    public function __construct(int $eventId = -1)
    {
        $this->eventId = $eventId;
    }

    public function handle(): void
    {
        $mclient = Mobilizon::getInstanceAdmin();
        $groups = $mclient->adminGetAllGroupsArray();

        $importedEventsQuery = $this->eventId === -1
            ? ImportedEvent::where('is_active', true)->with('user')
            : ImportedEvent::where('id', $this->eventId)->with('user');

        $importedEventsQuery->chunk(20, function ($importedEvents) use ($mclient, $groups) {
            foreach ($importedEvents as $importedEvent) {
                if (!$importedEvent->user) {
                    Log::warning("Imported Event ID {$importedEvent->id} has no associated user.");
                    continue;
                }
                $mclient = Mobilizon::getInstance(false, $importedEvent->user, true);
                if (!isset($groups[$importedEvent->mobilizon_group_id])) {
                    Log::warning("Group ID {$importedEvent->mobilizon_group_id} does not exist.");
                    continue;
                }

                try {
                    $icalData = IcalEventService::readEvents($importedEvent->url, $importedEvent->mobilizon_group_id, false)->getData(true);

                    if (isset($icalData['error'])) {
                        Log::error("Failed to parse iCal for {$importedEvent->url}: " . $icalData['error']);
                        continue;
                    }

                    $eventsToCreate = IcalEventService::buildAcceptedEvents(
                        $mclient,
                        array_map(function ($e) use ($importedEvent) {
                            $importedPhysicalAddressDescription = isset($importedEvent->mobilizon_fields['physicalAddress'])
                                ? ($importedEvent->mobilizon_fields['physicalAddress']['description'] ?? null)
                                : null;

                            $mobilizonFields = [
                                'description' => $e['description'] ?? $importedEvent->mobilizon_fields['description'],
                                'category' => $e['category'] ?? $importedEvent->mobilizon_fields['category'],
                                'status' => $e['status'] ?? $importedEvent->mobilizon_fields['status'],
                                'joinOptions' => $importedEvent->mobilizon_fields['joinOptions'],
                                'externalParticipationUrl' => $importedEvent->mobilizon_fields['joinOptions'] === 'EXTERNAL'
                                    ? $importedEvent->mobilizon_fields['externalParticipationUrl']
                                    : null,
                                'language' => $importedEvent->mobilizon_fields['language'] ?? 'de',
                                'visibility' => $importedEvent->mobilizon_fields['visibility'] ?? 'PUBLIC',
                                'picture' => $e['attachment'],
                                'onlineAddress' => $e['url'] ?? $importedEvent->mobilizon_fields['onlineAddress'] ?? null,
                                'physicalAddress' => $importedPhysicalAddressDescription,
                                'tags' => $importedEvent->mobilizon_fields['tags'],
                            ];

                            return array_merge((array) $e, [
                                'location' => $e['location'] ?? null,
                                'mobilizon_fields' => $mobilizonFields
                            ]);
                        }, $icalData['events']),
                        $importedEvent->mobilizon_group_id,
                        false
                    );

                    Log::info("Processing imported event: { $importedEvent->id } - { $importedEvent->url }");
                    Log::info("Found " . count($eventsToCreate) . " events to process.");

                    $existingEvents = CreatedEvent::where('imported_events_id', $importedEvent->id)
                        ->get()
                        ->keyBy('ical_id');

                    $currentUids = array_keys($eventsToCreate);
                    $deletedUids = array_diff($existingEvents->keys()->toArray(), $currentUids);

                    foreach ($deletedUids as $deletedUid) {
                        $deletedEvent = $existingEvents[$deletedUid];

                        $deletedEvent->delete();
                        $deleteEventResponse = $mclient->deleteEvent($deletedEvent->mobilizon_id);
                        if ($mclient->hasError($deleteEventResponse)) {
                            Log::error("Failed to delete event with UID $deletedUid: " . $mclient->getError($deleteEventResponse));
                        }
                    }

                    $mobilizonFields = $importedEvent->mobilizon_fields;
                    $pictureResponse = null;

                    foreach ($eventsToCreate as $uid => $event) {
                        if ($this->checkDuplicates($uid, $importedEvent->toArray(), $importedEvent->mobilizon_group_id)) {
                            Log::warning("Duplicate event detected for UID $uid in group {$importedEvent->mobilizon_group_id}. Skipping creation.");
                            continue;
                        }

                        $createdEvent = $existingEvents->has($uid) ? 
                            $existingEvents->get($uid) 
                            : new CreatedEvent(); 
                        
                        $createdEvent->user_id = $importedEvent->user_id;
                        $createdEvent->ical_id = $uid;
                        $createdEvent->imported_events_id = $importedEvent->id;
                        $createdEvent->ical_update_sequence = $event['sequence'];
                        unset($event['sequence']);

                        $createdEvent->start = Carbon::parse($event['beginsOn'])->format('Y-m-d');
                        $createdEvent->time = Carbon::parse($event['beginsOn'])->format('H:i');
                        $createdEvent->duration = IcalEventService::calculateDuration($event['beginsOn'], $event['endsOn']);

                        $mresponse = $existingEvents->get($uid) 
                            ? $mclient->updateEvent(array_merge($event, [
                                'eventId' => $existingEvents->get($uid)->mobilizon_id,
                            ]), false)
                            : $mclient->createEvent($event, isset($event['picture']['media']['file']));

                        if ($mclient->hasError($mresponse)) {
                            Log::error("Error creating/updating event for UID $uid: " . $mclient->getError($mresponse));
                            continue;
                        }

                        if (isset($mresponse['data']['createEvent'])) {
                            $createdEvent->mobilizon_uuid = $mresponse['data']['createEvent']['uuid'] ?? null;
                            $createdEvent->mobilizon_id = $mresponse['data']['createEvent']['id'] ?? null;
                        }

                        if (!$pictureResponse && isset($mresponse['data']['createEvent']['picture'])) {
                            $pictureResponse = $mresponse['data']['createEvent']['picture'];
                        }

                        $createdEvent->save();

                        if (array_key_exists('tags', $mobilizonFields) && is_array($mobilizonFields['tags'])) {
                            MobilizonTag::saveTags($mobilizonFields['tags'], (int)$importedEvent->mobilizon_group_id);
                        }
                    }

                    $mobilizonFields['picture'] = $pictureResponse ?? null;
                    $importedEvent->mobilizon_fields = $mobilizonFields;
                    $importedEvent->save();

                    Log::info("Successfully processed imported event: {$importedEvent->id} - {$importedEvent->url}");
                } catch (Throwable $e) {
                    Log::error("Unhandled error: " . $e->getMessage());
                    continue;
                }
            }
        });
    }


    /**
     * Same mobilizon group, same ical_id but not same imported event means duplicate event from another import, 
     * so we should not create a new one
     */
    private function checkDuplicates(string $icalId, array $importedEvent, int $mobilizonGroupId): bool
    {
        return CreatedEvent::where('ical_id', $icalId)
            ->whereHas('imported_event', function ($query) use ($mobilizonGroupId, $importedEvent) {
                $query->where('mobilizon_group_id', $mobilizonGroupId);
                $query->where('id', '!=', $importedEvent['id']);
            })
            ->exists();
    }
}
