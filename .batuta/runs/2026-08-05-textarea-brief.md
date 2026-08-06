# Brief — Textarea component (React → Blade) — onda 5, item 4/8

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/textarea (a git worktree of lyra-ds/blade; branch batuta/textarea).

## Goal

Create the `<x-lyra::textarea>` Blade component mirroring the React Textarea, with data-driven class-emission tests proving exact class-string parity.

## Context

Mirror resources/views/components/input.blade.php (this wave's pattern-setter — it will exist on main before this task runs) and its test/fixture trio.

React contract (verified against packages/react/src/textarea/textarea.tsx — do NOT deviate; never invent API):

Props: `label` (string, optional), `hint` (string, optional, replaced by error), `error` (string, optional). NO size prop, NO icon. Default slot content = the textarea's initial value (rendered between the tags). All native textarea attributes pass through.

Class computation (textarea element): `'lyra-input' + ' lyra-textarea'` + (`' lyra-input--error'` when error) + user classes ALWAYS LAST.

Same id/aria/branch rules as Input: bare `<textarea>` when no label/hint/error; `.lyra-field` wrapper with `lyra-label` label (for=id), message span `lyra-hint[ lyra-hint--error]` otherwise; generated unique ids; aria-invalid on error; aria-describedby merge.

## Conventions

PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both). TDD: failing tests first. Conventional commits (WIP allowed). Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/textarea.json` — cases: default, error, user class.
2. `resources/views/components/textarea.blade.php`
3. `tests/Feature/TextareaTest.php` — fixture-driven exact class assertions + markup: both branches, label wiring, hint/error replacement, aria, slot content inside the tags, passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green); `vendor/bin/pint --test` passes.
- `<x-lyra::textarea name="bio" />` → bare `<textarea>` class exactly `lyra-input lyra-textarea`.
- `<x-lyra::textarea label="Bio" error="Curto demais" class="x">texto</x-lyra::textarea>` → field div, textarea class exactly `lyra-input lyra-textarea lyra-input--error x`, content `texto`, aria-invalid, message span `lyra-hint lyra-hint--error`.

## Boundaries / Scope (closed list — nothing outside it; if the task requires it, stop and report)

- resources/views/components/textarea.blade.php (new)
- tests/Fixtures/class-emission/textarea.json (new)
- tests/Feature/TextareaTest.php (new)

No CSS/JS, no new dependencies.

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
