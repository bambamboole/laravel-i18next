<?php declare(strict_types=1);

namespace Bambamboole\LaravelI18Next;

use Bambamboole\LaravelI18Next\Http\Controller\FetchTranslationsController;
use Bambamboole\LaravelI18Next\Http\Controller\StoreMissingTranslationsController;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class I18NextServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('i18next')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(
            I18NextTranslationsLoader::class,
            fn (Application $app) => new I18NextTranslationsLoader(
                new Filesystem,
                $app->make('translation.loader'),
                $app->langPath(),
            ),
        );
    }

    public function packageBooted(): void
    {
        $this->registerRoutes();
    }

    protected function registerRoutes(): void
    {
        $config = $this->app['config'];

        if (! $config->get('i18next.routes.enabled', true)) {
            return;
        }

        $pattern = $config->get('i18next.routes.locale_pattern', '[A-Za-z_-]+');

        Route::group([
            'prefix' => $config->get('i18next.routes.prefix', ''),
            'middleware' => $config->get('i18next.routes.middleware', []),
        ], function () use ($config, $pattern): void {
            Route::get('locales/{locale}/translation.json', FetchTranslationsController::class)
                ->where('locale', $pattern)
                ->name('i18next.fetch');

            if ($config->get('i18next.save_missing.enabled', true)) {
                Route::post('locales/add/{locale}/translation', StoreMissingTranslationsController::class)
                    ->where('locale', $pattern)
                    ->middleware($config->get('i18next.save_missing.middleware', []))
                    ->name('i18next.store');
            }
        });
    }
}
