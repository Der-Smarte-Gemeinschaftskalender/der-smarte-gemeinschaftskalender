<?php

namespace App\Http\Controllers;

use App\Models\CreatedEvent;
use App\Models\Mobilizon;
use App\Models\SeriesEvent;
use App\Models\SingleEvent;
use Carbon\Carbon;
use Http;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;
use rdx\graphqlquery\Query;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CreatedEventController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('in_group', except: ['show', 'fetchEventImage', 'getEventId', 'delete'])
        ];
    }

    static public function buildMobilizonEventData(Request $request, SingleEvent|SeriesEvent $event, Mobilizon $mclient, array $customDateTimes = []): array
    {
        $mobilizonFields = $request->input('mobilizon_fields');
        $eventStart = Carbon::parse($request->get('start') . ' ' . $request->get('time'));

        $durationSeparated = explode(':', $request->input('duration'));
        $durationMinutes = (int)$durationSeparated[0] * 60 + (int)$durationSeparated[1];

        $eventData = [
            "beginsOn"         => $customDateTimes['beginsOn'] ?? $eventStart->toAtomString(),
            "endsOn"           => $customDateTimes['endsOn'] ?? $eventStart->copy()->addMinutes($durationMinutes)->toAtomString(),
            "title"            => $event->name,
            "description"      => $mobilizonFields['description'] ?? '',
            "category"         => Query::enum($mobilizonFields['category']),
            "joinOptions"      => Query::enum($mobilizonFields['joinOptions']),
            "language"         => $mobilizonFields['language'],
            "status"           => Query::enum($mobilizonFields['status']),
            "visibility"       => Query::enum($mobilizonFields['visibility']),
            "attributedToId"   => $request->get('mobilizon_group_id'),
            "organizerActorId" => $mclient->person_id,
            "tags"             => $mobilizonFields['tags'] ?? [],
        ];

        if ($request->hasFile('mobilizon_fields.picture.media.file')) {
            $eventData['picture'] = $mobilizonFields['picture'];
            $eventData['picture']['media']['file'] = $request->file('mobilizon_fields.picture.media.file');
        } elseif ($request->get('mobilizon_fields.picture.media.url')) {
            $eventData['picture'] = $mobilizonFields['picture'];
            $eventData['picture']['media']['url'] = $request->get('mobilizon_fields.picture.media.url');
        }

        if (isset($mobilizonFields['onlineAddress'])) {
            if (!preg_match('#^https?://#', $mobilizonFields['onlineAddress'])) {
                $mobilizonFields['onlineAddress'] = 'https://' . $mobilizonFields['onlineAddress'];
            }
            $eventData['onlineAddress'] = $mobilizonFields['onlineAddress'];
        }

        if (isset($mobilizonFields['physicalAddress'])) {
            $eventData['physicalAddress'] = $mobilizonFields['physicalAddress'];
        }

        if ($mobilizonFields['joinOptions'] === 'EXTERNAL') {
            if (isset($mobilizonFields['externalParticipationUrl']) && !preg_match('#^https?://#', $mobilizonFields['externalParticipationUrl'])) {
                $mobilizonFields['externalParticipationUrl'] = 'https://' . $mobilizonFields['externalParticipationUrl'];
            }

            $eventData['externalParticipationUrl'] = $mobilizonFields['externalParticipationUrl'];
        }

        return $eventData;
    }


    public function show(CreatedEvent $createdEvent): JsonResponse
    {
        $mclient = Mobilizon::getInstance();
        $mobilizon_fields = $mclient->getEvent($createdEvent->mobilizon_uuid)['data']['event'] ?? [];
        $createdEvent->load([
            'user',
            'series_event',
            'single_event',
            'uploaded_event',
            'imported_event',
        ]);

        $event = null;
        $eventType = null;

        // Determine which event exists and its type
        $possibleTypes = [
            'series_event',
            'single_event',
            'uploaded_event',
            'imported_event',
        ];

        foreach ($possibleTypes as $type) {
            if ($createdEvent->$type) {
                $event = $createdEvent->$type;
                $eventType = $type;
            } else unset($createdEvent[$type . 's_id']);

            unset($createdEvent->$type);
        }

        return response()->json([
            'mobilizon_fields' => $mobilizon_fields,
            'createdEvent' => $createdEvent,
            'event' => $event,
            'eventType' => $eventType,
        ]);
    }

    public function update(CreatedEvent $createdEvent, Request $request): JsonResponse
    {
        $mclient = Mobilizon::getInstance();

        $eventStart = Carbon::parse($request->get('start') . ' ' . $request->get('time'));
        $mobilizonFields = $request->input('mobilizon_fields');

        $durationSeperated = explode(':', $request->input('duration'));
        $durationMinutes = (int)$durationSeperated[0] * 60 + (int)$durationSeperated[1];


        $eventData = [
            "eventId" => $request->input('mobilizon_id'),
            "title" => $request->input('name'),
            "beginsOn" => $eventStart->toAtomString(),
            "endsOn" => $eventStart->copy()->addMinutes($durationMinutes)->toAtomString(),
            "description" => $request->input('mobilizon_fields')['description'] ?? $request->input('name'),
            "category" => Query::enum($mobilizonFields['category']),
            "joinOptions" => Query::enum($mobilizonFields['joinOptions']),
            "language" => $mobilizonFields['language'],
            "tags" => $mobilizonFields['tags'] ?? [],
        ];

        if ($request->hasFile('mobilizon_fields.picture.media.file')) {
            $eventData['picture'] = $mobilizonFields['picture'];
            $eventData['picture']['media']['file'] = $request->file('mobilizon_fields.picture.media.file');
        } else if ($request->get('mobilizon_fields.picture.media.url')) {
            $eventData['picture'] = $mobilizonFields['picture'];
            $eventData['picture']['media']['url'] = $request->get('mobilizon_fields.picture.media.url');
        }
        else {
            $eventData['picture'] = null;
        }

        if (isset($mobilizonFields['onlineAddress'])) {
            if (!preg_match('#^https?://#', $mobilizonFields['onlineAddress'])) {
                $mobilizonFields['onlineAddress'] = 'https://' . $mobilizonFields['onlineAddress'];
            }
            $eventData['onlineAddress'] = $mobilizonFields['onlineAddress'];
        }
        else {
            $eventData['onlineAddress'] = null;
        }

        if (isset($mobilizonFields['physicalAddress'])) {
            $eventData['physicalAddress'] = $mobilizonFields['physicalAddress'];
        }
        else {
            $eventData['physicalAddress'] = null;
        }

        if ($mobilizonFields['joinOptions'] === 'EXTERNAL') {
            if (isset($mobilizonFields['externalParticipationUrl']) && !preg_match('#^https?://#', $mobilizonFields['externalParticipationUrl'])) {
                $mobilizonFields['externalParticipationUrl'] = 'https://' . $mobilizonFields['externalParticipationUrl'];
            }

            $eventData['externalParticipationUrl'] = $mobilizonFields['externalParticipationUrl'];
        }
        else {
            $eventData['externalParticipationUrl'] = null;
        }

        $mresponse = $mclient->updateEvent($eventData, $request->hasFile('mobilizon_fields.picture.media.file'));
        if ($mclient->hasError($mresponse)) {
            return response()->json([
                'error' => $mclient->getError($mresponse)
            ], 400);
        } else {

            $createdEvent->start = Carbon::parse($eventStart)->format('Y-m-d'); // no Timezone
            $createdEvent->time = Carbon::parse($eventStart)->format('H:i');
            $createdEvent->duration = $request->input('duration');

            // if image changed, update the picture
            $mobilizonFields['picture'] = $mresponse['data']['updateEvent']['picture'];
            $createdEvent->save();

            if ($createdEvent->single_events_id) {
                $createdEvent->single_event->name = $eventData['title'];
                $createdEvent->single_event->mobilizon_fields = $mobilizonFields;
                $createdEvent->single_event->save();
            }
        }
        return response()->json([
            'createdEvent' => $createdEvent->load('user')
        ]);
    }

    public function delete(int $createdEvent): JsonResponse
    {
        $createdEvent = CreatedEvent::find($createdEvent);
        $mclient = Mobilizon::getInstance();
        $mresponse = $mclient->deleteEvent($createdEvent->mobilizon_id);
        if ($mclient->hasError($mresponse)) {
            return response()->json([
                'error' => $mclient->getError($mresponse)
            ], 400);
        }
        $createdEvent->delete();
        if ($createdEvent->single_events_id) {
            $createdEvent->single_event->delete();
        }
        return response()->json([
            'message' => 'Created event deleted successfully'
        ]);
    }

    public function updateStatus(CreatedEvent $createdEvent, Request $request): JsonResponse
    {
        $mclient = Mobilizon::getInstance();

        $eventData = [
            "eventId" => $request->input('mobilizon_id'),
            "status" => Query::enum($request->input('mobilizon_fields')['status']),
        ];

        $mresponse = $mclient->updateEvent($eventData, false);
        if ($mclient->hasError($mresponse)) {
            return response()->json([
                'error' => $mclient->getError($mresponse)
            ], 400);
        } else {
            $createdEvent->save();
        }
        return response()->json([
            'createdEvent' => $createdEvent->load('user')
        ]);
    }

    public function getEventId(Request $request): JsonResponse
    {
        $uuid = $request->input('uuid');
        $createdEvent = CreatedEvent::where('mobilizon_uuid', $uuid)->first();
        if (!$createdEvent) {
            return response()->json(['error' => 'Created event not found'], 404);
        }
        return response()->json($createdEvent->only('id'));
    }


    public function fetchEventImage(CreatedEvent $createdEvent): JsonResponse|StreamedResponse|BinaryFileResponse
    {
        $mclient = Mobilizon::getInstance();
        $mresponse = $mclient->getEventImage($createdEvent->mobilizon_uuid);

        $imageUrl = $mresponse['data']['event']['picture']['url'] ?? null;

        if (!$imageUrl) {
            return response()->json(['error' => 'Image not found'], 404);
        }

        try {
            $imageResponse = $this->tryFetchImage($imageUrl);

            if (!$imageResponse->successful()) {
                // replace http with https or vice versa
                $fallbackUrl = str_starts_with($imageUrl, 'https://')
                    ? str_replace('https://', 'http://', $imageUrl)
                    : str_replace('http://', 'https://', $imageUrl);

                Log::warning("HTTPS image fetch failed, retrying with HTTP", [
                    'https_url' => $imageUrl,
                    'status'    => $imageResponse->status(),
                    'fallback'  => $fallbackUrl,
                ]);

                $imageResponse = $this->tryFetchImage($fallbackUrl);

                if (!$imageResponse->successful()) {
                    return response()->json(['error' => 'Failed to fetch image'], 502);
                }

                $imageUrl = $fallbackUrl;
            }

            $contentType = $imageResponse->header('Content-Type') ?? 'image/jpeg';
            $fileName = basename(parse_url($imageUrl, PHP_URL_PATH)) ?: 'event_image.jpg';

            return response()->stream(function () use ($imageResponse) {
                echo $imageResponse->body();
            }, 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            ]);
        } catch (Throwable $e) {
            Log::error("Unexpected error while fetching event image", [
                'url'   => $imageUrl,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Unexpected error'], 500);
        }
    }

    private function tryFetchImage(string $url): Response
    {
        return Http::retry(3, 200)
            ->timeout(5)
            ->get($url);
    }
}
