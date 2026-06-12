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

