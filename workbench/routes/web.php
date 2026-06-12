<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;

if (app()->runningUnitTests()) {
    return;
}

app()->useLangPath(dirname(__DIR__, 2).'/tests/Fixtures/lang');

Route::get('/', fn () => redirect('/i18next-demo'));

Route::get('/i18next-demo', fn () => response(
    (string) file_get_contents(__DIR__.'/../resources/demo.html'),
));

Route::get('/i18next-demo/assets/{file}', function (string $file) {
    $assets = [
        'i18next.js' => 'i18next/dist/umd/i18next.min.js',
        'i18next-http-backend.js' => 'i18next-http-backend/i18nextHttpBackend.min.js',
        'i18next-intervalplural-postprocessor.js' => 'i18next-intervalplural-postprocessor/i18nextIntervalPluralPostProcessor.min.js',
    ];
    abort_unless(isset($assets[$file]), 404);

    return response(
        (string) file_get_contents(dirname(__DIR__, 2).'/node_modules/'.$assets[$file]),
        200,
        ['Content-Type' => 'application/javascript'],
    );
})->where('file', '[A-Za-z0-9._-]+');
