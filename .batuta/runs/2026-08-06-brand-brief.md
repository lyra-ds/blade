# Brief — Brand component (React → Blade) — fase 2, onda A, item 1/13

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/brand (a git worktree of lyra-ds/blade; branch batuta/brand).

## Goal

Create the `<x-lyra::brand>` Blade component mirroring the React Brand, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 26 components built to this exact pattern — study and mirror:
- resources/views/components/badge.blade.php (simple sibling)
- resources/views/components/container.blade.php (CSS-variable + user-style merge precedent)
- tests/Feature/BadgeTest.php, tests/Fixtures/class-emission/badge.json

React contract (verified against packages/react/src/brand/brand.tsx in the main repo — do NOT deviate; never invent API):

Props:
- `mark` (string, required) — light-theme mark image src.
- `markDark` (string, optional) — dark-theme mark image src.
- `size` (int, optional) — sets `--brand-mark-size: {size}px` as a CSS variable on the root's `style`. Merge rule from React (`{...variableStyle, ...style}`): the variable comes first, a user-supplied `style` attribute is appended after it (user wins on conflict). Mirror how container.blade.php merges its `--container-max` variable.
- `href` (string, optional) — when present the root is `<a href="...">`, otherwise `<span>`.
- `aria-label` (string, optional) — passes through to the root. (In React it is required when mark-only; in Blade it stays a plain passthrough attribute — no validation.)
- Default slot — the wordmark. When the slot is empty AND the root is a `<span>`, the span gets `role="img"`. An `<a>` root never gets `role`.
- React also has `asChild` (Slot merging, JS-only). CLOSED DECISION for the static port: NOT implemented. One-line docblock comment in the view noting the omission ("asChild: JS-only Slot merging, not ported"). Do not invent a replacement prop.

Rendered markup (exact):
```html
<a|span class="lyra-brand[ user classes last]" [style="--brand-mark-size: {size}px[; user style]"] [href] [aria-label] [role="img"] {...passthrough attrs}>
  <!-- markDark absent: -->
  <img class="lyra-brand__mark" src="{mark}" alt="">
  <!-- markDark present (replaces the single img): -->
  <img class="lyra-brand__mark lyra-brand__mark--light" src="{mark}" alt="">
  <img class="lyra-brand__mark lyra-brand__mark--dark" src="{markDark}" alt="">
  <!-- wordmark slot non-empty: -->
  <span class="lyra-brand__word">{slot}</span>
</a|span>
```
- Root class computation: `'lyra-brand'` + user classes ALWAYS LAST, single-space joined. No root modifier classes.
- All other HTML attributes pass through to the root.

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest for tests, Pint for style (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.
- Ignore any Compozy skills (cy-*) — implement directly in the worktree.

## Task

1. `tests/Fixtures/class-emission/brand.json` — cases: base (no props), user class appended.
2. `resources/views/components/brand.blade.php` — anonymous component per the contract.
3. `tests/Feature/BrandTest.php` — fixture-driven exact class assertions + markup: single vs dual mark imgs (`--light`/`--dark` modifiers), wordmark span present only when slot given, `role="img"` only on mark-only span, `<a>` root when `href` set (and no role), `--brand-mark-size` style with and without user style, attribute passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green).
- `<x-lyra::brand mark="/m.svg" aria-label="Acme" />` → `<span class="lyra-brand" role="img" aria-label="Acme">` with a single `<img class="lyra-brand__mark" src="/m.svg" alt="">`, no `lyra-brand__word`.
- `<x-lyra::brand mark="/m.svg" mark-dark="/d.svg" href="/">Acme</x-lyra::brand>` → `<a class="lyra-brand" href="/">` with the two modifier imgs and `<span class="lyra-brand__word">Acme</span>`, no `role`.
- `<x-lyra::brand mark="/m.svg" :size="24" class="x" style="color:red">A</x-lyra::brand>` → class exactly `lyra-brand x`, style contains `--brand-mark-size: 24px` followed by the user style.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/brand.blade.php (new)
- tests/Fixtures/class-emission/brand.json (new)
- tests/Feature/BrandTest.php (new)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
