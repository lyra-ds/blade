# Brief — Tooltip component (React → Blade + Alpine bindings) — fase 2, onda core, item 6/7

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/tooltip (a git worktree of lyra-ds/blade; branch batuta/tooltip). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create `<x-lyra::tooltip>`: hover/focus tooltip, served markup matching the React first paint plus the `@lyra-ds/alpine` `lyraTooltip` bindings. No JS here.

## Context

Interactive siblings for the pattern: resources/views/components/accordion.blade.php + tests/Feature/AccordionTest.php (latest lessons), dropdown.blade.php (trigger-wrapping precedent + x-data-before-bag regression).

Contract verified against the sources (do NOT deviate):
- React packages/react/src/tooltip/tooltip.tsx: props `tip` (string, required), `placement` ('top' default; 'top'|'bottom'|'left'|'right'), children = the target (React clones the child to attach aria-describedby/handlers), rest spread on the ROOT span. Root: `span class="lyra-tooltip[ lyra-tooltip--{placement}][ user]" data-tip="{tip}" data-state="closed|open"` (placement modifier only when != top; state starts closed — there is NO open/defaultOpen surface). Inside: the target, then `<span role="tooltip" hidden>{tip}</span>` (the AT bubble; the stylesheet draws the visible tip from data-tip).
- Alpine packages/alpine/src/tooltip.ts + tooltip.browser.test.ts (canonical markup): `Alpine.data('lyraTooltip', ...)` REQUIRES `{ tip: string, placement?: }`. Bindings: `root` (dynamic :data-state/:data-tip/:class placement modifier + mouseenter/mouseleave/focusin/focusout/keydown), `target` (:aria-describedby), `bubble` (:id). The plugin finds the target via `[x-bind="target"]` inside the root. Canonical markup: root element with x-data + class lyra-tooltip + x-bind="root"; the consumer's element carrying x-bind="target"; the bubble span `role="tooltip" hidden x-bind="bubble"` containing the tip text.

### Blade API (closed decisions)

- `tip` (string, required), `placement` ('top'|'bottom'|'left'|'right', default 'top'; unknown value coerces to 'top' — safe-coercion precedent).
- Default slot = the target content, ALWAYS wrapped in `<span x-bind="target">{slot}</span>` (Dropdown-wrapping precedent). Docblock line: for full keyboard/screen-reader support the slot content should be focusable; the wrapper carries the plugin's target binding — aria-describedby lands on the wrapper, a documented Blade limitation vs React's child cloning.
- NO x-modelable, NO x-cloak, NO model split (there is no controllable state; the bubble is hidden via the `hidden` attribute).
- Passthrough on the ROOT span; x-data + x-bind="root" render BEFORE the bag (ordering lesson). User classes last after the computed class string.
- x-data literal: `lyraTooltip({ tip: '...', placement: '...' })` — both keys always, tip escaped exactly like the sibling pattern (JS-escape + htmlspecialchars ENT_COMPAT|ENT_SUBSTITUTE).
- Served attributes on root: `data-tip="{tip}"` (normal Blade escaping) and `data-state="closed"` always.
- No server ids.

Rendered markup (exact; [] = conditional):
```html
<span x-data="lyraTooltip({ tip: '...', placement: '...' })" x-bind="root"
      {passthrough} class="lyra-tooltip[ lyra-tooltip--{placement}][ user]"
      data-tip="{tip}" data-state="closed">
  <span x-bind="target">{slot}</span>
  <span role="tooltip" hidden x-bind="bubble">{tip}</span>
</span>
```
(The class attribute comes from the bag merge; fixed attrs data-tip/data-state may render after the bag ONLY if no collision is possible — they are component-owned names, so render them adjacent to x-data BEFORE the bag, consistent with the precedence lesson.)

## Conventions

Same block as the Accordion brief (PHP >= 8.3, Pest+Pint, TDD failing-first, test laws, containment assertions, multi-line directives never butted, no drive-bys, no new deps, ignore cy-* skills; superpowers `test-driven-development` if available, else test-first). COMMIT WHEN GREEN.

## Task

1. `tests/Fixtures/class-emission/tooltip.json` — root cases: base (`lyra-tooltip`), placement bottom (`lyra-tooltip lyra-tooltip--bottom`), base + user class.
2. `resources/views/components/tooltip.blade.php` per the contract.
3. `tests/Feature/TooltipTest.php` — fixture-driven root class + markup: x-data exact literal (top default; bottom variant; malformed-UTF-8 tip regression), x-bind="root", data-tip escaped, data-state="closed", precedence regression (component x-data ahead of a consumer x-data), placement coercion (unknown → top, no modifier), passthrough on root with user class last; target wrapper with x-bind="target" containing slot (Htmlable/plain both fine — slot passthrough); bubble span role/hidden/x-bind with the ESCAPED tip text; no server ids. NO Livewire test (no modelable state) — instead one plain Blade render inside the existing Livewire smoke pattern is NOT needed; skip Livewire entirely for this component and note it in the test file docblock (no controllable state).
4. Regenerate `resources/boost/guidelines/lyra-blade.md` via `php bin/generate-boost-guidelines`.

## Acceptance criteria

- `vendor/bin/pest` fully green; `vendor/bin/pint --test` passes; work committed on the branch.

## Boundaries / Scope (closed list — nothing outside it; stop and report if needed)

- resources/views/components/tooltip.blade.php (new)
- tests/Fixtures/class-emission/tooltip.json (new)
- tests/Feature/TooltipTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Files touched; pest and pint output; uncertainties declared as such.

## Stop conditions

The code's shape contradicts this brief; the same command fails twice; a fix needs edits beyond Scope.
