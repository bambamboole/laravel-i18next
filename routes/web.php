<?php declare(strict_types=1);

use Bambamboole\LaravelI18Next\Http\Controller\FetchTranslationsController;
use Bambamboole\LaravelI18Next\Http\Controller\StoreMissingTranslationsController;
use Illuminate\Support\Facades\Route;

Route::post('/locales/add/{locale}/translation', StoreMissingTranslationsController::class)->name('i18next.store');
Route::get('/locales/{locale}/translation.json', FetchTranslationsController::class)->name('i18next.fetch');
