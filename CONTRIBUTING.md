# Contributing

Thank you for contributing to Lyra Blade Components.

## Development Setup

```bash
composer install
npm ci
```

Do not run `php artisan boost:install`: Laravel Boost is included only as development tooling, and its installer targets full applications.

## Making Changes

- Keep code and documentation in English.
- Follow Laravel package conventions and keep changes focused.
- Do not add CSS or JavaScript; visual styles belong in `@lyra-ds/styles` and interactivity belongs in `@lyra-ds/alpine`.
- Add or update deterministic tests for behavior changes.
- **Docs example required:** Every new or updated component requires a dedicated usage snippet in `resources/docs-examples/<slug>.blade.php`. The test suite fails if a component lacks an example.
- **Regenerate artifacts:** When adding or changing a component, prop, or example, regenerate the public artifacts using:
  ```bash
  php bin/generate-docs-api
  php bin/generate-boost-guidelines
  ```
  Commit the updated `docs/api.json` and Boost guidelines alongside your changes (`php bin/generate-*`).
- **Browser tests:** Interactive components backed by Alpine must pass browser tests. Run `npm run test:browser` (see browser CI for prerequisites and details).

## Commit Messages and Releases

We use [Conventional Commits](https://www.conventionalcommits.org/) to power automated releases via Release Please:

- Use standard types: `feat:`, `fix:`, `docs:`, `chore:`, `refactor:`, `test:`, etc.
- **Breaking changes:** Use `feat!:` or `fix!:` with a `BREAKING CHANGE:` footer explaining the breaking change and necessary migrations:
  ```gitcommit
  feat!: rename item prop to items

  BREAKING CHANGE: The `item` prop on `<lyra:dropdown>` is renamed to `items`.
  ```
  Release Please detects breaking commit formats to bump the major version and create the `⚠ BREAKING CHANGES` section in the changelog.
- Refer to [VERSIONING.md](VERSIONING.md) for our full SemVer policy, breaking change thresholds, and deprecation cycle.

## Pre-PR Checklist

Before submitting a pull request, ensure all checks pass:

```bash
composer validate --strict
vendor/bin/pint --test
vendor/bin/pest
npx playwright install chromium
npm run test:browser
```

The browser suite loads each rendered fixture from `docs/api.json` with locally bundled
`@lyra-ds/alpine` and `@lyra-ds/styles`. It checks Alpine hydration and key interactions.
Pull requests run Chromium; pushes to `main` also run Firefox and WebKit. To run all
three locally, install them with `npx playwright install chromium firefox webkit` and
run `npx playwright test`. All three projects are always registered; `--project=<name>`
selects one.

By participating, you agree to follow the [Code of Conduct](CODE_OF_CONDUCT.md).
