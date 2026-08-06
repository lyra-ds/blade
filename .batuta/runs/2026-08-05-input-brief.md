# Brief — Input component (React → Blade) — onda 5, item 3/8

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/input (a git worktree of lyra-ds/blade; branch batuta/input).

## Goal

Create the `<x-lyra::input>` Blade component mirroring the React Input, with data-driven class-emission tests proving exact class-string parity. This is the richest form control — it sets the pattern Textarea and Select will reuse.

## Context

Mirror the established component pattern (see resources/views/components/ and tests/). Closest structural sibling for forced attributes: spinner/icon-button (except+merge).

React contract (verified against packages/react/src/input/input.tsx — do NOT deviate; never invent API):

Props:
- `label`: string — optional
- `hint`: string — optional (replaced by `error` when both present)
- `error`: string — optional
- `size`: 'sm' | 'md' | 'lg' — default 'md' (NO modifier class for md)
- `iconLeft`: named slot — optional
- All native input attributes pass through to the `<input>` (type, name, value, placeholder, disabled, required...).

Class computation (the input element): `'lyra-input'` + (`' lyra-input--sm'` when sm) + (`' lyra-input--lg'` when lg) + (`' lyra-input--error'` when error) + user classes ALWAYS LAST.

Id/aria wiring:
- input id = consumer `id` attribute ?? generated unique id (use `'lyra-input-'.uniqid()` or similar; must be unique per render).
- message span id = another generated unique id.
- `aria-invalid="true"` when error (consumer aria-invalid respected when no error).
- `aria-describedby` = consumer value + message id joined with space when a hint/error message renders; consumer value alone otherwise.

Render branches:
1. Control element (always): `<input id="..." class="..." {aria} {...passthrough} />`. When `iconLeft` slot present, the input is wrapped: `<span class="lyra-input-wrap"><span class="lyra-input-wrap__icon">{iconLeft}</span><input .../></span>`.
2. No label AND no hint AND no error → return the bare control (with icon wrap when applicable) — NO `.lyra-field` div.
3. Any of label/hint/error present → 
```html
<div class="lyra-field">
  {label: <label class="lyra-label" for="{inputId}">{label}</label>}
  {control}
  {error: <span id="{messageId}" class="lyra-hint lyra-hint--error">{error}</span>
   else hint: <span id="{messageId}" class="lyra-hint">{hint}</span>}
</div>
```
Note: user classes and passthrough attrs go to the INPUT, not the field div.

## Conventions

PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both). TDD: failing tests first. Conventional commits (WIP allowed). Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/input.json` — cases: default, sm, lg, error, sm+error, user class.
2. `resources/views/components/input.blade.php`
3. `tests/Feature/InputTest.php` — fixture-driven exact class assertions + markup: bare vs field branches, label for= wiring with explicit id, generated id uniqueness (two renders → different ids), hint vs error replacement, aria-invalid/aria-describedby, icon wrap, passthrough to input, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green); `vendor/bin/pint --test` passes.
- `<x-lyra::input name="q" />` → bare `<input>` class exactly `lyra-input`, no `.lyra-field`, no aria-invalid.
- `<x-lyra::input id="email" label="E-mail" error="Obrigatório" size="sm" class="x" />` → field div; label `for="email"`; input class exactly `lyra-input lyra-input--sm lyra-input--error x`, `aria-invalid="true"`, `aria-describedby` pointing at the message span id; message span class `lyra-hint lyra-hint--error` with text `Obrigatório`.
- `<x-lyra::input label="Busca" hint="3+ letras"><x-slot:iconLeft><svg/></x-slot:iconLeft></x-lyra::input>` → icon wrap span before input, hint span without --error.

## Boundaries / Scope (closed list — nothing outside it; if the task requires it, stop and report)

- resources/views/components/input.blade.php (new)
- tests/Fixtures/class-emission/input.json (new)
- tests/Feature/InputTest.php (new)

No CSS/JS, no new dependencies.

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
