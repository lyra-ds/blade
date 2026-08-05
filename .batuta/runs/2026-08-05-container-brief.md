# Brief — Container component (React → Blade) — onda 4, item 1/3

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/container (a git worktree of lyra-ds/blade; branch batuta/container).

## Goal

Create the `<x-lyra::container>` Blade component mirroring the React Container, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 13 components built to one pattern — study and mirror:
- resources/views/components/skeleton.blade.php (computed style + attribute technique)
- tests/Feature/SkeletonTest.php + tests/Fixtures/class-emission/skeleton.json

React contract (verified against packages/react/src/container/container.tsx — do NOT deviate; never invent API):

Props:
- `max`: number (pixels) — optional
- content: default slot

Rendered markup (exact):
```html
<div class="lyra-container[ user classes last]"[ style="--container-max: {max}px[; consumer style]"] {...passthrough attrs}>{slot}</div>
```
- Class computation: `'lyra-container'` + user classes ALWAYS LAST. No modifiers.
- `max` absent → NO style attribute added by the component (a consumer-passed style flows through untouched).
- `max` present (e.g. 960) → custom property `--container-max: 960px` in the style attribute; a consumer-passed `style` is merged AFTER it (consumer wins on conflicts — mirror React's `{...variableStyle, ...style}` order).
- All other HTML attributes pass through to the root div.

## Conventions

- PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/container.json` — cases: base, user class appended.
2. `resources/views/components/container.blade.php` — anonymous component per the contract.
3. `tests/Feature/ContainerTest.php` — fixture-driven exact class assertions + markup: no style attr without max; `--container-max: 960px` with :max="960"; consumer style merged after the custom property; slot; passthrough; user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green).
- `<x-lyra::container>C</x-lyra::container>` → `<div class="lyra-container">C</div>` with NO style attribute.
- `<x-lyra::container :max="960" class="x" style="color: red">C</x-lyra::container>` → class exactly `lyra-container x`, style containing `--container-max: 960px` followed by `color: red`.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/container.blade.php (new)
- tests/Fixtures/class-emission/container.json (new)
- tests/Feature/ContainerTest.php (new)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
