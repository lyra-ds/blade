# Brief — CheckboxGroup component (React → Blade) — fase 2 onda B, item 1/2

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/checkbox-group (a git worktree of lyra-ds/blade; branch batuta/checkbox-group). Ignore any Compozy skills; implement directly.

## Goal

Create the `<x-lyra::checkbox-group>` Blade component mirroring the React CheckboxGroup, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 48 components built to one pattern — study and mirror:
- resources/views/components/checkbox.blade.php (the delegated control this group renders)
- resources/views/components/input.blade.php (label/hint/error field pattern + uniqid id generation)
- tests/Feature/CheckboxTest.php + tests/Fixtures/class-emission/checkbox.json

React contract (verified against packages/react/src/checkbox-group/checkbox-group.tsx in the main repo — do NOT deviate; never invent API):

Props:
- `label`: optional — rendered above the group
- `hint`: optional — rendered below when no error
- `error`: optional — rendered below IN PLACE of hint
- `options`: array, default `[]` — each option: `value` (string), `label`, `hint` (optional), `disabled` (optional bool)
- `value`: string[] — selected values (server-side: marks checked)
- `defaultValue`: string[], default `[]` — initial selection; `value` wins when both given
- `direction`: 'column' | 'row' — default 'column'; modifier class ONLY when 'row'
- `name`: optional string — ADAPTATION (documented, same spirit as Pagination onChange→links): when present, each checkbox input gets `name="{name}[]"` for native form submission. React has no name here because it is JS-state-driven; the Blade port is form-native. Flag this adaptation in your report.

Rendered markup (exact):
```html
<div class="lyra-field[ user]" role="group" [aria-labelledby="{merged}"] {...passthrough}>
  {label: <span id="{generatedLabelId}" class="lyra-label">{label}</span>}
  <div class="lyra-choicegroup[ lyra-choicegroup--row]">
    {per option: the EXACT markup resources/views/components/checkbox.blade.php emits for a labeled checkbox, with:
      - checked when option value ∈ (value ?? defaultValue)
      - disabled when option disabled
      - name="{name}[]" + value="{option value}" when the group name is set (value attribute needed for submission)
      - label content: plain label when no option hint; when option hint present the label content is
        <span class="lyra-choice"><span>{label}</span><span class="lyra-choice__hint">{hint}</span></span>}
  </div>
  {error: <span class="lyra-hint lyra-hint--error">{error}</span>
   else hint: <span class="lyra-hint">{hint}</span>}
</div>
```

aria-labelledby wiring (exact React logic): generated label id via 'lyra-checkbox-group-label-'.uniqid() (id-generation precedent: input.blade.php). With `label` present: aria-labelledby = consumer-passed aria-labelledby (if any) + ' ' + generatedLabelId (space-joined, consumer first). Without label: only the consumer-passed value, or omit the attribute entirely.

Class computation (root): `'lyra-field'` + user classes ALWAYS LAST. No other root modifiers.

Reuse `<x-lyra::checkbox>` for the options if it can emit the exact markup above (including the lyra-choice label wrapper); if it cannot without changing checkbox.blade.php, inline the input markup in the group template mirroring checkbox.blade.php's emission exactly — checkbox.blade.php itself is out of Scope.

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/checkbox-group.json` — cases: default (column), row, root with user class (user last), choicegroup exact strings, hint/error spans, lyra-choice wrapper.
2. `resources/views/components/checkbox-group.blade.php` — anonymous component per the contract.
3. `tests/Feature/CheckboxGroupTest.php` — fixture-driven exact class assertions + markup: label span with id + aria-labelledby merge (with and without consumer aria-labelledby), no label → no generated aria-labelledby, checked from value vs defaultValue (value wins), disabled per option, name adaptation (`name="x[]"` + value attr on each input; no name → no name attr), option hint wrapper exact structure, error replaces hint, direction row modifier, passthrough attrs, user class last.
4. Regenerate `resources/boost/guidelines/lyra-blade.md` via `php bin/generate-boost-guidelines`.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green).
- `<x-lyra::checkbox-group :options="[['value' => 'a', 'label' => 'A']]" />` → root class exactly `lyra-field`, `role="group"`, no aria-labelledby, inner div class exactly `lyra-choicegroup`.
- `<x-lyra::checkbox-group label="Prefs" direction="row" error="Required" class="x" :options="[['value' => 'a', 'label' => 'A', 'hint' => 'h']]" />` → root class exactly `lyra-field x`, aria-labelledby pointing at the label span id, choicegroup class exactly `lyra-choicegroup lyra-choicegroup--row`, option label wrapped in `lyra-choice`/`lyra-choice__hint`, message span class exactly `lyra-hint lyra-hint--error`.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies, no changes to checkbox.blade.php.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/checkbox-group.blade.php (new)
- tests/Fixtures/class-emission/checkbox-group.json (new)
- tests/Feature/CheckboxGroupTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint runs with actual output; the name adaptation flagged; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
