<?php declare(strict_types=1);
use Bambamboole\LaravelI18Next\I18NextTranslationsLoader;

it('serves the laravel translations in the i18next format', function () {
    $response = $this->getJson('/locales/en/translation.json');

    $response->assertOk();

    expect($response->json())
        ->toMatchArray([
            'simple' => 'value',
            'intro' => 'Hi {{name}}',
            'test.nested.key' => 'value',
            'test.greeting' => 'Hello {{name}}',
            'test.plural_one' => 'one apple',
            'test.plural_other' => '{{count}} apples',
        ]);
});

it('expands laravel multi-form plurals into an i18next interval string', function () {
    $response = $this->getJson('/locales/en/translation.json');

    $response->assertOk();

    expect($response->json())
        ->toMatchArray([
            'test.multiPlural_interval' => '(0)[no files];(1)[one file];(2-inf)[{{count}} files];',
        ]);
});

it('returns an empty set for a locale without translation files', function () {
    $this->getJson('/locales/dev/translation.json')
        ->assertOk()
        ->assertExactJson([]);
});

it('serves translations from nested php files under sub directories', function () {
    $response = $this->getJson('/locales/en/translation.json');

    $response->assertOk();

    expect($response->json())
        ->toMatchArray([
            'entities.salesOrder.title' => 'Sales order',
            'entities.salesOrder.status.open' => 'Open',
            'entities.salesOrder.status.shipped' => 'Shipped',
            'entities.salesOrder.summary' => '{{count}} items for {{customer}}',
            'entities.salesOrder.lines_one' => 'one line',
            'entities.salesOrder.lines_other' => '{{count}} lines',
        ]);
});

it('persists a missing translation under its key and returns the updated set', function () {
    $response = $this->postJson('/locales/add/en/translation', [
        'A brand new string' => 'A brand new string',
    ]);

    $response->assertOk();

    expect($response->json())
        ->toHaveKey('A brand new string', 'i18next-A brand new string');

    $stored = json_decode(file_get_contents($this->langPath.'/en.json'), true);

    expect($stored)
        ->toHaveKey('A brand new string', 'i18next-A brand new string')
        ->toHaveKey('simple', 'value');
});

it('stores the request key, not the fallback value', function () {
    $this->postJson('/locales/add/en/translation', [
        'Missing headline' => 'Some default text',
    ])->assertOk();

    $stored = json_decode(file_get_contents($this->langPath.'/en.json'), true);

    expect($stored)
        ->toHaveKey('Missing headline')
        ->not->toHaveKey('Some default text');
});

it('rejects a locale that does not match the allowed pattern', function () {
    $this->getJson('/locales/en.US/translation.json')->assertNotFound();
    $this->postJson('/locales/add/..%2F../translation')->assertNotFound();
});

it('registers the package routes with their names', function () {
    expect(route('i18next.fetch', ['locale' => 'en']))->toEndWith('/locales/en/translation.json')
        ->and(route('i18next.store', ['locale' => 'en']))->toEndWith('/locales/add/en/translation');
});

it('does not register the store route when saving is disabled', function () {
    $this->withConfig(['i18next.save_missing.enabled' => false]);

    expect(app('router')->has('i18next.store'))->toBeFalse();
    $this->postJson('/locales/add/en/translation', ['Foo' => 'Foo'])->assertNotFound();
});

it('returns a nested tree when the output is configured as nested', function () {
    $this->withConfig(['i18next.output' => 'nested']);

    expect($this->getJson('/locales/en/translation.json')->json())
        ->toHaveKey('test.greeting', 'Hello {{name}}')
        ->toHaveKey('entities.salesOrder.status.open', 'Open')
        ->toHaveKey('test.plural_one', 'one apple');
});

it('serves the converted payload from the cache when caching is enabled', function () {
    $this->withConfig(['i18next.cache.enabled' => true]);

    $key = I18NextTranslationsLoader::cacheKey('en');
    expect(cache()->has($key))->toBeFalse();

    $this->getJson('/locales/en/translation.json')->assertOk();

    expect(cache()->get($key))->toMatchArray(['test.greeting' => 'Hello {{name}}']);
});
