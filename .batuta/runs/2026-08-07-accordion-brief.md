# Brief — Accordion component (React → Blade + Alpine bindings) — fase 2, onda core, item 5/7

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/accordion (a git worktree of lyra-ds/blade; branch batuta/accordion). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create `<x-lyra::accordion>`: the uncontrolled disclosure list, served markup matching the React first paint plus `@lyra-ds/alpine` `lyraAccordion` bindings. No JS in this package.

## Context

Interactive siblings for the pattern (all lessons apply): resources/views/components/tabs.blade.php + tests/Feature/TabsTest.php (most recent: items normalization via array_values, x-data escaping, model split, fixed-attrs-before-bag), dialog.blade.php (bag on the same element as x-data → x-data/x-modelable FIRST).

Contract verified against the sources (do NOT deviate):
- React packages/react/src/accordion/accordion.tsx: props `items` (AccordionItem[]: `id` string, `title` ReactNode, `content` ReactNode), `defaultOpen?` (string — id of the item initially open), `multiple?` (bool, false), rest spread on the ROOT div. Markup:
  root `div class="lyra-accordion[ user]"`; per item `div class="lyra-acc__item[ lyra-acc__item--open]"` > `button type="button" class="lyra-acc__trigger" aria-expanded={open}` containing {title} + `<span class="lyra-acc__chevron" aria-hidden="true"></span>`, then `div class="lyra-acc__panel-wrap" inert={!open}` > `div class="lyra-acc__panel-clip"` > `div class="lyra-acc__panel"` containing {content}. (React also renders generated ids/aria-controls/aria-labelledby — we serve NO ids, closed decision; the plugin assigns them.)
- Alpine packages/alpine/src/accordion.ts: `Alpine.data('lyraAccordion', ...)` accepts `{ defaultOpen?: string, multiple?: bool }`; state `openItems: string[]`. Bindings: `item` (:class --open), `trigger` (static type=button; dynamic :id/:aria-expanded/:aria-controls/@click), `panelWrap` (:inert), `panel` (:id/:aria-labelledby). The plugin finds items via `.lyra-acc__item` and reads each item's identity from its `data-value` attribute; triggers/panels are located via their literal `x-bind` attribute values inside the item.

### Blade API (closed decisions)

- `items` (array, required; normalize with array_values): per item `['id' => string (required), 'title' => string|Htmlable (required), 'content' => string|Htmlable (required)]`.
- `defaultOpen` (string|null, null), `multiple` (bool, false).
- Passthrough on the ROOT div (React parity); x-data + x-modelable=\"openItems\" render BEFORE the bag; `wire:model*`/`x-model*` need no split here (they belong on the root anyway — do NOT split, just let them flow with the bag) — BUT the bag renders after x-data, so a consumer wire:model lands on the root correctly; assert that in the Livewire test.
- x-data literal: `lyraAccordion({ multiple: true|false[, defaultOpen: '...'] })` — defaultOpen key only when the prop is non-null (escaped exactly like Tabs' active: JS-escape + htmlspecialchars ENT_COMPAT|ENT_SUBSTITUTE).
- Served state: the item whose id === defaultOpen renders `lyra-acc__item--open`, `aria-expanded="true"` on its trigger, and NO inert on its panel-wrap; all others `aria-expanded="false"` + bare `inert` attribute on the wrap. defaultOpen not matching any item = all closed (no fallback — React behaves the same: Set contains an id no item has).
- Per-item markup adds `data-value="{id}"` on the item div plus the four x-bind attributes: item div `x-bind="item"`, trigger `x-bind="trigger"`, panel-wrap `x-bind="panelWrap"`, panel `x-bind="panel"`. Chevron span exactly as React (aria-hidden, empty).
- No server ids / aria-controls / aria-labelledby.

Rendered markup (exact; [] = conditional):
```html
<div x-data="lyraAccordion({ multiple: false[, defaultOpen: '...'] })" x-modelable="openItems" {passthrough incl. class merge 'lyra-accordion'}>
  <!-- per item -->
  <div class="lyra-acc__item[ lyra-acc__item--open]" data-value="{id}" x-bind="item">
    <button type="button" class="lyra-acc__trigger" aria-expanded="true|false" x-bind="trigger">{title}<span class="lyra-acc__chevron" aria-hidden="true"></span></button>
    <div class="lyra-acc__panel-wrap"[ inert] x-bind="panelWrap">
      <div class="lyra-acc__panel-clip">
        <div class="lyra-acc__panel" x-bind="panel">{content}</div>
      </div>
    </div>
  </div>
</div>
```

## Conventions

Same block as the Tabs brief (PHP >= 8.3, Pest+Pint, TDD failing-first, test laws, containment assertions, multi-line directives never butted, no drive-bys, no new deps, ignore cy-* skills; superpowers `test-driven-development` if available, else test-first). COMMIT WHEN GREEN — a finished turn without a commit fails the cycle.

## Task

1. `tests/Fixtures/class-emission/accordion.json` — root cases: base (`lyra-accordion`), base + user class.
2. `resources/views/components/accordion.blade.php` per the contract.
3. `tests/Feature/AccordionTest.php` — fixture-driven root class + markup: x-data exact literal (defaults; multiple=true + defaultOpen variant; no defaultOpen key when null; malformed-UTF-8 defaultOpen regression), x-modelable before bag (ordering regression with a consumer x-data attribute, Dropdown pattern), passthrough on root; per-item contract (data-value, x-bind attrs, open item: --open modifier + aria-expanded + no inert; closed items: inert present; defaultOpen unknown → all closed); chevron span; title/content Htmlable vs escaped; keyed/sparse items regression (array_values); NO server ids; Livewire test (wire:model="openItems" visible on the root opening tag + server-seeded defaultOpen renders the open item).
4. Regenerate `resources/boost/guidelines/lyra-blade.md` via `php bin/generate-boost-guidelines`.

## Acceptance criteria

- `vendor/bin/pest` fully green; `vendor/bin/pint --test` passes; work committed on the branch.

## Boundaries / Scope (closed list — nothing outside it; stop and report if needed)

- resources/views/components/accordion.blade.php (new)
- tests/Fixtures/class-emission/accordion.json (new)
- tests/Feature/AccordionTest.php (new; inline test-only Livewire component allowed)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Files touched; pest and pint output; uncertainties declared as such.

## Stop conditions

The code's shape contradicts this brief; the same command fails twice; a fix needs edits beyond Scope.
