# Brief — Popover component (React → Blade + Alpine bindings) — fase 2, onda core, item 7/7 (último)

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/popover (a git worktree of lyra-ds/blade; branch batuta/popover). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create `<x-lyra::popover>`: trigger-anchored non-modal dialog, served markup matching the React first paint plus `@lyra-ds/alpine` `lyraPopover` bindings. No JS here. This closes the core interactive wave.

## Context

Interactive siblings for the pattern: dropdown.blade.php (trigger slot wrapped in span, x-data-before-bag), dialog.blade.php (model split, escaping), tooltip.blade.php + TooltipTest.php (latest).

Contract verified against the sources (do NOT deviate):
- React packages/react/src/popover/popover.tsx: props `trigger` (ReactNode), `open`/`defaultOpen` (false)/`onOpenChange` (controlled surface — JS side not ported; x-model/wire:model covers it), `side` ('auto'|'bottom'|'top', default 'auto'), `align` ('start'|'end'|'center', default automatic = 'start' initially), `width` (number, px), `ariaLabel` ('Popover'), rest spread on the ROOT span. Root: `span class="lyra-popover-anchor[ user]"`. Trigger plain-content branch: `span role="button" tabindex="0" aria-haspopup="dialog" aria-expanded aria-controls`. Panel (only when open in React): `div class="lyra-popover lyra-popover--{down→bottom|up→top} lyra-popover--align-{align??'start'}" role="dialog" aria-label="{ariaLabel}"[ style="width: {n}px"]`. Initial placement resolution: side 'auto' → 'bottom'; 'bottom' → 'bottom'; 'top' → 'top'; align given ?? 'start'.
- Alpine packages/alpine/src/popover.ts: `Alpine.data('lyraPopover', ...)` accepts `{ defaultOpen?, side? ('auto' default), align?, width?, ariaLabel? ('Popover' default) }`. Bindings: `trigger` (static type=button + aria-haspopup="dialog"; dynamic :aria-expanded/:aria-controls/@click/@keydown Enter+Space) and `panel` (static role=dialog; dynamic :id/:aria-label/:style width/:class side+align/x-show open). The plugin finds them via `[x-bind="trigger"]` / `[x-bind="panel"]` inside the root; Escape/outside-click close; ids assigned at init.

### Blade API (closed decisions)

- Named slot `trigger` (required) — ALWAYS wrapped in `<span role="button" tabindex="0" aria-haspopup="dialog" aria-expanded="{defaultOpen}" x-bind="trigger">{trigger}</span>` (Dropdown precedent + React's plain-content branch; docblock: pass non-interactive content). No aria-controls served (no server ids).
- Default slot = panel content.
- `defaultOpen` (bool, false), `side` ('auto'|'bottom'|'top', unknown coerces to 'auto'), `align` (null|'start'|'end'|'center', unknown coerces to null), `width` (int|null, null), `ariaLabel` (string, 'Popover').
- Root span: x-data + `x-modelable="open"` + the `wire:model*`/`x-model*` split render BEFORE `$attributes->class('lyra-popover-anchor')` (rest of passthrough on the root via the bag).
- x-data literal, deterministic: `lyraPopover({ defaultOpen: true|false, side: '...'[, align: '...'][, width: N], ariaLabel: '...' })` — align/width keys only when non-null; width is an unquoted integer; ariaLabel escaped like tip in Tooltip (JS-escape + htmlspecialchars ENT_COMPAT|ENT_SUBSTITUTE).
- Panel ALWAYS served (Alpine toggles with x-show): `div class="lyra-popover lyra-popover--{top|bottom} lyra-popover--align-{align ?? 'start'}" role="dialog" aria-label="{ariaLabel}"[ style="width: {width}px"] x-bind="panel"[ x-cloak]` — side class: 'top' only when side='top', else 'bottom' (auto resolves down initially, React parity); x-cloak iff !defaultOpen. Fixed attrs before nothing (no bag on the panel — passthrough stays on the root).

Rendered markup (exact; [] = conditional):
```html
<span x-data="lyraPopover({ defaultOpen: false, side: 'auto', ariaLabel: 'Popover' })"
      x-modelable="open" {wire:model*/x-model* split} {passthrough} class="lyra-popover-anchor[ user]">
  <span role="button" tabindex="0" aria-haspopup="dialog" aria-expanded="false|true" x-bind="trigger">{trigger}</span>
  <div class="lyra-popover lyra-popover--bottom|top lyra-popover--align-start|end|center"
       role="dialog" aria-label="{ariaLabel}"[ style="width: {n}px"] x-bind="panel"[ x-cloak]>{slot}</div>
</span>
```

## Conventions

Same block as the Tooltip brief (PHP >= 8.3, Pest+Pint, TDD failing-first, test laws, containment assertions, multi-line directives never butted, no drive-bys, no new deps, ignore cy-* skills; superpowers `test-driven-development` if available, else test-first). COMMIT WHEN GREEN.

## Task

1. `tests/Fixtures/class-emission/popover.json` — root cases: base (`lyra-popover-anchor`), base + user class.
2. `resources/views/components/popover.blade.php` per the contract.
3. `tests/Feature/PopoverTest.php` — root class fixtures + markup: x-data exact literals (defaults; defaultOpen=true + side=top + align=end + width=320 + custom ariaLabel variant; no align/width keys when null; malformed-UTF-8 ariaLabel regression), x-modelable + model split (wire:model on root opening tag), x-data-before-consumer-x-data precedence regression, passthrough/user-class-last on root; trigger wrapper contract (role/tabindex/aria-haspopup/aria-expanded seeded/x-bind, slot inside); panel contract (class string for side/align combos incl. auto→bottom and align default start, role, aria-label escaped, width style iff given, x-cloak iff closed, x-bind, default slot content, Htmlable vs escaped); side/align coercion (unknown side → auto literal in x-data AND bottom class; unknown align → omitted key + align-start class); NO server ids; Livewire test (server-seeded defaultOpen + wire:model="open" on root).
4. Regenerate `resources/boost/guidelines/lyra-blade.md` via `php bin/generate-boost-guidelines`.

## Acceptance criteria

- `vendor/bin/pest` fully green; `vendor/bin/pint --test` passes; work committed on the branch.

## Boundaries / Scope (closed list — nothing outside it; stop and report if needed)

- resources/views/components/popover.blade.php (new)
- tests/Fixtures/class-emission/popover.json (new)
- tests/Feature/PopoverTest.php (new; inline test-only Livewire component allowed)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Files touched; pest and pint output; uncertainties declared as such.

## Stop conditions

The code's shape contradicts this brief; the same command fails twice; a fix needs edits beyond Scope.
