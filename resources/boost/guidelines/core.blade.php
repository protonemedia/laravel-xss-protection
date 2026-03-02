{{-- Laravel XSS Protection Guidelines for AI Code Assistants --}}
{{-- Source: https://github.com/protonemedia/laravel-xss-protection --}}
{{-- License: MIT | (c) Protone Media --}}

## XSS Protection

- `protonemedia/laravel-xss-protection` provides middleware that sanitizes request input to protect against Cross-site scripting (XSS), using the `voku/anti-xss` package, with optional Blade echo statement sanitization.
- Always activate the `xss-protection-development` skill when working with XSS sanitization, the `XssCleanInput` middleware, input filtering, Blade echo cleaning, or the `MaliciousInputFound` event.
