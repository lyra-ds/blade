# Brief — Stat component (React → Blade) — onda 3, item 3/5

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/stat (a git worktree of lyra-ds/blade; branch batuta/stat).

## Goal

Create the `<x-lyra::stat>` Blade component mirroring the React Stat, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 10 components built to one pattern — study and mirror:
- resources/views/components/alert.blade.php (closest sibling: named slots + conditionals)
- tests/Feature/AlertTest.php + tests/Fixtures/class-emission/alert.json

React contract (verified against packages/react/src/stat/stat.tsx — do NOT deviate; never invent API):

Props:
- `label`: ReactNode → Blade named slot `label` (required)
- `value`: ReactNode → Blade named slot `value` (required)
- `delta`: ReactNode → Blade named slot `delta` (optional)
- `direction`: 'up' | 'down' | 'flat' — default 'flat'; only affects the delta span

Rendered markup (exact):
```html
<div class="lyra-stat[ user classes last]" {...passthrough attrs}>
  <span class="lyra-stat__label">{label}</span>
  <span class="lyra-stat__value">{value}</span>
  {delta: <span class="lyra-stat__delta lyra-stat__delta--{direction}">{arrow} {delta}</span>}
</div>
```
- Class computation (root): `'lyra-stat'` + user classes ALWAYS LAST. No root modifiers.
- Arrow character by direction: up → `↑`, down → `↓`, flat → `→`. Rendered INSIDE the delta span, before the delta content, separated by a single space (exactly `{arrow} {delta}`). No aria-hidden on the arrow (React has none).
- Delta span only when the delta slot is present. All attributes pass through to root.

## Conventions

- PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/stat.json` — cases: base, user class appended; plus delta-span class cases per direction (up/down/flat) asserted in the test.
2. `resources/views/components/stat.blade.php` — anonymous component per the contract.
3. `tests/Feature/StatTest.php` — fixture-driven exact class assertions + markup: label/value spans and order, delta span conditional with direction modifier and correct arrow character, passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green).
- `<x-lyra::stat><x-slot:label>MRR</x-slot:label><x-slot:value>R$ 10k</x-slot:value></x-lyra::stat>` → root class exactly `lyra-stat`, label and value spans, NO delta span.
- `<x-lyra::stat direction="up" class="x"><x-slot:label>L</x-slot:label><x-slot:value>V</x-slot:value><x-slot:delta>+5%</x-slot:delta></x-lyra::stat>` → root class exactly `lyra-stat x`, delta span class exactly `lyra-stat__delta lyra-stat__delta--up` containing `↑ +5%`.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/stat.blade.php (new)
- tests/Fixtures/class-emission/stat.json (new)
- tests/Feature/StatTest.php (new)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
