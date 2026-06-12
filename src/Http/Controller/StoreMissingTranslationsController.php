<?php declare(strict_types=1);

namespace Bambamboole\LaravelI18Next\Http\Controller;

use Bambamboole\LaravelI18Next\I18NextTranslationsLoader;
use Bambamboole\LaravelTranslationDumper\DTO\Translation;
use Bambamboole\LaravelTranslationDumper\TranslationDumper;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StoreMissingTranslationsController
{
    public function __construct(private Filesystem $fs, private I18NextTranslationsLoader $translationsLoader) {}

    public function __invoke(Request $request, string $locale, ?string $namespace = null): array
    {
        // In namespace mode the keys are relative to the namespace (its path),
        // so prepend it to reconstruct the full Laravel key.
        $prefix = $namespace !== null && $namespace !== 'translation'
            ? str_replace('/', '.', $namespace).'.'
            : '';

        // i18next posts { "<key>": "<fallbackValue>" }; the property name is the
        // missing key, so store that (not the value) with a placeholder.
        $translations = [];
        foreach ($request->json()->all() as $key => $value) {
            $fullKey = $prefix.$key;
            $translations[] = new Translation($fullKey, 'i18next-'.$fullKey);
        }

        $dumper = new TranslationDumper($this->fs, lang_path(), $locale);
        Cache::lock('i18next-translation-dump', 5)
            ->block(5, function () use ($dumper, $translations) {
                $dumper->dump($translations);
            });

        $cache = config('i18next.cache', []);
        if ($cache['enabled'] ?? false) {
            Cache::store($cache['store'] ?? null)->forget(I18NextTranslationsLoader::cacheKey($locale, $namespace));
        }

        return $namespace === null
            ? $this->translationsLoader->loadTranslations($locale)
            : $this->translationsLoader->loadNamespace($locale, $namespace);
    }
}
