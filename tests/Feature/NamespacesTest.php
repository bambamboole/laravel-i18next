<?php declare(strict_types=1);

beforeEach(function () {
    $this->withConfig(['i18next.namespaces' => true]);
});

it('serves only the root JSON strings under the translation namespace', function () {
    expect($this->getJson('/locales/en/translation.json')->json())
        ->toMatchArray(['simple' => 'value', 'intro' => 'Hi {{name}}'])
        ->not->toHaveKey('test.greeting');
});

it('serves a top level group as a namespace', function () {
    expect($this->getJson('/locales/en/test.json')->json())
        ->toMatchArray([
            'nested.key' => 'value',
            'plural_one' => 'one apple',
            'multiPlural_interval' => '(0)[no files];(1)[one file];(2-inf)[{{count}} files];',
        ]);
});

it('serves a nested sub-directory file as a slashed namespace', function () {
    expect($this->getJson('/locales/en/entities/salesOrder.json')->json())
        ->toMatchArray([
            'title' => 'Sales order',
            'status.open' => 'Open',
            'lines_one' => 'one line',
        ]);
});

it('serves a registered laravel package namespace as an i18next namespace', function () {
    registerPackageTranslations($this->langPath);

    expect($this->getJson('/locales/en/courier.json')->json())
        ->toMatchArray([
            'messages.welcome' => 'Welcome {{name}}',
        ]);
});

it('stores a missing key in the translation namespace and returns it', function () {
    $this->postJson('/locales/add/en/translation', ['New label' => 'New label'])
        ->assertOk();

    expect($this->getJson('/locales/en/translation.json')->json())
        ->toHaveKey('New label', 'i18next-New label');
});

it('writes a missing slashed-namespace key back into its nested file', function () {
    $this->postJson('/locales/add/en/entities/salesOrder', ['subtitle' => 'subtitle'])
        ->assertOk();

    expect(require $this->langPath.'/en/entities/salesOrder.php')->toHaveKey('subtitle');
    expect(is_file($this->langPath.'/en/entities.php'))->toBeFalse();
});

it('writes missing keys from a laravel package namespace into the registered package lang path', function () {
    $packageLangPath = registerPackageTranslations($this->langPath);

    $this->postJson('/locales/add/en/courier', ['messages.subtitle' => 'messages.subtitle'])
        ->assertOk();

    expect(require $packageLangPath.'/en/messages.php')
        ->toHaveKey('subtitle', 'i18next-messages.subtitle');
});

it('names the namespaced routes', function () {
    expect(route('i18next.fetch', ['locale' => 'en', 'namespace' => 'entities/salesOrder']))
        ->toEndWith('/locales/en/entities/salesOrder.json');
});
