# Brief — PageHeader component (React → Blade) — fase 2, onda A, item 8/13

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/page-header (a git worktree of lyra-ds/blade; branch batuta/page-header). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create the `<x-lyra::page-header>` Blade component mirroring the React PageHeader, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 32 components built to this exact pattern — study and mirror:
- resources/views/components/footer.blade.php (fresh sibling with named slots + trim-empty checks)
- tests/Feature/FooterTest.php, tests/Fixtures/class-emission/badge.json (fixture pattern)

React contract (verified against packages/react/src/page-header/page-header.tsx in the main repo — do NOT deviate; never invent API):

Props:
- `title` (string, required) — the page heading text.
- `titleAs` (`h1`|`h2`|`h3`, default `h1`) — the heading element.
- Named slots: `eyebrow`, `description`, `actions` — each optional; wrapper renders ONLY when the slot is provided (React uses truthiness — mirror footer's `isset && trim !== ''` check).
- Default slot — optional secondary row rendered AFTER the row div, directly inside `<header>` (no wrapper).

Rendered markup (exact):
```html
<header class="lyra-pageheader[ user classes last]" {...passthrough attrs}>
  <div class="lyra-pageheader__row">
    <div class="lyra-pageheader__text">
      [<span class="lyra-pageheader__eyebrow">{eyebrow slot}</span>]
      <h1|h2|h3 class="lyra-pageheader__title">{title}</h1|h2|h3>
      [<p class="lyra-pageheader__desc">{description slot}</p>]
    </div>
    [<div class="lyra-pageheader__actions">{actions slot}</div>]
  </div>
  [{default slot}]
</header>
```
- Root class computation: `'lyra-pageheader'` + user classes ALWAYS LAST. No modifiers.
- All other HTML attributes pass through to the root `<header>`.

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest for tests, Pint for style (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.
- Test assertions: per-element containment (toContain) / strpos ordering, never exact whole-output equality — Blade emits whitespaces.
- View style: multi-line directives; NEVER butt two Blade directives together.
- Ignore any Compozy skills (cy-*) — implement directly in the worktree.

## Task

1. `tests/Fixtures/class-emission/page-header.json` — cases: base, user class appended.
2. `resources/views/components/page-header.blade.php` — anonymous component per the contract.
3. `tests/Feature/PageHeaderTest.php` — fixture-driven exact class assertions + markup: default `<h1 class="lyra-pageheader__title">`, `titleAs="h2"` and `"h3"` render the right element, eyebrow/description/actions wrappers only when their slot is given, default slot content appears after the row div (strpos ordering), attribute passthrough, user class last.
4. Regenerate `resources/boost/guidelines/lyra-blade.md` with `php bin/generate-boost-guidelines` (never hand-edit it).

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green, including the guidelines freshness test).
- `<x-lyra::page-header title="Reports" />` → `<h1 class="lyra-pageheader__title">Reports</h1>` inside row/text, no eyebrow/desc/actions.
- Full example with all slots + `title-as="h2"` + `class="x"` → `<h2>` title, all wrappers, root class exactly `lyra-pageheader x`.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/page-header.blade.php (new)
- tests/Fixtures/class-emission/page-header.json (new)
- tests/Feature/PageHeaderTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
