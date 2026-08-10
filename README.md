# Lyra Blade Components

Thin Blade wrappers for the Lyra Design System in Laravel applications. Each component renders the same canonical `.lyra-*` classes as its React counterpart—all appearance lives in `@lyra-ds/styles`, never in this Composer package.

The package ships 42 static and 30 interactive anonymous Blade components under the `lyra` namespace. Laravel discovers its service provider automatically, so components are ready to use with the short syntax (`<lyra:button>`) or the equivalent namespaced syntax (`<x-lyra::button>`).

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
| Actions | `action-bar`, `button`, `icon-button` |
| Brand and identity | `avatar`, `brand`, `icon`, `person-cell` |
| Feedback and status | `alert`, `badge`, `empty-state`, `progress`, `segmented-ring`, `skeleton`, `spinner`, `stat`, `tag`, `toast`, `toast-stack` |
| Layout and structure | `card`, `container`, `footer`, `grid`, `page-header`, `separator`, `shell`, `stack` |
| Navigation | `app-sidebar`, `bottom-nav`, `breadcrumb`, `nav-link`, `navbar`, `pagination`, `sidebar-group`, `stepper`, `table-of-contents`, `tabs`, `workspace-switcher` |
| Forms and selection | `checkbox`, `checkbox-group`, `combobox`, `fieldset`, `form-row`, `input`, `radio`, `radio-group`, `segmented-control`, `select`, `switch`, `textarea` |
| Dates and scheduling | `calendar`, `date-picker`, `date-range-picker`, `recurrence-selector`, `slot-picker`, `time-input`, `time-picker`, `time-zone-picker`, `weekly-schedule-editor` |
| Data and files | `code-block`, `data-table`, `file-manager`, `file-upload`, `table` |
| Overlays and disclosure | `accordion`, `bottom-sheet`, `command-palette`, `cookie-banner`, `dialog`, `drawer`, `dropdown`, `popover`, `tooltip` |

Use each name with either equivalent form—for example, `button` becomes `<lyra:button>` or `<x-lyra::button>`.

## Class parity with React

Every component emits exactly the class strings emitted by the corresponding Lyra React component. Data-driven class-emission tests enforce that contract using the fixtures in `tests/Fixtures/class-emission/`, keeping Blade and React on the same styling surface.

## Compatibility

| `lyra-ds/blade` | Laravel 12 | Laravel 13 | PHP 8.3 | PHP 8.4 | `@lyra-ds/styles` | `@lyra-ds/alpine` |
| --- | --- | --- | --- | --- | --- | --- |
| `0.x` (unreleased/dev) | Supported | Supported | Supported | Supported | `^0.4.2` | `^0.4.0` |

These are the versions the current `main` branch was tested against.

Laravel 11 is not supported because it reached security end-of-life in March 2026.

Package versions are independent from the React, `@lyra-ds/styles`, and `@lyra-ds/alpine` package versions. This matrix will grow as versions are released.

## Releasing

Conventional commits drive the changelog in the bot-maintained release PR. Merge that PR to cut a release; the resulting tag triggers Packagist through its GitHub webhook. Review the compatibility matrix above for every release.

## Interactivity

The Alpine-backed components are `accordion`, `app-sidebar`, `bottom-sheet`, `calendar`, `code-block`, `combobox`, `command-palette`, `cookie-banner`, `data-table`, `date-picker`, `date-range-picker`, `dialog`, `drawer`, `dropdown`, `file-manager`, `file-upload`, `popover`, `recurrence-selector`, `segmented-control`, `sidebar-group`, `slot-picker`, `table-of-contents`, `tabs`, `time-input`, `time-picker`, `time-zone-picker`, `toast-stack`, `tooltip`, `weekly-schedule-editor`, and `workspace-switcher`. They get their behavior from the `@lyra-ds/alpine` plugin. Alpine.js `>=3.13 <4` is a consumer-installed peer and is never bundled.

Static components continue to work without Alpine. Alpine-backed components are static-first: except for the data-driven regions described below, their structure and initial state are present in the served HTML and remain inert until Alpine starts.

Some repeated content is intentionally runtime-rendered because filtering, locale-aware generation, queue state, or user-added rows belong to the Alpine binding. `calendar`, `combobox`, `command-palette`, `file-upload`, `time-picker`, `toast-stack`, and `weekly-schedule-editor` stamp those grids, options, items, toasts, or rows through `x-for`; those repeated regions do not exist in the served DOM until Alpine boots.

`data-table` keeps sorting server-side by default: its header controls emit `lyra:sort`, and the application returns the rows in the requested order. Set `clientSort` to opt into in-browser sorting; sortable cells then provide their comparison value through `data-sort-value`.

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

### Theme

Place `@lyraThemeScript` in the document `<head>`, before stylesheets that use theme tokens. It emits a blocking inline script that applies the stored Lyra theme before the first paint; the Alpine theme store takes over after it starts.

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    @lyraThemeScript
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{ $slot }}
</body>
</html>
```

The optional storage key is declared in one place—the directive argument—and the Alpine store reads it from the `<html data-lyra-theme-key>` attribute written by the script. Without an argument, the key defaults to `lyra-theme`; alternatively, declare `data-lyra-theme-key` on the `<html>` element in your layout and keep the argumentless directive.

```blade
@lyraThemeScript(config('app.theme_key'))
```

Toggle between the resolved light and dark themes through the Alpine store:

```blade
<button type="button" x-on:click="$store.theme.toggle()">
    Toggle theme
</button>
```

Add this required rule to your application's CSS:

```css
[x-cloak] {
    display: none !important;
}
```

The styles package does not ship this rule. Without it, closed menus and dialogs can flash before Alpine boots.

Livewire is a first-class integration. Twenty-four components expose controllable state through `x-modelable`:

- `open`: `bottom-sheet`, `command-palette` (overlay mode), `dialog`, `drawer`, `dropdown`, `popover`, and `workspace-switcher`
- `selected`: `calendar`, `date-picker`, `date-range-picker`, `time-input`, and `time-picker`
- `value`: `combobox`, `recurrence-selector`, and `segmented-control`
- Component-specific state: `accordion` (`openItems`), `app-sidebar` (`collapsed`), `data-table` (`selected`, or `sorting` via `x-modelable`), `file-manager` (`view`, with `query` modelable on its search field), `file-upload` (`items`), `slot-picker` (`date`, or `timezone` via `x-modelable`), `table-of-contents` (`activeId`), `tabs` (`active`), and `weekly-schedule-editor` (`value`, or `exceptions` via `x-modelable`)

Use `wire:model` or `x-model` directly on a component tag for its root modelable state. Where alternatives are listed, select the target with the `x-modelable` attribute.

## Requirements

- PHP 8.3 or later
- Laravel 12 or 13

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## License

This package is open-sourced software licensed under the [MIT License](LICENSE).
