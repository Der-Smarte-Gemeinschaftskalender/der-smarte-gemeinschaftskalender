<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const SUPPORTED_LOCALES = ['de', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $default = config('app.locale');

        // The default locale goes first, so getPreferredLanguage() falls back to it
        // when the header is missing or contains no supported language.
        $candidates = array_values(array_unique([$default, ...self::SUPPORTED_LOCALES]));
        $preferred = $request->getPreferredLanguage($candidates);

        $locale = in_array($preferred, self::SUPPORTED_LOCALES, true) ? $preferred : $default;

        // App::setLocale() also overwrites config('app.locale'), so restore the previous
        // locale afterwards to not leak it into subsequent requests of the same process.
        $previousLocale = App::getLocale();
        App::setLocale($locale);

        try {
            return $next($request);
        } finally {
            App::setLocale($previousLocale);
        }
    }
}
