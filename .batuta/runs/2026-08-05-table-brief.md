# Brief — Table component (React → Blade) — onda 6, item 3/3

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/table (a git worktree of lyra-ds/blade; branch batuta/table).

## Goal

Create the `<x-lyra::table>` Blade component mirroring the React Table, with data-driven class-emission tests proving exact class-string parity.

## Context

Mirror the established trio structure. React contract verified against packages/react/src/table/table.tsx.

Props:
- `columns`: PHP array of `['key' => string, 'label' => string, 'align' => ?('left'|'center'|'right')]` (required)
- `rows`: PHP array of associative arrays (required); cell value = `$row[$column['key']]` (missing key → empty cell)
- `hover`: bool — default false

Rendered markup (exact):
```html
<div class="lyra-table-wrap[ user classes last]" {...passthrough attrs}>
  <table class="lyra-table[ lyra-table--hover]">
    <thead><tr>
      <th[ style="text-align: {align}" only when align set]>{label}</th>
    </tr></thead>
    <tbody>
      <tr>
        <td class="lyra-table__primary"[ style...]>{first column value}</td>
        <td[ style...]>{other values}</td>
      </tr>
    </tbody>
  </table>
</div>
```
- Wrapper class: `'lyra-table-wrap'` + user classes LAST. Table class: `'lyra-table'` + `--hover` modifier only.
- FIRST column's `<td>` gets class `lyra-table__primary` (th does not). align → inline `text-align` style on th AND td, only when set.
- No scope/aria-sort/colgroup. Passthrough goes to the wrapper div.

## Conventions

PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both). TDD: failing tests first. Conventional commits (WIP allowed). Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/table.json` — cases: base, hover, user class (wrapper).
2. `resources/views/components/table.blade.php`
3. `tests/Feature/TableTest.php` — fixture-driven class assertions + markup: thead/tbody structure, first-td primary class, align styles on th+td only when set, hover modifier, missing row key → empty td, passthrough on wrapper, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green); `vendor/bin/pint --test` passes.
- `<x-lyra::table :columns="[['key'=>'name','label'=>'Nome'],['key'=>'qty','label'=>'Qtd','align'=>'right']]" :rows="[['name'=>'A','qty'=>1]]" hover class="x" />` → wrapper class exactly `lyra-table-wrap x`; table class exactly `lyra-table lyra-table--hover`; th Qtd with `text-align: right`; td Nome with class `lyra-table__primary`; td qty with `text-align: right`.

## Boundaries / Scope (closed list — nothing outside it; if the task requires it, stop and report)

- resources/views/components/table.blade.php (new)
- tests/Fixtures/class-emission/table.json (new)
- tests/Feature/TableTest.php (new)

No CSS/JS, no new dependencies.

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
