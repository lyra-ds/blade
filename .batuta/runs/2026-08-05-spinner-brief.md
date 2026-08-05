# Brief — Spinner component (React → Blade) — onda 1, item 1/6

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/spinner (a git worktree of lyra-ds/blade; branch batuta/spinner).

## Goal

Create the `<x-lyra::spinner>` Blade component mirroring the React Spinner, with data-driven class-emission tests proving exact class-string parity.

## Context

The package already ships `<x-lyra::button>` built to this exact pattern — study these three files first and mirror their structure and style:
- resources/views/components/button.blade.php
- tests/Feature/ButtonTest.php
- tests/Fixtures/class-emission/button.json

React contract (verified against packages/react/src/spinner/spinner.tsx in the main repo — do NOT deviate; never invent API):

Props:
- `size`: 'sm' | 'md' | 'lg' — default 'md'
- `aria-label`: string — default 'Loading' (consumer can override via attribute)

Rendered markup (exact):
```html
<span class="lyra-spinner lyra-spinner--{size}[ user classes last]" role="status" aria-label="Loading" {...passthrough attrs}></span>
```
- Empty element — no children, no slot content.
- Class computation: `'lyra-spinner' + ' lyra-spinner--{size}' + user classes ALWAYS LAST`, single-space joined.
- `role="status"` always; `aria-label` defaults to `Loading`, overridden when the consumer passes `aria-label="..."` — it must not be duplicated.
- All other HTML attributes pass through to the root span.

## Conventions

- PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest for tests, Pint for style (run both).
- TDD: failing class-emission tests first, then the component. Conventional commits (WIP commits allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/spinner.json` — cases: each size (3), default (no size), user class appended, format identical to button.json.
2. `resources/views/components/spinner.blade.php` — anonymous component per the contract.
3. `tests/Feature/SpinnerTest.php` — fixture-driven exact class assertions + markup: role, default aria-label, aria-label override (no duplication), empty element, attribute passthrough.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green).
- `<x-lyra::spinner />` → class exactly `lyra-spinner lyra-spinner--md`, `role="status"`, `aria-label="Loading"`.
- `<x-lyra::spinner size="lg" class="x" aria-label="Carregando" />` → class exactly `lyra-spinner lyra-spinner--lg x`, `aria-label="Carregando"` appearing once.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/spinner.blade.php (new)
- tests/Fixtures/class-emission/spinner.json (new)
- tests/Feature/SpinnerTest.php (new)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
