<?php declare(strict_types=1);

namespace Bambamboole\LaravelI18Next;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class I18NextTranslationsLoader
{
    public function __construct(
        private Filesystem $fs,
        private Loader $loader,
        private string $langPath,
    ) {}

    public function loadTranslations(string $locale): array
    {
        $translations = $this->loader->load($locale, '*', '*');
        $localePath = $this->langPath.'/'.$locale;

        foreach ($this->fs->allFiles($localePath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            // Namespace each group by its path relative to the locale directory,
            // so entities/salesOrder.php becomes entities.salesOrder.* while a
            // top level test.php stays test.*
            $relativePath = ltrim(str_replace($localePath, '', $file->getPathname()), '/\\');
            $group = str_replace('\\', '/', substr($relativePath, 0, -strlen('.php')));
            $prefix = str_replace('/', '.', $group);

            $nonPrefixedGroupTranslations = $this->loader->load($locale, $group);
            $groupTranslations = [];
            foreach ($nonPrefixedGroupTranslations as $key => $translation) {
                $groupTranslations[$prefix.'.'.$key] = $translation;
            }
            $translations = array_merge($translations, $groupTranslations);
        }

        return $this->prepare($translations);
    }

    private function prepare(array $translations): array
    {
        $i18nTranslations = [];
        $keys = array_keys($translations);

        for ($i = 0; $i < count($keys); $i++) {
            $laravelKey = $keys[$i];
            $i18nKey = preg_replace("/:([\w\d]+)/", '{{$1}}', is_int($laravelKey) ? (string) $laravelKey : $laravelKey);
            $laravelValue = $translations[$laravelKey];

            if (is_array($laravelValue)) {
                $i18nTranslations[$i18nKey] = $this->prepare($laravelValue);
            } else {
                $translationWithReplacedVariableSyntax = preg_replace("/:([\w\d]+)/", '{{$1}}', $laravelValue);
                // handle pluralisation
                if (Str::contains($translationWithReplacedVariableSyntax, '|')) {
                    [$one, $other] = explode('|', $translationWithReplacedVariableSyntax);
                    $i18nTranslations[$i18nKey.'_one'] = $one;
                    $i18nTranslations[$i18nKey.'_other'] = $other;
                } else {
                    $i18nTranslations[$i18nKey] = $translationWithReplacedVariableSyntax;
                }
            }
        }

        return $this->flatten($i18nTranslations);
    }

    private function flatten($translations): array
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($translations));
        $flattened = [];

        foreach ($iterator as $leafValue) {
            $keys = [];

            foreach (range(0, $iterator->getDepth()) as $depth) {
                $keys[] = $iterator->getSubIterator($depth)->key();
            }

            $flattened[implode('.', $keys)] = $leafValue;
        }

        return $flattened;
    }
}
