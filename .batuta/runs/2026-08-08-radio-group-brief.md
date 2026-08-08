# Brief — RadioGroup component (React → Blade) — fase 2 onda B, item 2/2

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/radio-group (a git worktree of lyra-ds/blade; branch batuta/radio-group). Ignore any Compozy skills; implement directly.

## Goal

Create the `<x-lyra::radio-group>` Blade component mirroring the React RadioGroup, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 49 components built to one pattern — study and mirror:
- resources/views/components/checkbox-group.blade.php (the sibling component, integrated today — same field structure, delegation and aria wiring; mirror its shape)
- resources/views/components/radio.blade.php (the delegated control: `label.lyra-check-row > input.lyra-radio + span` when labeled)
- tests/Feature/CheckboxGroupTest.php + tests/Fixtures/class-emission/checkbox-group.json (the test pattern to mirror)

React contract (verified against packages/react/src/radio-group/radio-group.tsx in the main repo — do NOT deviate; never invent API):

Props:
- `label`, `hint`, `error`: optional — same field treatment as checkbox-group (error replaces hint)
- `options`: array, default `[]` — each option: `value` (string), `label`, `hint` (optional), `disabled` (optional bool)
- `value`: string — selected value (server-side: marks checked); wins over defaultValue
- `defaultValue`: string — initial selection
- `name`: optional string — shared native name for all radio inputs; when omitted, GENERATE one: 'lyra-radio-group-'.uniqid() (React generates via useId; radios need a shared name to group natively). Every input always gets the name (generated or given) AND value="{option value}" — unlike checkbox-group, where name is an adaptation, here name is React API.
- `direction`: 'column' | 'row' — default 'column'; modifier class ONLY when 'row'

Rendered markup (exact):
```html
<div class="lyra-field[ user]" role="radiogroup" [aria-labelledby="{merged}"] {...passthrough}>
  {label: <span id="{generatedLabelId}" class="lyra-label">{label}</span>}
  <div class="lyra-choicegroup[ lyra-choicegroup--row]">
    {per option: the EXACT markup radio.blade.php emits for a labeled radio, with:
      - name="{group name}" and value="{option value}" always
      - checked when option value === (value ?? defaultValue), strict comparison
      - disabled when option disabled
      - label content: plain label when no option hint; with hint:
        <span class="lyra-choice"><span>{label}</span><span class="lyra-choice__hint">{hint}</span></span>}
  </div>
  {error: <span class="lyra-hint lyra-hint--error">{error}</span>
   else hint: <span class="lyra-hint">{hint}</span>}
</div>
```

aria-labelledby wiring: identical to checkbox-group (generated label id 'lyra-radio-group-label-'.uniqid(); merge consumer-first when label present; consumer value or omitted otherwise).

Class computation (root): `'lyra-field'` + user classes ALWAYS LAST. Root role is `radiogroup` (NOT `group`).

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/radio-group.json` — same case set as checkbox-group.json (root default, user class last, choicegroup default/row, hint/error, choice/choice__hint).
2. `resources/views/components/radio-group.blade.php` — anonymous component per the contract.
3. `tests/Feature/RadioGroupTest.php` — fixture-driven exact class assertions + markup: role radiogroup, aria-labelledby merge (with/without consumer value, no label → none generated), shared name on all inputs (given name and generated fallback — assert the generated one is a single shared value across inputs), value attribute per option, checked from value vs defaultValue (value wins, strict), disabled per option, option hint wrapper, error replaces hint, row modifier, passthrough, user class last.
4. Regenerate `resources/boost/guidelines/lyra-blade.md` via `php bin/generate-boost-guidelines`.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green).
- `<x-lyra::radio-group :options="[['value' => 'a', 'label' => 'A']]" />` → root class exactly `lyra-field`, `role="radiogroup"`, no aria-labelledby, all inputs share one generated name, input has `value="a"`.
- `<x-lyra::radio-group label="Plan" name="plan" value="b" direction="row" class="x" :options="[['value' => 'a', 'label' => 'A'], ['value' => 'b', 'label' => 'B', 'hint' => 'h']]" />` → root class exactly `lyra-field x`, aria-labelledby → label span id, choicegroup exactly `lyra-choicegroup lyra-choicegroup--row`, both inputs `name="plan"`, second checked with `lyra-choice`/`lyra-choice__hint` wrapper.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies, no changes to radio.blade.php or checkbox-group.blade.php.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/radio-group.blade.php (new)
- tests/Fixtures/class-emission/radio-group.json (new)
- tests/Feature/RadioGroupTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
