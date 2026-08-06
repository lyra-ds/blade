# Brief — Icon component (React → Blade) — fase 2, onda A, item 13/13 (último)

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/icon (a git worktree of lyra-ds/blade; branch batuta/icon). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create the `<x-lyra::icon>` Blade component mirroring the React Icon, backed by the `mallardduck/blade-lucide-icons` composer dependency (CLOSED USER DECISION 2026-08-06 — do not reopen), with a curated 79-name allow-list and class-emission parity tests.

## Context

The package ships 37 components; study resources/views/components/badge.blade.php and tests/Feature/BadgeTest.php for the established patterns, and tests/TestCase.php for how package providers are registered in testbench.

React contract (verified against packages/react/src/icon/icon.tsx + icon-registry.ts in the main repo — do NOT deviate; never invent API):

Props:
- `name` (string, optional) — curated registry key, kebab-case. The curated allow-list is EXACTLY these 79 names (transcribed from the React registry — the API contract):
  archive arrow-left arrow-right arrow-up-right bell book-open calendar chart-line check chevron-down chevron-left chevron-right chevrons-left chevrons-right chevrons-up-down circle circle-alert circle-check circle-dot circle-x cloud-upload code copy credit-card download ellipsis external-link eye file file-archive file-plus file-spreadsheet file-text film filter folder folder-open github globe hard-drive heart house image inbox info layout-dashboard layout-grid link list lock log-out mail message-circle minus moon music package pencil plus rocket scale search send settings shield sliders-horizontal sparkles star sun terminal timer trash-2 triangle-alert upload user user-plus users x zap
- `size` (int, default 20) — svg width AND height.
- `color` (string, optional) — sets `stroke` on the svg; absent → lucide's native `stroke="currentColor"` stays.
- `title` (string, optional) — accessible label. With title: `role="img"` + `aria-label="{title}"`, NO aria-hidden. Without: `aria-hidden="true"`, no role, no aria-label.
- `class` — merged as `lyra-icon` + user classes ALWAYS LAST. The `.lyra-icon` class is ALWAYS emitted on the svg root.
- Unknown or absent `name` → the component renders NOTHING (empty output, no exception) — mirroring React's `return null`. A name outside the 79-name allow-list is "unknown" even if the lucide set ships it (curation is the API).
- React also has an `icon` escape-hatch prop (a lucide-react component, JS-only). NOT portable: omit with a one-line docblock ("icon escape hatch: JS-only, not ported").

Implementation constraints:
- Dependency: `composer require mallardduck/blade-lucide-icons:^2.0` — production require in composer.json (this package wraps blade-ui-kit/blade-icons; icons are named `lucide-{name}`).
- Register the dependency's service providers in tests/TestCase.php (blade-icons + blade-lucide-icons) so testbench resolves the icon set.
- Render via the blade-icons mechanism (e.g. `svg('lucide-'.$name, ...)` or the component form) applying: class merge, width/height from size, stroke from color, and the title/aria contract above. Verify what the helper emits and adjust attributes so the FINAL svg carries exactly the contract's attributes.
- `github`: lucide removed brand icons in v1.0 — the React registry vendors its own github glyph. Check whether the installed lucide set still ships `lucide-github`; if NOT, vendor the same two-path github SVG as a custom blade-icons set inside this package (resources/svg/github.svg, paths: 'M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4' and 'M9 18c-4.51 2-5-2-7-2') registered so `name="github"` works transparently.
- The allow-list lives in the package as data (a small PHP file or const in the view) — hand-maintained, matching the list above exactly.

## Conventions

- PHP >= 8.3, Laravel 12/13. Pest for tests, Pint for style (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.
- Test assertions: per-element containment / attribute checks, never exact whole-output equality.
- Ignore any Compozy skills (cy-*) — implement directly in the worktree.

## Task

1. `tests/Fixtures/class-emission/icon.json` — cases: base (`lyra-icon`), user class appended.
2. `resources/views/components/icon.blade.php` (+ the allow-list data file and, if needed, resources/svg/github.svg) per the contract.
3. `tests/Feature/IconTest.php` — fixture-driven class assertions + markup: `<x-lyra::icon name="check" />` renders an `<svg>` with class starting `lyra-icon`, `width="20" height="20"`, `aria-hidden="true"`, no role; `title` case renders `role="img"` + aria-label and no aria-hidden; `:size="16"` and `color="red"` reflected; unknown name (`name="constructor"`, `name="not-real"`) and absent name → empty output; `name="github"` renders an svg; a plain lucide name NOT in the curated list renders empty; user class last.
4. composer.json updated with the new require; tests/TestCase.php updated with the providers.
5. Regenerate `resources/boost/guidelines/lyra-blade.md` with `php bin/generate-boost-guidelines` (never hand-edit it).

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green, including the guidelines freshness test).
- The behaviors above pinned by tests.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS beyond the vendored github.svg if needed. No other new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- composer.json (require line only)
- tests/TestCase.php (provider registration only)
- resources/views/components/icon.blade.php (new)
- resources/svg/github.svg (new, only if the lucide set lacks github)
- src/ or resources/ allow-list data file (new, name it sensibly, e.g. src/IconRegistry.php or resources/icon-registry.php)
- src/BladeServiceProvider.php (ONLY if registering the custom svg set requires it)
- tests/Fixtures/class-emission/icon.json (new)
- tests/Feature/IconTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint runs with actual output; whether lucide ships github or the vendored fallback was needed; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, the dependency cannot be installed, or the fix needs edits beyond Scope.
