# Brief — Dropdown component (React → Blade + Alpine bindings) — fase 2, onda core, item 1/7

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/dropdown (a git worktree of lyra-ds/blade; branch batuta/dropdown). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create `<x-lyra::dropdown>`, the package's FIRST interactive component: it renders the complete initial (server-side) markup with the exact classes/ARIA the React Dropdown emits on first paint, plus the `@lyra-ds/alpine` plugin bindings (`x-data="lyraDropdown(...)"`, `x-bind` names). The behavior itself lives in the npm plugin — this package ships NO JS. This component sets the pattern for the remaining 6 core interactives.

## Context

The package ships 33 components built to a strict pattern — study and mirror:
- resources/views/components/breadcrumb.blade.php and tests/Feature/BreadcrumbTest.php (closest sibling: data-driven items array, per-item type branching)
- tests/Fixtures/class-emission/*.json (fixture pattern), tests/Feature/LivewireSmokeTest.php (Livewire test pattern, just added)
- docs/prd-fase-2-alpine.md (the architecture: markup+bindings here, behavior in @lyra-ds/alpine; without Alpine loaded the component stays inert in its served state — that is by design)

Contract verified against the sources of truth (do NOT deviate; never invent API):
- React: packages/react/src/dropdown/dropdown.tsx (main repo) — props `trigger`, `items`, `align` ('start'|'end', default 'start'), `defaultOpen` (default false), plus span passthrough attrs.
- Alpine plugin: @lyra-ds/alpine@0.1.0, `Alpine.data('lyraDropdown', ...)` accepting `{ defaultOpen?, align? }`, exposing x-bind object bindings named `trigger`, `menu`, `item`. The plugin finds the trigger via `.lyra-dropdown__trigger` and the menu via `[role="menu"]` inside the root, sets `menuId = "{root id}-menu"` (assigns a root id only when missing), and toggles the menu with x-show.

### Blade API (closed decisions — implement exactly)

Props/slots:
- Named slot `trigger` (required) — the visible trigger content. Unlike React (which merges semantics onto a passed element), the Blade version ALWAYS wraps the slot in the trigger `<span>` (mirror of React's plain-content branch). One-line docblock: pass non-interactive content (text, icon); do not pass a button/link.
- `items` (array prop, required) — mirrors React's DropdownItem union, array-shape per item:
  - command: `['label' => string|Htmlable, 'icon' => string|Htmlable (optional), 'danger' => bool (optional), 'id' => string (optional, ignored for rendering)]`
  - separator: `['type' => 'separator']`
  - label: `['type' => 'label', 'label' => string|Htmlable]`
  - React's `onSelect` is a JS callback — NOT ported (selection behavior belongs to the consumer's own Alpine/Livewire code; the plugin closes the menu on click). One-line docblock, no replacement prop.
- `align` (string, 'start'|'end', default 'start'), `defaultOpen` (bool, default false).
- Attribute passthrough on the root span; user classes always LAST after the base class.

Rendered markup (exact; [] = conditional):
```html
<span {passthrough attrs} class="lyra-dropdown[ user classes]"
      x-data="lyraDropdown({ defaultOpen: true|false, align: 'start'|'end' })"
      x-modelable="open">
  <span class="lyra-dropdown__trigger" role="button" tabindex="0"
        aria-haspopup="menu" aria-expanded="false|true" x-bind="trigger">{trigger slot}</span>
  <div class="lyra-menu lyra-menu--{align}" role="menu" x-bind="menu"[ x-cloak]>
    <!-- per item, in order: -->
    <hr class="lyra-menu__sep">                                  <!-- separator -->
    <span class="lyra-menu__label">{label}</span>                <!-- label -->
    <button type="button" role="menuitem"
            class="lyra-menu__item[ lyra-menu__item--danger]"
            x-bind="item">[{icon}]{label}</button>               <!-- command -->
  </div>
</span>
```

Details that matter:
- `x-data` options object is always emitted with both keys, JS-literal syntax (booleans unquoted, align single-quoted). `aria-expanded` seeds the defaultOpen state server-side. Escape correctness: x-data/x-bind/x-modelable are plain HTML attributes emitted by the view — make sure Blade does not double-encode the quotes (attributes bag or explicit echo, mirror whatever sibling components do for attribute output).
- `x-cloak` on the menu ONLY when `defaultOpen` is false (prevents flash-of-open-menu before Alpine boots; when defaultOpen is true the menu must be visible in the served state). Do not add inline styles.
- No server-side ids: the root gets an id only via user passthrough; `aria-controls` on the trigger and `id` on the menu are NOT server-rendered (the plugin assigns/derives them at init — React's useId has no Blade equivalent, and a hardcoded fallback would break on multiple instances). This is a closed decision.
- `x-modelable="open"` on the root — exposes the open state to `x-model`/`wire:model` (Livewire entangle) per docs/prd-fase-2-alpine.md §4.
- `danger` item modifier appends `lyra-menu__item--danger` after the base item class.
- label/icon accept Htmlable (render unescaped) and plain strings (escaped) — mirror the sibling components' handling (e.g. breadcrumb/bottom-nav pattern).

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest for tests, Pint for style (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.
- Test assertions: per-element containment (toContain) / strpos ordering, never exact whole-output equality — Blade emits whitespace.
- View style: multi-line directives; NEVER butt two Blade directives together (`@endisset@isset` does not compile).
- Follow the existing code style; change only what the brief asks; no drive-by refactors; no new dependencies.
- Ignore any Compozy skills (cy-*) — implement directly in the worktree.
- Method: if superpowers skills are available in your environment, conduct the work with them — `test-driven-development` for implementation, `systematic-debugging` for bug investigation. Otherwise, work test-first.

## Task

1. `tests/Fixtures/class-emission/dropdown.json` — cases: base root, root + user class, menu align start, menu align end, item base, item danger.
2. `resources/views/components/dropdown.blade.php` per the contract above.
3. `tests/Feature/DropdownTest.php` — fixture-driven class assertions plus markup assertions:
   - root: base class + user class last, passthrough attrs, `x-data="lyraDropdown({ defaultOpen: false, align: 'start' })"` exact for defaults and for `defaultOpen=true`/`align=end`, `x-modelable="open"` present.
   - trigger span: exact static ARIA set, `aria-expanded` follows defaultOpen, `x-bind="trigger"`, slot content inside.
   - menu: `lyra-menu lyra-menu--{align}` exact, `role="menu"`, `x-bind="menu"`, `x-cloak` present iff defaultOpen is false, NO id/aria-controls server-rendered.
   - items: ordering preserved; separator hr; label span; command button with type/role/x-bind; danger modifier; icon before label; Htmlable unescaped vs string escaped.
   - Livewire integration: a test-only Livewire component (pattern of LivewireSmokeTest) whose view renders `<x-lyra::dropdown :default-open="$open" wire:model="open" ...>`; assert it renders through `Livewire::test`, output contains `wire:model="open"` on the root and `aria-expanded="true"` when the server property seeds open=true.
4. Regenerate `resources/boost/guidelines/lyra-blade.md` with `php bin/generate-boost-guidelines` (never hand-edit it).

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green, including the guidelines freshness test).
- The exact-markup contract above holds, verified by the assertions in item 3.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS files, no new composer/npm dependencies, no changes to other components, no CI changes.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/dropdown.blade.php (new)
- tests/Fixtures/class-emission/dropdown.json (new)
- tests/Feature/DropdownTest.php (new; a small test-only Livewire component class inside this file or under tests/ is allowed — declare it)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief (e.g. attribute escaping cannot produce the exact x-data string), the same command fails twice, or the fix needs edits beyond Scope.
