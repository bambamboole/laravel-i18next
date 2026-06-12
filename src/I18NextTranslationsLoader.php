<?php declare(strict_types=1);

namespace Bambamboole\LaravelI18Next;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Filesystem\Filesystem;
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

        // A locale may have only a JSON file (or not exist at all, e.g. an
        // i18next fallback such as "dev"); in that case there are no PHP groups.
        $phpFiles = $this->fs->isDirectory($localePath) ? $this->fs->allFiles($localePath) : [];

        foreach ($phpFiles as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            // Namespace each group by its path relative to the locale directory,
            // so entities/salesOrder.php becomes entities.salesOrder.* while a
            // top level test.php stays test.*
            $group = str_replace('\\', '/', substr($file->getRelativePathname(), 0, -strlen('.php')));
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

    /**
     * Map a Laravel translation value to one or more i18next entries keyed by
     * their plural suffix. A non plural value yields a single entry with an
     * empty suffix; a simple "one|other" value yields _one/_other; and explicit
     * forms like "{0} none|{1} one|[2,*] many" yield a single _interval value in
     * the i18next-intervalplural-postprocessor format.
     *
     * @return array<string, string>
     */
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

        // Every segment carried an explicit count/range condition: emit an
        // i18next interval plural string. The frontend needs the
        // i18next-intervalplural-postprocessor to resolve it.
        if (count($intervals) === count($segments)) {
            return ['_interval' => implode(';', $intervals).';'];
        }

        [$one, $other] = array_pad($segments, 2, '');

        return ['_one' => $one, '_other' => $other];
    }

    /**
     * Translate a Laravel plural condition into an i18next interval:
     * "0" -> "0", "1" -> "1", "2,5" -> "2-5", "2,*" -> "2-inf".
     */
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
