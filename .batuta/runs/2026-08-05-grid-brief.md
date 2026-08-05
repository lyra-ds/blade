# Brief — Grid component (React → Blade) — onda 4, item 3/3

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/grid (a git worktree of lyra-ds/blade; branch batuta/grid).

## Goal

Create the `<x-lyra::grid>` Blade component mirroring the React Grid, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 15 components built to one pattern — study and mirror:
- resources/views/components/stack.blade.php (closest sibling: conditional custom properties, numeric gap → var(--space-N))
- tests/Feature/StackTest.php + tests/Fixtures/class-emission/stack.json

React contract (verified against packages/react/src/grid/grid.tsx — do NOT deviate; never invent API):

Props:
- `columns`: number | string — optional (CSS default: 2 columns)
- `minItem`: number (pixels) — optional; takes PRECEDENCE over columns
- `gap`: number | string — optional (CSS default: var(--space-4))
- content: default slot

Rendered markup (exact):
```html
<div class="lyra-grid[ user classes last]"[ style="{custom properties}[; consumer style]"] {...passthrough attrs}>{slot}</div>
```
Custom-property mapping — ONLY set what was provided (no props → NO style attribute):
- minItem present → `--lyra-grid-columns: repeat(auto-fit, minmax(min({minItem}px, 100%), 1fr))` (columns IGNORED)
- else columns number → `--lyra-grid-columns: repeat({columns}, minmax(0, 1fr))`
- else columns string → `--lyra-grid-columns: {columns}` verbatim
- gap number → `--lyra-grid-gap: var(--space-{gap})`; gap string → `--lyra-grid-gap: {gap}` verbatim
Property order in the style string: columns first, then gap (matching React source). Consumer-passed `style` merges AFTER (consumer wins).
- Class computation: `'lyra-grid'` + user classes ALWAYS LAST. No modifiers.
- All other HTML attributes pass through to the root div.

## Conventions

- PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/grid.json` — cases: base, user class appended.
2. `resources/views/components/grid.blade.php` — anonymous component per the contract.
3. `tests/Feature/GridTest.php` — fixture-driven exact class assertions + markup: no style without props; columns number/string; minItem template and its precedence over columns; gap number/string; consumer style after; passthrough; user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green).
- `<x-lyra::grid>G</x-lyra::grid>` → `<div class="lyra-grid">G</div>` with NO style attribute.
- `<x-lyra::grid :columns="3" :gap="6" class="x">G</x-lyra::grid>` → class exactly `lyra-grid x`, style exactly `--lyra-grid-columns: repeat(3, minmax(0, 1fr)); --lyra-grid-gap: var(--space-6)`.
- `<x-lyra::grid :columns="3" :min-item="240">G</x-lyra::grid>` → style containing `--lyra-grid-columns: repeat(auto-fit, minmax(min(240px, 100%), 1fr))` and NOT `repeat(3`.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/grid.blade.php (new)
- tests/Fixtures/class-emission/grid.json (new)
- tests/Feature/GridTest.php (new)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
