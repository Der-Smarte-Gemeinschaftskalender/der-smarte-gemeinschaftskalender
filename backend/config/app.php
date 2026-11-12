<?php

// The default locale is always enabled and comes first, empty entries are dropped.
$supportedLocales = array_values(array_unique([
    env('APP_LOCALE', 'de'),
    ...array_filter(array_map('trim', explode(',', (string) env('APP_SUPPORTED_LOCALES', 'de,en')))),
]));

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'Europe/Berlin'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    // Instance default language. Note: App::setLocale() (SetLocale middleware) overwrites
    // this value per request, so within a request it holds the request language.
    'locale' => env('APP_LOCALE', 'de'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'de'),

    // Languages enabled on this instance (UI, emails, admin email templates), e.g. "de,en".
    'supported_locales' => $supportedLocales,

    // Optional instance name per language (e.g. APP_NAME_EN), used in emails instead of APP_NAME.
    // See App\Support\Localization::appName().
    'localized_names' => array_filter(array_combine(
        $supportedLocales,
        array_map(fn (string $locale) => env('APP_NAME_' . strtoupper($locale)), $supportedLocales),
    )),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    'mobilizon_url' => env('APP_MOBILIZON_URL', 'http://localhost:4000'),
    'mobilizon_access_token' => env('APP_MOBILIZON_ACCESS_TOKEN', 'envtoken'),

    'mobilizon_email' => env('APP_MOBILIZON_EMAIL', ''),
    'mobilizon_password' => env('APP_MOBILIZON_PASSWORD', ''),

    'max_uploaded_event_occurrences' => (int) env('APP_MAX_UPLOADED_EVENT_OCCURRENCES', 1000),
    'max_image_size_kb' => (int) env('APP_MAX_IMAGE_SIZE_KB', 2048),
];
