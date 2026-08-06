# Brief — NavLink component (React → Blade) — fase 2, onda A, item 3/13

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/nav-link (a git worktree of lyra-ds/blade; branch batuta/nav-link). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create the `<x-lyra::nav-link>` Blade component mirroring the React NavLink, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 28 components built to this exact pattern — study and mirror:
- resources/views/components/badge.blade.php (simple sibling with modifier classes)
- tests/Feature/BadgeTest.php, tests/Fixtures/class-emission/badge.json

React contract (verified against packages/react/src/nav-link/nav-link.tsx in the main repo — do NOT deviate; never invent API):

Props:
- `active` (bool, default `false`) — when true: adds modifier class `lyra-navlink--active` AND `aria-current="page"` on the root. When false: neither is present.
- Default slot — the link content.
- React also has `asChild` (JS-only Slot merging). CLOSED DECISION for the static port: NOT implemented. One-line docblock comment in the view noting the omission ("asChild: JS-only Slot merging, not ported"). Do not invent a replacement prop.

Rendered markup (exact):
```html
<a class="lyra-navlink[ lyra-navlink--active][ user classes last]" [aria-current="page"] {...passthrough attrs}>{slot}</a>
```
- Class order: `lyra-navlink`, then the active modifier (only when active), then user classes ALWAYS LAST, single-space joined.
- Root is always `<a>`; `href` and all other HTML attributes pass through (no dedicated href prop — it arrives via the attribute bag).

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest for tests, Pint for style (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.
- Test assertions: per-element containment (toContain) / strpos ordering, never exact whole-output equality — Blade emits whitespace.
- View style: multi-line directives per sibling views; NEVER butt two Blade directives together (`@endif@if` does not compile).
- Ignore any Compozy skills (cy-*) — implement directly in the worktree.

## Task

1. `tests/Fixtures/class-emission/nav-link.json` — cases: base, active modifier, user class appended (with and without active).
2. `resources/views/components/nav-link.blade.php` — anonymous component per the contract.
3. `tests/Feature/NavLinkTest.php` — fixture-driven exact class assertions + markup: `aria-current="page"` present only when active, slot content, attribute passthrough (incl. `href`), user class last.
4. Regenerate `resources/boost/guidelines/lyra-blade.md` with `php bin/generate-boost-guidelines` (never hand-edit it).

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green, including the guidelines freshness test).
- `<x-lyra::nav-link href="/docs">Docs</x-lyra::nav-link>` → `<a href="/docs" class="lyra-navlink">Docs</a>` semantics: class exactly `lyra-navlink`, no `aria-current`.
- `<x-lyra::nav-link :active="true" class="x">Now</x-lyra::nav-link>` → class exactly `lyra-navlink lyra-navlink--active x`, `aria-current="page"` present.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/nav-link.blade.php (new)
- tests/Fixtures/class-emission/nav-link.json (new)
- tests/Feature/NavLinkTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
