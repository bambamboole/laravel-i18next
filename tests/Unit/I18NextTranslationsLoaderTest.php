<?php declare(strict_types=1);

use Bambamboole\LaravelI18Next\I18NextTranslationsLoader;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Filesystem\Filesystem;

it('converts laravel translations into the i18next format', function () {
    $fs = $this->createMock(Filesystem::class);
    $loader = $this->createMock(Loader::class);

    $fs->expects($this->once())
        ->method('files')
        ->with('langPath/en')
        ->willReturn([
            new SplFileInfo('langPath/en/test.php'),
        ]);

    $loader->expects($counter = $this->exactly(2))
        ->method('load')
        ->willReturnCallback(function ($locale, $group) use ($counter) {
            if ($counter->numberOfInvocations() === 1) {
                expect($locale)->toBe('en');
                expect($group)->toBe('*');

                return [
                    'simple' => 'value',
                    'test' => 'value with :variable',
                ];
            }

            expect($locale)->toBe('en');
            expect($group)->toBe('test');

            return [
                'nested' => [
                    'key' => 'value',
                ],
                'plural' => 'one apple|:count apples',
            ];
        });

    $subject = new I18NextTranslationsLoader($fs, $loader, 'langPath');

    expect($subject->loadTranslations('en'))->toEqual([
        'test' => 'value with {{variable}}',
        'simple' => 'value',
        'test.nested.key' => 'value',
        'test.plural_one' => 'one apple',
        'test.plural_other' => '{{count}} apples',
    ]);
});
