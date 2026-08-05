# Brief — Card component (React → Blade) — onda 3, item 1/5

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/card (a git worktree of lyra-ds/blade; branch batuta/card).

## Goal

Create the `<x-lyra::card>` Blade component mirroring the React Card, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 8 components built to one pattern — study and mirror:
- resources/views/components/separator.blade.php (closest sibling: conditional branches + named slot)
- tests/Feature/SeparatorTest.php + tests/Fixtures/class-emission/separator.json

React contract (verified against packages/react/src/card/card.tsx — do NOT deviate; never invent API):

Props:
- `padded`: bool — default TRUE
- `interactive`: bool — default false
- `title`: ReactNode → Blade named slot `title`
- `actions`: ReactNode → Blade named slot `actions`
- `footer`: ReactNode → Blade named slot `footer`
- body: React `children` → Blade default slot
- React `asChild` is NOT implemented in phase 1 (React Slot mechanism; closed precedent from Button). One-line docblock noting the omission.

Class computation (root):
`'lyra-card'` + (`' lyra-card--interactive'` when interactive) + (`' lyra-card--padded'` ONLY when there is NO title AND NO footer AND padded is true) + user classes ALWAYS LAST.

TWO render branches (asChild excluded):
1. NO title and NO footer:
```html
<div class="lyra-card[ lyra-card--interactive][ lyra-card--padded][ user]" {...passthrough}>{slot}</div>
```
(slot direct inside root, no wrapper)
2. title OR footer present:
```html
<div class="lyra-card[ lyra-card--interactive][ user]" {...passthrough}>
  {title: <div class="lyra-card__header"><h3 class="lyra-card__title">{title}</h3>{actions: <div class="lyra-card__actions">{actions}</div>}</div>}
  <div[ class="lyra-card__body" when padded]>{slot}</div>
  {footer: <div class="lyra-card__footer">{footer}</div>}
</div>
```
Notes for branch 2: the body wrapper div ALWAYS exists; it gets class `lyra-card__body` only when padded (no class attribute at all when not padded). The `lyra-card--padded` root class NEVER appears in branch 2. `actions` renders only when provided, inside the header (header exists only when title is present — actions without title: header div is NOT rendered, actions are ignored, matching React where the header block is gated on title).

## Conventions

- PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/card.json` — cases: default (bare, padded), not padded, interactive, interactive+user class, with-title (no --padded class), with-footer (no --padded class).
2. `resources/views/components/card.blade.php` — anonymous component per the contract.
3. `tests/Feature/CardTest.php` — fixture-driven exact class assertions + markup: both branches, header/title/actions/footer structure and order, body wrapper class gated on padded, passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green).
- `<x-lyra::card>Body</x-lyra::card>` → `<div class="lyra-card lyra-card--padded">Body</div>` (no wrappers).
- `<x-lyra::card :padded="false" interactive class="x">B</x-lyra::card>` → class exactly `lyra-card lyra-card--interactive x`.
- `<x-lyra::card><x-slot:title>T</x-slot:title><x-slot:actions>A</x-slot:actions>B<x-slot:footer>F</x-slot:footer></x-lyra::card>` → root class exactly `lyra-card`; header div with h3 `lyra-card__title` (T) and `lyra-card__actions` (A); body div with class `lyra-card__body` (B); footer div `lyra-card__footer` (F), in that order.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/card.blade.php (new)
- tests/Fixtures/class-emission/card.json (new)
- tests/Feature/CardTest.php (new)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
