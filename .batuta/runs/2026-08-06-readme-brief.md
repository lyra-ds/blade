# Brief — README/quickstart — pós-fase 1

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/readme (a git worktree of lyra-ds/blade; branch batuta/readme).
CRITICAL PATH RULE: every file you create or edit MUST be under the current working directory using RELATIVE paths.

## Goal

Rewrite README.md as the package's real front page: positioning + full quickstart + compat matrix + notes. English only. Mirror the tone and structure of the sibling React README (quoted below) — thin wrapper philosophy, CSS lives in @lyra-ds/styles.

## Facts (verified — do not invent beyond them)

- Package: `lyra-ds/blade` — Blade components for the Lyra Design System. PHP >= 8.3, Laravel 11/12 (illuminate/support + illuminate/view ^11.0|^12.0). MIT.
- 26 static components shipped, used as `<x-lyra::button>`, `<x-lyra::input>`, etc. (anonymous components, namespace `lyra`; service provider auto-discovered).
- Central guarantee: every component emits EXACTLY the class strings the React package emits, enforced by data-driven class-emission tests (fixtures in tests/Fixtures/class-emission/).
- CSS: NONE ships in this package. All appearance comes from the npm package `@lyra-ds/styles` (import once, e.g. `import '@lyra-ds/styles';` in the app's JS entry, or `@import '@lyra-ds/styles/styles.css';` in CSS — both exports exist).
- Fonts: `@fontsource/plus-jakarta-sans` + `@fontsource/jetbrains-mono` as documented by @lyra-ds/styles.
- Alpine.js: NOT required; phase 1 is static-only. Interactive components (Dropdown, Dialog, Tabs...) come in a future phase with Alpine as a suggested (never bundled) peer.
- Versioning: independent from the React/styles packages. Compat matrix table required: rows = this package's versions (currently `0.x` unreleased/dev), columns: Laravel 11 / Laravel 12 / PHP 8.3 / PHP 8.4 — all supported today. Note that the matrix will grow as versions release.
- React sibling README for tone (quote): "Thin, tree-shakable React wrappers ... Each component is a light wrapper that renders the canonical .lyra-* markup — all appearance lives in @lyra-ds/styles, never in JS."

## Quickstart section (exact steps to document)

1. `composer require lyra-ds/blade`
2. `npm i @lyra-ds/styles` (+ fonts)
3. Import styles once (Vite: `import '@lyra-ds/styles';` in resources/js/app.js, or the CSS import variant)
4. First component: a small Blade snippet using `<x-lyra::button variant="primary">Save</x-lyra::button>` and one more realistic sample (e.g. input with label + error).
5. Component list: a compact table of the 26 components grouped by category (Buttons: button, icon-button; Display: badge, tag, card, avatar; Feedback: alert, spinner, skeleton, progress; Data: stat, empty-state, table; Layout: container, stack, grid, separator; Forms: fieldset, form-row, input, textarea, select, checkbox, radio, switch; Navigation: breadcrumb, pagination).

## Boundaries / Scope (closed list)

- README.md (rewrite)

Nothing else. Keep LICENSE/CONTRIBUTING references intact if the current README links them. Run `vendor/bin/pest` once to confirm nothing broke (README-only change; suite must stay 260 passed) and `vendor/bin/pint --test`.

## Expected evidence

Report: the sections written, pest/pint output confirming green, uncertainties declared.

## Stop conditions

Stop and report when: a fact you need is missing from this brief (do not invent), the same command fails twice, or the change needs files beyond Scope.
