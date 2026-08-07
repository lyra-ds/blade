# Brief — Tabs component (React → Blade + Alpine bindings) — fase 2, onda core, item 4/7

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/tabs (a git worktree of lyra-ds/blade; branch batuta/tabs). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create `<x-lyra::tabs>`: APG tabs (automatic activation, roving focus) with served markup matching the React first paint plus the `@lyra-ds/alpine` `lyraTabs` bindings. No JS in this package.

## Context

Interactive siblings to mirror for pattern (x-data literal building + escaping, wire:model/x-model split onto the root, fixed attributes BEFORE any passthrough bag, no server ids, Livewire test, fixtures root-only):
- resources/views/components/dialog.blade.php + tests/Feature/DialogTest.php (most recent, has all lessons)
- resources/views/components/dropdown.blade.php (items array pattern, Htmlable-aware item rendering)

Contract verified against the sources (do NOT deviate):
- React packages/react/src/tabs/tabs.tsx: props `items` (TabItem[]: `id` string, `label` ReactNode, `count?` number, `icon?` ReactNode), `active` (string, REQUIRED — controlled), `onChange` (JS callback — not ported), `variant` ('line' default | 'pills'), rest spread on the TABLIST div. Renders (Fragment, no wrapper): tablist div `class="lyra-tabs[ lyra-tabs--pills][ user]" role="tablist"` containing per item a `<button type="button" role="tab" aria-selected tabindex={selected?0:-1} class="lyra-tab[ lyra-tab--active]">{icon}{label}[<span class="lyra-tab__count">{count}</span>]</button>`; then per item an EMPTY `<div role="tabpanel" tabindex="0" hidden={!selected}>`. Active resolution: `Math.max(0, items.findIndex(id === active))` — unknown/missing active falls back to index 0.
- Alpine packages/alpine/src/tabs.ts + tabs.browser.test.ts (canonical markup): `Alpine.data('lyraTabs', ...)` REQUIRES `{ active: string }`. Bindings: `list` (role=tablist), `tab` (static role/type; dynamic :id/:aria-selected/:aria-controls/:tabindex/:class active-modifier/@click/@keydown), `panel` (static role=tabpanel, tabindex 0; dynamic :id/:aria-labelledby/:hidden). The canonical structure is a PLAIN UNCLASSED root div carrying x-data, containing the tablist (x-bind="list") with tab buttons (`data-value` + x-bind="tab"), then sibling panel divs (`data-value` + x-bind="panel") WITH content. Tabs/panels are matched by `data-value`; ids are assigned by the plugin when absent.

### Blade API (closed decisions — implement exactly)

- `items` (array, required): per item `['id' => string (required), 'label' => string|Htmlable (required), 'count' => int|null, 'icon' => string|Htmlable|null, 'panel' => string|Htmlable|null]`. The `panel` key is a documented Blade extension: React renders empty labelled panels (its handoff contract supplies labels only), but the Alpine plugin's canonical markup carries panel content — one docblock line stating exactly that. `onChange` is not ported (docblock line; consumer state flows via x-model/wire:model).
- `active` (string, required) — seeds the served state; unknown value falls back to the FIRST item (React parity).
- `variant` ('line'|'pills', default 'line') — pills adds `lyra-tabs--pills`; any other value = line (safe-coercion precedent).
- Attribute passthrough on the TABLIST div (React parity), user classes last; `wire:model*`/`x-model*` split onto the ROOT div (where x-modelable lives).
- Root div: NO class, `x-data="lyraTabs({ active: '...' })"` (escaping exactly like Dialog's labelId: JS-escape then htmlspecialchars ENT_COMPAT|ENT_SUBSTITUTE), `x-modelable="active"`, model split. NOTHING else — the root is structural (the plugin queries within it).
- NO server ids anywhere (no id/aria-controls/aria-labelledby served — the plugin assigns at init). Served state parity: the ACTIVE tab (resolved index) gets `lyra-tab--active`, `aria-selected="true"`, `tabindex="0"`; others `aria-selected="false"`, `tabindex="-1"`. Inactive panels get the bare `hidden` attribute.

Rendered markup (exact; [] = conditional):
```html
<div x-data="lyraTabs({ active: '...' })" x-modelable="active" {wire:model*/x-model* split}>
  <div {passthrough} class="lyra-tabs[ lyra-tabs--pills][ user]" role="tablist" x-bind="list">
    <!-- per item -->
    <button type="button" role="tab" aria-selected="true|false" tabindex="0|-1"
            class="lyra-tab[ lyra-tab--active]" data-value="{id}" x-bind="tab">[{icon}]{label}[<span class="lyra-tab__count">{count}</span>]</button>
  </div>
  <!-- per item -->
  <div role="tabpanel" tabindex="0" data-value="{id}"[ hidden] x-bind="panel">[{panel}]</div>
</div>
```
- Fixed attributes render BEFORE the passthrough bag on the tablist (duplicate-resolution lesson; regression test like Dialog's role test).
- count renders iff not null (0 renders — React checks `!= null`). Htmlable unescaped / strings escaped for label, icon, panel.
- data-value: escaped normally by Blade.

## Conventions

Same block as the Dialog brief (PHP >= 8.3, Pest+Pint, TDD failing-first, test laws, containment assertions, multi-line directives never butted, no drive-bys, no new deps, ignore cy-* skills; superpowers `test-driven-development` if available, else test-first). COMMIT WHEN GREEN — a finished turn without a commit fails the cycle.

## Task

1. `tests/Fixtures/class-emission/tabs.json` — root-element convention: the component's classed root is the TABLIST div; cases: base (`lyra-tabs`), pills (`lyra-tabs lyra-tabs--pills`), base + user class (`lyra-tabs x`).
2. `resources/views/components/tabs.blade.php` per the contract.
3. `tests/Feature/TabsTest.php` — fixture-driven tablist class assertions + markup: structural root (no class, x-data exact literal with escaping regression for malformed UTF-8 active, x-modelable="active"); tablist role + x-bind + fixed-before-bag precedence regression; per-tab contract (type/role/data-value/x-bind, active tab modifier+aria-selected+tabindex vs inactive, unknown active → first tab active); icon before label; count span iff non-null (0 renders, null doesn't); panels (role/tabindex/data-value/x-bind, hidden only on inactive, content Htmlable vs escaped, empty when panel absent); NO server ids anywhere; Livewire test (wire:model="active" lands on the ROOT opening tag, server property seeds the active tab).
4. Regenerate `resources/boost/guidelines/lyra-blade.md` via `php bin/generate-boost-guidelines`.

## Acceptance criteria

- `vendor/bin/pest` fully green; `vendor/bin/pint --test` passes; work committed on the branch.

## Boundaries / Scope (closed list — nothing outside it; stop and report if needed)

- resources/views/components/tabs.blade.php (new)
- tests/Fixtures/class-emission/tabs.json (new)
- tests/Feature/TabsTest.php (new; inline test-only Livewire component allowed)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Files touched; pest and pint output; uncertainties declared as such.

## Stop conditions

The code's shape contradicts this brief; the same command fails twice; a fix needs edits beyond Scope.
