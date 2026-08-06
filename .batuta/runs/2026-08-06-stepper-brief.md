# Brief — Stepper component (React → Blade) — fase 2, onda A, item 6/13

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/stepper (a git worktree of lyra-ds/blade; branch batuta/stepper). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create the `<x-lyra::stepper>` Blade component mirroring the React Stepper, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 30 components built to this exact pattern — study and mirror:
- resources/views/components/breadcrumb.blade.php (sibling: list from an array prop)
- tests/Feature/BreadcrumbTest.php, tests/Fixtures/class-emission/badge.json (fixture pattern)

React contract (verified against packages/react/src/stepper/stepper.tsx in the main repo — do NOT deviate; never invent API):

Props:
- `steps` (array of strings, required) — step labels in order.
- `active` (int, required) — zero-based index of the current step.

Rendered markup (exact) — for each step index i, in order:
```html
<div class="lyra-stepper[ user classes last]" {...passthrough attrs}>
  <!-- before every step except the first: -->
  <span class="lyra-step__line[ lyra-step__line--done when i <= active]"></span>
  <!-- the step: -->
  <span class="lyra-step[ lyra-step--active when i == active][ lyra-step--done when i < active]" [aria-current="step" when i == active]>
    <span class="lyra-step__dot">
      <!-- i < active: check SVG; otherwise the 1-based number {i+1} -->
      <svg aria-hidden="true" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
    </span>
    <span class="lyra-step__label">{label}</span>
  </span>
</div>
```
- Note the line modifier condition is `i <= active` (the line BEFORE step i is done when i <= active) while the step's done modifier is `i < active`.
- Root class computation: `'lyra-stepper'` + user classes ALWAYS LAST. No root modifiers.
- Step class order: `lyra-step`, then `--active` (i == active), then `--done` (i < active) — mutually exclusive by construction.
- All other HTML attributes pass through to the root div. No default slot.

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest for tests, Pint for style (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.
- Test assertions: per-element containment (toContain) / strpos ordering, never exact whole-output equality — Blade emits whitespace.
- View style: multi-line directives; NEVER butt two Blade directives together (`@endif@if` does not compile).
- Ignore any Compozy skills (cy-*) — implement directly in the worktree.

## Task

1. `tests/Fixtures/class-emission/stepper.json` — cases: base, user class appended.
2. `resources/views/components/stepper.blade.php` — anonymous component per the contract.
3. `tests/Feature/StepperTest.php` — fixture-driven exact class assertions + markup for a 3-step case with active=1: first step done (check SVG, no number, `lyra-step--done`), second active (`lyra-step--active`, `aria-current="step"`, dot shows `2`), third pending (no modifier, no aria-current, dot shows `3`); two connector lines with exactly the first one `--done`; strpos ordering line-vs-step; edge cases active=0 (no done, no line done) and active beyond last (all done); attribute passthrough; user class last.
4. Regenerate `resources/boost/guidelines/lyra-blade.md` with `php bin/generate-boost-guidelines` (never hand-edit it).

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green, including the guidelines freshness test).
- The 3-step behaviors above are pinned by tests.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/stepper.blade.php (new)
- tests/Fixtures/class-emission/stepper.json (new)
- tests/Feature/StepperTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
