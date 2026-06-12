<?php declare(strict_types=1);

namespace Bambamboole\LaravelI18Next\Tests\Browser;

use Bambamboole\LaravelI18Next\I18NextServiceProvider;
use Illuminate\Http\Response;
use Orchestra\Testbench\TestCase as BaseTestCase;

use function Orchestra\Testbench\package_path;

abstract class TestCase extends BaseTestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app->useLangPath(dirname(__DIR__).'/Fixtures/lang');
    }

    /** @return array<int, class-string> */
    protected function getPackageProviders($app): array
    {
        return [
            I18NextServiceProvider::class,
        ];
    }

    protected function defineRoutes($router): void
    {
        $page = (string) file_get_contents(__DIR__.'/fixtures/demo.html');

        $router->get('/i18next-demo', fn () => response($page));

        $router->get('/i18next-demo/i18next.js', fn () => $this->javascript(
            package_path('node_modules/i18next/dist/umd/i18next.min.js'),
        ));

        $router->get('/i18next-demo/i18next-http-backend.js', fn () => $this->javascript(
            package_path('node_modules/i18next-http-backend/i18nextHttpBackend.min.js'),
        ));
    }

    private function javascript(string $path): Response
    {
        return response((string) file_get_contents($path), 200, [
            'Content-Type' => 'application/javascript',
        ]);
    }
}
