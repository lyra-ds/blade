# Brief — Navbar component (React → Blade) — fase 2, onda A, item 11/13

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/navbar (a git worktree of lyra-ds/blade; branch batuta/navbar). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create the `<x-lyra::navbar>` Blade component mirroring the React Navbar, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 35 components built to this exact pattern — study and mirror:
- resources/views/components/footer.blade.php (closest sibling: same shape — named slots + container inner + conditional aria-label)
- tests/Feature/FooterTest.php, tests/Fixtures/class-emission/badge.json (fixture pattern)

React contract (verified against packages/react/src/navbar/navbar.tsx in the main repo — do NOT deviate; never invent API):

Props:
- `sticky` (bool, default `true`) — the modifier class `lyra-navbar--static` is emitted ONLY when sticky is explicitly `false`. Default/absent/true → no modifier.
- `navLabel` (string, optional) — `aria-label` of the `<nav>`; when absent the nav has NO aria-label attribute.
- Named slots: `brand`, `nav`, `actions` — each optional; wrapper renders ONLY when the slot is provided (mirror footer's `isset && trim !== ''` check).
- No default slot.

Rendered markup (exact):
```html
<header class="lyra-navbar[ lyra-navbar--static][ user classes last]" {...passthrough attrs}>
  <div class="lyra-container lyra-navbar__inner">   <!-- via <x-lyra::container class="lyra-navbar__inner"> -->
    [<div class="lyra-navbar__brand">{brand slot}</div>]
    [<nav class="lyra-navbar__nav" [aria-label="{navLabel}"]>{nav slot}</nav>]
    [<div class="lyra-navbar__actions">{actions slot}</div>]
  </div>
</header>
```
- Slot order fixed: brand, nav, actions.
- Root class order: `lyra-navbar`, then `--static` (only when sticky === false), then user classes ALWAYS LAST.
- The inner wrapper MUST reuse the existing container component, not duplicate its markup.
- All other HTML attributes pass through to the root `<header>`.

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest for tests, Pint for style (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.
- Test assertions: per-element containment (toContain) / strpos ordering, never exact whole-output equality.
- View style: multi-line directives; NEVER butt two Blade directives together.
- Ignore any Compozy skills (cy-*) — implement directly in the worktree.

## Task

1. `tests/Fixtures/class-emission/navbar.json` — cases: base, static modifier (sticky false), user class appended (with and without sticky false).
2. `resources/views/components/navbar.blade.php` — anonymous component per the contract.
3. `tests/Feature/NavbarTest.php` — fixture-driven exact class assertions + markup: each slot wrapper only when given (absent AND present for all three), slot order (strpos), nav aria-label only with navLabel, inner div exactly `lyra-container lyra-navbar__inner`, sticky=true and default emit NO modifier, attribute passthrough, user class last.
4. Regenerate `resources/boost/guidelines/lyra-blade.md` with `php bin/generate-boost-guidelines` (never hand-edit it).

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green, including the guidelines freshness test).
- `<x-lyra::navbar />` → `<header class="lyra-navbar">` with only the container inner div.
- `<x-lyra::navbar :sticky="false" nav-label="Main" class="x">` with all slots → root class exactly `lyra-navbar lyra-navbar--static x`, `<nav class="lyra-navbar__nav" aria-label="Main">`.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies. Do not modify container.blade.php.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/navbar.blade.php (new)
- tests/Fixtures/class-emission/navbar.json (new)
- tests/Feature/NavbarTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
