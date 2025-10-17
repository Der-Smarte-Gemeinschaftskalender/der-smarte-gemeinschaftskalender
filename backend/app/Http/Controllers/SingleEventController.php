<?php

namespace App\Http\Controllers;

use App\Models\CreatedEvent;
use App\Models\Mobilizon;
use App\Models\MobilizonTag;
use App\Models\SingleEvent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Log;
use Throwable;

class SingleEventController extends Controller implements HasMiddleware
{

    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('in_group', except: ['show'])
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('pageSize', 10);

        $page = max($page, 1);
        $pageSize = max($pageSize, 1);

        $query = SingleEvent::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->where('mobilizon_group_id', (int)$request->input('mobilizon_group_id'))
            ->with('created_event');

        $data = $query->paginate(
            $pageSize,
            ['id', 'name'],
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
    public function show(SingleEvent $singleEvent): JsonResponse
    {
        try {
            return response()->json($singleEvent->load('created_event')->load('user'));
        } catch (Throwable $e) {
            Log::error("Error showing single event: " . $e->getMessage());
            return response()->json(['error' => 'Event not found'], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $mobilizonFields = $request->input('mobilizon_fields');
        $mobilizonFields['description'] = $mobilizonFields['description'] ?? '';

        try {
            $mclient = Mobilizon::getInstance();

            $singleEvent = new SingleEvent();
            $singleEvent->name = $request->input('name');
            $singleEvent->user_id = $request->user()->id;
            $singleEvent->mobilizon_group_id = $request->input('mobilizon_group_id');
            $singleEvent->mobilizon_fields = $mobilizonFields;
            $singleEvent->save();

            $eventData = CreatedEventController::buildMobilizonEventData($request, $singleEvent, $mclient);

            $eventStart = Carbon::parse($request->get('start') . ' ' . $request->get('time'));

            $createdEvent = new CreatedEvent();
            $createdEvent->single_events_id = $singleEvent->id;
            $createdEvent->user_id = $request->user()->id;
            $createdEvent->start = $eventStart->format('Y-m-d');
            $createdEvent->time = $eventStart->format('H:i');
            $createdEvent->duration = $request->input('duration');
            $createdEvent->save();

            if (array_key_exists('tags', $mobilizonFields) && is_array($mobilizonFields['tags'])) {
                MobilizonTag::saveTags($mobilizonFields['tags'], (int)$request->get('mobilizon_group_id'));
            }

            $mresponse = $mclient->createEvent($eventData, $request->hasFile('mobilizon_fields.picture.media.file'));
            if ($mclient->hasError($mresponse)) {
                $singleEvent->delete();
                $createdEvent->delete();

                return response()->json([
                    'error' => $mclient->getError($mresponse)
                ], 400);
            } else {
                $createdEvent->mobilizon_uuid = $mresponse['data']['createEvent']['uuid'];
                $createdEvent->mobilizon_id = $mresponse['data']['createEvent']['id'];

                $mobilizonFields['picture'] = $mresponse['data']['createEvent']['picture'] ?? null;
                $singleEvent->mobilizon_fields = $mobilizonFields;

                $singleEvent->save();
                $createdEvent->save();
            }

            return response()->json([
                'singleEvent' => $singleEvent->load('created_event'),
            ]);
        } catch (Throwable $e) {

            Log::error("Error creating single event: " . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create event',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
