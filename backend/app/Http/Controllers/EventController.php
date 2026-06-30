<?php

namespace App\Http\Controllers;

use App\Enums\MobilizonEventStatus;
use App\Models\CreatedEvent;
use App\Models\Mobilizon;
use App\Models\SeriesEvent;
use App\Models\SingleEvent;
use App\Models\UploadedEvent;
use App\Models\ImportedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class EventController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('in_group', only: ['suggestedAddresses']),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $events = CreatedEvent::all();

        $singleEventsCount   = $events->filter(fn($e) => $e->single_events_id !== null)->count();
        $seriesEventsCount   = $events->filter(fn($e) => $e->series_events_id !== null)->count();
        $uploadedEventsCount = $events->filter(fn($e) => $e->uploaded_events_id !== null)->count();
        $importedEventsCount = $events->filter(fn($e) => $e->imported_events_id !== null)->count();

        $confirmedEvents = $events->filter(fn($e) => $e->getStatus() === MobilizonEventStatus::CONFIRMED->value)->count();
        $tentativeEvents = $events->filter(fn($e) => $e->getStatus() === MobilizonEventStatus::TENTATIVE->value)->count();
        $cancelledEvents = $events->filter(fn($e) => $e->getStatus() === MobilizonEventStatus::CANCELLED->value)->count();

        $incomingEvents = $events->filter(fn($e) => $e->start >= now());

        return response()->json([
            'singleEventsCount'     => $singleEventsCount,
            'seriesEventsCount'     => $seriesEventsCount,
            'uploadedEventsCount'   => $uploadedEventsCount,
            'importedEventsCount'   => $importedEventsCount,
            'confirmedEventsCount'  => $confirmedEvents,
            'tentativeEventsCount'  => $tentativeEvents,
            'cancelledEventsCount'  => $cancelledEvents,
            'incomingEventsCount'   => $incomingEvents,
            'test'                  => $events[0]->getRelatedEvent()->mobilizon_fields['status'] === MobilizonEventStatus::CONFIRMED,
            'confirmed'             => MobilizonEventStatus::CONFIRMED
        ]);
    }

    public function suggestedAddresses(Request $request): JsonResponse
    {
        $groupId = (int) $request->input('mobilizon_group_id');

        $frequencies = [];

        foreach ([SingleEvent::class, SeriesEvent::class, ImportedEvent::class, UploadedEvent::class] as $eventModel) {
            foreach ($eventModel::where('mobilizon_group_id', $groupId)->pluck('mobilizon_fields') as $fields) {
                $address = is_array($fields) ? ($fields['physicalAddress'] ?? null) : null;

                if (!is_array($address) || empty($address['geom'])) {
                    continue;
                }

                // Nach Adresstext zusammenfassen, nicht nach geom – dieselbe
                // Adresse kann je Termin minimal abweichende Koordinaten haben.
                $key = mb_strtolower(trim(implode('|', array_filter([
                    $address['street'] ?? '',
                    $address['postalCode'] ?? '',
                    $address['locality'] ?? '',
                ])))) ?: $address['geom'];

                $frequencies[$key]['address'] ??= $address;
                $frequencies[$key]['count'] = ($frequencies[$key]['count'] ?? 0) + 1;
            }
        }

        usort($frequencies, fn($a, $b) => $b['count'] <=> $a['count']);

        $group = Mobilizon::getInstance()->getGroup($groupId);

        return response()->json([
            'organisation_address' => is_array($group) ? ($group['physicalAddress'] ?? null) : null,
            'frequent_addresses'   => array_map(fn($entry) => $entry['address'], array_slice($frequencies, 0, 2)),
        ]);
    }
}
