# Lyra Blade Components

Thin Blade wrappers for the Lyra Design System in Laravel applications. Each component renders the same canonical `.lyra-*` classes as its React counterpart—all appearance lives in `@lyra-ds/styles`, never in this Composer package.

The package ships 27 static and 7 interactive anonymous Blade components under the `lyra` namespace. Laravel discovers its service provider automatically, so components are ready to use with the short syntax (`<lyra:button>`) or the equivalent namespaced syntax (`<x-lyra::button>`).

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
<lyra:button variant="primary">Save</lyra:button>

{{-- Equivalent namespaced syntax: --}}
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
| Interactive | `dropdown`, `dialog`, `drawer`, `tabs`, `accordion`, `tooltip`, `popover` |

Use each name with either equivalent form—for example, `button` becomes `<lyra:button>` or `<x-lyra::button>`.

## Class parity with React

Every component emits exactly the class strings emitted by the corresponding Lyra React component. Data-driven class-emission tests enforce that contract using the fixtures in `tests/Fixtures/class-emission/`, keeping Blade and React on the same styling surface.

## Compatibility

| `lyra-ds/blade` | Laravel 12 | Laravel 13 | PHP 8.3 | PHP 8.4 | `@lyra-ds/styles` | `@lyra-ds/alpine` |
| --- | --- | --- | --- | --- | --- | --- |
| `0.x` (unreleased/dev) | Supported | Supported | Supported | Supported | `^0.4` | `^0.1.1` |

These are the versions the current `main` branch was tested against.

Laravel 11 is not supported because it reached security end-of-life in March 2026.

Package versions are independent from the React, `@lyra-ds/styles`, and `@lyra-ds/alpine` package versions. This matrix will grow as versions are released.

## Releasing

Conventional commits drive the changelog in the bot-maintained release PR. Merge that PR to cut a release; the resulting tag triggers Packagist through its GitHub webhook. Review the compatibility matrix above for every release.

## Interactivity

Dropdown, Dialog, Drawer, Tabs, Accordion, Tooltip, and Popover get their behavior from the `@lyra-ds/alpine` plugin. Alpine.js `>=3.13 <4` is a consumer-installed peer and is never bundled. Static components continue to work without Alpine; if Alpine is not loaded, interactive components remain inert in their served initial state.

After installing `@lyra-ds/styles` as described in the Quickstart, install Alpine.js and the Lyra plugin:

```bash
npm install alpinejs @lyra-ds/alpine
```

Then register the plugin before starting Alpine in `resources/js/app.js`:

```js
import Alpine from 'alpinejs';
import lyra from '@lyra-ds/alpine';

Alpine.plugin(lyra);
Alpine.start();
```

Add this required rule to your application's CSS:

```css
[x-cloak] {
    display: none !important;
}
```

The styles package does not ship this rule. Without it, closed menus and dialogs can flash before Alpine boots.

Livewire is a first-class integration. Controllable state is exposed through `x-modelable`: `open` for Dropdown, Dialog, Drawer, and Popover; `active` for Tabs; and `openItems` for Accordion. Use `wire:model` or `x-model` directly on the component tag.

## Requirements

- PHP 8.3 or later
- Laravel 12 or 13

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## License

This package is open-sourced software licensed under the [MIT License](LICENSE).
