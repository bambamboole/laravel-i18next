<?php declare(strict_types=1);

namespace Bambamboole\LaravelI18Next\Http\Controller;

use Bambamboole\LaravelI18Next\I18NextTranslationsLoader;
use Illuminate\Support\Facades\Cache;

class FetchTranslationsController
{
    public function __construct(private I18NextTranslationsLoader $translationsLoader) {}

    public function __invoke(string $locale, ?string $namespace = null): array
    {
        $factory = $namespace === null
            ? fn (): array => $this->translationsLoader->loadTranslations($locale)
            : fn (): array => $this->translationsLoader->loadNamespace($locale, $namespace);

        $cache = config('i18next.cache', []);

        if (! ($cache['enabled'] ?? false)) {
            return $factory();
        }

        $store = Cache::store($cache['store'] ?? null);
        $key = I18NextTranslationsLoader::cacheKey($locale, $namespace);

        return ($cache['ttl'] ?? null) === null
            ? $store->rememberForever($key, $factory)
            : $store->remember($key, $cache['ttl'], $factory);
    }
}
