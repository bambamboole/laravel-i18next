<?php declare(strict_types=1);

use Bambamboole\LaravelI18Next\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;

uses(TestCase::class)->in('Feature');
uses(Bambamboole\LaravelI18Next\Tests\Browser\TestCase::class)->in('Browser');

function registerPackageTranslations(string $langPath): string
{
    $packageLangPath = $langPath.'/packages/courier/lang';
    $filesystem = new Filesystem;

    $filesystem->ensureDirectoryExists($packageLangPath.'/en');
    $filesystem->put(
        $packageLangPath.'/en/messages.php',
        "<?php declare(strict_types=1);\n\nreturn ['welcome' => 'Welcome :name'];\n",
    );

    app('translation.loader')->addNamespace('courier', $packageLangPath);

    return $packageLangPath;
}
