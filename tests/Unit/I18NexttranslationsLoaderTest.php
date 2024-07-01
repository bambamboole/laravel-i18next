<?php declare(strict_types=1);

namespace Bambamboole\LaravelI18Next\Tests\Unit;

use Bambamboole\LaravelI18Next\I18NextTranslationsLoader;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class I18NexttranslationsLoaderTest extends TestCase
{
    private MockObject|Filesystem $fs;

    private Loader|MockObject $loader;

    protected function setUp(): void
    {
        $this->fs = $this->createMock(Filesystem::class);
        $this->loader = $this->createMock(Loader::class);
    }

    public function testItCanLoadTranslations()
    {
        $this->fs->expects($this->once())
            ->method('files')
            ->with('langPath/en')
            ->willReturn([
                new \SplFileInfo('langPath/en/test.php'),
            ]);

        $this->loader->expects($counter = self::exactly(2))
            ->method('load')
            ->willReturnCallback(function ($locale, $group) use ($counter) {
                if ($counter->numberOfInvocations() === 1) {
                    self::assertEquals('en', $locale);
                    self::assertEquals('*', $group);

                    return [
                        'simple' => 'value',
                        'test' => 'value with :variable',
                    ];
                }
                self::assertEquals('en', $locale);
                self::assertEquals('test', $group);

                return [
                    'nested' => [
                        'key' => 'value',
                    ],
                    'plural' => 'one apple|:count apples',
                ];
            });

        $subject = $this->createSubject();
        $result = $subject->loadTranslations('en');

        $this->assertEquals([
            'test' => 'value with {{variable}}',
            'simple' => 'value',
            'test.nested.key' => 'value',
            'test.plural_one' => 'one apple',
            'test.plural_other' => '{{count}} apples',
        ], $result);
    }

    private function createSubject(): I18NextTranslationsLoader
    {
        return new I18NextTranslationsLoader(
            $this->fs,
            $this->loader,
            'langPath'
        );
    }
}
