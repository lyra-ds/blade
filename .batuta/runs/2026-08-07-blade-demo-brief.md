# Brief — Demo Laravel app consuming lyra-ds/blade (manual testing playground)

Work only inside /home/franciscpd/Projects/lyra-ds/blade-demo (empty directory, NOT a worktree — a brand-new standalone project). The package under test lives at /home/franciscpd/Projects/lyra-ds/blade (read-only for you: never edit anything there).

## Goal

A runnable Laravel demo app that consumes the local `lyra-ds/blade` package (composer path repository, symlinked — edits in the package show up live) plus the published npm assets (`@lyra-ds/styles`, `alpinejs`, `@lyra-ds/alpine`), with one demo page rendering static components AND the new interactive Dropdown, so the user can eyeball everything in a browser via `php artisan serve`.

## Context

- Machine: PHP 8.4, Node 24, Composer 2.10. Laravel current major is 13.
- The package: `lyra-ds/blade` (Blade components, namespace `<x-lyra::*>`, also short syntax `<lyra:*>`). No CSS/JS ships in it — CSS comes from `@lyra-ds/styles@^0.4` (npm), interactive behavior from `@lyra-ds/alpine@^0.1` (npm Alpine plugin).
- Consumer setup per the package design (docs/prd-fase-2-alpine.md in the package repo):
  ```js
  import Alpine from 'alpinejs';
  import lyra from '@lyra-ds/alpine';
  Alpine.plugin(lyra);
  Alpine.start();
  ```
- The Dropdown needs an `[x-cloak] { display: none !important; }` CSS rule to avoid flash before Alpine boots (check whether @lyra-ds/styles already ships one — look in node_modules after install; add it to app.css only if missing).
- Check the actual CSS entrypoint of `@lyra-ds/styles` in its package.json (exports/main/style fields) after npm install — do not guess the import path.

## Conventions

- Standard Laravel 13 skeleton conventions (this is a fresh app, keep it stock — no starter kits, no auth scaffolding).
- Change only what the demo needs; keep the diff to the skeleton minimal and readable.
- Deterministic tests; Pest (ships with Laravel 13 by default).
- Ignore any Compozy skills (cy-*) — implement directly.
- Method: if superpowers skills are available in your environment, conduct the work with them — `test-driven-development` where it applies. Otherwise, work test-first for the route test.

## Task

1. `composer create-project laravel/laravel .` (Laravel 13) in the empty directory.
2. composer.json: add a `repositories` entry `{"type": "path", "url": "../blade", "options": {"symlink": true}}` and require `lyra-ds/blade` as `*@dev` (or `dev-main`). Composer must symlink it — verify `vendor/lyra-ds/blade` is a symlink.
3. npm: install `@lyra-ds/styles@^0.4`, `alpinejs@^3`, `@lyra-ds/alpine@^0.1`. Wire resources/js/app.js with the consumer setup above and import the styles CSS in resources/css/app.css (real entrypoint, verified). Add the `[x-cloak]` rule if styles doesn't ship it.
4. Route `/` (replace the welcome view with a demo view): a page using `@vite` that renders a representative spread — at minimum Button (variants), Badge, Tag, Alert, Card, Toast, Icon, Stat, and the interactive Dropdown (`<x-lyra::dropdown>` with a trigger slot using a Button-like content [remember: non-interactive content only in the trigger slot], items including a label, commands, a separator and a danger command). Group sections with headings so the page reads as a component gallery.
5. A feature test (Pest) asserting GET `/` returns 200 and the HTML contains `lyra-btn`, `lyra-dropdown`, `x-data="lyraDropdown(` and `x-bind="trigger"`.
6. `npm run build` must succeed (Vite production build).
7. `git init` + a single initial commit of the whole app (respect the skeleton's .gitignore).

## Acceptance criteria

- `composer install` / create-project resolved; `vendor/lyra-ds/blade` is a symlink to the local package.
- `php artisan test` passes (skeleton tests + the new demo page test).
- `npm run build` completes without errors.
- The demo view uses real package components only — never hand-written lyra-* markup.

## Boundaries

Never edit anything under /home/franciscpd/Projects/lyra-ds/blade (the package) or any other sibling project. No database usage beyond the skeleton defaults (sqlite is fine). No auth, no extra pages, no CSS beyond the imports + x-cloak rule + minimal page-layout styles for the gallery.

## Scope

The whole new directory /home/franciscpd/Projects/lyra-ds/blade-demo (greenfield). Outside it: nothing.

## Expected evidence

Report: the resolved laravel/framework and lyra-ds/blade versions; proof vendor/lyra-ds/blade is a symlink (ls -l output); artisan test and npm run build outputs; the styles CSS entrypoint you found; whether styles ships an x-cloak rule; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: create-project or the path-repo resolution fails twice, the styles package has no importable CSS entrypoint, or the fix needs edits outside the Scope directory.
