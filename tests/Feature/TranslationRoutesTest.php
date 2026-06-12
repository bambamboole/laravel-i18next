<?php declare(strict_types=1);

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

    // '{0} no files|{1} one file|[2,*] :count files'
    expect($response->json())
        ->toMatchArray([
            'test.multiPlural_interval' => '(0)[no files];(1)[one file];(2-inf)[{{count}} files];',
        ]);
});

it('serves translations from nested php files under sub directories', function () {
    $response = $this->getJson('/locales/en/translation.json');

    $response->assertOk();

    // lang/en/entities/salesOrder.php is namespaced by its path: entities.salesOrder.*
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

it('persists missing translations and returns the updated set', function () {
    $response = $this->postJson('/locales/add/en/translation', [
        'some.missing.key' => 'A brand new string',
    ]);

    $response->assertOk();

    // The value sent by i18next becomes the translation key; a value containing
    // spaces is stored as a JSON translation.
    expect($response->json())
        ->toHaveKey('A brand new string', 'i18next-A brand new string');

    $stored = json_decode(file_get_contents($this->langPath.'/en.json'), true);

    expect($stored)
        ->toHaveKey('A brand new string', 'i18next-A brand new string')
        ->toHaveKey('simple', 'value');
});

it('registers the package routes with their names', function () {
    expect(route('i18next.fetch', ['locale' => 'en']))->toEndWith('/locales/en/translation.json')
        ->and(route('i18next.store', ['locale' => 'en']))->toEndWith('/locales/add/en/translation');
});
