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
                $app['config']->get('i18next.output', 'flat') === 'nested',
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
        $namespaces = (bool) $config->get('i18next.namespaces', false);

        [$fetchUri, $storeUri] = $namespaces
            ? ['locales/{locale}/{namespace}.json', 'locales/add/{locale}/{namespace}']
            : ['locales/{locale}/translation.json', 'locales/add/{locale}/translation'];

        Route::group([
            'prefix' => $config->get('i18next.routes.prefix', ''),
            'middleware' => $config->get('i18next.routes.middleware', []),
        ], function () use ($config, $pattern, $namespaces, $fetchUri, $storeUri): void {
            $apply = function ($route) use ($pattern, $namespaces) {
                $route->where('locale', $pattern);
                if ($namespaces) {
                    $route->where('namespace', '[A-Za-z0-9_/-]+');
                }

                return $route;
            };

            $apply(Route::get($fetchUri, FetchTranslationsController::class))->name('i18next.fetch');

            if ($config->get('i18next.save_missing.enabled', true)) {
                $apply(Route::post($storeUri, StoreMissingTranslationsController::class))
                    ->middleware($config->get('i18next.save_missing.middleware', []))
                    ->name('i18next.store');
            }
        });
    }
}
