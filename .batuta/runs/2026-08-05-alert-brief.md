# Brief — Alert component (React → Blade) — onda 3, item 2/5

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/alert (a git worktree of lyra-ds/blade; branch batuta/alert).

## Goal

Create the `<x-lyra::alert>` Blade component mirroring the React Alert, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 9 components built to one pattern — study and mirror:
- resources/views/components/card.blade.php (closest sibling: named slots + conditional sections)
- tests/Feature/CardTest.php + tests/Fixtures/class-emission/card.json

React contract (verified against packages/react/src/alert/alert.tsx — do NOT deviate; never invent API):

Props:
- `tone`: 'info' | 'success' | 'warning' | 'danger' — default 'info'
- `title`: ReactNode → Blade named slot `title`
- `icon`: ReactNode → Blade named slot `icon`
- body: React `children` → Blade default slot (required)

Rendered markup (exact):
```html
<div class="lyra-alert lyra-alert--{tone}[ user classes last]" role="status" {...passthrough attrs}>
  {icon: <span class="lyra-alert__icon">{icon}</span>}
  <div>
    {title: <p class="lyra-alert__title">{title}</p>}
    <p class="lyra-alert__body">{slot}</p>
  </div>
</div>
```
- Class computation: `'lyra-alert' + ' lyra-alert--{tone}'` + user classes ALWAYS LAST.
- `role="status"` always on root (component wins — use the except+merge technique like spinner's role).
- The inner `<div>` wrapper has NO class. Icon span only when icon slot present. Title p only when title slot present. Body p always.

## Conventions

- PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/alert.json` — cases: each of the 4 tones, default (no tone), user class appended.
2. `resources/views/components/alert.blade.php` — anonymous component per the contract.
3. `tests/Feature/AlertTest.php` — fixture-driven exact class assertions + markup: role=status, icon/title conditionals, body p always present, inner div unclassed, passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green).
- `<x-lyra::alert>Saved</x-lyra::alert>` → root class exactly `lyra-alert lyra-alert--info`, `role="status"`, body `<p class="lyra-alert__body">Saved</p>`, no icon span, no title p.
- `<x-lyra::alert tone="danger" class="x"><x-slot:icon><svg/></x-slot:icon><x-slot:title>Erro</x-slot:title>Falhou</x-lyra::alert>` → root class exactly `lyra-alert lyra-alert--danger x`, icon span before the inner div, title p then body p inside it.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/alert.blade.php (new)
- tests/Fixtures/class-emission/alert.json (new)
- tests/Feature/AlertTest.php (new)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
