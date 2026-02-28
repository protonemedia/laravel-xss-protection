# Laravel XSS Protection Reference

Complete reference for `protonemedia/laravel-xss-protection`.

Primary docs: https://github.com/protonemedia/laravel-xss-protection#readme

## What this package does

- Provides middleware that sanitizes request input to mitigate XSS.
- Uses `voku/anti-xss` under the hood.
- Can also sanitize Blade echo statements (`{{ }}`, `{{{ }}}`, `{!! !!}`) depending on configuration.

## PHP 8.4+ compatibility note

The package vendors a patched version of `voku/portable-utf8` in `vendor-lib` because upstream hasn’t tagged a PHP 8.4 compatible release yet.

Do not remove/alter this workaround without verifying upstream state.

## Installation

```bash
composer require protonemedia/laravel-xss-protection
```

Publish config:

```bash
php artisan vendor:publish --tag="xss-protection-config"
```

## Middleware usage

### Route middleware

Attach to the routes that handle user input:

```php
use ProtoneMedia\LaravelXssProtection\Middleware\XssCleanInput;

Route::post('account', CreateAccountController::class)
    ->middleware(XssCleanInput::class);
```

### Global middleware

Register in `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ...
    \ProtoneMedia\LaravelXssProtection\Middleware\XssCleanInput::class,
];
```

### Skipping certain requests

If registered globally, skip specific requests:

```php
use Illuminate\Http\Request;
use ProtoneMedia\LaravelXssProtection\Middleware\XssCleanInput;

XssCleanInput::skipWhen(function (Request $request) {
    return $request->is('admin.*');
});
```

### Skipping specific input keys

Skip certain keys (often password-like fields):

```php
XssCleanInput::skipKeyWhen(function (string $key, $value, Request $request) {
    return in_array($key, [
        'current_password',
        'password',
        'password_confirmation',
    ], true);
});
```

## Configuration

Key options (names from README; see config file for exact nesting):

### File uploads

- Default: allows file uploads.
- Disable by setting `middleware.allow_file_uploads` to `false`.

### Blade echo statements

- Default: sanitizes Blade echoes.
- Allow raw Blade echoes by setting `middleware.allow_blade_echoes` to `true`.

> Take care: allowing Blade echoes may reintroduce XSS if your views echo untrusted input.

### Completely replace malicious input

- Default: transforms malicious input to `null`.
- If you want to only strip the malicious parts, set `middleware.completely_replace_malicious_input` to `false`.

Example behavior (from README):

- Input: `hey <script>alert('laravel')</script>`
- Default output: `null`
- Partial replace output: `hey`

### Terminate request on malicious input

Instead of sanitizing, you can reject the request by setting:

- `middleware.terminate_request_on_malicious_input` to `true`

This throws an `HttpException` with status code 403.

### Dispatch an event when malicious input is found

Enable event dispatch:

- `middleware.dispatch_event_on_malicious_input` → `true`

The event class:

- `ProtoneMedia\LaravelXssProtection\Events\MaliciousInputFound`

Listening example:

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

As of v1.6.0 you can pass extra config via `middleware.anti_xss`:

```php
'anti_xss' => [
    'evil' => [
        'attributes' => ['href'],
        'tags' => ['video'],
    ],

    'replacement' => '*redacted*',
],
```

## Common patterns

- Apply middleware to form endpoints and API endpoints that accept user-generated HTML/text.
- If you need rich HTML input, consider skipping that specific key and handling sanitization separately with a stricter allowlist.

## Pitfalls / gotchas

- **Passwords & secrets:** avoid sanitizing password fields (use `skipKeyWhen`).
- **Breaking input expectations:** changing replacement mode can change validation behavior (e.g., string becomes `null`).
- **Blade echo sanitization:** sanitizing `{!! !!}` output can surprise developers; ensure docs and config defaults remain consistent.

## Testing

```bash
composer test
```
