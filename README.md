# Lyra Blade Components

Blade components for the Lyra Design System in Laravel applications.

## Status

- Phase 1: Static components — in progress.
- Phase 2: Alpine.js-powered interactive components — not started.

Alpine.js will be a suggested peer for interactive components. It will never be bundled with this package.

## Requirements

- PHP 8.3 or later
- Laravel 11 or 12

## Installation

Install the Blade package and the separate styles package:

```bash
composer require lyra-ds/blade
npm i @lyra-ds/styles
```

The Composer package ships no CSS or JavaScript. Import the styles once in `resources/css/app.css`, which Vite already processes in a standard Laravel application:

```css
@import '@lyra-ds/styles';
```

## Usage

Laravel discovers the package service provider automatically. Components use the `lyra` namespace:

```blade
<x-lyra::button variant="primary">
    Save changes
</x-lyra::button>
```

Static components are being added during phase 1. Refer to the release notes for the components available in your installed version.

## Compatibility

| `lyra-ds/blade` | `@lyra-ds/styles` | Laravel | PHP |
| --- | --- | --- | --- |
| `0.x` | `^0.4` | 11 or 12 | >= 8.3 |

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## License

This package is open-sourced software licensed under the [MIT License](LICENSE).
