<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{

    public function handle(Request $request, Closure $next): JsonResponse|Response
    {
        if (auth()->user()->type !== 'admin') {
            return response()->json(['error' => 'Zugriff verweigert. Admin-Rechte erforderlich.'], 403);
        }
        return $next($request);
    }
}
