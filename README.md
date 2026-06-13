# Laravel i18next

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bambamboole/laravel-i18next.svg?style=flat-square)](https://packagist.org/packages/bambamboole/laravel-i18next)
[![Total Downloads](https://img.shields.io/packagist/dt/bambamboole/laravel-i18next.svg?style=flat-square)](https://packagist.org/packages/bambamboole/laravel-i18next)
![GitHub Actions](https://github.com/bambamboole/laravel-i18next/actions/workflows/ci.yml/badge.svg)

If you are using i18next in your frontend and Laravel in your backend, this package is for you.

## Requirements

- PHP 8.3+
- Laravel 11, 12 or 13


## How does it work?
The package provides the routes to fetch the translations and to save missing translations when using i18next-http-backend.   
it supports Laravels JSON and PHP translation files and converts them on the fly to be compatible with i18next.

PHP files in sub directories are namespaced by their path, so `lang/en/entities/salesOrder.php` is exposed under the `entities.salesOrder.*` keys.

### Pluralization
Laravel plural strings are converted to i18next plurals:

| Laravel | i18next |
| --- | --- |
| `one apple\|:count apples` | `key_one`, `key_other` |
| `{0} no files\|{1} one file\|[2,*] :count files` | `key_interval` (see below) |

Simple `one\|other` strings become standard i18next `_one`/`_other` plurals.

Explicit count/range forms (`{0}`, `{1}`, `[2,*]`, …) are converted to the
[i18next-intervalplural-postprocessor](https://github.com/i18next/i18next-intervalPlural-postProcessor)
format, e.g. `(0)[no files];(1)[one file];(2-inf)[{{count}} files];`. Add that
postprocessor on the frontend to resolve them.

## Installation

You can install the package via composer.

```bash
composer require bambamboole/laravel-i18next
```

## Configuration

Publish the config to customise routing, the missing-translation endpoint and caching:

```bash
php artisan vendor:publish --tag="i18next-config"
```

```php
return [
    'routes' => [
        'enabled' => true,
        'prefix' => '',                 // e.g. 'api'
        'middleware' => [],             // e.g. ['web'] for session/CSRF
        'locale_pattern' => '[A-Za-z_-]+', // also guards against path traversal
    ],

    // Map a requested locale to the one used on disk, e.g. ['de-DE' => 'de'].
    'locale_map' => [],

    // The store route writes files to disk — keep it out of production.
    'save_missing' => [
        'enabled' => env('I18NEXT_SAVE_MISSING', true),
        'middleware' => [],             // e.g. ['auth'] or a throttle
    ],

    // Cache the converted payload per locale; flushed when missing keys are saved.
    'cache' => [
        'enabled' => false,
        'store' => null,                // null = default cache store
        'ttl' => null,                  // null = forever
    ],

    // 'flat' = dotted keys (use keySeparator: false), 'nested' = nested JSON tree.
    'output' => 'flat',

    // Load translations as i18next namespaces (/locales/{lng}/{ns}.json).
    'namespaces' => false,
];
```

Set `'output' => 'nested'` if you'd rather receive a nested JSON tree and keep i18next's default dot key separator (no `keySeparator: false` needed).

### Namespaces
Set `'namespaces' => true` to load translations as i18next namespaces, where the
**namespace is the path of the group file** under `lang/{locale}`:

| Request | Source | i18next |
| --- | --- | --- |
| `/locales/en/translation.json` | `lang/en.json` (root strings) | `t('key')` |
| `/locales/en/auth.json` | `lang/en/auth.php` | `t('auth:key')` |
| `/locales/en/entities/salesOrder.json` | `lang/en/entities/salesOrder.php` | `t('entities/salesOrder:title')` |
| `/locales/en/lattice.json` | `packages/lattice/lang/en/messages.php` | `useTranslation('lattice')`, then `t('messages.key')` |

Keys are returned unprefixed (the namespace is the prefix). Configure the backend
with `loadPath: '/locales/{{lng}}/{{ns}}.json'` and list your namespaces in `ns`.
For Laravel package translations, use the Laravel package namespace as the i18next
namespace, for example `lattice`. Package group files are exposed below it as
dotted keys, for example `messages.key`. The package must register the path with
`loadTranslationsFrom($path, 'lattice')`.

When namespaces are disabled, package translations are exposed with their full
Laravel keys, for example `lattice::messages.key`. Keys without a `::` namespace
continue to target normal app translations.

> Saving missing keys for a slashed namespace reconstructs the full dotted key
> (`entities.salesOrder.subtitle`). With a `laravel-translation-dumper` version
> that supports writing into existing nested files, it lands back in
> `entities/salesOrder.php`; otherwise it falls into a flat `entities.php`.

> Saving missing keys for a Laravel package namespace writes back into the
> registered package lang path, for example `messages.subtitle` in the `lattice`
> i18next namespace is written as `lattice::messages.subtitle` and lands in
> `packages/lattice/lang/en/messages.php`.

> **Security:** the store route writes translation files. Disable it in production
> (`I18NEXT_SAVE_MISSING=false`) or put it behind auth via `save_missing.middleware`.

## Usage
The package is still in its early development and therefor pretty opinionated and not very flexible.

It provides two routes. One is for fetching the translations and the other one is for saving missing translations.

### Fetching translations
`GET /locales/{locale}/translation.json` returns all translations for the locale, converted to the i18next format. Point `i18next-http-backend`'s `loadPath` at it.

### Saving missing translations
`POST /locales/add/{locale}/translation` persists the keys reported by i18next's `saveMissing`. Point `i18next-http-backend`'s `addPath` at it (and send the CSRF token, see below).

### With Vue.js
To use the translations in your Vue.js components you can use the `i18next-vue` package.
```bash 
npm install -D i18next-vue i18next-http-backend
```

To configure `i18next-vue` you can use the following code snippet:
```js
import I18NextVue from 'i18next-vue';
import HttpBackend from 'i18next-http-backend'

i18next.use(HttpBackend).init({
    saveMissing: true,
    lng: 'en',
    backend: {
        // This is needed for CSRF protection
        withCredentials: true,
        customHeaders: () => {
            const csrf = document.querySelector<HTMLElement>('meta[name="csrf-token"]')

            return csrf === null ? {} : {'X-CSRF-TOKEN': csrf.getAttribute('content')}
        },
    },
});

//...

app.use(I18NextVue, {i18next})
```

### With React (Inertia v3)
Install i18next and the React bindings alongside your Inertia app:

```bash
npm install -D i18next i18next-http-backend react-i18next
# optional, only needed for interval plurals:
npm install -D i18next-intervalplural-postprocessor
```

Initialise i18next in your Inertia entry point (`resources/js/app.tsx`) and wrap the app with `I18nextProvider`. Resolving the `init()` promise before `createInertiaApp` avoids a flash of untranslated keys on first paint:

```tsx
import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'
import i18next from 'i18next'
import HttpBackend from 'i18next-http-backend'
import IntervalPlural from 'i18next-intervalplural-postprocessor'
import { initReactI18next, I18nextProvider } from 'react-i18next'

const csrfHeaders = () => {
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content

    return token ? { 'X-CSRF-TOKEN': token } : {}
}

i18next
    .use(HttpBackend)
    .use(IntervalPlural) // optional: resolves the `_interval` plural keys
    .use(initReactI18next)
    .init({
        lng: document.documentElement.lang || 'en',
        fallbackLng: false,
        keySeparator: false, // the package exposes flat, dotted keys
        saveMissing: true,
        backend: {
            loadPath: '/locales/{{lng}}/translation.json',
            addPath: '/locales/add/{{lng}}/translation',
            withCredentials: true, // send the session cookie for CSRF
            customHeaders: csrfHeaders,
        },
    })
    .then(() =>
        createInertiaApp({
            resolve: (name) => {
                const pages = import.meta.glob('./Pages/**/*.tsx')

                return pages[`./Pages/${name}.tsx`]()
            },
            setup({ el, App, props }) {
                createRoot(el).render(
                    <I18nextProvider i18n={i18next}>
                        <App {...props} />
                    </I18nextProvider>,
                )
            },
        }),
    )
```

Then translate inside your pages with the `useTranslation` hook:

```tsx
import { useTranslation } from 'react-i18next'

export default function Dashboard() {
    const { t } = useTranslation()

    return (
        <div>
            <h1>{t('test.greeting', { name: 'World' })}</h1>
            <p>{t('test.plural', { count: 5 })}</p>
            {/* interval plurals use the postprocessor and the `_interval` key */}
            <p>{t('test.multiPlural_interval', { count: 5, postProcess: 'interval' })}</p>
        </div>
    )
}
```

> Set `keySeparator: false` so i18next looks up the flat, dotted keys the package emits (`test.greeting`) verbatim instead of treating the dots as nesting.

#### A `t` / `tp` helper
Interval plurals need the `_interval` suffix and `postProcess: 'interval'` on every call. A tiny wrapper hides that — `t` for everything (including standard plurals), `tp` for interval plurals:

```ts
// resources/js/useT.ts
import { useTranslation } from 'react-i18next'

export function useT() {
    const { t, i18n } = useTranslation()

    return {
        t,
        // interval pluralization, e.g. tp('test.multiPlural', 5)
        tp: (key: string, count: number, options: Record<string, unknown> = {}) =>
            t(`${key}_interval`, { count, postProcess: 'interval', ...options }),
        i18n,
    }
}
```

```tsx
const { t, tp } = useT()

t('test.greeting', { name: 'World' }) // Hello World
t('test.plural', { count: 5 })        // 5 apples  (standard plural, handled by i18next)
tp('test.multiPlural', 5)             // 5 files   (interval plural)
```

Outside of React, bind the same two functions to the i18next instance directly:

```ts
import i18next from 'i18next'

export const t = i18next.t.bind(i18next)
export const tp = (key: string, count: number, options: Record<string, unknown> = {}) =>
    i18next.t(`${key}_interval`, { count, postProcess: 'interval', ...options })
```


### Testing

```bash
composer test          # unit + feature
composer test:browser  # end-to-end browser test (needs npm install + playwright)
```

### Demo

Run a live demo page that drives i18next in the browser against the package routes:

```bash
npm install
composer serve   # then open http://127.0.0.1:8000/i18next-demo
```

## Contributing

### Ideas/Roadmap
* Add more tests
* Make the package more flexible
* Support namespaces
* Support i18next multiload


Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email manuel@christlieb.eu instead of using the issue tracker.

## Credits

-   [Manuel Christlieb](https://github.com/bambamboole)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
