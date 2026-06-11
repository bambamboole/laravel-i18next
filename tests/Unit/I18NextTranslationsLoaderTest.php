<?php declare(strict_types=1);

use Bambamboole\LaravelI18Next\I18NextTranslationsLoader;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Filesystem\Filesystem;

it('converts laravel translations into the i18next format', function () {
    $fs = $this->createMock(Filesystem::class);
    $loader = $this->createMock(Loader::class);

    $fs->expects($this->once())
        ->method('allFiles')
        ->with('langPath/en')
        ->willReturn([
            new SplFileInfo('langPath/en/test.php'),
            new SplFileInfo('langPath/en/entities/salesOrder.php'),
        ]);

    $loader->method('load')
        ->willReturnCallback(function ($locale, $group) {
            expect($locale)->toBe('en');

            return match ($group) {
                '*' => [
                    'simple' => 'value',
                    'test' => 'value with :variable',
                ],
                'test' => [
                    'nested' => ['key' => 'value'],
                    'plural' => 'one apple|:count apples',
                ],
                'entities/salesOrder' => [
                    'title' => 'Sales order',
                    'status' => ['open' => 'Open'],
                ],
                default => [],
            };
        });

    $subject = new I18NextTranslationsLoader($fs, $loader, 'langPath');

    expect($subject->loadTranslations('en'))->toEqual([
        'test' => 'value with {{variable}}',
        'simple' => 'value',
        'test.nested.key' => 'value',
        'test.plural_one' => 'one apple',
        'test.plural_other' => '{{count}} apples',
        'entities.salesOrder.title' => 'Sales order',
        'entities.salesOrder.status.open' => 'Open',
    ]);
});
