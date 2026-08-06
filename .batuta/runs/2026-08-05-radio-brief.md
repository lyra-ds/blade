# Brief — Radio component (React → Blade) — onda 5, item 7/8

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/radio (a git worktree of lyra-ds/blade; branch batuta/radio).

## Goal

Create the `<x-lyra::radio>` Blade component mirroring the React Radio, with data-driven class-emission tests proving exact class-string parity.

## Context

Mirror resources/views/components/checkbox.blade.php (it will exist on main before this task runs) and its test/fixture trio — Radio is its twin.

React contract (verified against packages/react/src/radio/radio.tsx — do NOT deviate; never invent API):

Props: `label` (plain prop, optional — same closed decision as Checkbox). `name` and all native input attributes pass through.

Rendered markup (exact):
- No label: bare `<input type="radio" class="lyra-radio[ user classes last]" {...passthrough} />`
- With label: `<label class="lyra-check-row"><input type="radio" class="lyra-radio[ user]" {...passthrough} /><span>{label}</span></label>`
- NO id generation, NO aria wiring. Class `'lyra-radio'` + user classes LAST, on the INPUT.

## Conventions

PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both). TDD: failing tests first. Conventional commits (WIP allowed). Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/radio.json` — cases: base, user class.
2. `resources/views/components/radio.blade.php`
3. `tests/Feature/RadioTest.php` — fixture-driven exact class assertions + markup: bare vs labeled, type=radio always, name/checked passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green); `vendor/bin/pint --test` passes.
- `<x-lyra::radio name="plan" value="pro" />` → bare input, class exactly `lyra-radio`, `type="radio"`.
- `<x-lyra::radio label="Pro" name="plan" class="x" />` → label.lyra-check-row wrapping input (class exactly `lyra-radio x`) + span `Pro`.

## Boundaries / Scope (closed list — nothing outside it; if the task requires it, stop and report)

- resources/views/components/radio.blade.php (new)
- tests/Fixtures/class-emission/radio.json (new)
- tests/Feature/RadioTest.php (new)

No CSS/JS, no new dependencies.

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
