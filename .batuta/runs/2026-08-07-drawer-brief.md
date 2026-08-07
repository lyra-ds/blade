# Brief — Drawer component (React → Blade + Alpine bindings) — fase 2, onda core, item 3/7

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/drawer (a git worktree of lyra-ds/blade; branch batuta/drawer). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create `<x-lyra::drawer>`: the modal slide-over panel, mirroring the React Drawer's served markup exactly plus the `@lyra-ds/alpine` `lyraDrawer` bindings. Behavior lives in the npm plugin — no JS here.

## Context

This is the SIBLING of the Dialog component just shipped — mirror it structurally, applying the deltas below:
- resources/views/components/dialog.blade.php — copy this pattern: x-data literal building, wire:model/x-model split onto the overlay root, fixed panel attributes BEFORE the passthrough bag, labelId escaping (ENT_COMPAT | ENT_SUBSTITUTE), x-cloak when closed, closable/closeLabel close button with the same SVG.
- tests/Feature/DialogTest.php — mirror the test structure 1:1 (including the role-precedence and malformed-UTF-8 regressions and the Livewire split test).
- tests/Fixtures/class-emission/dialog.json — same single-case root fixture convention.

Contract verified against the sources (do NOT deviate; never invent API):
- React packages/react/src/drawer/drawer.tsx: props `open`, `onClose` (close button only when provided), `closeLabel` ('Close'), `title` (required), `footer` (optional), `container`, rest spread onto the PANEL. Markup identical in shape to Dialog with drawer class names: `lyra-drawer-overlay` (+`--closing`), `lyra-drawer` (+`--closing`, + user classes), `lyra-drawer__header`, `lyra-drawer__title` (h2), `lyra-drawer__close` (same 14x14 SVG, path `M18 6 6 18M6 6l12 12`), `lyra-drawer__body`, `lyra-drawer__footer` (conditional). Panel: role="dialog", aria-modal="true", tabindex="-1", aria-labelledby={titleId}.
- Alpine packages/alpine/src/drawer.ts: `Alpine.data('lyraDrawer', ...)` accepts ONLY `{ defaultOpen?: bool, labelId?: string }` — there are NO closeOnEsc/closeOnOverlayClick options (Escape and backdrop-identity click always close). Bindings exposed: `overlay`, `panel`, `title`, `close` — same roles as Dialog's; the plugin finds the panel via `.lyra-drawer` inside the root.

### Blade API (closed decisions — Dialog minus the two close-behavior props)

- `title` (required, string|Htmlable), default slot = body, named slot `footer` (optional).
- `closable` (bool, default true), `closeLabel` (string, 'Close').
- `defaultOpen` (bool, false), `labelId` (string|null, null) — same semantics and escaping as Dialog.
- NO closeOnEsc / closeOnOverlayClick props — do not invent them.
- x-data literal: `lyraDrawer({ defaultOpen: true|false[, labelId: '...'] })` (exact format asserted).
- Root (overlay): fixed class `lyra-drawer-overlay`, x-data, `x-modelable="open"`, `x-bind="overlay"`, x-cloak iff !defaultOpen, receives ONLY the `wire:model*`/`x-model*` split. Panel: fixed attrs (role/aria-modal/tabindex/conditional aria-labelledby/x-bind="panel") BEFORE `$panelAttributes->class('lyra-drawer')`.

## Conventions

Same as the Dialog brief (PHP >= 8.3, Pest+Pint, TDD failing-first, test laws, per-element containment assertions, multi-line directives never butted, no drive-bys, no new deps, ignore cy-* skills). Method: if superpowers skills are available in your environment, use `test-driven-development`; otherwise work test-first.

## Task

1. `tests/Fixtures/class-emission/drawer.json` — single root case (`lyra-drawer-overlay`).
2. `resources/views/components/drawer.blade.php` per the contract.
3. `tests/Feature/DrawerTest.php` — mirror DialogTest coverage: overlay contract (class/x-data exact for defaults and defaultOpen=true/x-modelable/x-bind/x-cloak conditional/no passthrough leak), panel contract (fixed attrs, precedence regression, user class last, passthrough lands there), labelId (absent → no server ids anywhere; present → panel aria-labelledby + h2 id + x-data option; malformed UTF-8 regression), header/title Htmlable vs escaped, close button default/closeLabel/closable=false, body/footer conditional, Livewire test with wire:model split (wire:model on overlay opening tag) + server-seeded defaultOpen.
4. Regenerate `resources/boost/guidelines/lyra-blade.md` via `php bin/generate-boost-guidelines`.

## Acceptance criteria

- `vendor/bin/pest` fully green (existing suite + new tests; guidelines freshness included).
- `vendor/bin/pint --test` passes.

## Boundaries / Scope (closed list — nothing outside it; stop and report if needed)

- resources/views/components/drawer.blade.php (new)
- tests/Fixtures/class-emission/drawer.json (new)
- tests/Feature/DrawerTest.php (new; inline test-only Livewire component allowed)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Files touched; pest and pint output; uncertainties declared as such.

## Stop conditions

The code's shape contradicts this brief; the same command fails twice; a fix needs edits beyond Scope.
