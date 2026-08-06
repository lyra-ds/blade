# Brief — BottomNav component (React → Blade) — fase 2, onda A, item 10/13

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/bottom-nav (a git worktree of lyra-ds/blade; branch batuta/bottom-nav). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create the `<x-lyra::bottom-nav>` Blade component mirroring the React BottomNav, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 34 components built to this exact pattern — study and mirror:
- resources/views/components/breadcrumb.blade.php (sibling: items array prop)
- tests/Feature/BreadcrumbTest.php, tests/Fixtures/class-emission/badge.json (fixture pattern)

React contract (verified against packages/react/src/bottom-nav/bottom-nav.tsx in the main repo — do NOT deviate; never invent API):

Props:
- `items` (array, required) — entries of shape `{icon, label, active?: bool}`. React also carries `id` + `onClick` per item and `onSelect` on the nav (JS selection flow). CLOSED DECISION for the static port: the buttons render WITHOUT any click behavior; `id` is accepted in the array but unused (ignore it silently — consumers share data structures with the React package); do NOT invent href/onclick replacements. One-line docblock ("id/onClick/onSelect: JS selection flow, phase 2").
- `icon` and `label` item values are rendered with Blade's `{{ }}` — plain strings are escaped; consumers pass an `Illuminate\Contracts\Support\Htmlable` (e.g. `HtmlString`) for markup icons. Do not use `{!! !!}`.
- No default slot.

Rendered markup (exact) — for each item, in order:
```html
<nav class="lyra-bottomnav[ user classes last]" {...passthrough attrs}>
  <button type="button" class="lyra-bottomnav__item[ lyra-bottomnav__item--active when active]" [aria-current="page" when active]>
    <span class="lyra-bottomnav__icon">{icon}</span>
    <span class="lyra-bottomnav__label">{label}</span>
  </button>
</nav>
```
- Item class order: `lyra-bottomnav__item`, then the active modifier. `aria-current="page"` only when active.
- Root class computation: `'lyra-bottomnav'` + user classes ALWAYS LAST. No root modifiers. All other HTML attributes pass through to the root nav.

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest for tests, Pint for style (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.
- Test assertions: per-element containment (toContain) / strpos ordering, never exact whole-output equality.
- View style: multi-line directives; NEVER butt two Blade directives together.
- Ignore any Compozy skills (cy-*) — implement directly in the worktree.

## Task

1. `tests/Fixtures/class-emission/bottom-nav.json` — cases: base, user class appended.
2. `resources/views/components/bottom-nav.blade.php` — anonymous component per the contract.
3. `tests/Feature/BottomNavTest.php` — fixture-driven exact class assertions + markup: three-item case with one active (modifier + aria-current only there), buttons are type=button with NO onclick attribute, icon span before label span per item, HtmlString icon renders raw while a plain-string icon is escaped, item order preserved (strpos), attribute passthrough, user class last.
4. Regenerate `resources/boost/guidelines/lyra-blade.md` with `php bin/generate-boost-guidelines` (never hand-edit it).

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green, including the guidelines freshness test).
- The three-item behaviors above are pinned by tests.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/bottom-nav.blade.php (new)
- tests/Fixtures/class-emission/bottom-nav.json (new)
- tests/Feature/BottomNavTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
