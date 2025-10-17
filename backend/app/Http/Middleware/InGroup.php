<?php

namespace App\Http\Middleware;

use App\Models\Mobilizon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InGroup
{
    /**
     * Handle an incoming request.
     *
     */
    public function handle(Request $request, Closure $next): Response
    {
        $groupId = $request->input('mobilizon_group_id');
        if (!$groupId) {
            return response()->json(['error' => 'mobilizon_group_id ist erforderlich.'], 400);
        }

        $mobilizon = Mobilizon::getInstance();

        $groups = $mobilizon->getGroupsAsArray();

        if (!in_array($groupId, array_column($groups, 'id'))) {
            return response()->json(['error' => 'Du bist nicht Teil der Gruppe oder die Gruppe existiert nicht.'], 403);
        }

        return $next($request);
    }
}
