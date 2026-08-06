# Brief — FormRow component (React → Blade) — onda 5, item 2/8

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/form-row (a git worktree of lyra-ds/blade; branch batuta/form-row).

## Goal

Create the `<x-lyra::form-row>` Blade component mirroring the React FormRow, with data-driven class-emission tests proving exact class-string parity.

## Context

Mirror resources/views/components/grid.blade.php (custom property + conditional style) and its test/fixture trio.

React contract (verified against packages/react/src/fieldset/fieldset.tsx lines 41-60, where FormRow lives — do NOT deviate; never invent API):

Props: `columns` (number, optional), default slot (required).

Rendered markup (exact):
```html
<div class="lyra-formrow[ user classes last]" style="--lyra-formrow-columns: repeat({N}, minmax(0, 1fr))[; consumer style]" {...passthrough attrs}>{slot}</div>
```
- `N` = `columns` when provided. React default is `Children.count(children)` — the Blade equivalent (a closed decision): count the TOP-LEVEL child elements of the default slot's rendered HTML (parse with a simple regex/DOMDocument over the slot string counting root-level tags). Slot with 3 inputs and no columns prop → `repeat(3, ...)`.
- The style attribute is ALWAYS present (unlike Grid). Consumer style merges after.
- Class: `'lyra-formrow'` + user classes LAST.

## Conventions

PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both). TDD: failing tests first. Conventional commits (WIP allowed). Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/form-row.json` — cases: base, user class.
2. `resources/views/components/form-row.blade.php`
3. `tests/Feature/FormRowTest.php` — fixture-driven class assertions + markup: explicit columns, derived count from slot children (e.g. 3 divs → repeat(3,...)), consumer style after, passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green); `vendor/bin/pint --test` passes.
- `<x-lyra::form-row :columns="2">…</x-lyra::form-row>` → style containing `--lyra-formrow-columns: repeat(2, minmax(0, 1fr))`.
- `<x-lyra::form-row><div>a</div><div>b</div><div>c</div></x-lyra::form-row>` → `repeat(3, minmax(0, 1fr))`.

## Boundaries / Scope (closed list — nothing outside it; if the task requires it, stop and report)

- resources/views/components/form-row.blade.php (new)
- tests/Fixtures/class-emission/form-row.json (new)
- tests/Feature/FormRowTest.php (new)

No CSS/JS, no new dependencies.

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
