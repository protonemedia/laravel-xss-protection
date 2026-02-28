---
name: laravel-xss-protection-development
description: Build and work with protonemedia/laravel-xss-protection features including XSS input sanitization middleware, configuration of replacement modes, Blade echo sanitization, and event dispatching on malicious input.
license: MIT
metadata:
  author: ProtoneMedia
---

# Laravel XSS Protection Development

## Overview
Use protonemedia/laravel-xss-protection to sanitize request input and mitigate XSS attacks. Supports middleware-based sanitization, configurable replacement modes, Blade echo sanitization, and malicious-input events.

## When to Activate
- Activate when working with XSS input sanitization or request filtering in Laravel.
- Activate when code references `XssCleanInput`, `MaliciousInputFound`, or the `xss-protection` config.
- Activate when the user wants to add, configure, or customize XSS middleware behavior.

## Scope
- In scope: middleware registration, configuration, skipping requests/keys, event handling, integration patterns.
- Out of scope: modifying this package's internal source code unless the user explicitly says they are contributing to the package.

## Workflow
1. Identify the task (install/setup, middleware registration, configuration, event handling, tests, etc.).
2. Read `references/laravel-xss-protection-guide.md` and focus on the relevant section.
3. Apply the patterns from the reference, keeping code minimal and Laravel-native.

## Core Concepts

### Middleware Registration
Apply the middleware to routes that handle user input:

```php
use ProtoneMedia\LaravelXssProtection\Middleware\XssCleanInput;

Route::post('account', CreateAccountController::class)
    ->middleware(XssCleanInput::class);
```

### Skipping Requests
Skip specific requests when the middleware is registered globally:

```php
use Illuminate\Http\Request;
use ProtoneMedia\LaravelXssProtection\Middleware\XssCleanInput;

XssCleanInput::skipWhen(function (Request $request) {
    return $request->is('admin.*');
});
```

### Skipping Input Keys
Skip certain keys such as password fields:

```php
XssCleanInput::skipKeyWhen(function (string $key, $value, Request $request) {
    return in_array($key, ['current_password', 'password', 'password_confirmation'], true);
});
```

### Malicious Input Events
Listen for sanitization events when enabled in config:

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
- Always skip password and secret fields with `skipKeyWhen()` to avoid corrupting credentials.
- Use `skipWhen()` to bypass sanitization for trusted admin routes or webhooks.
- Use the `MaliciousInputFound` event for logging or alerting on XSS attempts.
- Publish and review the config file before changing defaults.

Don't:
- Don't sanitize password fields — use `skipKeyWhen()` to exclude them.
- Don't allow Blade echoes (`allow_blade_echoes`) without understanding the XSS implications.
- Don't assume `completely_replace_malicious_input` is always appropriate — partial replacement may be better for UX.
- Don't invent undocumented methods/options; stick to the docs and reference.

## References
- `references/laravel-xss-protection-guide.md`
