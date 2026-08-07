# Brief — Dialog component (React → Blade + Alpine bindings) — fase 2, onda core, item 2/7

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/dialog (a git worktree of lyra-ds/blade; branch batuta/dialog). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create `<x-lyra::dialog>`: server-rendered initial markup with the exact classes/ARIA of the React Dialog panel, plus the `@lyra-ds/alpine` bindings (`x-data="lyraDialog(...)"`, `x-bind` overlay/panel/title/close). Behavior (focus trap, scroll-lock, Esc, overlay dismiss, presence animation) lives in the npm plugin — this package ships NO JS.

## Context

The package's FIRST interactive, Dropdown, established the pattern — study and mirror it closely:
- resources/views/components/dropdown.blade.php (x-data emitted BEFORE the attributes bag; x-modelable; x-cloak conditional; docblock decisions)
- tests/Feature/DropdownTest.php (opening-tag helpers, exact x-data literal assertions, Livewire integration test)
- tests/Fixtures/class-emission/dropdown.json (fixtures = ROOT class emissions only; nested elements are asserted inline in the test)

Contract verified against the sources of truth (do NOT deviate; never invent API):
- React: packages/react/src/dialog/dialog.tsx — props `open` (controlled), `onClose` (close button renders only when provided), `closeLabel` ('Close'), `title` (required, aria-labelledby source), `footer` (optional), `closeOnEsc` (true), `closeOnOverlayClick` (true), `container` (portal host), rest spread onto the PANEL div (user className joins `lyra-dialog` there).
- Alpine plugin: `Alpine.data('lyraDialog', ...)` accepts `{ defaultOpen?: bool, closeOnEsc?: bool, closeOnOverlayClick?: bool, labelId?: string }`; exposes x-bind objects `overlay` (on the root/backdrop: x-show mounted, :class closing, backdrop dismiss), `panel` (static role="dialog", aria-modal="true", tabindex="-1"; dynamic :aria-labelledby, :class closing, Esc, animationend), `title` (:id), `close` (static type="button", aria-label="Close"; @click closes). The plugin finds the panel via `.lyra-dialog` inside the root; the root element itself is the overlay.
- React SSR renders NOTHING (portal) — irrelevant here: per docs/prd-fase-2-alpine.md the Blade version SERVES the full markup and x-show/x-cloak control visibility.

### Blade API (closed decisions — implement exactly)

