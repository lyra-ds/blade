# Lyra Blade Components

Thin Blade wrappers for the Lyra Design System in Laravel applications. Each component renders the same canonical `.lyra-*` classes as its React counterpart—all appearance lives in `@lyra-ds/styles`, never in this Composer package.

The package ships 26 static anonymous Blade components under the `lyra` namespace. Laravel discovers its service provider automatically, so components are ready to use as `<x-lyra::button>`, `<x-lyra::input>`, and the other tags listed below.

## Quickstart

### 1. Install the Blade package

```bash
composer require lyra-ds/blade
```

### 2. Install the styles and fonts

```bash
npm i @lyra-ds/styles @fontsource/plus-jakarta-sans @fontsource/jetbrains-mono
```

Follow the `@lyra-ds/styles` font setup to load Plus Jakarta Sans and JetBrains Mono in your application.

### 3. Import the styles once

With Vite, import the package from your JavaScript entry point, usually `resources/js/app.js`:

```js
import '@lyra-ds/styles';
```

Alternatively, import the CSS export from your application's stylesheet:

```css
@import '@lyra-ds/styles/styles.css';
```

`lyra-ds/blade` ships no CSS. The styles package is the single source of appearance for both the Blade and React components.

### 4. Render your first components

```blade
<x-lyra::button variant="primary">Save</x-lyra::button>
```

Component props and ordinary HTML attributes can be combined. For example, this input renders its label and validation message while passing `name`, `type`, and `autocomplete` to the underlying input:

```blade
<x-lyra::input
    name="email"
    type="email"
    label="Email address"
    error="Enter a valid email address."
    autocomplete="email"
/>
```

## Components

| Category | Components |
| --- | --- |
| Buttons | `button`, `icon-button` |
| Display | `badge`, `tag`, `card`, `avatar` |
| Feedback | `alert`, `spinner`, `skeleton`, `progress` |
| Data | `stat`, `empty-state`, `table` |
| Layout | `container`, `stack`, `grid`, `separator` |
| Forms | `fieldset`, `form-row`, `input`, `textarea`, `select`, `checkbox`, `radio`, `switch` |
| Navigation | `breadcrumb`, `pagination` |

Use each name with the package namespace—for example, `button` becomes `<x-lyra::button>`.

## Class parity with React

Every component emits exactly the class strings emitted by the corresponding Lyra React component. Data-driven class-emission tests enforce that contract using the fixtures in `tests/Fixtures/class-emission/`, keeping Blade and React on the same styling surface.

## Compatibility

| `lyra-ds/blade` | Laravel 12 | Laravel 13 | PHP 8.3 | PHP 8.4 |
| --- | --- | --- | --- | --- |
| `0.x` (unreleased/dev) | Supported | Supported | Supported | Supported |

Laravel 11 is not supported because it reached security end-of-life in March 2026.

Package versions are independent from the React and `@lyra-ds/styles` package versions. This matrix will grow as versions are released.

## Interactivity

The current package is static-only and does not require Alpine.js. Interactive components such as Dropdown, Dialog, and Tabs are planned for a future phase; Alpine.js will be a suggested peer for those components and will never be bundled.

## Requirements

- PHP 8.3 or later
- Laravel 12 or 13

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## License

This package is open-sourced software licensed under the [MIT License](LICENSE).
