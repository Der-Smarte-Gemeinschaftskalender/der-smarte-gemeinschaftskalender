<?php

namespace App\Http\Controllers;

use App\Enums\MobilizonEventStatus;
use App\Models\CreatedEvent;
use App\Models\SeriesEvent;
use App\Models\UploadedEvent;
use App\Models\ImportedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

class EventController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return ['auth'];
    }

    public function index(Request $request): JsonResponse
    {
        $events = CreatedEvent::all();

        $singleEventsCount   = $events->filter(fn ($e) => $e->single_events_id !== null)->count();
        $seriesEventsCount   = $events->filter(fn ($e) => $e->series_events_id !== null)->count();
        $uploadedEventsCount = $events->filter(fn ($e) => $e->uploaded_events_id !== null)->count();
        $importedEventsCount = $events->filter(fn ($e) => $e->imported_events_id !== null)->count();

        $confirmedEvents = $events->filter(fn ($e) => $e->getStatus() === MobilizonEventStatus::CONFIRMED->value)->count();
        $tentativeEvents = $events->filter(fn ($e) => $e->getStatus() === MobilizonEventStatus::TENTATIVE->value)->count();
        $cancelledEvents = $events->filter(fn ($e) => $e->getStatus() === MobilizonEventStatus::CANCELLED->value)->count();

        $incomingEvents = $events->filter(fn ($e) => $e->start >= now());

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
}
