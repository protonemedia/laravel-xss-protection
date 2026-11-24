---
name: xss-protection-development
description: Build and work with protonemedia/laravel-xss-protection features including applying XSS sanitization middleware, skipping routes or keys, configuring sanitization behavior, handling malicious input events, and tuning the underlying voku/anti-xss options.
license: MIT
metadata:
  author: Protone Media
---

# XSS Protection Development

## Overview
Use protonemedia/laravel-xss-protection to sanitize request input against Cross-site scripting (XSS). The package provides a middleware that leverages voku/anti-xss, supports Blade echo statement sanitization, and offers flexible configuration for file uploads, input replacement, request termination, and event dispatching.

## When to Activate
- Activate when working with XSS protection, input sanitization, or security middleware in Laravel.
- Activate when code references `XssCleanInput`, `MaliciousInputFound`, `BladeEchoes`, or the `xss-protection` config.
- Activate when the user wants to add, configure, or customize XSS sanitization on request input.

## Scope
- In scope: middleware registration, route/key skipping, configuration options, malicious input events, Blade echo sanitization, voku/anti-xss tuning.
- Out of scope: output encoding, CSP headers, general security hardening unrelated to input sanitization.

## Workflow
1. Identify the task (middleware setup, skipping routes/keys, configuring behavior, listening for events, etc.).
2. Read `references/xss-protection-guide.md` and focus on the relevant section.
3. Apply the patterns from the reference, keeping code minimal and Laravel-native.

## Core Concepts

### Middleware Registration (per-route)
```php
use ProtoneMedia\LaravelXssProtection\Middleware\XssCleanInput;

Route::post('account', CreateAccountController::class)->middleware(XssCleanInput::class);
```

### Global Middleware Registration
```php
// inside app/Http/Kernel.php
protected $middleware = [
    // ...
    \ProtoneMedia\LaravelXssProtection\Middleware\XssCleanInput::class,
];
```

### Skipping Routes
```php
use Illuminate\Http\Request;
use ProtoneMedia\LaravelXssProtection\Middleware\XssCleanInput;

XssCleanInput::skipWhen(function (Request $request) {
    return $request->is('admin.*');
});
```

### Skipping Keys
```php
XssCleanInput::skipKeyWhen(function (string $key, $value, Request $request) {
    return in_array($key, [
        'current_password',
        'password',
        'password_confirmation',
    ]);
});
```

### Listening for Malicious Input
```php
use ProtoneMedia\LaravelXssProtection\Events\MaliciousInputFound;

Event::listen(function (MaliciousInputFound $event) {
    $event->sanitizedKeys;
    $event->originalRequest;
    $event->sanitizedRequest;
});
```

## Do and Don't

Do:
- Use `skipWhen` to exclude entire routes (e.g., admin or webhook endpoints that accept trusted HTML).
- Use `skipKeyWhen` to exclude specific keys like passwords that should not be sanitized.
- Set `completely_replace_malicious_input` to `false` when you want to keep the safe portion of input.
- Set `terminate_request_on_malicious_input` to `true` for strict environments that should reject malicious requests outright.
- Enable `dispatch_event_on_malicious_input` to log or audit sanitization events.
- Publish the config file with `php artisan vendor:publish --tag="xss-protection-config"` before customizing options.

Don't:
- Don't register the middleware globally without adding `skipWhen` or `skipKeyWhen` callbacks for routes/keys that legitimately accept HTML (e.g., WYSIWYG editors).
- Don't set `allow_blade_echoes` to `true` unless you are certain no user input flows through Blade echo statements unsanitized.
- Don't set `allow_file_uploads` to `false` unless you intentionally want the middleware to null out uploaded files.
- Don't rely solely on this middleware for security — always validate and escape output as well.

## References
- `references/xss-protection-guide.md`
