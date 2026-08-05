# Brief — Progress component (React → Blade) — onda 1, item 6/6

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/progress (a git worktree of lyra-ds/blade; branch batuta/progress).

## Goal

Create the `<x-lyra::progress>` Blade component mirroring the React Progress, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships button/spinner/badge/tag/separator/skeleton built to this pattern — mirror the spinner/skeleton trio of files.

React contract (verified against packages/react/src/progress/progress.tsx lines 18-32 — do NOT deviate; never invent API):

Props:
- `value`: number (0–100) — required by usage; clamp to [0, 100] (`max(0, min(100, value))`)
- `tone`: 'success' | 'danger' — OPTIONAL, NO DEFAULT: when absent there is NO tone modifier class

Rendered markup (exact):
```html
<div class="lyra-progress[ lyra-progress--{tone}][ user classes last]"
     role="progressbar" aria-valuenow="{clamped}" aria-valuemin="0" aria-valuemax="100"
     {...passthrough attrs}>
  <div class="lyra-progress__fill" style="width: {clamped}%"></div>
</div>
```
- Class computation: `'lyra-progress'` + (`' lyra-progress--{tone}'` only when tone present) + user classes ALWAYS LAST.
- `aria-valuenow` is the CLAMPED value (value=150 → 100; value=-5 → 0).
- Fill div always present with width style from the clamped value.

## Conventions

- PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits (WIP allowed).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/progress.json` — cases: no tone, success, danger, user class, tone+user class.
2. `resources/views/components/progress.blade.php` — anonymous component per the contract.
3. `tests/Feature/ProgressTest.php` — fixture-driven exact class assertions + markup: progressbar aria set, clamping (over 100, below 0), fill width, passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green).
- `<x-lyra::progress :value="30" />` → class exactly `lyra-progress`, `aria-valuenow="30"`, fill `width: 30%`.
- `<x-lyra::progress :value="150" tone="danger" class="x" />` → class exactly `lyra-progress lyra-progress--danger x`, `aria-valuenow="100"`, fill `width: 100%`.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/progress.blade.php (new)
- tests/Fixtures/class-emission/progress.json (new)
- tests/Feature/ProgressTest.php (new)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
