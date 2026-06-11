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
