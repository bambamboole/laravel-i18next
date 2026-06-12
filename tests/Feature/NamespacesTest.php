<?php declare(strict_types=1);

beforeEach(function () {
    $this->withConfig(['i18next.namespaces' => true]);
});

it('serves only the root JSON strings under the translation namespace', function () {
    expect($this->getJson('/locales/en/translation.json')->json())
        ->toMatchArray(['simple' => 'value', 'intro' => 'Hi {{name}}'])
        ->not->toHaveKey('test.greeting');   // groups are their own namespaces now
});

it('serves a top level group as a namespace', function () {
    expect($this->getJson('/locales/en/test.json')->json())
        ->toMatchArray([
            'nested.key' => 'value',          // unprefixed (the namespace is the prefix)
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

it('stores a missing key in the translation namespace and returns it', function () {
    $this->postJson('/locales/add/en/translation', ['New label' => 'New label'])
        ->assertOk();

    expect($this->getJson('/locales/en/translation.json')->json())
        ->toHaveKey('New label', 'i18next-New label');
});

it('reconstructs the full key for a slashed namespace before dumping', function () {
    $this->postJson('/locales/add/en/entities/salesOrder', ['subtitle' => 'subtitle'])
        ->assertOk();

    // The key is stored as entities.salesOrder.subtitle somewhere under lang/en;
    // with the translation-dumper's nested-file support it lands back in
    // entities/salesOrder.php, otherwise in a flat entities.php.
    $combined = array_replace(
        require $this->langPath.'/en/entities/salesOrder.php',
        is_file($this->langPath.'/en/entities.php')
            ? data_get(require $this->langPath.'/en/entities.php', 'salesOrder', [])
            : [],
    );

    expect($combined)->toHaveKey('subtitle');
});

it('names the namespaced routes', function () {
    expect(route('i18next.fetch', ['locale' => 'en', 'namespace' => 'entities/salesOrder']))
        ->toEndWith('/locales/en/entities/salesOrder.json');
});
