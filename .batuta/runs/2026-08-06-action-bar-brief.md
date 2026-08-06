# Brief — ActionBar component (React → Blade) — fase 2, onda A, item 9/13

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/action-bar (a git worktree of lyra-ds/blade; branch batuta/action-bar). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create the `<x-lyra::action-bar>` Blade component mirroring the React ActionBar, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 33 components built to this exact pattern — study and mirror:
- resources/views/components/toast.blade.php (fresh sibling: role merge, omitted interactive button)
- tests/Feature/ToastTest.php, tests/Fixtures/class-emission/badge.json (fixture pattern)

React contract (verified against packages/react/src/action-bar/action-bar.tsx in the main repo — do NOT deviate; never invent API):

Props:
- `open` (bool, default `true`).
- `count` (int, optional — nullable, no default).
- `label` (string, default `"selected"`) — text after the count.
- Named slot `actions` (optional).
- Default slot — content between the count span and the actions span.
- React also has `onClear` (JS callback) + `clearLabel`, which render a clear `<button class="lyra-actionbar__clear">` with an inline X SVG. CLOSED DECISION for the static port (Tag/Toast precedent): NOT implemented. One-line docblock comment ("onClear/clearLabel: interactive clear button, phase 2"). Do not invent any replacement prop.

Visibility rule (exact): when `!open || count === 0` the component renders NOTHING at all (empty output). Note: `count === null` (absent) still renders the bar — only an explicit `0` hides it (strict comparison).

Rendered markup (exact, when visible):
```html
<div class="lyra-actionbar[ user classes last]" role="toolbar" {...passthrough attrs}>
  [<span class="lyra-actionbar__count" role="status" aria-live="polite"><strong>{count}</strong> {label}</span>]   <!-- only when count is not null -->
  [{default slot}]
  <span class="lyra-actionbar__actions">[{actions slot}]</span>   <!-- the span is ALWAYS rendered, even empty -->
</div>
```
- Count span content: `<strong>{count}</strong>` then a single space then the label.
- Root class computation: `'lyra-actionbar'` + user classes ALWAYS LAST. No modifiers.
- role="toolbar" always on the root (a user-supplied role must still override — mirror toast's merge).
- All other HTML attributes pass through to the root div.

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest for tests, Pint for style (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.
- Test assertions: per-element containment (toContain) / strpos ordering, never exact whole-output equality — Blade emits whitespace.
- View style: multi-line directives; NEVER butt two Blade directives together.
- Ignore any Compozy skills (cy-*) — implement directly in the worktree.

## Task

1. `tests/Fixtures/class-emission/action-bar.json` — cases: base, user class appended.
2. `resources/views/components/action-bar.blade.php` — anonymous component per the contract.
3. `tests/Feature/ActionBarTest.php` — fixture-driven exact class assertions + markup: hidden when `:open="false"`, hidden when `:count="0"`, visible with no count (no count span, actions span still present), count span exact structure (`<strong>3</strong> selected`, custom label), default slot between count and actions (strpos), NO `lyra-actionbar__clear` anywhere, role=toolbar + user role override, attribute passthrough, user class last.
4. Regenerate `resources/boost/guidelines/lyra-blade.md` with `php bin/generate-boost-guidelines` (never hand-edit it).

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green, including the guidelines freshness test).
- `<x-lyra::action-bar :count="0">x</x-lyra::action-bar>` and `<x-lyra::action-bar :open="false">x</x-lyra::action-bar>` → empty output (trimmed).
- `<x-lyra::action-bar :count="3" class="x">Bulk</x-lyra::action-bar>` → root `lyra-actionbar x` with role=toolbar, `<strong>3</strong> selected`, `Bulk` before the empty actions span.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/action-bar.blade.php (new)
- tests/Fixtures/class-emission/action-bar.json (new)
- tests/Feature/ActionBarTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
