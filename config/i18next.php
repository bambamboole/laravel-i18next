<?php declare(strict_types=1);

return [

    /*
     |--------------------------------------------------------------------------
     | Routes
     |--------------------------------------------------------------------------
     |
     | The package registers a GET route to fetch translations and a POST route
     | to store missing ones. Adjust the prefix, middleware and the pattern the
     | {locale} parameter must match (the pattern also guards against path
     | traversal, so keep it restrictive).
     |
     */
    'routes' => [
        'enabled' => true,
        'prefix' => '',
        'middleware' => [],
        'locale_pattern' => '[A-Za-z_-]+',
    ],

    /*
     |--------------------------------------------------------------------------
     | Saving missing translations
     |--------------------------------------------------------------------------
     |
     | The store route writes translation files to disk. It is convenient in
     | development but you usually do not want it reachable in production, so it
     | can be disabled and given its own middleware (e.g. auth/throttle).
     |
     */
    'save_missing' => [
        'enabled' => env('I18NEXT_SAVE_MISSING', true),
        'middleware' => [],
    ],

    /*
     |--------------------------------------------------------------------------
     | Caching
     |--------------------------------------------------------------------------
     |
     | The fetch route walks the lang directory and converts the files on every
     | request. Enable caching to serve the converted payload from the cache
     | instead; it is flushed automatically when missing translations are saved.
     |
     */
    'cache' => [
        'enabled' => false,
        'store' => null,
        'ttl' => null,
    ],

];
