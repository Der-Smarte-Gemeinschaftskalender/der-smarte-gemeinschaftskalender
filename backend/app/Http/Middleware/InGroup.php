<?php

namespace App\Http\Middleware;

use App\Models\Mobilizon;
use App\Models\SeriesEvent;
use App\Models\CreatedEvent;
use App\Models\ImportedEvent;
use App\Models\UploadedEvent;
use App\Models\SingleEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Log;

class InGroup
{
    /**
     * Handle an incoming request.
     *
     */
    public function handle(Request $request, Closure $next): Response
    {
        /**
         * get id from request route
         */
        $groupId = $this->resolveGroupId($request);

        if (!$groupId) {
            return response()->json(['error' => 'Mobilizon Group ID ist erforderlich.'], 400);
        }

        $mobilizon = Mobilizon::getInstance();

        $groups = $mobilizon->getGroupsAsArray();

        if (!in_array($groupId, array_column($groups, 'id'))) {
            return response()->json(['error' => 'Du bist nicht Teil der Gruppe oder die Gruppe existiert nicht.'], 403);
        }

        return $next($request);
    }

    private function resolveGroupId(Request $request): ?int
    {
        $directGroupId = $request->input('mobilizon_group_id');
        if ($directGroupId) {
            return (int) $directGroupId;
        }

        $eventId = $request->route('seriesEvent')?->id
            ?? $request->route('createdEvent')?->id
            ?? $request->route('importedEvent')?->id
            ?? $request->route('uploadedEvent')?->id
            ?? $request->route('singleEvent')?->id;

        if ($eventId) {
            $actionName = (string) ($request->route()?->getActionName() ?? '');
            $groupId = match (true) {
                str_contains($actionName, 'CreatedEventController')  => CreatedEvent::find($eventId)?->getRelatedEvent()?->mobilizon_group_id,
                str_contains($actionName, 'SingleEventController')   => SingleEvent::find($eventId)?->mobilizon_group_id,
                str_contains($actionName, 'SeriesEventController')   => SeriesEvent::find($eventId)?->mobilizon_group_id,
                str_contains($actionName, 'ImportedEventController') => ImportedEvent::find($eventId)?->mobilizon_group_id,
                str_contains($actionName, 'UploadedEventController') => UploadedEvent::find($eventId)?->mobilizon_group_id,
                default => null,
            };

            return $groupId ? (int) $groupId : null;
        }

        return null;
    }
}
