<?php declare(strict_types=1);

namespace Bambamboole\LaravelI18Next;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class I18NextServiceProvider extends ServiceProvider
{
    public function register(): void
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

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/i18next.php', 'i18next');
        $this->loadRoutesFrom(__DIR__.'/routes.php');
    }
}
