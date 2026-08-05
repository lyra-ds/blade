# Brief — Badge component (React → Blade) — onda 1, item 2/6

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/badge (a git worktree of lyra-ds/blade; branch batuta/badge).

## Goal

Create the `<x-lyra::badge>` Blade component mirroring the React Badge, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships `<x-lyra::button>` and `<x-lyra::spinner>` built to this exact pattern — study and mirror:
- resources/views/components/spinner.blade.php (closest sibling)
- tests/Feature/SpinnerTest.php
- tests/Fixtures/class-emission/spinner.json

React contract (verified against packages/react/src/badge/badge.tsx in the main repo — do NOT deviate; never invent API):

Props:
- `tone`: 'neutral' | 'accent' | 'success' | 'warning' | 'danger' | 'info' — default 'neutral'
- `dot`: bool — default false
- content: React `children` → Blade default slot

Rendered markup (exact):
```html
<span class="lyra-badge lyra-badge--{tone}[ user classes last]" {...passthrough attrs}>
  {dot: <span class="lyra-badge__dot" aria-hidden="true"></span>}{slot}
</span>
```
- Class computation: `'lyra-badge' + ' lyra-badge--{tone}' + user classes ALWAYS LAST`, single-space joined.
- Dot span renders only when `dot` is true, BEFORE the slot content, with `aria-hidden="true"`.
- No role/aria on the root; all other HTML attributes pass through.

## Conventions

- PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest for tests, Pint for style (run both).
- TDD: failing class-emission tests first, then the component. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/badge.json` — cases: each of the 6 tones, default (no tone), user class appended; format identical to spinner.json.
2. `resources/views/components/badge.blade.php` — anonymous component per the contract.
3. `tests/Feature/BadgeTest.php` — fixture-driven exact class assertions + markup: dot span presence/absence and position before slot, aria-hidden on dot, slot content rendered, attribute passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green).
- `<x-lyra::badge>New</x-lyra::badge>` → class exactly `lyra-badge lyra-badge--neutral`, no dot span, content `New`.
- `<x-lyra::badge tone="success" dot class="x">Ok</x-lyra::badge>` → class exactly `lyra-badge lyra-badge--success x`, dot span with `aria-hidden="true"` before `Ok`.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/badge.blade.php (new)
- tests/Fixtures/class-emission/badge.json (new)
- tests/Feature/BadgeTest.php (new)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
