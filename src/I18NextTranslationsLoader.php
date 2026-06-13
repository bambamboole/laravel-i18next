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

        foreach ($this->phpGroups($this->langPath, $locale) as $group) {
            $prefix = str_replace('/', '.', $group);

            $nonPrefixedGroupTranslations = $this->loader->load($locale, $group);
            $groupTranslations = [];
            foreach ($nonPrefixedGroupTranslations as $key => $translation) {
                $groupTranslations[$prefix.'.'.$key] = $translation;
            }
            $translations = array_merge($translations, $groupTranslations);
        }

        foreach (array_keys($this->namespacePaths()) as $namespace) {
            foreach ($this->loadLaravelNamespace($locale, (string) $namespace) as $key => $translation) {
                $translations[$namespace.'::'.$key] = $translation;
            }
        }

        return $this->finalize($translations);
    }

    public function loadNamespace(string $locale, string $namespace): array
    {
        if ($namespace === 'translation') {
            return $this->finalize($this->loader->load($locale, '*', '*'));
        }

        if ($this->isLaravelNamespace($namespace)) {
            return $this->finalize($this->loadLaravelNamespace($locale, $namespace));
        }

        [$laravelNamespace, $group] = $this->parseLaravelNamespace($namespace);

        $translations = $this->loader->load($locale, $group, $laravelNamespace);

        return $this->finalize($translations);
    }

    /**
     * @return array<string, string>
     */
    public function namespacePaths(): array
    {
        return $this->loader->namespaces();
    }

    public function isLaravelNamespace(string $namespace): bool
    {
        return isset($this->namespacePaths()[$namespace]);
    }

    /** @return array<string, mixed> */
    private function loadLaravelNamespace(string $locale, string $namespace): array
    {
        $translations = [];

        foreach ($this->phpGroups($this->namespacePaths()[$namespace], $locale) as $group) {
            $prefix = str_replace('/', '.', $group);
            foreach ($this->loader->load($locale, $group, $namespace) as $key => $translation) {
                $translations[$prefix.'.'.$key] = $translation;
            }
        }

        return $translations;
    }

    /** @return array<int, string> */
    private function phpGroups(string $langPath, string $locale): array
    {
        $localePath = $langPath.'/'.$locale;
        $phpFiles = $this->fs->isDirectory($localePath) ? $this->fs->allFiles($localePath) : [];
        $groups = [];

        foreach ($phpFiles as $file) {
            if ($file->getExtension() === 'php') {
                $groups[] = str_replace('\\', '/', substr($file->getRelativePathname(), 0, -strlen('.php')));
            }
        }

        return $groups;
    }

    /** @return array{0: string|null, 1: string} */
    private function parseLaravelNamespace(string $namespace): array
    {
        if (! str_contains($namespace, '::')) {
            return [null, $namespace];
        }

        [$laravelNamespace, $group] = explode('::', $namespace, 2);

        return [$laravelNamespace, $group];
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
        return preg_replace('/(?<!:):(\w+)/', '{{$1}}', $value) ?? $value;
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
