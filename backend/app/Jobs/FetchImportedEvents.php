<?php

namespace App\Jobs;

use App\Models\CreatedEvent;
use App\Models\ImportedEvent;
use App\Models\Mobilizon;
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
        $groups = $mclient->getGroupsAsArray();

        $importedEventsQuery = $this->eventId === -1
            ? ImportedEvent::where('is_active', true)
            : ImportedEvent::where('id', $this->eventId);

        $importedEventsQuery->chunk(20, function ($importedEvents) use ($mclient, $groups) {
            foreach ($importedEvents as $importedEvent) {
                if (!isset($groups[$importedEvent->mobilizon_group_id])) {
                    Log::warning("Group ID {$importedEvent->mobilizon_group_id} does not exist.");
                    continue;
                }

                try {
                    $icalData = IcalEventService::readEvents($importedEvent->url)->getData(true);

                    if (isset($icalData['error'])) {
                        Log::error("Failed to parse iCal for {$importedEvent->url}: " . $icalData['error']);
                        continue;
                    }

                    $eventsToCreate = IcalEventService::buildAcceptedEvents(
                        $mclient,
                        array_map(function ($e) use ($importedEvent) {
                            return array_merge((array) $e, [
                                'mobilizon_fields' => [
                                    'description' => $e['description'] ?? $importedEvent->mobilizon_fields['description'],
                                    'category' => $e['category'] ?? $importedEvent->mobilizon_fields['category'],
                                    'status' => $e['status'] ?? $importedEvent->mobilizon_fields['status'],
                                    'joinOptions' => $importedEvent->mobilizon_fields['joinOptions'],
                                    'externalParticipationUrl' => $importedEvent->mobilizon_fields['joinOptions'] === 'EXTERNAL'
                                        ? $importedEvent->mobilizon_fields['externalParticipationUrl']
                                        : null,
                                    'language' => $importedEvent->mobilizon_fields['language'] ?? 'de',
                                    'visibility' => $importedEvent->mobilizon_fields['visibility'] ?? 'PUBLIC',
                                    'onlineAddress' => $e['url'] ?? $importedEvent->mobilizon_fields['onlineAddress'] ?? null,
                                    'physicalAddress' => $importedEvent->mobilizon_fields['physicalAddress'] ?? null,
                                    'tags' => $importedEvent->mobilizon_fields['tags'],
                                ]
                            ]);
                        }, $icalData['events']),
                        $importedEvent->mobilizon_group_id,
                        false
                    );

                    Log::info("Processing imported event: { $importedEvent->id } - { $importedEvent->url }");
                    Log::info("Found " . count($eventsToCreate) . " events to create.");

                    $existingEvents = CreatedEvent::where('imported_events_id', $importedEvent->id)->get()->keyBy('ical_id');
                    $currentUids = array_keys($eventsToCreate);

                    $deletedUids = array_diff($existingEvents->keys()->toArray(), $currentUids);

                    foreach ($deletedUids as $deletedUid) {
                        $deletedEvent = $existingEvents[$deletedUid];
                        Log::info("Deleting event no longer in iCal feed: {$deletedUid}");

                        // Optional: delete or mark as deleted (soft delete or set `is_active = false`, etc.)
                        $deletedEvent->delete();
                    }


                    foreach ($deletedUids as $deletedUid) {
                        $deletedEvent = $existingEvents[$deletedUid];
                        Log::info("Deleting event no longer in iCal feed: {$deletedUid}");

                        // Optional: delete or mark as deleted (soft delete or set `is_active = false`, etc.)
                        $deletedEvent->delete();
                    }

                    foreach ($eventsToCreate as $uid => $event) {
                        if (CreatedEvent::where('ical_id', $uid)->exists()) {
                            continue;
                        }

                        $createdEvent = new CreatedEvent();
                        $createdEvent->user_id = $importedEvent->user_id;
                        $createdEvent->ical_id = $uid;
                        $createdEvent->imported_events_id = $importedEvent->id;
                        $createdEvent->ical_update_sequence = $event['sequence'];
                        unset($event['sequence']);

                        $createdEvent->start = Carbon::parse($event['beginsOn'])->format('Y-m-d');
                        $createdEvent->time = Carbon::parse($event['beginsOn'])->format('H:i');
                        $createdEvent->duration = IcalEventService::calculateDuration($event['beginsOn'], $event['endsOn']);
                        $createdEvent->save();

                        $mresponse = $mclient->createEvent($event, false);
                        if ($mclient->hasError($mresponse)) {
                            Log::error("Error creating event for UID $uid: " . $mclient->getError($mresponse));
                            $createdEvent->delete();
                            continue;
                        }

                        $createdEvent->mobilizon_uuid = $mresponse['data']['createEvent']['uuid'] ?? null;
                        $createdEvent->mobilizon_id = $mresponse['data']['createEvent']['id'] ?? null;
                        $createdEvent->save();
                    }

                    Log::info("Successfully processed imported event: {$importedEvent->id} - {$importedEvent->url}");
                } catch (Throwable $e) {
                    Log::error("Unhandled error: " . $e->getMessage());
                    continue;
                }
            }
        });
    }
}
