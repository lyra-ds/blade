# Versioning Policy

`lyra-ds/blade` follows [Semantic Versioning 2.0.0](https://semver.org/) with independent SemVer from the rest of the Lyra DS ecosystem (`@lyra-ds/styles`, `@lyra-ds/react`, and `@lyra-ds/alpine`). It receives the smallest bump required by changes to its own public surface.

## Independent SemVer and Coordinated Releases

Independent versions do not remove coordination. When a change in `@lyra-ds/styles` or `@lyra-ds/alpine` alters a shared CSS, markup, state, or behavior contract, `lyra-ds/blade` updates in coordination to maintain parity.

The project maintains a compatibility matrix in the [README](README.md#compatibility) detailing tested combinations of PHP, Laravel, `@lyra-ds/styles`, and `@lyra-ds/alpine`. Independent versioning does not imply that every combination is compatible.

## Post-1.0 SemVer

After reaching `1.0.0`, the stable public contract follows these SemVer rules:

- A **patch** (`1.0.x`) preserves the stable public contract. It contains backwards-compatible bug fixes, documentation improvements, internal refactoring, test updates, and internal dependency updates that preserve the contract.
- A **minor** (`1.x.0`) adds backwards-compatible functionality, such as new components, new props, new slots, new events, or new adapter capabilities. A minor release may also introduce deprecations of existing APIs with advance warning.
- A **major** (`x.0.0`) signals any backwards-incompatible change to the public contract, including:
  - Removing or renaming any component, prop, slot, custom event, or emitted CSS class.
  - Changing an emitted HTML tag or root element contract.
  - Raising the minimum PHP or Laravel version requirements.
  - Bumping the required major version range of `@lyra-ds/styles` or `@lyra-ds/alpine`.

## Declared Public API Surface

The following items constitute the **public API contract** of `lyra-ds/blade`. A breaking change to any of these requires a major release:

1. **Component tags** — the short `<lyra:slug>` syntax and the namespaced `<x-lyra::slug>` syntax for all shipped components registered by `BladeServiceProvider`.
2. **Component `@props`** — declared component props, their accepted types, defaults, and validation behaviors.
3. **Named slots** — documented slots (such as `title`, `header`, `footer`, `trigger`, etc.) and default slot behavior.
4. **Pass-through of `$attributes` and owned-root policy** — standard HTML attributes, event listeners, class merges, and Alpine directives forwarded to component root elements:
   - **Owned-root `x-data` policy:** Components that emit an interactive `lyra*` Alpine `x-data` binding own their root element exclusively. Consumer `x-data` is not accepted on these components. In `local` and `testing` environments, passing `x-data` throws a descriptive `InvalidArgumentException` to catch conflicting state scope early; in `production`, consumer `x-data` is stripped. Consumer state must be composed by wrapping the component in a parent container (e.g. `<div x-data="{ ... }"><lyra:tabs ... /></div>`).
   - Components without a Lyra Alpine binding (pure static components, or `code-block` when copy controls are disabled) accept and pass through consumer `x-data`.
   - The root guard also strips consumer attributes that would conflict with or duplicate component-owned bindings (such as `x-modelable` or `x-bind`).
5. **`lyra:*` custom DOM events** — custom events dispatched by components or their Alpine bindings (e.g. `lyra:sort`, `lyra:select`, `lyra:change`, `lyra:file-upload:*`).
6. **`data-*` state attributes** — state attributes emitted by components (e.g. `data-open`, `data-state`, `data-selected`, `data-sort-value`).
7. **Emitted `.lyra-*` classes** — guaranteed CSS class strings verified by the class-emission fixtures in `tests/Fixtures/class-emission/`.
8. **Directive `@lyraThemeScript` and `data-lyra-theme-key`** — the Blade theme script directive and its HTML attribute storage key contract.
9. **Service provider** — `LyraDs\Blade\BladeServiceProvider` and its automatic discovery configuration.
10. **`docs/api.json` schema and Boost guidelines** — the public schema of the documentation artifact consumed by `lyra-ds.dev` (including the `rootXData` property declaring `"owned"` or `"passthrough"` root status), along with the generated Laravel Boost guidelines.
11. **Alpine behavior contracts** — required `@lyra-ds/alpine` component binding factories and the `alpinejs >=3.13 <4` peer dependency requirement for interactive components.

## Explicitly NOT Public API

The following are internal implementation details and are **not** covered by SemVer guarantees. They may change in any release without a major version bump:

- Internal DOM structure of components, element hierarchy, or wrapper elements not specified as contract targets or slots.
- Generated element IDs (such as IDs produced dynamically via `uniqid()` or auto-assigned identifiers).
- PHP classes in `src/` other than `LyraDs\Blade\BladeServiceProvider` (for example `BladePropParser`, `DocsApiGenerator`, `IconRegistry`, `OwnedRoot`, `ShortComponentSyntax`, and `ThemeScript`).
- Undocumented CSS classes or private utility styles.
- Development scripts, tests, test fixtures, browser testing infrastructure, and internal tooling in `bin/`, `browser/`, `tests/`, and `.batuta/`.

## Deprecation Policy

When an existing API or feature is slated for removal, it must first be **deprecated in a minor release** (`1.x.0`). Deprecations will include:
- A clear notice in the documentation and release notes.
- Guidance on the replacement API or upgrade path.
- Deprecation warnings or docblock annotations where applicable.

Deprecated APIs remain functional until the next major release (`2.0.0`), where they may be removed.

## Migration Guides

Every major release ships with a dedicated **migration guide** (e.g., `docs/migration-1-0.md`, `docs/migration-2-0.md`) and a corresponding `BREAKING CHANGES` section in the changelog, documenting all breaking changes and concrete migration steps for application code.
