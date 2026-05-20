<?php

namespace App\Services;

use App\Enums\Intervall;
use App\Models\CreatedEvent;
use App\Models\Mobilizon;
use Carbon\Carbon;
use ICal\ICal;
use Illuminate\Support\Facades\Http;
use RRule\RRule;
use Illuminate\Http\JsonResponse;
use rdx\graphqlquery\Query;
use Illuminate\Support\Facades\Log;
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
            if ($checkDuplicates && self::checkDuplicates($event['uid'], $mobilizon_group_id)) {
                continue;
            }

            $eventMobilizonFields = $event['mobilizon_fields']; // mobilizon fields SHOULD BE present in the event

            $events[$event['uid']] = [
                'beginsOn'          => Carbon::parse($event['dtstart'])->toAtomString(),
                'endsOn'            => Carbon::parse($event['dtend'])->toAtomString(),
                'title'             => $event['summary'],
                'description'       => $eventMobilizonFields['description'] ?? $event['summary'],
                'category'          => Query::enum($eventMobilizonFields['category']),
                'joinOptions'       => Query::enum($eventMobilizonFields['joinOptions']),
                'language'          => $eventMobilizonFields['language'],
                'status'            => Query::enum($eventMobilizonFields['status']), // Some icals might not have a status, default to CONFIRMED
                'visibility'        => Query::enum($eventMobilizonFields['visibility']),
                'tags'              => $eventMobilizonFields['tags'],
                'attributedToId'    => $mobilizon_group_id,
                'organizerActorId'  => $mclient->person_id,
                'sequence'          => $event['sequence'] ?? 0,
            ];

            if (isset($event['attachment'])) {
                $events[$event['uid']]['picture'] = $event['attachment'];
            }

            if (isset($eventMobilizonFields['onlineAddress'])) {
                $events[$event['uid']]['onlineAddress'] = $event['mobilizon_fields']['onlineAddress'];
            }

            $events[$event['uid']]['physicalAddress'] = self::resolvePhysicalAddress(
                $mclient,
                $event,
                $eventMobilizonFields
            );

            if ($eventMobilizonFields['joinOptions'] === 'EXTERNAL') {
                $events[$event['uid']]['externalParticipationUrl'] = $eventMobilizonFields['externalParticipationUrl'];
            }
        }

        return $events;
    }

    static public function readEvents(string $ical_try, int $mobilizon_group_id, bool $checkDuplicates = true): JsonResponse
    {
        try {
            $ical = new ICal($ical_try, array(
                'httpUserAgent'               => config('app.url'),
                'defaultTimeZone'             => 'Europe/Berlin',
                'filterDaysBefore'            => 1,
                'filterDaysAfter'             => 365,
            ));

            return response()->json([
                'events' => IcalEventService::adjustEvents($ical->events(), $mobilizon_group_id, $checkDuplicates),
            ]);
        } catch (Throwable $e) {
            Log::error("Error parsing iCal data: " . $e->getMessage());
            return response()->json([
                'error' => 'Error parsing ical: ' . $e->getMessage()
            ], 500);
        }
    }

    private static function adjustEvents(array $events, int $mobilizon_group_id, bool $checkDuplicates = true): array
    {
        $foundIds = [];
        $processedEvents = [];
        $uniqueEvents = array_filter($events, function ($event) use (&$foundIds) {
            if (isset($foundIds[$event->uid])) {
                return false;
            }

            $foundIds[$event->uid] = true;
            return true;
        });

        foreach ($uniqueEvents as $event) {
            $event->url = $event->url ?: null;
            $event->tags = $event->tags ?: [];
            $event->summary = $event->summary ?: 'Unbenanntes Ereignis';
            $event->dtstart = Carbon::parse($event->dtstart)->toAtomString();
            $event->dtend = Carbon::parse($event->dtend)->toAtomString();
            $event->duration = self::calculateDuration($event->dtstart, $event->dtend);

            $event->status = $event->status ?: 'CONFIRMED';
            $event->attachment = self::processAttachments(
                    $event->attach_array ?? $event->attach ?? null, 
                    $event->image_array ?? $event->image ?? null
                );
            
            if ($checkDuplicates) {
                $event->already_exists = self::checkDuplicates($event->uid, $mobilizon_group_id);
            }

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
                    'already_exists'    => false,
                ]
            );
        }

        $instanceIds = array_values(array_filter(array_map(fn($instance) => $instance['uid'] ?? null, $instances)));
        $existingIds = CreatedEvent::whereIn('ical_id', $instanceIds)->pluck('ical_id')->all();
        $existingLookup = array_fill_keys($existingIds, true);

        foreach ($instances as &$instance) {
            $instance['already_exists'] = isset($existingLookup[$instance['uid']]);
        }
        unset($instance);


        return $instances;
    }

    public static function checkDuplicates($ical_id, ?int $mobilizon_group_id): bool
    {
        if (!$ical_id || !$mobilizon_group_id) {
            return false;
        }

        return CreatedEvent::where('ical_id', $ical_id)
            ->where(function ($query) use ($mobilizon_group_id) {
                $query->whereHas('imported_event', function ($q) use ($mobilizon_group_id) {
                    $q->where('mobilizon_group_id', $mobilizon_group_id);
                })->orWhereHas('uploaded_event', function ($q) use ($mobilizon_group_id) {
                    $q->where('mobilizon_group_id', $mobilizon_group_id);
                });
            })
            ->exists();
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

    private static function resolvePhysicalAddress(Mobilizon $mclient, array $event, array $eventMobilizonFields): ?array
    {
        $icalLocation = self::normalizeAddressQuery($event['location'] ?? null);
        $fallbackPhysicalAddress = self::normalizeAddressQuery($eventMobilizonFields['physicalAddress'] ?? null);

        return $mclient->searchAddress($icalLocation)
            ?? $mclient->searchAddress($fallbackPhysicalAddress)
            ?? null;
    }

    private static function normalizeAddressQuery(mixed $address): ?string
    {
        if (is_array($address)) {
            $address = $address['description'] ?? null;
        }

        if (is_string($address)) {
            $address = trim($address);
            return $address !== '' ? $address : null;
        }

        return null;
    }
 
    private static function processAttachments($attachData, $imageData): array|null
    {
        if (empty($attachData) && empty($imageData)) {
            return null;
        }

        $attachData = !is_array($attachData) ? [$attachData] : $attachData;
        $imageData = !is_array($imageData) ? [$imageData] : $imageData;

        // Prioritize image attachments
        $urls = array_filter(
            array_merge($imageData, $attachData), 
            fn($item) => is_string($item) && filter_var($item, FILTER_VALIDATE_URL)
        );

        $maxSizeBytes = config('app.max_image_size_kb', 2048) * 1024;
        foreach ($urls as $url) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders([
                        'User-Agent' => config('app.url') . ' Event Image Fetcher'
                    ])
                    ->head($url);
                $contentLength = $response->header('Content-Length');
                $contentType = $response->header('Content-Type');

                if (!$response->successful()) {
                    Log::warning("Failed to fetch attachment URL: {$url} (HTTP status: {$response->status()}), skipping.");
                    continue;
                }

                if (!str_starts_with($contentType, 'image/')) {
                    Log::warning("URL is not an image: {$url} (Content-Type: {$contentType}), skipping.");
                    continue;
                }

                if ($contentLength && (int)$contentLength > $maxSizeBytes) {
                    Log::warning("Image attachment too large: {$url} ({$contentLength} bytes), skipping.");
                    continue;
                }

                // Extract file extension from content-type
                $extension = match(true) {
                    str_contains($contentType, 'jpeg') || str_contains($contentType, 'jpg') => 'jpg',
                    str_contains($contentType, 'png') => 'png',
                    str_contains($contentType, 'gif') => 'gif',
                    str_contains($contentType, 'webp') => 'webp',
                    str_contains($contentType, 'svg') => 'svg',
                    str_contains($contentType, 'bmp') => 'bmp',
                    str_contains($contentType, 'tiff') => 'tiff',
                    str_contains($contentType, 'avif') => 'avif',
                    str_contains($contentType, 'heic') => 'heic',
                    str_contains($contentType, 'heif') => 'heif',
                    default => pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg'
                };

                $parsedUrl = parse_url($url);
                $path = $parsedUrl['path'] ?? '';
                $filename = basename($path) ?: 'attachment';
                
                if (!str_ends_with(strtolower($filename), '.' . $extension)) {
                    $filename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $extension;
                }
                
                return [
                    'media' => [
                        'name' => $filename,
                        'alt'  => null,
                        'url'  => $url
                    ]
                ];
            } catch (Throwable $e) {
                Log::warning("Failed to check image for {$url}: " . $e->getMessage());
                continue;
            }
        }

        return null;
    }
}
