{{-- Laravel XSS Protection Guidelines for AI Code Assistants --}}
{{-- Source: https://github.com/protonemedia/laravel-xss-protection --}}
{{-- License: MIT | (c) ProtoneMedia --}}

## Laravel XSS Protection

- `protonemedia/laravel-xss-protection` provides middleware that sanitizes request input to mitigate XSS attacks, using `voku/anti-xss` under the hood.
- Always activate the `laravel-xss-protection-development` skill when working with XSS input sanitization, the `XssCleanInput` middleware, or any code that references this package's middleware, config, or events.
