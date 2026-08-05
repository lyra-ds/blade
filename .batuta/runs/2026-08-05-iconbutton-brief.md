# Brief — IconButton component (React → Blade) — onda 2

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/iconbutton (a git worktree of lyra-ds/blade; branch batuta/iconbutton).

## Goal

Create the `<x-lyra::icon-button>` Blade component mirroring the React IconButton, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships button/spinner/badge/tag/separator/skeleton/progress built to one pattern — study and mirror:
- resources/views/components/button.blade.php (closest sibling)
- resources/views/components/spinner.blade.php (for the forced-attribute technique: `$attributes->except(...)->...->merge(...)`)
- tests/Feature/ButtonTest.php + tests/Fixtures/class-emission/button.json

React contract (verified against packages/react/src/icon-button/icon-button.tsx, lines 18-41 — do NOT deviate; never invent API):

Props:
- `label`: string — REQUIRED accessible name; becomes `aria-label` AND `title`
- `variant`: 'primary' | 'secondary' | 'soft' | 'ghost' | 'danger' — default 'secondary' (NOT primary)
- `size`: 'sm' | 'md' | 'lg' — default 'md'
- icon content: React `children` → Blade default slot (consumer passes their own SVG; the package never bundles an icon library — closed PRD decision)

Rendered markup (exact):
```html
<button class="lyra-btn lyra-btn--icon lyra-btn--{variant} lyra-btn--{size}[ user classes last]" aria-label="{label}" title="{label}" {...passthrough attrs}>{slot}</button>
```
- Class computation: `'lyra-btn' + ' lyra-btn--icon' + ' lyra-btn--{variant}' + ' lyra-btn--{size}'` + user classes ALWAYS LAST.
- `aria-label` and `title` both come from `label` and WIN over consumer-passed aria-label/title (in React they are set after the spread) — use the except+merge technique.
- Slot content rendered directly inside the button (no wrapper span). All other HTML attributes pass through.

## Conventions

- PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/icon-button.json` — cases: default (secondary/md), each variant at md (5), each size at secondary (3, md may repeat), user class appended; format identical to button.json.
2. `resources/views/components/icon-button.blade.php` — anonymous component per the contract.
3. `tests/Feature/IconButtonTest.php` — fixture-driven exact class assertions + markup: aria-label AND title from label (component wins over consumer-passed ones), slot rendered inside button unwrapped, passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green).
- `<x-lyra::icon-button label="Fechar"><svg/></x-lyra::icon-button>` → class exactly `lyra-btn lyra-btn--icon lyra-btn--secondary lyra-btn--md`, `aria-label="Fechar"`, `title="Fechar"`, svg inside.
- `<x-lyra::icon-button label="X" variant="danger" size="sm" class="x" />` → class exactly `lyra-btn lyra-btn--icon lyra-btn--danger lyra-btn--sm x`.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/icon-button.blade.php (new)
- tests/Fixtures/class-emission/icon-button.json (new)
- tests/Feature/IconButtonTest.php (new)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
