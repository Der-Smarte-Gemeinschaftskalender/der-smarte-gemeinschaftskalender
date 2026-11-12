<?php

namespace App\Http\Middleware;

use App\Support\Localization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        // The default locale always comes first, so getPreferredLanguage() falls back to it
        // when the header is missing or contains no supported language.
        $supportedLocales = config('app.supported_locales');
        $preferred = $request->getPreferredLanguage($supportedLocales);

        // getPreferredLanguage() normalizes the format (e.g. "en-GB" -> "en_GB"), so only accept exact matches.
        $locale = Localization::isSupported($preferred) ? $preferred : $supportedLocales[0];

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
