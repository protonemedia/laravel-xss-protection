{{-- Laravel XSS Protection Guidelines for AI Code Assistants --}}
{{-- Source: https://github.com/protonemedia/laravel-xss-protection --}}
{{-- License: MIT | (c) ProtoneMedia --}}

## Laravel XSS Protection

- Middleware to sanitize request input (and optionally Blade echoes) to reduce XSS risk.
- Always activate the `laravel-xss-protection-development` skill when making package-specific changes.
- For middleware usage, skip rules, configuration, and events, consult:
  - `resources/boost/skills/laravel-xss-protection-development/references/laravel-xss-protection-guide.md`
