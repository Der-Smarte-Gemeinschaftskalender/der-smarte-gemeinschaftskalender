<?php

namespace App\Http\Controllers;

use App\Models\Mobilizon;
use App\Models\SeriesEvent;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\CreatedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;
use App\Enums\Intervall;
use App\Models\MobilizonTag;

class SeriesEventController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('in_group', except: ['loadMobilizonGroups', 'getMobilizonGroups', 'show'])
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('pageSize', 10);

        $page = max($page, 1);
        $pageSize = max($pageSize, 1);

        $query = SeriesEvent::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->where('mobilizon_group_id', (int)$request->input('mobilizon_group_id'));

        $data = $query->paginate(
            $pageSize,
            ['id', 'name', 'start', 'end', 'time', 'intervall', 'duration'],
            'page',
            $page
        );

        return response()->json([
            'data' => $data->items(),
            'total' => $data->total(),
            'page' => $page,
            'pageSize' => $pageSize,

        ]);
    }

    public function show(SeriesEvent $seriesEvent): SeriesEvent
    {
        $seriesEvent->load('created_events');
        $seriesEvent->load('user');
        return $seriesEvent;
    }

    public function create(Request $request): JsonResponse
    {
        $intervall = Intervall::toIso($request->get('intervall'));
        $mclient = Mobilizon::getInstance();

        $start = Carbon::parse($request->get('start') . ' ' . $request->get('time'));
        $end = Carbon::parse($request->get('end'));
        $period = CarbonPeriod::create($start, $intervall, $end);

        $durationSeperated = explode(':', $request->input('duration'));
        $durationMinutes = (int)$durationSeperated[0] * 60 + (int)$durationSeperated[1];

        $mobilizonFields = $request->input('mobilizon_fields');
        $mobilizonFields['description'] = $mobilizonFields['description'] ?: '';

        // create series event
        $seriesEvent = new SeriesEvent();
        $seriesEvent->user_id = auth()->user()->id;
        $seriesEvent->mobilizon_fields = $mobilizonFields;
        $seriesEvent->intervall = $request->get('intervall');
        $seriesEvent->mobilizon_group_id = $request->get('mobilizon_group_id');
        $seriesEvent->name = $request->get('name');
        $seriesEvent->start = $request->get('start');
        $seriesEvent->end = $request->get('end');
        $seriesEvent->time = $request->get('time');
        $seriesEvent->duration = $request->get('duration');
        $seriesEvent->save();

        // generate events in series time frame
        foreach ($period as $eventDate) {
            $eventData = CreatedEventController::buildMobilizonEventData($request, $seriesEvent, $mclient, [
                "beginsOn"  => $eventDate->toAtomString(),
                "endsOn"    => $eventDate->copy()->addMinutes($durationMinutes)->toAtomString()
            ]);

            $pictureResponse = null;
            $createdEvent = new CreatedEvent();
            $createdEvent->series_events_id = $seriesEvent->id;
            $createdEvent->user_id = $request->user()->id;
            $createdEvent->start = $eventDate->format('Y-m-d');
            $createdEvent->time = $eventDate->format('H:i');
            $createdEvent->duration = $request->get('duration');
            $createdEvent->save();

            if (array_key_exists('tags', $mobilizonFields) && is_array($mobilizonFields['tags'])) {
                MobilizonTag::saveTags($mobilizonFields['tags'], (int)$request->input('mobilizon_group_id'));
            }

            $mresponse = $mclient->createEvent($eventData, $request->hasFile('mobilizon_fields.picture.media.file'));

            if ($mclient->hasError($mresponse)) {
                $createdEvent->delete();

                return response()->json([
                    'error' => $mclient->getError($mresponse)
                ], 400);
            } else {
                $createdEvent->mobilizon_uuid = $mresponse['data']['createEvent']['uuid'];
                $createdEvent->mobilizon_id = $mresponse['data']['createEvent']['id'];

                if (!$pictureResponse && isset($mresponse['data']['createEvent']['picture'])) {
                    $pictureResponse = $mresponse['data']['createEvent']['picture'];
                }

                $createdEvent->save();
            }
        }

        $mobilizonFields['picture'] = $pictureResponse ?? null;
        $seriesEvent->mobilizon_fields = $mobilizonFields;
        $seriesEvent->save();


        return response()->json([
            'seriesEvent' => $seriesEvent->load('created_events')
        ]);
    }
}
