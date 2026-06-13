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

    public function __construct(
        private Filesystem $fs,
        private I18NextTranslationsLoader $translationsLoader,
    ) {}

    public function __invoke(Request $request, string $locale, ?string $namespace = null): array
    {
        $locale = $this->resolveLocale($locale);
        $group = $this->translationGroup($namespace);
        $translationNamespace = $this->translationNamespace($namespace);

        $translations = [];
        foreach (array_keys($request->json()->all()) as $key) {
            $key = (string) $key;
            $translations[] = new Translation($translationNamespace.$key, 'i18next-'.$key);
        }

        $dumper = new TranslationDumper(
            new FileTranslationWriter($this->fs, lang_path(), fn (): array => $this->translationsLoader->namespacePaths()),
            $locale,
        );
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

    private function translationNamespace(?string $namespace): string
    {
        if ($namespace === null || $namespace === 'translation') {
            return '';
        }

        if (str_contains($namespace, '::')) {
            return $namespace.'.';
        }

        return $this->translationsLoader->isLaravelNamespace($namespace) ? $namespace.'::' : '';
    }

    private function translationGroup(?string $namespace): ?string
    {
        if (
            $namespace === null
            || $namespace === 'translation'
            || str_contains($namespace, '::')
            || $this->translationsLoader->isLaravelNamespace($namespace)
        ) {
            return null;
        }

        return $namespace;
    }
}
