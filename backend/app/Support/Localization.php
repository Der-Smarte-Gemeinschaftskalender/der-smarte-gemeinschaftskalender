<?php

namespace App\Support;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;

final class Localization
{
    /**
     * Whether the language is enabled on this instance (APP_SUPPORTED_LOCALES).
     */
    public static function isSupported(?string $locale): bool
    {
        return in_array($locale, config('app.supported_locales'), true);
    }

    /**
     * Instance name for display in the given language (default: the current locale).
     * APP_NAME_<LOCALE> (e.g. APP_NAME_EN) if set, otherwise APP_NAME.
     */
    public static function appName(?string $locale = null): string
    {
        return config('app.localized_names')[$locale ?? App::getLocale()] ?? config('app.name');
    }

    /**
     * Translation of the key in the current locale, or the fallback (e.g. a raw database value)
     * if there is no translation for it.
     */
    public static function translateOr(string $key, string $fallback): string
    {
        return Lang::has($key) ? __($key) : $fallback;
    }
}
