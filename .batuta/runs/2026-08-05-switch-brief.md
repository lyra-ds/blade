# Brief — Switch component (React → Blade) — onda 5, item 8/8

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/switch (a git worktree of lyra-ds/blade; branch batuta/switch).

## Goal

Create the `<x-lyra::switch>` Blade component mirroring the React Switch, with data-driven class-emission tests proving exact class-string parity.

## Context

Mirror resources/views/components/checkbox.blade.php trio (labeled-control twin) — but note the structural differences below.

React contract (verified against packages/react/src/switch/switch.tsx — do NOT deviate; never invent API):

Props: `label` (plain prop, optional — same closed decision as Checkbox). All native input attributes pass through to the `<input>`.

Rendered markup (exact — ONE branch only, the label wrapper is ALWAYS present):
```html
<label class="lyra-switch[ user classes last]">
  <input type="checkbox" role="switch" {...passthrough} />
  <span class="lyra-switch__track" aria-hidden="true"></span>
  {label: <span>{label}</span>}
</label>
```
- IMPORTANT: user classes land on the LABEL (root), not the input — `cx('lyra-switch', className)` is on the label in React.
- The input has NO class. `type="checkbox"` + `role="switch"` always. Track span always, aria-hidden. Label text span (no class) only when label provided.
- NO id generation, NO aria wiring beyond role=switch.

## Conventions

PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both). TDD: failing tests first. Conventional commits (WIP allowed). Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/switch.json` — cases: base, user class (asserted on the LABEL).
2. `resources/views/components/switch.blade.php`
3. `tests/Feature/SwitchTest.php` — fixture-driven exact class assertions + markup: label root always, input type/role and NO class on input, track span aria-hidden, label span conditional, checked/disabled passthrough to input, user class last on label.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green); `vendor/bin/pint --test` passes.
- `<x-lyra::switch name="dark" />` → label class exactly `lyra-switch`, input `type="checkbox" role="switch"` without class, track span present, no text span.
- `<x-lyra::switch label="Tema escuro" checked class="x" />` → label class exactly `lyra-switch x`, input checked, text span `Tema escuro` after the track.

## Boundaries / Scope (closed list — nothing outside it; if the task requires it, stop and report)

- resources/views/components/switch.blade.php (new)
- tests/Fixtures/class-emission/switch.json (new)
- tests/Feature/SwitchTest.php (new)

No CSS/JS, no new dependencies.

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
