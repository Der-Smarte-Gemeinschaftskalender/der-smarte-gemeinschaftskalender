<?php

namespace App\Http\Controllers;


use App\Jobs\FetchImportedEvents;
use App\Models\ImportedEvent;
use App\Models\MobilizonTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ImportedEventController extends Controller implements HasMiddleware
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

        $query = ImportedEvent::where('user_id', $request->user()->id)
            ->withCount('created_events')
            ->orderBy('created_at', 'desc')
            ->where('mobilizon_group_id', (int)$request->input('mobilizon_group_id'));

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
            return response()->json(['error' => 'Mobilizon fields are required'], 400);
        }

        if (!$request->has('url')) {
            return response()->json(['error' => 'URL is required'], 400);
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

    public function changeStatus(ImportedEvent $importedEvent, Request $request): JsonResponse
    {
        $importedEvent->is_active = $request->input('status') === 'active' ? 1 : 0;
        $importedEvent->save();

        return response()->json([
            'importedEvent' => $importedEvent
        ]);
    }
}
