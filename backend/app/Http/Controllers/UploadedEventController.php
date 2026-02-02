<?php

namespace App\Http\Controllers;

use App\Models\Mobilizon;
use App\Models\MobilizonTag;
use App\Models\UploadedEvent;
use App\Models\CreatedEvent;
use App\Services\IcalEventService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Carbon\Carbon;
use Illuminate\Routing\Controllers\Middleware;
use Log;

class UploadedEventController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('in_group', except: ['getMobilizonGroups', 'getMobilizonTags', 'show'])
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('pageSize', 10);

        $page = max($page, 1);
        $pageSize = max($pageSize, 1);

        $query = UploadedEvent::where('mobilizon_group_id', (int)$request->input('mobilizon_group_id'))
            ->withCount('created_events')
            ->orderBy('created_at', 'desc');

        $data = $query->paginate(
            $pageSize,
            ['id', 'filename', 'created_at'],
            'page',
            $page
        );

        return response()->json([
            'data' => $data->items(),
            'total' => $data->total(),
            'page' => $page,
            'pageSize' => $pageSize
        ]);
    }

    public function create(Request $request): JsonResponse
    {
        $formValues = null;
        if ($request->has('createFromUploadedEvent')) {
            $uploadedEvent = UploadedEvent::find($request->get('createFromUploadedEvent'));
            if ($uploadedEvent) {
                $formValues = $uploadedEvent->mobilizon_fields;
            }
        }
        $mobilizon_groups = $this->getMobilizonGroups();
        $mobilizon_tags = $this->getMobilizonTags();

        return response()->json([
            'mobilizon_groups' => $mobilizon_groups,
            'mobilizon_tags' => $mobilizon_tags,
            'formValues' => $formValues
        ]);
    }

    public function create_url(): JsonResponse
    {
        $mobilizon_groups = $this->getMobilizonGroups();
        $mobilizon_tags = $this->getMobilizonTags();

        return response()->json([
            'mobilizon_groups' => $mobilizon_groups,
            'mobilizon_tags' => $mobilizon_tags
        ]);
    }

    public function show(UploadedEvent $uploadedEvent): JsonResponse
    {
        $uploadedEvent = UploadedEvent::with(['user', 'created_events'])->find($uploadedEvent->id);
        return response()->json([
            'uploadedEvent' => $uploadedEvent
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        Log::info('Received upload request for uploaded events.');
        if ($request->hasFile('uploaded_file')) {
            $request->validate([
                'uploaded_file' => 'required|max:2048|mimes:ics|mimetypes:text/calendar' // 2MB needs to be adjusted later
            ]);

            $ical_try = $request->file('uploaded_file')->getPathName();
        } else {
            return response()->json([
                'error' => 'Keine Daten hochgeladen. Bitte laden Sie eine gültige ICS-Datei hoch.'
            ], 400);
        }

        return IcalEventService::readEvents($ical_try);
    }

    public function accept_upload(Request $request): JsonResponse
    {

        $mclient = Mobilizon::getInstance();
        $events = IcalEventService::buildAcceptedEvents(
            $mclient,
            $request->get('events'),
            $request->get('mobilizon_group_id')
        );

        if (count($events) <= 0) {
            return response()->json([
                'error' => 'No events selected or already created.'
            ], 400);
        }

        // create uploaded event
        $uploadedEvent                      = new UploadedEvent();
        $uploadedEvent->user_id             = $request->user()->id;
        $uploadedEvent->mobilizon_group_id  = $request->get('mobilizon_group_id');
        $uploadedEvent->filename            = $request->get('filename');
        $uploadedEvent->mobilizon_fields    = $request->get('mobilizon_fields');
        $uploadedEvent->save();

        foreach ($events as $uid => $event) {
            if (CreatedEvent::where('ical_id', $uid)->exists()) {
                continue;
            }

            $createdEvent                       = new CreatedEvent();
            $createdEvent->uploaded_events_id   = $uploadedEvent->id;
            $createdEvent->user_id              = $request->user()->id;
            $createdEvent->ical_id              = $uid;
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

            $createdEvent->mobilizon_uuid = $mresponse['data']['createEvent']['uuid'];
            $createdEvent->mobilizon_id = $mresponse['data']['createEvent']['id'];
            $createdEvent->save();

            if (array_key_exists('tags', $uploadedEvent->mobilizon_fields) && is_array($uploadedEvent->mobilizon_fields['tags'])) {
                MobilizonTag::saveTags($uploadedEvent->mobilizon_fields['tags'], (int)$request->input('mobilizon_group_id'));
            }
        }

        return response()->json([
            'success' => 'Events created successfully.'
        ]);
    }

    private function getMobilizonGroups(): array
    {
        $mclient = Mobilizon::getInstance();

        return $mclient->getGroupsAsArray();
    }

    private function getMobilizonTags(): Collection
    {
        return MobilizonTag::all();
    }
}
