<?php declare(strict_types=1);

namespace Bambamboole\LaravelI18Next;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class I18NextServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('i18next')
            ->hasConfigFile()
            ->hasRoute('web');
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
}
