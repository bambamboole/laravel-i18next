<?php declare(strict_types=1);

namespace Bambamboole\LaravelI18Next\Http\Controller;

use Bambamboole\LaravelI18Next\I18NextTranslationsLoader;

class FetchTranslationsController
{
    public function __construct(private I18NextTranslationsLoader $translationsLoader) {}

    public function __invoke(string $locale): array
    {
        return $this->translationsLoader->loadTranslations($locale);
    }
}
