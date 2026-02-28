# laravel-xss-protection development guide

For full documentation, see the README: https://github.com/protonemedia/laravel-xss-protection#readme

## At a glance
Middleware to sanitize request input and optionally sanitize Blade echoes to reduce XSS risk (uses voku/anti-xss).

## Local setup
- Install dependencies: `composer install`
- Keep the dev loop package-focused (avoid adding app-only scaffolding).

## Testing
- Run: `composer test` (preferred) or the repository’s configured test runner.
- Add regression tests for bug fixes.

## Notes & conventions
- Security-sensitive: avoid weakening sanitization defaults.
- Add tests for edge cases (nested arrays, JSON payloads, encoding).
- Ensure it plays nicely with Laravel's request lifecycle and does not mutate unintended data.
