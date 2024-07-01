# Laravel i18next

[![Latest Version on Packagist](https://img.shields.io/packagist/v/bambamboole/laravel-i18next.svg?style=flat-square)](https://packagist.org/packages/bambamboole/laravel-i18next)
[![Total Downloads](https://img.shields.io/packagist/dt/bambamboole/laravel-i18next.svg?style=flat-square)](https://packagist.org/packages/bambamboole/laravel-i18next)
![GitHub Actions](https://github.com/bambamboole/laravel-i18next/actions/workflows/main.yml/badge.svg)

If you are using i18next in your frontend and Laravel in your backend, this package is for you.


## How does it work?
todo

## Installation

You can install the package via composer.

```bash
composer require bambamboole/laravel-i18next
```

## Usage
The package is still in its early development and therefor pretty opinionated and not very flexible.

It provides two routes. One is for fetching the translations and the other one is for saving missing translations.
### Fetching translations
The route

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


### Testing

```bash
composer test
```

## Contributing

### Ideas/Roadmap
* todo

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email manuel@christlieb.eu instead of using the issue tracker.

## Credits

-   [Manuel Christlieb](https://github.com/bambamboole)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

