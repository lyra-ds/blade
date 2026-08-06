# Brief — Fieldset component (React → Blade) — onda 5, item 1/8

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/fieldset (a git worktree of lyra-ds/blade; branch batuta/fieldset).

## Goal

Create the `<x-lyra::fieldset>` Blade component mirroring the React Fieldset, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 16 components built to one pattern — mirror resources/views/components/card.blade.php (named slots + conditionals) and its test/fixture trio.

React contract (verified against packages/react/src/fieldset/fieldset.tsx — do NOT deviate; never invent API):

Props: `legend` (named slot, optional), `description` (named slot, optional), default slot (required — the fields).

Rendered markup (exact):
```html
<fieldset class="lyra-fieldset[ user classes last]" {...passthrough attrs}>
  {legend: <legend class="lyra-fieldset__legend">{legend}</legend>}
  {description: <p class="lyra-fieldset__desc">{description}</p>}
  <div class="lyra-fieldset__fields">{slot}</div>
</fieldset>
```
Class: `'lyra-fieldset'` + user classes LAST. No id/aria wiring — semantics come from fieldset/legend.

## Conventions

PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both). TDD: failing tests first. Conventional commits (WIP allowed). Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/fieldset.json` — cases: base, user class.
2. `resources/views/components/fieldset.blade.php`
3. `tests/Feature/FieldsetTest.php` — fixture-driven exact class assertions + markup: legend/desc conditionals, fields wrapper always, order, passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green); `vendor/bin/pint --test` passes.
- `<x-lyra::fieldset>F</x-lyra::fieldset>` → fieldset class exactly `lyra-fieldset`, only the fields div inside.
- `<x-lyra::fieldset class="x"><x-slot:legend>L</x-slot:legend><x-slot:description>D</x-slot:description>F</x-lyra::fieldset>` → class exactly `lyra-fieldset x`, legend then p then fields div.

## Boundaries / Scope (closed list — nothing outside it; if the task requires it, stop and report)

- resources/views/components/fieldset.blade.php (new)
- tests/Fixtures/class-emission/fieldset.json (new)
- tests/Feature/FieldsetTest.php (new)

No CSS/JS, no new dependencies.

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
