<?php declare(strict_types=1);

namespace Bambamboole\LaravelI18Next\Tests;

use Bambamboole\LaravelI18Next\I18NextServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Writable copy of tests/Fixtures/lang the application reads from during a
     * test. A fresh copy per test keeps the committed fixtures pristine while
     * still letting the store route actually write translation files.
     */
    protected string $langPath;

    protected function setUp(): void
    {
        $this->langPath = sys_get_temp_dir().'/laravel-i18next-tests/'.uniqid('lang-', true);

        $fs = new Filesystem;
        $fs->ensureDirectoryExists($this->langPath);
        $fs->copyDirectory(__DIR__.'/Fixtures/lang', $this->langPath);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->langPath);

        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app->useLangPath($this->langPath);

        // The store route acquires a cache lock; keep it on the in-memory array
        // store so the test does not depend on the ambient cache driver.
        $app['config']->set('cache.default', 'array');
    }

    /** @return array<int, class-string> */
    protected function getPackageProviders($app): array
    {
        return [
            I18NextServiceProvider::class,
        ];
    }
}
