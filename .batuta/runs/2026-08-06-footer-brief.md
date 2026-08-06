# Brief — Footer component (React → Blade) — fase 2, onda A, item 2/13

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/footer (a git worktree of lyra-ds/blade; branch batuta/footer). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create the `<x-lyra::footer>` Blade component mirroring the React Footer, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 27 components built to this exact pattern — study and mirror:
- resources/views/components/card.blade.php (sibling with named slots)
- resources/views/components/container.blade.php (the inner wrapper reuses this component)
- tests/Feature/CardTest.php, tests/Fixtures/class-emission/footer.json pattern per badge.json

React contract (verified against packages/react/src/footer/footer.tsx in the main repo — do NOT deviate; never invent API):

Props:
- `linksLabel` (string, optional) — `aria-label` of the links `<nav>`; when absent the nav has NO aria-label attribute.
- Named slots: `brand`, `note`, `links` — each optional; its wrapper renders ONLY when the slot is provided.
- No default slot (React omits `children`); do not render `{{ $slot }}`.

Rendered markup (exact):
```html
<footer class="lyra-footer[ user classes last]" {...passthrough attrs}>
  <div class="lyra-container lyra-footer__inner">   <!-- via <x-lyra::container class="lyra-footer__inner"> -->
    [<div class="lyra-footer__brand">{brand slot}</div>]
    [<div class="lyra-footer__note">{note slot}</div>]
    [<nav class="lyra-footer__links" [aria-label="{linksLabel}"]>{links slot}</nav>]
  </div>
</footer>
```
- Slot order is fixed: brand, note, links — regardless of authoring order.
- Root class computation: `'lyra-footer'` + user classes ALWAYS LAST, single-space joined. No modifier classes.
- All other HTML attributes pass through to the root `<footer>`.
- The inner wrapper MUST reuse the existing container component (mirroring React's `<Container className="lyra-footer__inner">`), not duplicate its markup.

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest for tests, Pint for style (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.
- Ignore any Compozy skills (cy-*) — implement directly in the worktree.

## Task

1. `tests/Fixtures/class-emission/footer.json` — cases: base (no props), user class appended.
2. `resources/views/components/footer.blade.php` — anonymous component per the contract.
3. `tests/Feature/FooterTest.php` — fixture-driven exact class assertions + markup: each slot wrapper renders only when its slot is given (test absent AND present for all three), links nav aria-label present only with `linksLabel`, inner div carries exactly `lyra-container lyra-footer__inner`, attribute passthrough, user class last.
4. Regenerate `resources/boost/guidelines/lyra-blade.md` with `php bin/generate-boost-guidelines` (never hand-edit it) so the guidelines freshness test stays green.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green, including the Boost guidelines freshness test).
- `<x-lyra::footer />` → `<footer class="lyra-footer">` containing only `<div class="lyra-container lyra-footer__inner">` (no brand/note/links wrappers).
- `<x-lyra::footer links-label="Resources" class="x" data-id="1">` with all three slots → wrappers in brand→note→links order, `<nav class="lyra-footer__links" aria-label="Resources">`, root class exactly `lyra-footer x`, `data-id="1"` present.
- Footer with `links` slot but no `linksLabel` → the `<nav>` has no `aria-label` attribute.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies. Do not modify container.blade.php.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/footer.blade.php (new)
- tests/Fixtures/class-emission/footer.json (new)
- tests/Feature/FooterTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
