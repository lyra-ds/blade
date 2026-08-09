# Brief — TimeInput component (React → Blade) — onda C, task 12

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/time-input (a git worktree of lyra-ds/blade; branch batuta/time-input). Ignore any Compozy skills; implement directly.

IMPORTANT — commit protocol: when the work is verified, COMMIT FIRST, then compose your report. The commit must never wait on the report.

## Goal

Create the `<lyra:time-input>` Blade component mirroring the React TimeInput, wired to the `lyraTimeInput` Alpine binding (parsing/stepping live in the binding — the Blade ships ZERO JS).

## Context

- Interactive precedent (wiring pattern): resources/views/components/table-of-contents.blade.php (integrated today) and sidebar-group.blade.php; tests + fixtures alongside. House rules in .batuta/profile.md (short syntax in examples; served attributes vs binding attributes rule).
- Binding contract (authoritative, read it): /home/franciscpd/Projects/lyra-ds/lyra/packages/alpine/src/time-input.ts — factory options, x-bind objects (input/steppers), modelable state; never invent names.
- React contract (authoritative for markup/classes/field layout): /home/franciscpd/Projects/lyra-ds/lyra/packages/react/src/time-input/time-input.tsx. Key served state to mirror:
  - input `role="spinbutton"`, `inputmode="numeric"`, aria-valuemin/valuemax (minutes; defaults 0/1439 or from min/max props), aria-valuenow/aria-valuetext only when a value is set, `aria-invalid` when error/invalid, `aria-describedby` → message id when hint/error present;
  - steppers with `tabindex="-1"` and their aria-labels; steppers container `aria-hidden` when disabled;
  - field layout label/hint/error like the existing input.blade.php (ids via uniqid precedent);
  - sizes (`sm|md|lg`) and error/invalid classes exactly as the React strings;
  - value prop (`HH:mm` string) served as the input value; `selected` modelable via the binding.

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits.
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. tests/Fixtures/class-emission/time-input.json — exact class strings (root/sizes/error/invalid, input, steppers, hint/error spans).
2. resources/views/components/time-input.blade.php — served markup + binding wiring (x-data with options from props, x-bind objects, x-modelable).
3. tests/Feature/TimeInputTest.php — fixture parity + markup: spinbutton ARIA set (min/max minutes from props, valuenow/valuetext presence rules), steppers tabindex/labels, disabled behavior, label/hint/error wiring with ids, value served, binding wiring, Livewire modelable, passthrough, user class last.
4. Regenerate resources/boost/guidelines/lyra-blade.md via php bin/generate-boost-guidelines.
5. COMMIT, then report.

## Acceptance criteria

- vendor/bin/pest passes (baseline 685 stays green).
- Class emissions match React exactly; ARIA served state matches the React first render.
- vendor/bin/pint --test passes; guidelines idempotent.

## Boundaries

Nothing outside Scope. No JS in the package. No new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/time-input.blade.php (new)
- tests/Fixtures/class-emission/time-input.json (new)
- tests/Feature/TimeInputTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint output; commit hash; uncertainties declared.

## Stop conditions

Stop and report when: the binding source contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.

---

# Run result (appended)

- Executor: codex (gpt-5.6-sol, reasoning high) via Compozy `sess-33a1c42d63125d25`. Commit-antes-do-report cumprido (`673aec1` no worktree).
- Verificação: scope exato; 714/714 (2976 assertions); pint ok; idempotente; spinbutton ARIA servido completo (valuemin/max de min/max em minutos, valuenow/valuetext só com valor, describedby merge); aria-valuetext default literal igual ao React.
- Verdict: ✅ approved — squash `fd68ef2`, cleanup completo.
