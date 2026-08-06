# Brief — Checkbox component (React → Blade) — onda 5, item 6/8

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/checkbox (a git worktree of lyra-ds/blade; branch batuta/checkbox).

## Goal

Create the `<x-lyra::checkbox>` Blade component mirroring the React Checkbox, with data-driven class-emission tests proving exact class-string parity.

## Context

Mirror the established pattern (see resources/views/components/ and tests/).

React contract (verified against packages/react/src/checkbox/checkbox.tsx — do NOT deviate; never invent API):

Props: `label` — plain Blade prop carrying a string (closed decision: React's ReactNode label maps to a string prop in phase 1; rich label content is out of scope). All native input attributes pass through to the `<input>`.

Rendered markup (exact):
- No label: bare `<input type="checkbox" class="lyra-checkbox[ user classes last]" {...passthrough} />`
- With label:
```html
<label class="lyra-check-row">
  <input type="checkbox" class="lyra-checkbox[ user]" {...passthrough} />
  <span>{label}</span>
</label>
```
- NO id generation, NO aria wiring, NO error/hint — association is the implicit wrapping label. The label span has NO class.
- Class: `'lyra-checkbox'` + user classes LAST, on the INPUT.

## Conventions

PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both). TDD: failing tests first. Conventional commits (WIP allowed). Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/checkbox.json` — cases: base, user class.
2. `resources/views/components/checkbox.blade.php`
3. `tests/Feature/CheckboxTest.php` — fixture-driven exact class assertions + markup: bare vs labeled branches, type=checkbox always, checked/disabled passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green); `vendor/bin/pint --test` passes.
- `<x-lyra::checkbox name="ok" />` → bare input, class exactly `lyra-checkbox`, `type="checkbox"`.
- `<x-lyra::checkbox label="Aceito" checked class="x" />` → label.lyra-check-row wrapping input (class exactly `lyra-checkbox x`, checked) + span `Aceito`.

## Boundaries / Scope (closed list — nothing outside it; if the task requires it, stop and report)

- resources/views/components/checkbox.blade.php (new)
- tests/Fixtures/class-emission/checkbox.json (new)
- tests/Feature/CheckboxTest.php (new)

No CSS/JS, no new dependencies.

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
