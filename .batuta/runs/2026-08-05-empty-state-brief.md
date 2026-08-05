# Brief — EmptyState component (React → Blade) — onda 3, item 4/5

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/empty-state (a git worktree of lyra-ds/blade; branch batuta/empty-state).

## Goal

Create the `<x-lyra::empty-state>` Blade component mirroring the React EmptyState, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 11 components built to one pattern — study and mirror:
- resources/views/components/stat.blade.php (closest sibling: required + optional named slots)
- tests/Feature/StatTest.php + tests/Fixtures/class-emission/stat.json

React contract (verified against packages/react/src/empty-state/empty-state.tsx — do NOT deviate; never invent API):

Props (all content → Blade named slots):
- `icon` slot — optional
- `title` slot — REQUIRED (always rendered)
- `description` slot — optional
- `action` slot — optional

Rendered markup (exact, fixed order):
```html
<div class="lyra-empty[ user classes last]" {...passthrough attrs}>
  {icon: <div class="lyra-empty__icon">{icon}</div>}
  <h3 class="lyra-empty__title">{title}</h3>
  {description: <p class="lyra-empty__desc">{description}</p>}
  {action: <div class="lyra-empty__action">{action}</div>}
</div>
```
- Class computation (root): `'lyra-empty'` + user classes ALWAYS LAST. No modifiers.
- Title h3 ALWAYS rendered. No role/aria anywhere. All attributes pass through to root.

## Conventions

- PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/empty-state.json` — cases: base, user class appended.
2. `resources/views/components/empty-state.blade.php` — anonymous component per the contract.
3. `tests/Feature/EmptyStateTest.php` — fixture-driven exact class assertions + markup: fixed element order, icon/desc/action conditionals, h3 always, passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green).
- `<x-lyra::empty-state><x-slot:title>Nada aqui</x-slot:title></x-lyra::empty-state>` → root class exactly `lyra-empty`, only the h3 inside.
- `<x-lyra::empty-state class="x"><x-slot:icon><svg/></x-slot:icon><x-slot:title>T</x-slot:title><x-slot:description>D</x-slot:description><x-slot:action><button>A</button></x-slot:action></x-lyra::empty-state>` → root class exactly `lyra-empty x`, four children in the contract order.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/empty-state.blade.php (new)
- tests/Fixtures/class-emission/empty-state.json (new)
- tests/Feature/EmptyStateTest.php (new)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
