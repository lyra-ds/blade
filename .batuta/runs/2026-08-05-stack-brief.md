# Brief — Stack component (React → Blade) — onda 4, item 2/3

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/stack (a git worktree of lyra-ds/blade; branch batuta/stack).

## Goal

Create the `<x-lyra::stack>` Blade component mirroring the React Stack, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 14 components built to one pattern — study and mirror:
- resources/views/components/container.blade.php (closest sibling: conditional style with custom properties)
- tests/Feature/ContainerTest.php + tests/Fixtures/class-emission/container.json

React contract (verified against packages/react/src/stack/stack.tsx — do NOT deviate; never invent API):

Props:
- `direction`: 'row' | 'column' — optional (CSS default: column)
- `gap`: number | string — optional (CSS default: var(--space-4))
- `align`: string (CSS align-items value) — optional (CSS default: stretch)
- `justify`: string (CSS justify-content value) — optional (CSS default: flex-start)
- `wrap`: bool — default false
- `as`: string (HTML element name) — default 'div' (polymorphic root element)
- content: default slot

Rendered markup (exact):
```html
<{as} class="lyra-stack[ user classes last]"[ style="{custom properties}[; consumer style]"] {...passthrough attrs}>{slot}</{as}>
```
Custom-property mapping — ONLY set what was provided (absent prop → no property; no props at all → NO style attribute):
- direction → `--lyra-stack-direction: {direction}`
- gap number → `--lyra-stack-gap: var(--space-{gap})`; gap string → `--lyra-stack-gap: {gap}` verbatim
- align → `--lyra-stack-align: {align}`
- justify → `--lyra-stack-justify: {justify}`
- wrap true → `--lyra-stack-wrap: wrap` (false/absent → nothing)
Order of properties in the style string follows the list above (direction, gap, align, justify, wrap — matching the React source order). Consumer-passed `style` merges AFTER the computed properties (consumer wins).
- Class computation: `'lyra-stack'` + user classes ALWAYS LAST. No modifiers.
- All other HTML attributes pass through to the root element.

## Conventions

- PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/stack.json` — cases: base, user class appended.
2. `resources/views/components/stack.blade.php` — anonymous component per the contract (dynamic tag via the `as` prop).
3. `tests/Feature/StackTest.php` — fixture-driven exact class assertions + markup: no style attr when no layout props; each custom property mapping (incl. gap number → var(--space-N) and gap string verbatim); wrap true/false; `as="section"` renders `<section>`; consumer style merged after; passthrough; user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green).
- `<x-lyra::stack>S</x-lyra::stack>` → `<div class="lyra-stack">S</div>` with NO style attribute.
- `<x-lyra::stack as="section" direction="row" :gap="6" align="center" wrap class="x">S</x-lyra::stack>` → `<section>` root, class exactly `lyra-stack x`, style containing exactly `--lyra-stack-direction: row; --lyra-stack-gap: var(--space-6); --lyra-stack-align: center; --lyra-stack-wrap: wrap`.
- `<x-lyra::stack gap="24px" />` → style containing `--lyra-stack-gap: 24px`.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/stack.blade.php (new)
- tests/Fixtures/class-emission/stack.json (new)
- tests/Feature/StackTest.php (new)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
