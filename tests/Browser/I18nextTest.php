<?php declare(strict_types=1);

it('renders translations fetched from the package route through i18next', function () {
    visit('/i18next-demo')
        // Variable interpolation: 'Hello :name' -> 'Hello {{name}}' -> 'Hello World'.
        ->assertSee('Hello World')
        // Pluralization: 'one apple|:count apples' -> 'test.plural_other' -> '5 apples'.
        ->assertSee('5 apples')
        // Nested sub directory file: lang/en/entities/salesOrder.php.
        ->assertSee('Sales order')
        ->assertNoJavascriptErrors();
});
