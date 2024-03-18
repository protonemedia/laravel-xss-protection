# Laravel XSS Protection Middleware

[![Latest Version on Packagist](https://img.shields.io/packagist/v/protonemedia/laravel-xss-protection.svg?style=flat-square)](https://packagist.org/packages/protonemedia/laravel-xss-protection)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/protonemedia/laravel-xss-protection/run-tests?label=tests)](https://github.com/protonemedia/laravel-xss-protection/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/protonemedia/laravel-xss-protection.svg?style=flat-square)](https://packagist.org/packages/protonemedia/laravel-xss-protection)

Laravel Middleware to protect your app against Cross-site scripting (XSS). It sanitizes request input by utilising the [Security Core](https://github.com/GrahamCampbell/Security-Core) package, and it can sanatize [Blade echo statements](https://laravel.com/docs/8.x/blade#displaying-data) as well.

* PHP 8.2 and higher
* Laravel 10 and higher

## Sponsor this package!

❤️ We proudly support the community by developing Laravel packages and giving them away for free. If this package saves you time or if you're relying on it professionally, please consider [sponsoring the maintenance and development](https://github.com/sponsors/pascalbaljet). Keeping track of issues and pull requests takes time, but we're happy to help!

## Installation

You can install the package via composer:

```bash
composer require protonemedia/laravel-xss-protection
```

You may publish the config file with:

```bash
php artisan vendor:publish --tag="xss-protection-config"
```

## Middleware Usage

You may use the `ProtoneMedia\LaravelXssProtection\Middleware\XssCleanInput` middleware in the route that handles the form submission.

```php
use App\Http\Controllers\CreateAccountController;
use ProtoneMedia\LaravelXssProtection\Middleware\XssCleanInput;

Route::post('account', CreateAccountController::class)->middleware(XssCleanInput::class);
```

If your app has a lot of forms handled by many different controllers, you could opt to register it as global middleware.

```php
// inside app\Http\Kernel.php

protected $middleware = [
   // ...
   \ProtoneMedia\LaravelXssProtection\Middleware\XssCleanInput::class,
];
```

If you register the middleware globally, you may exclude requests by using the static `skipWhen` method. You can add a callback to interact with the request:

```php
XssCleanInput::skipWhen(function (Request $request) {
    return $request->is('admin.*');
});
```

You can also exclude keys by using the static `skipKeyWhen` method. This also allows you to interact with the value and request.

```php
XssCleanInput::skipKeyWhen(function (string $key, $value, Request $request) {
    return in_array($key, [
        'current_password',
        'password',
        'password_confirmation',
    ]);
});
```

## Configuration

### File uploads

By default, the middleware allows file uploads. However, you may disallow file uploads by changing the `middleware.allow_file_uploads` configuration key to `false`.

### Blade echo statements

By default, the middleware sanitizes [Blade echo statements](https://laravel.com/docs/8.x/blade#displaying-data) like `{{ $name }}`, `{{{ $name }}}`, and `{!! $name !!}`. You may allow echo statements by changing the `middleware.allow_blade_echoes` configuration key to `true`.

### Completely replace malicious input

By default, the middleware transforms malicious input to `null`. You may configure the middleware to only transform the malicious part by setting the `middleware.completely_replace_malicious_input` configuration key to `false`. That way, an input string like `hey <script>alert('laravel')</script>` will be transformed to `hey` instead of `null`.

### Terminate request

Instead of transforming malicious input, you may configure the middleware to terminate the request whenever anything malicious has been found. You may do this by setting the `middleware.terminate_request_on_malicious_input` to `true`, which will throw an `HttpException` with status code 403.

### Dispatch event

You may configure the middleware to dispatch an event whenever malicious input has been found. Setting the `middleware.dispatch_event_on_malicious_input` to `true` will dispatch an `ProtoneMedia\LaravelXssProtection\Events\MaliciousInputFound` event with the sanitized keys, the original request and the sanitized request.

```php
use Illuminate\Support\Facades\Event;
use ProtoneMedia\LaravelXssProtection\Events\MaliciousInputFound;

Event::listen(function (MaliciousInputFound $event) {
    $event->sanitizedKeys;
    $event->originalRequest;
    $event->sanitizedRequest;
});
```

### Additional configuration for `voku/anti-xss`

As of version 1.6.0, you may provide additional configuration for the `voku/anti-xss` package. You may do this by filling the `middleware.anti_xss` key. This is similar to the [Laravel Security](https://github.com/GrahamCampbell/Laravel-Security) package, which this package used to rely on.

```php
'anti_xss' => [
    'evil' => [
        'attributes' => ['href'],
        'tags' => ['video'],
    ],

    'replacement' => '*redacted*',
]
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about what has changed recently.

## Testing

```bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Other Laravel packages

* [`Laravel Blade On Demand`](https://github.com/protonemedia/laravel-blade-on-demand): Laravel package to compile Blade templates in memory.
* [`Laravel Cross Eloquent Search`](https://github.com/protonemedia/laravel-cross-eloquent-search): Laravel package to search through multiple Eloquent models.
* [`Laravel Eloquent Scope as Select`](https://github.com/protonemedia/laravel-eloquent-scope-as-select): Stop duplicating your Eloquent query scopes and constraints in PHP. This package lets you re-use your query scopes and constraints by adding them as a subquery.
* [`Laravel FFMpeg`](https://github.com/protonemedia/laravel-ffmpeg): This package provides an integration with FFmpeg for Laravel. The storage of the files is handled by Laravel's Filesystem.
* [`Laravel MinIO Testing Tools`](https://github.com/protonemedia/laravel-minio-testing-tools): Run your tests against a MinIO S3 server.
* [`Laravel Mixins`](https://github.com/protonemedia/laravel-mixins): A collection of Laravel goodies.
* [`Laravel Paddle`](https://github.com/protonemedia/laravel-paddle): Paddle.com API integration for Laravel with support for webhooks/events.
* [`Laravel Task Runner`](https://github.com/protonemedia/laravel-task-runner): Write Shell scripts like Blade Components and run them locally or on a remote server.
* [`Laravel Verify New Email`](https://github.com/protonemedia/laravel-verify-new-email): This package adds support for verifying new email addresses: when a user updates its email address, it won't replace the old one until the new one is verified.

## Security

If you discover any security-related issues, please email code@protone.media instead of using the issue tracker. Please do not email any questions, open an issue if you have a question.

## Credits

- [Pascal Baljet](https://github.com/pascalbaljet)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Treeware

This package is [Treeware](https://treeware.earth). If you use it in production, then we ask that you [**buy the world a tree**](https://plant.treeware.earth/pascalbaljetmedia/laravel-analytics-event-tracking) to thank us for our work. By contributing to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.
