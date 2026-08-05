# Brief — Separator component (React → Blade) — onda 1, item 4/6

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/separator (a git worktree of lyra-ds/blade; branch batuta/separator).

## Goal

Create the `<x-lyra::separator>` Blade component mirroring the React Separator, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships `<x-lyra::button>`, `<x-lyra::spinner>`, `<x-lyra::badge>`, `<x-lyra::tag>` built to this pattern — study and mirror the badge/spinner trio of files.

React contract (verified against packages/react/src/separator/separator.tsx lines 14-48 — do NOT deviate; never invent API):

Props:
- `orientation`: 'horizontal' | 'vertical' — default 'horizontal'
- `label`: React ReactNode prop → Blade named slot `label` (rich content allowed)

THREE render paths, in this priority order:
1. `label` slot present (non-empty): 
   `<div class="lyra-separator--label[ user classes last]" role="separator" {...passthrough}>{label}</div>`
   NOTE: the base class `lyra-separator` is ABSENT here — only `lyra-separator--label`. Orientation is ignored on this path (always horizontal visually).
2. else if `orientation === 'vertical'`:
   `<span class="lyra-separator lyra-separator--vertical[ user]" role="separator" aria-orientation="vertical" {...passthrough}></span>` (empty element)
3. else (default):
   `<hr class="lyra-separator[ user]" {...passthrough}>` — no role, no aria.

User classes ALWAYS LAST in every path.

## Conventions

- PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits (WIP allowed).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/separator.json` — cases: default (hr), vertical, label path, each with and without user class.
2. `resources/views/components/separator.blade.php` — anonymous component with the three paths.
3. `tests/Feature/SeparatorTest.php` — fixture-driven exact class assertions + per-path markup: element type (hr/span/div), role/aria-orientation presence and absence, label slot rendered, passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green).
- `<x-lyra::separator />` → `<hr>` with class exactly `lyra-separator`, no role.
- `<x-lyra::separator orientation="vertical" class="x" />` → `<span>` class exactly `lyra-separator lyra-separator--vertical x`, `role="separator"`, `aria-orientation="vertical"`.
- `<x-lyra::separator><x-slot:label>ou</x-slot:label></x-lyra::separator>` → `<div>` class exactly `lyra-separator--label`, `role="separator"`, content `ou`.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/separator.blade.php (new)
- tests/Fixtures/class-emission/separator.json (new)
- tests/Feature/SeparatorTest.php (new)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
