# Brief — Skeleton component (React → Blade) — onda 1, item 5/6

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/skeleton (a git worktree of lyra-ds/blade; branch batuta/skeleton).

## Goal

Create the `<x-lyra::skeleton>` Blade component mirroring the React Skeleton, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships button/spinner/badge/tag/separator built to this pattern — mirror the spinner trio of files (closest sibling: empty span, no slot).

React contract (verified against packages/react/src/skeleton/skeleton.tsx — do NOT deviate; never invent API):

Props:
- `width`: number | string — default '100%'
- `height`: number | string — default 14
- `circle`: bool — default false

Rendered markup (exact):
```html
<span class="lyra-skeleton[ lyra-skeleton--circle][ user classes last]" aria-hidden="true" style="width: ...; height: ..." {...passthrough attrs}></span>
```
- Class computation: `'lyra-skeleton'` + (`' lyra-skeleton--circle'` when circle) + user classes ALWAYS LAST.
- Inline style: `width: circle ? height : width; height: height`. React CSS semantics: NUMERIC values get `px` appended (14 → `14px`); string values verbatim ('100%' → `100%`, '2rem' → `2rem`). Defaults: width '100%', height 14 → default style `width: 100%; height: 14px`; with circle=true and height=40 → `width: 40px; height: 40px`.
- `aria-hidden="true"` always. Empty element. All other HTML attributes pass through; a consumer-passed `style` attribute merges with the computed style (computed first, consumer appended — Blade `$attributes->merge(['style' => ...])` semantics are acceptable as long as computed width/height are present; assert presence, not exclusivity, in the consumer-style test case).

## Conventions

- PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits (WIP allowed).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/skeleton.json` — cases: default, circle, user class, circle+user class.
2. `resources/views/components/skeleton.blade.php` — anonymous component per the contract.
3. `tests/Feature/SkeletonTest.php` — fixture-driven exact class assertions + markup: aria-hidden, style computation (defaults, numeric px, string verbatim, circle uses height as width), passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green).
- `<x-lyra::skeleton />` → class exactly `lyra-skeleton`, `aria-hidden="true"`, style contains `width: 100%` and `height: 14px`.
- `<x-lyra::skeleton circle :height="40" class="x" />` → class exactly `lyra-skeleton lyra-skeleton--circle x`, style contains `width: 40px` and `height: 40px`.
- `<x-lyra::skeleton width="2rem" height="1rem" />` → style contains `width: 2rem` and `height: 1rem`.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/skeleton.blade.php (new)
- tests/Fixtures/class-emission/skeleton.json (new)
- tests/Feature/SkeletonTest.php (new)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
