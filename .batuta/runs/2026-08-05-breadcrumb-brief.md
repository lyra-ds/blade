# Brief — Breadcrumb component (React → Blade) — onda 6, item 1/3

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/breadcrumb (a git worktree of lyra-ds/blade; branch batuta/breadcrumb).

## Goal

Create the `<x-lyra::breadcrumb>` Blade component mirroring the React Breadcrumb, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 24 components to one pattern — mirror the established trio structure (see tests/Feature and tests/Fixtures/class-emission).

React contract (verified against packages/react/src/breadcrumb/breadcrumb.tsx — do NOT deviate; never invent API):

Props:
- `items`: PHP array of associative arrays `['label' => string, 'href' => ?string]` (Blade mapping of BreadcrumbItem[]; required)
- `aria-label`: default `Breadcrumb`, consumer-overridable (merge semantics — default only when absent)

Rendered markup (exact — flat nav, NO ol/li):
```html
<nav class="lyra-breadcrumb[ user classes last]" aria-label="Breadcrumb" {...passthrough attrs}>
  <!-- for each item except the last: -->
  [<span class="lyra-breadcrumb__sep" aria-hidden="true"></span> — before every item except the first]
  <a href="{href or '#'}">{label}</a>
  <!-- last item: -->
  [separator span]
  <span class="lyra-breadcrumb__current" aria-current="page">{label}</span>
</nav>
```
- Separator: empty span, before every item except the first (including before the current one).
- Last item is ALWAYS the current-page span (no link); other items are `<a>` with `href` falling back to `#`.
- Class: `'lyra-breadcrumb'` + user classes LAST.

## Conventions

PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both). TDD: failing tests first. Conventional commits (WIP allowed). Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/breadcrumb.json` — cases: base, user class.
2. `resources/views/components/breadcrumb.blade.php`
3. `tests/Feature/BreadcrumbTest.php` — fixture-driven exact class assertions + markup: separator count/placement, current span with aria-current, href fallback '#', aria-label default and override, single-item edge (just the current span, no separator), passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green); `vendor/bin/pint --test` passes.
- `<x-lyra::breadcrumb :items="[['label'=>'Home','href'=>'/'],['label'=>'Docs','href'=>'/docs'],['label'=>'Blade']]" />` → nav `lyra-breadcrumb`, `<a href="/">Home</a>`, sep, `<a href="/docs">Docs</a>`, sep, `<span class="lyra-breadcrumb__current" aria-current="page">Blade</span>`.
- `<x-lyra::breadcrumb :items="[['label'=>'Só']]" aria-label="Trilha" class="x" />` → class exactly `lyra-breadcrumb x`, `aria-label="Trilha"`, only the current span, no separator.

## Boundaries / Scope (closed list — nothing outside it; if the task requires it, stop and report)

- resources/views/components/breadcrumb.blade.php (new)
- tests/Fixtures/class-emission/breadcrumb.json (new)
- tests/Feature/BreadcrumbTest.php (new)

No CSS/JS, no new dependencies.

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
