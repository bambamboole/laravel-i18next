<?php declare(strict_types=1);

namespace Bambamboole\LaravelI18Next\Http\Controller;

trait ResolvesLocale
{
    protected function resolveLocale(string $locale): string
    {
        $map = config('i18next.locale_map');

        return is_array($map) && isset($map[$locale]) && is_string($map[$locale])
            ? $map[$locale]
            : $locale;
    }
}
