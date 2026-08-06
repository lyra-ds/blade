# Brief — Select component (React → Blade) — onda 5, item 5/8

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/select (a git worktree of lyra-ds/blade; branch batuta/select).

## Goal

Create the `<x-lyra::select>` Blade component mirroring the React Select, with data-driven class-emission tests proving exact class-string parity.

## Context

Mirror resources/views/components/input.blade.php (same field pattern; it will exist on main before this task runs) and its test/fixture trio.

React contract (verified against packages/react/src/select/select.tsx — do NOT deviate; never invent API):

Props: `label` (string, optional), `hint` (string, optional, replaced by error), `error` (string, optional), `size` sm|md|lg default md. Default slot = native `<option>`/`<optgroup>` children. All native select attributes pass through to the `<select>`.

Class computation (select element): `'lyra-input'` + (`' lyra-input--sm'` when sm) + (`' lyra-input--lg'` when lg) + (`' lyra-input--error'` when error) + user classes ALWAYS LAST. (Same formula as Input — no `lyra-select` class.)

Structure: the `<select>` is ALWAYS wrapped in `<span class="lyra-select-wrap">…</span>` (both branches).
- Bare branch (no label/hint/error): return just the wrap+select.
- Field branch: `.lyra-field` div with `lyra-label` label (for=id), then wrap+select, then message span `lyra-hint[ lyra-hint--error]`.
Same id/aria rules as Input (generated unique ids, aria-invalid on error, aria-describedby merge).

## Conventions

PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both). TDD: failing tests first. Conventional commits (WIP allowed). Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/select.json` — cases: default, sm, lg, error, user class.
2. `resources/views/components/select.blade.php`
3. `tests/Feature/SelectTest.php` — fixture-driven exact class assertions + markup: wrap span always, both branches, label wiring, options rendered, aria, passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green); `vendor/bin/pint --test` passes.
- `<x-lyra::select name="uf"><option>SP</option></x-lyra::select>` → `<span class="lyra-select-wrap">` wrapping select class exactly `lyra-input`, option inside.
- `<x-lyra::select label="UF" size="lg" error="Escolha" class="x"><option>SP</option></x-lyra::select>` → field div, select class exactly `lyra-input lyra-input--lg lyra-input--error x`, aria-invalid, message span `lyra-hint lyra-hint--error`.

## Boundaries / Scope (closed list — nothing outside it; if the task requires it, stop and report)

- resources/views/components/select.blade.php (new)
- tests/Fixtures/class-emission/select.json (new)
- tests/Feature/SelectTest.php (new)

No CSS/JS, no new dependencies.

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
