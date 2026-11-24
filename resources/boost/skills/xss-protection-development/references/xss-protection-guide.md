# Laravel XSS Protection Reference

Complete reference for `protonemedia/laravel-xss-protection`. Source: https://github.com/protonemedia/laravel-xss-protection

## Installation

```bash
composer require protonemedia/laravel-xss-protection
```

Publish the config file:

```bash
php artisan vendor:publish --tag="xss-protection-config"
```

## Middleware Usage

### Per-route registration
```php
use App\Http\Controllers\CreateAccountController;
use ProtoneMedia\LaravelXssProtection\Middleware\XssCleanInput;

Route::post('account', CreateAccountController::class)->middleware(XssCleanInput::class);
```

### Global registration
```php
// inside app/Http/Kernel.php
protected $middleware = [
    // ...
    \ProtoneMedia\LaravelXssProtection\Middleware\XssCleanInput::class,
];
```

## Skipping Routes

Use the static `skipWhen` method to exclude entire requests from sanitization. Register in a service provider:

```php
use Illuminate\Http\Request;
use ProtoneMedia\LaravelXssProtection\Middleware\XssCleanInput;

XssCleanInput::skipWhen(function (Request $request) {
    return $request->is('admin.*');
});
```

Multiple callbacks can be registered — if any returns `true`, sanitization is skipped for that request.

## Skipping Keys

Use the static `skipKeyWhen` method to exclude specific input keys. The callback receives the key, value, and the original request:

```php
XssCleanInput::skipKeyWhen(function (string $key, $value, Request $request) {
    return in_array($key, [
        'current_password',
        'password',
        'password_confirmation',
    ]);
});
```

This is useful for password fields, WYSIWYG content fields, or any input where sanitization would corrupt the value.

## Configuration

The config file is at `config/xss-protection.php`:

```php
return [
    'blade_echo_tags' => [
        ['{!!', '!!}'],
        ['{{', '}}'],
        ['{{{', '}}}'],
    ],

    'middleware' => [
        'allow_file_uploads' => true,
        'allow_blade_echoes' => false,
        'completely_replace_malicious_input' => true,
        'terminate_request_on_malicious_input' => false,
        'dispatch_event_on_malicious_input' => false,
    ],

    'anti_xss' => [
        'evil' => [
            'attributes' => null,
            'tags' => null,
        ],
        'replacement' => null,
    ],
];
```

### File Uploads

`allow_file_uploads` (default: `true`) — when set to `false`, the middleware replaces uploaded files with `null`.

### Blade Echo Statements

`allow_blade_echoes` (default: `false`) — when `false`, the middleware strips Blade echo statements (`{{ }}`, `{{{ }}}`, `{!! !!}`) from input. Set to `true` only if you are certain no user input reaches Blade echo tags unsanitized.

### Completely Replace Malicious Input

`completely_replace_malicious_input` (default: `true`) — when `true`, any input containing malicious content is replaced with `null`. When `false`, only the malicious part is removed and the safe portion is kept.

Example with `completely_replace_malicious_input` set to `false`:
- Input: `hey <script>alert('xss')</script>` → Output: `hey`

Example with `completely_replace_malicious_input` set to `true` (default):
- Input: `hey <script>alert('xss')</script>` → Output: `null`

### Terminate Request

`terminate_request_on_malicious_input` (default: `false`) — when `true`, the middleware throws an `HttpException` with status code 403 instead of sanitizing input.

### Dispatch Event

`dispatch_event_on_malicious_input` (default: `false`) — when `true`, dispatches a `MaliciousInputFound` event whenever malicious input is detected.

## Events

### MaliciousInputFound

Dispatched when `dispatch_event_on_malicious_input` is `true` and malicious input is found:

```php
use Illuminate\Support\Facades\Event;
use ProtoneMedia\LaravelXssProtection\Events\MaliciousInputFound;

Event::listen(function (MaliciousInputFound $event) {
    $event->sanitizedKeys;      // array of input keys that were sanitized
    $event->originalRequest;    // the original Request before sanitization
    $event->sanitizedRequest;   // the Request after sanitization
});
```

## Anti-XSS Configuration

Additional configuration for the underlying `voku/anti-xss` package via the `anti_xss` config key:

```php
'anti_xss' => [
    'evil' => [
        'attributes' => ['href'],   // additional evil attributes
        'tags' => ['video'],        // additional evil tags
    ],
    'replacement' => '*redacted*',  // replacement string for evil content
],
```

## Clearing Callbacks

In testing or when resetting state, clear all registered skip callbacks:

```php
XssCleanInput::clearCallbacks();
```
