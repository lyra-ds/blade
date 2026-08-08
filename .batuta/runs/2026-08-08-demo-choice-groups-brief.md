# Brief — demo gallery: CheckboxGroup + RadioGroup sections

Work only inside /home/franciscpd/Projects/lyra-ds/blade-demo (the demo Laravel app; lyra-ds/blade is symlinked via path repository, so the two new components are already available). Ignore any Compozy skills; implement directly.

## Goal

Add a "Choice groups" section to the demo gallery showcasing the two new components: `<x-lyra::checkbox-group>` and `<x-lyra::radio-group>`.

## Context

- resources/views/demo.blade.php — single-page gallery; sections are `<section class="demo-section"><h2>…</h2><div class="demo-grid">…</div></section>`. Mirror the existing section pattern exactly.
- Component APIs (both): `label`, `hint`, `error`, `options` (array of ['value','label','hint','disabled']), `value`, `defaultValue`, `direction` ('column'|'row'), `name`. RadioGroup `value` is a string; CheckboxGroup `value`/`defaultValue` are arrays.

## Task

In demo.blade.php, add ONE new section titled "Choice groups" immediately after the "Feedback" section, containing:
1. A `<x-lyra::checkbox-group>` with a label, a hint, 3 options (one with an option hint, one disabled), `:defaultValue="['…']"` marking one checked, name="prefs".
2. A `<x-lyra::radio-group>` with a label, `direction="row"`, 3 options, `value` marking one checked, name="plan".

Realistic copy in English matching the gallery's tone (release/build/workspace vocabulary).

## Acceptance criteria

- `php artisan test` passes (existing suite stays green).
- `php artisan view:clear && php artisan view:cache` compiles without error.
- The new section follows the existing markup pattern (demo-section/h2/demo-grid) and sits right after Feedback.

## Boundaries / Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/demo.blade.php (only the new section; do not alter existing sections)

## Expected evidence

Report: the diff summary; test and view:cache runs with actual output.

## Stop conditions

Stop and report when: a component renders an error, the same command fails twice, or the change needs edits beyond Scope.

---

# Run result (appended)

- Executor: codex (default model) via Compozy `sess-fe5baa7c7347e7e0`, cwd blade-demo
- Primeira tentativa limpa. Verificação: diff só com a seção nova (padrão demo-section/h2/demo-grid, após Feedback); `php artisan test` 3/3 (19 assertions); `php artisan view:cache` compila. Commit `1ea42fa` no repo do demo.
- Verdict: ✅ approved