Props/slots:
- `title` (required, string|Htmlable) — heading content.
- Default slot — the body content.
- Named slot `footer` (optional) — footer div renders only when the slot is present.
- `closable` (bool, default true) — renders the close × button. Maps React's "onClose provided" condition (there is no callback to port; the plugin's close binding owns the click). One docblock line stating the mapping.
- `closeLabel` (string, default 'Close') — aria-label of the close button (server-side; the plugin's static aria-label is overridden by the server-rendered one only in the initial paint — emit ours explicitly).
- `defaultOpen` (bool, default false), `closeOnEsc` (bool, default true), `closeOnOverlayClick` (bool, default true).
- `labelId` (string, optional): when given, server-render `id="{labelId}"` on the title h2 AND `aria-labelledby="{labelId}"` on the panel, and include `labelId: '...'` in the x-data options. When absent, render NO server ids (the plugin assigns them at init) — same closed decision as Dropdown.
- Attribute passthrough goes on the PANEL div (mirror React's rest spread); user classes always LAST after `lyra-dialog`. The overlay root takes NO passthrough.

Rendered markup (exact; [] = conditional):
```html
<div class="lyra-dialog-overlay"
     x-data="lyraDialog({ defaultOpen: true|false, closeOnEsc: true|false, closeOnOverlayClick: true|false[, labelId: '...'] })"
     x-modelable="open"
     x-bind="overlay"[ x-cloak]>
  <div {passthrough} class="lyra-dialog[ user classes]" role="dialog" aria-modal="true" tabindex="-1"[ aria-labelledby="{labelId}"] x-bind="panel">
    <div class="lyra-dialog__header">
      <h2 class="lyra-dialog__title"[ id="{labelId}"] x-bind="title">{title}</h2>
      [<button type="button" class="lyra-dialog__close" aria-label="{closeLabel}" x-bind="close">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <path d="M18 6 6 18M6 6l12 12" />
        </svg>
      </button>]
    </div>
    <div class="lyra-dialog__body">{default slot}</div>
    [<div class="lyra-dialog__footer">{footer slot}</div>]
  </div>
</div>
```

Details that matter:
- x-data options: always emit the three boolean keys (JS literals, unquoted); `labelId` key only when the prop is given (single-quoted). Exact literal format asserted in tests, like Dropdown.
- x-data/x-modelable/x-bind are emitted before any other attribute logic on the root; the root has no attributes bag at all (fixed attrs only), so the Dropdown ordering concern applies only if you add one — don't.
- `x-cloak` on the ROOT (overlay) only when `defaultOpen` is false. No `--closing` classes are ever server-rendered.
- No spread/`{{ $attributes }}` on the overlay; panel gets `$attributes->class('lyra-dialog')`.
- SVG exactly as the React close icon (attributes above, kebab-case in HTML).
- `title` and slots: Htmlable unescaped, strings escaped — same helper conventions as siblings.

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest for tests, Pint for style (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.
- Test assertions: per-element containment (toContain) / strpos ordering, never exact whole-output equality — Blade emits whitespace.
- View style: multi-line directives; NEVER butt two Blade directives together.
- Follow the existing code style; change only what the brief asks; no drive-by refactors; no new dependencies.
- Ignore any Compozy skills (cy-*) — implement directly in the worktree.
- Method: if superpowers skills are available in your environment, conduct the work with them — `test-driven-development` for implementation, `systematic-debugging` for bug investigation. Otherwise, work test-first.

## Task

1. `tests/Fixtures/class-emission/dialog.json` — ROOT-only cases for the PANEL class emission? No: fixtures target the component's root element by convention. Here the root is the overlay with fixed class `lyra-dialog-overlay` (no user classes), so fixtures carry exactly one case: base overlay. The panel's `lyra-dialog[ user]` emission is asserted inline in the test (user class last).
2. `resources/views/components/dialog.blade.php` per the contract above.
3. `tests/Feature/DialogTest.php` — assertions:
   - overlay root: `lyra-dialog-overlay` exact, x-data exact literal (defaults; and a variant with defaultOpen=true + closeOnEsc=false + closeOnOverlayClick=false), `x-modelable="open"`, `x-bind="overlay"`, x-cloak iff defaultOpen false, NO passthrough leakage to the root (a user class must NOT appear on the overlay).
   - panel: `lyra-dialog` + user class last, passthrough attrs land on panel, `role="dialog"`, `aria-modal="true"`, `tabindex="-1"`, `x-bind="panel"`; no aria-labelledby/id anywhere when labelId absent; with `labelId="my-title"` → panel aria-labelledby AND h2 id AND `labelId: 'my-title'` inside x-data.
   - header/title: header div class; h2 with `lyra-dialog__title`, `x-bind="title"`, title content (Htmlable unescaped vs string escaped).
   - close button: present by default with type/class/aria-label ('Close' default, override via closeLabel), `x-bind="close"`, the exact svg path `M18 6 6 18M6 6l12 12`; absent entirely when `closable=false` (no `lyra-dialog__close` in output).
   - body wraps default slot; footer div only when footer slot present (no `lyra-dialog__footer` otherwise).
   - Livewire integration (pattern of DropdownTest): component seeding `defaultOpen` from a server property with `wire:model="open"` — but NOTE: passthrough lands on the panel, so `wire:model` must be asserted where it renders (the panel opening tag). Think about whether wire:model on the panel still entangles the root's x-modelable state — it does NOT (x-modelable lives on the root). Therefore the Livewire test asserts server seeding only (aria-expanded equivalent here: x-data contains defaultOpen: true) and documents that wire:model/x-model must be placed by the consumer on the component tag → which lands on the panel → HOLD: stop condition. See Stop conditions.
4. Regenerate `resources/boost/guidelines/lyra-blade.md` with `php bin/generate-boost-guidelines` (never hand-edit).

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green, including guidelines freshness).
- The exact-markup contract holds, verified by item 3's assertions.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS files, no new dependencies, no changes to other components, no CI changes.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/dialog.blade.php (new)
- tests/Fixtures/class-emission/dialog.json (new)
- tests/Feature/DialogTest.php (new; a test-only Livewire component class inside the file is allowed)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such — including your resolution of the wire:model placement question below.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.

SPECIFIC design checkpoint (stop and report BEFORE implementing the Livewire test if your analysis differs): with passthrough on the panel, `wire:model="open"` written on `<x-lyra::dialog>` renders on the panel div, but `x-modelable="open"` lives on the overlay root — Alpine's x-modelable only pairs with an x-model/wire:model ON THE SAME element. Proposed resolution (implement unless contradicted): ALSO forward `wire:model`/`x-model` attributes specifically from the bag to the ROOT (overlay) instead of the panel — i.e. pull `wire:model*` and `x-model*` attributes out of `$attributes` and render them on the overlay, everything else on the panel; assert exactly that split in the tests (wire:model on overlay opening tag, other passthrough on panel). If the bag manipulation cannot express this cleanly, stop and report.
