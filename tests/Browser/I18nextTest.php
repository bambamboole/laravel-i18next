<?php declare(strict_types=1);

it('renders translations fetched from the package route through i18next', function () {
    visit('/i18next-demo')
        ->assertSee('Hello World')
        ->assertSee('5 apples')
        ->assertSee('Sales order')
        ->assertNoJavascriptErrors();
});
