# Contributing

Thank you for contributing to Lyra Blade Components.

## Development setup

```bash
composer install
```

Do not run `php artisan boost:install`: Laravel Boost is included only as development tooling, and its installer targets full applications.

## Making changes

- Keep code and documentation in English.
- Follow Laravel package conventions and keep changes focused.
- Do not add CSS or JavaScript; visual styles belong in `@lyra-ds/styles`.
- Add or update deterministic tests for behavior changes.

Before submitting a pull request, run:

```bash
vendor/bin/pint --test
vendor/bin/pest
```

By participating, you agree to follow the [Code of Conduct](CODE_OF_CONDUCT.md).
