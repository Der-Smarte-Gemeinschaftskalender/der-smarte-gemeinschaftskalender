<?php

namespace App\Http\Controllers;

use App\Models\Mobilizon;
use App\Models\MobilizonTag;
use App\Jobs\FetchImportedEvents;
use App\Models\ImportedEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Log;

class ImportedEventController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            'in_group'
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->input('page', 1);
        $pageSize = (int) $request->input('pageSize', 10);

        $page = max($page, 1);
        $pageSize = max($pageSize, 1);

        $query = ImportedEvent::where('mobilizon_group_id', (int)$request->input('mobilizon_group_id'))
            ->withCount('created_events')
            ->orderBy('created_at', 'desc');

        $data = $query->paginate(
            $pageSize,
            ['id', 'name', 'start', 'end', 'time', 'intervall'],
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

    public function show(ImportedEvent $importedEvent): JsonResponse
    {
        $importedEvent = ImportedEvent::with(['user', 'created_events'])->find($importedEvent->id);
        return response()->json([
            'importedEvent' => $importedEvent
        ]);
    }


    public function store(Request $request): JsonResponse
    {

        if (!$request->has('mobilizon_fields')) {
            return response()->json(['error' => 'Ungültige Mobilizon-Felder'], 400);
        }

        if (!$request->has('url')) {
            return response()->json(['error' => 'URL ist erforderlich'], 400);
        }

        $mobilizonFields = $request->input('mobilizon_fields');
        $mobilizonFields['description'] = $mobilizonFields['description'] ?? '';

        $importedEvent = new ImportedEvent();
        $importedEvent->url = $request->input('url');
        $importedEvent->is_active = 1;
        $importedEvent->user_id = auth()->user()->id;
        $importedEvent->mobilizon_group_id = (int)$request->input('mobilizon_group_id');
        $importedEvent->mobilizon_fields = $mobilizonFields;
        $importedEvent->save();

        if (array_key_exists('tags', $mobilizonFields) && is_array($mobilizonFields['tags'])) {
            MobilizonTag::saveTags($mobilizonFields['tags'], (int)$request->input('mobilizon_group_id'));
        }

        FetchImportedEvents::dispatch($importedEvent->id);

        return response()->json([
            'importedEvent' => $importedEvent
        ]);
    }

    public function delete(ImportedEvent $importedEvent): JsonResponse
    {
        $mclient = Mobilizon::getInstance();
        foreach ($importedEvent->created_events as $createdEvent) {
            $mresponse = $mclient->deleteEvent($createdEvent->mobilizon_id);

            if ($mclient->hasError($mresponse) && $mclient->getError($mresponse) !== 'Veranstaltung nicht gefunden') {
                Log::error("Error deleting event with Mobilizon ID {$createdEvent->mobilizon_id}: " . $mclient->getError($mresponse));
                return response()->json([
                    'error' => "Fehler beim Löschen der Veranstaltung mit Mobilizon ID {$createdEvent->mobilizon_id}: " . $mclient->getError($mresponse)
                ]);
            }

            $createdEvent->delete();
        }

        $importedEvent->delete();

        return response()->json([
            'message' => 'Erfolgreich gelöscht.'
        ]);
    }

    public function changeStatus(ImportedEvent $importedEvent, Request $request): JsonResponse
    {
        $importedEvent->is_active = $request->input('status') === 'active' ? 1 : 0;
        $importedEvent->save();

        return response()->json([
            'importedEvent' => $importedEvent
        ]);
    }
}
