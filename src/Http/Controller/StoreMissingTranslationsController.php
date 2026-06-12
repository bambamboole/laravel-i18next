<?php declare(strict_types=1);

namespace Bambamboole\LaravelI18Next\Http\Controller;

use Bambamboole\LaravelI18Next\I18NextTranslationsLoader;
use Bambamboole\LaravelTranslationDumper\DTO\Translation;
use Bambamboole\LaravelTranslationDumper\FileTranslationWriter;
use Bambamboole\LaravelTranslationDumper\TranslationDumper;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StoreMissingTranslationsController
{
    use ResolvesLocale;

    public function __construct(private Filesystem $fs, private I18NextTranslationsLoader $translationsLoader) {}

    public function __invoke(Request $request, string $locale, ?string $namespace = null): array
    {
        $locale = $this->resolveLocale($locale);
        $group = $namespace !== null && $namespace !== 'translation' ? $namespace : null;

        $translations = [];
        foreach (array_keys($request->json()->all()) as $key) {
            $translations[] = new Translation((string) $key, 'i18next-'.$key);
        }

        $dumper = new TranslationDumper(new FileTranslationWriter($this->fs, lang_path()), $locale);
        Cache::lock('i18next-translation-dump', 5)
            ->block(5, function () use ($dumper, $translations, $group) {
                $dumper->dump($translations, $group);
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
