<?php declare(strict_types=1);

return [

    'routes' => [
        'enabled' => true,
        'prefix' => '',
        'middleware' => [],
        'locale_pattern' => '[A-Za-z_-]+',
    ],

    'save_missing' => [
        'enabled' => env('I18NEXT_SAVE_MISSING', true),
        'middleware' => [],
    ],

    'cache' => [
        'enabled' => false,
        'store' => null,
        'ttl' => null,
    ],

    'output' => 'flat',

    'namespaces' => false,

];
