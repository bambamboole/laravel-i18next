<?php declare(strict_types=1);

namespace Bambamboole\LaravelI18Next;

use Illuminate\Support\ServiceProvider;

class LaravelI18NextServiceProvider extends ServiceProvider
{
    public function register(): void
    {

    }

    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/i18next.php', 'i18next');

    }
}
