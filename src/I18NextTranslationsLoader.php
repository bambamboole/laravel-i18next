<?php declare(strict_types=1);

namespace Bambamboole\LaravelI18Next;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class I18NextTranslationsLoader
{
    public function __construct(
        private Filesystem $fs,
        private Loader $loader,
        private string $langPath,
        private bool $nested = false,
    ) {}

    public static function cacheKey(string $locale, ?string $namespace = null): string
    {
        return 'i18next.translations.'.$locale.($namespace !== null ? '.'.$namespace : '');
    }

    public function loadTranslations(string $locale): array
    {
        $translations = $this->loader->load($locale, '*', '*');
        $localePath = $this->langPath.'/'.$locale;

        $phpFiles = $this->fs->isDirectory($localePath) ? $this->fs->allFiles($localePath) : [];

        foreach ($phpFiles as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $group = str_replace('\\', '/', substr($file->getRelativePathname(), 0, -strlen('.php')));
            $prefix = str_replace('/', '.', $group);

            $nonPrefixedGroupTranslations = $this->loader->load($locale, $group);
            $groupTranslations = [];
            foreach ($nonPrefixedGroupTranslations as $key => $translation) {
                $groupTranslations[$prefix.'.'.$key] = $translation;
            }
            $translations = array_merge($translations, $groupTranslations);
        }

        return $this->finalize($translations);
    }

    public function loadNamespace(string $locale, string $namespace): array
    {
        $translations = $namespace === 'translation'
            ? $this->loader->load($locale, '*', '*')
            : $this->loader->load($locale, $namespace);

        return $this->finalize($translations);
    }

    /** @param array<array-key, mixed> $translations */
    private function finalize(array $translations): array
    {
        $prepared = $this->prepare($translations);

        return $this->nested ? Arr::undot($prepared) : $prepared;
    }

    private function prepare(array $translations): array
    {
        $i18nTranslations = [];

        foreach ($translations as $laravelKey => $laravelValue) {
            $i18nKey = $this->replaceVariables(is_int($laravelKey) ? (string) $laravelKey : $laravelKey);

            if (is_array($laravelValue)) {
                $i18nTranslations[$i18nKey] = $this->prepare($laravelValue);

                continue;
            }

            foreach ($this->expandPluralization($laravelValue) as $suffix => $text) {
                $i18nTranslations[$i18nKey.$suffix] = $this->replaceVariables($text);
            }
        }

        return $this->flatten($i18nTranslations);
    }

    private function replaceVariables(string $value): string
    {
        return preg_replace('/:(\w+)/', '{{$1}}', $value) ?? $value;
    }

    /** @return array<string, string> */
    private function expandPluralization(string $value): array
    {
        if (! str_contains($value, '|')) {
            return ['' => $value];
        }

        $segments = explode('|', $value);
        $intervals = [];
        foreach ($segments as $segment) {
            if (preg_match('/^[{\[]([^\[\]{}]*)[}\]]\s*(.*)$/s', $segment, $matches)) {
                $intervals[] = '('.$this->toInterval($matches[1]).')['.$matches[2].']';
            }
        }

        if (count($intervals) === count($segments)) {
            return ['_interval' => implode(';', $intervals).';'];
        }

        [$one, $other] = array_pad($segments, 2, '');

        return ['_one' => $one, '_other' => $other];
    }

    private function toInterval(string $condition): string
    {
        $condition = trim($condition);

        if (! str_contains($condition, ',')) {
            return $condition;
        }

        [$from, $to] = array_map('trim', explode(',', $condition, 2));

        return $from.'-'.($to === '*' ? 'inf' : $to);
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
