<?php declare(strict_types=1);

namespace Bambamboole\LaravelI18Next\Http\Controller;

use Bambamboole\LaravelI18Next\I18NextTranslationsLoader;
use Bambamboole\LaravelTranslationDumper\ArrayExporter;
use Bambamboole\LaravelTranslationDumper\TranslationDumper;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StoreMissingTranslationsController
{
    public function __construct(private Filesystem $fs, private I18NextTranslationsLoader $translationsLoader) {}

    public function __invoke(Request $request, string $locale): array
    {
        $translations = $request->json()->all();
        $dumper = new TranslationDumper($this->fs, new ArrayExporter(), lang_path(), $locale, 'i18next-');
        Cache::lock('i18next-translation-dump', 5)
            ->block(5, function () use ($dumper, $translations) {
                $dumper->dump($translations);
            });

        return $this->translationsLoader->loadTranslations($locale);
    }
}
