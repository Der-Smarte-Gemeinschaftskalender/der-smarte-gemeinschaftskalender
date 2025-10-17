<?php

namespace App\Services;

use App\Enums\Intervall;
use App\Models\CreatedEvent;
use App\Models\Mobilizon;
use App\Models\SeriesEvent;
use Carbon\Carbon;
use ICal\ICal;
use RRule\RRule;
use Illuminate\Http\JsonResponse;
use rdx\graphqlquery\Query;
use Throwable;

class IcalEventService
{

    static public function buildAcceptedEvents(Mobilizon|null $mclient, array $requestedEvents, int|null $mobilizon_group_id, bool $checkDuplicates = true): array
    {

        $events = [];

        if (!$mclient || count($requestedEvents) === 0) {
            return $events;
        }

        foreach ($requestedEvents as $event) {
            if ($checkDuplicates && CreatedEvent::where('ical_id', $event['uid'])->first()) continue;

            $eventMobilizonFields = $event['mobilizon_fields']; // mobilizon fields SHOULD BE present in the event

            $events[$event['uid']] = [
                'beginsOn'          => Carbon::parse($event['dtstart'])->toAtomString(),
                'endsOn'            => Carbon::parse($event['dtend'])->toAtomString(),
                'title'             => $event['summary'],
                'description'       => $eventMobilizonFields['description'] ?? $event['summary'],
                'category'          => Query::enum($eventMobilizonFields['category']),
                'joinOptions'       => Query::enum($eventMobilizonFields['joinOptions']),
                'language'          => $eventMobilizonFields['language'],
                'status'            => Query::enum($eventMobilizonFields['status']),
                'visibility'        => Query::enum($eventMobilizonFields['visibility']),
                'tags'              => $eventMobilizonFields['tags'],
                'attributedToId'    => $mobilizon_group_id,
                'organizerActorId'  => $mclient->person_id,
                'sequence'          => $event['sequence'] ?? 0,
            ];

            if (isset($eventMobilizonFields['onlineAddress'])) {
                $events[$event['uid']]['onlineAddress'] = $event['mobilizon_fields']['onlineAddress'];
            }

            if (isset($eventMobilizonFields['physicalAddress'])) {
                $events[$event['uid']]['physicalAddress'] = $eventMobilizonFields['physicalAddress'];
            }

            if ($eventMobilizonFields['joinOptions'] === 'EXTERNAL') {
                $events[$event['uid']]['externalParticipationUrl'] = $eventMobilizonFields['externalParticipationUrl'];
            }
        }

        return $events;
    }

    static public function readEvents($ical_try): JsonResponse
    {
        try {
            $ical = new ICal($ical_try, array(
                'defaultTimeZone'             => 'Europe/Berlin',
                'filterDaysBefore'            => 1,
                'filterDaysAfter'             => 365,
            ));

            return response()->json([
                'events' => IcalEventService::adjustEvents($ical->events()),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => 'Error parsing ical: ' . $e->getMessage()
            ], 500);
        }
    }

    private static function adjustEvents(array $events): array
    {
        $foundIds = [];
        $processedEvents = [];
        $uniqueEvents = array_filter($events, function ($event) use (&$foundIds) {
            if (in_array($event->uid, $foundIds)) return false;

            $foundIds[] = $event->uid;
            return true;
        });

        foreach ($uniqueEvents as $event) {
            $event->url = $event->url ?: null;
            $event->tags = $event->tags ?: [];
            $event->summary = $event->summary ?: 'Unbenanntes Ereignis';
            $event->dtstart = Carbon::parse($event->dtstart)->toAtomString();
            $event->dtend = Carbon::parse($event->dtend)->toAtomString();
            $event->duration = self::calculateDuration($event->dtstart, $event->dtend);

            $event->already_exists = CreatedEvent::where('ical_id', $event->uid)->exists();

            if (isset($event->rrule)) {
                $rrule = IcalEventService::setRRuleProperties($event);

                if ($rrule && $rrule['FREQ'] !== 'none') {
                    $instances = IcalEventService::populateRecurringEvents($event, $rrule);

                    if (!empty($instances)) {
                        $processedEvents = array_merge($processedEvents, $instances);
                        continue;
                    }
                }
            }

            // pushing non-recurring event
            $processedEvents[] = $event;
        }

        return $processedEvents;
    }

    private static function setRRuleProperties(object $event): ?array
    {
        $rrule = [
            'FREQ'   => 'none',
            'UNTIL'       => '',
            'COUNT'       => '',
            'BYDAY'       => [],
            'BYSETPOS'    => [],
            'BYMONTH'     => [],
            'BYMONTHDAY'  => [],
            'DTSTART' => Carbon::parse($event->dtstart)->toAtomString(),
        ];


        $rruleParts = explode(';', $event->rrule);

        foreach ($rruleParts as $part) {
            [$key, $value] = explode('=', $part, 2);
            $key = strtoupper($key);

            switch ($key) {
                case 'FREQ':
                case 'UNTIL':
                case 'COUNT':
                    $rrule[$key] = $value;
                    break;

                case 'BYDAY':
                    $rrule['BYDAY'] = explode(',', $value);
                    break;

                case 'BYSETPOS':
                case 'BYMONTH':
                case 'BYMONTHDAY':
                    $rrule[$key] = array_map('intval', explode(',', $value));
                    break;
            }
        }

        $rrule['FREQ'] = !Intervall::validate($rrule['FREQ'])
            ? Intervall::defaultInterval()->value
            : $rrule['FREQ'];

        if (empty($rrule['COUNT']) && empty($rrule['UNTIL'])) {
            $startDate = Carbon::parse($rrule['DTSTART']);
            $rrule['UNTIL'] = $startDate->copy()->addYear()->toAtomString();
        }

        return $rrule;
    }

    static private function populateRecurringEvents(object $baseEvent, array $rrule): array
    {
        $instances = [];

        $rule = new RRule($rrule);

        $duration = Carbon::parse($baseEvent->dtstart)->diffInMinutes(Carbon::parse($baseEvent->dtend));

        $maxOccurrences = $rrule['COUNT'] ?: config('app.max_uploaded_event_occurrences');
        $count = 0;
        foreach ($rule as $occurrence) {
            if ($count++ >= $maxOccurrences) break;

            $occurrence = Carbon::parse($occurrence);
            $uniqueId = $baseEvent->uid . '__' . $occurrence->format('Ymd\THis');

            $instances[] = array_merge(
                (array) $baseEvent,
                [
                    'uid'               => $uniqueId,
                    'original_uid'      => $baseEvent->uid,
                    'dtstart'           => $occurrence->toAtomString(),
                    'dtend'             => $occurrence->copy()->addMinutes($duration)->toAtomString(),
                    'already_exists'    => CreatedEvent::where('ical_id', $uniqueId)->exists()
                ]
            );
        }


        return $instances;
    }

    public static function calculateDuration($start, $end): string
    {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        $diffInMinutes = $start->diffInMinutes($end);
        $hours = floor($diffInMinutes / 60);
        $minutes = $diffInMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
