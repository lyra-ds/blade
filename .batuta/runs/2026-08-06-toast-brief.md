# Brief — Toast + ToastStack components (React → Blade) — fase 2, onda A, item 7/13

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/toast (a git worktree of lyra-ds/blade; branch batuta/toast). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create `<x-lyra::toast>` and `<x-lyra::toast-stack>` Blade components mirroring the React Toast and ToastStack (both live in the same React module — one coupled deliverable), with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 31 components built to this exact pattern — study and mirror:
- resources/views/components/alert.blade.php (closest sibling: tone + icon slot + role)
- tests/Feature/AlertTest.php, tests/Fixtures/class-emission/badge.json (fixture pattern)

React contract (verified against packages/react/src/toast/toast.tsx in the main repo — do NOT deviate; never invent API):

### Toast

Props:
- `tone` (`info`|`success`|`danger`, default `info`) — modifier on the ICON span only (the root has no tone modifier).
- Named slot `icon` (optional) — when present renders `<span class="lyra-toast__icon lyra-toast__icon--{tone}">{icon}</span>` before the message. When absent, no icon span at all (tone then has no visible effect).
- Default slot — the message content, ALWAYS wrapped in a plain `<span>`.
- React also has `onClose` (JS callback) + `closeLabel`, which render a close `<button class="lyra-toast__close">`. CLOSED DECISION for the static port (same as Tag's remove button): the close button is NOT implemented. One-line docblock comment in the view ("onClose/closeLabel: interactive close button, phase 2"). Do not invent any replacement prop. The toast-provider machinery does not exist in this module and is out of scope entirely.

Rendered markup (exact):
```html
<div class="lyra-toast[ user classes last]" role="status" {...passthrough attrs}>
  [<span class="lyra-toast__icon lyra-toast__icon--{tone}">{icon slot}</span>]
  <span>{default slot}</span>
</div>
```

### ToastStack

No props. Default slot only.
```html
<div class="lyra-toast-stack[ user classes last]" {...passthrough attrs}>{slot}</div>
```

- Class computation both: base class + user classes ALWAYS LAST. Root `role="status"` is Toast-only and always present (a user-supplied role attribute must still override it — check how alert.blade.php handles its role merge and mirror).

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest for tests, Pint for style (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.
- Test assertions: per-element containment (toContain) / strpos ordering, never exact whole-output equality — Blade emits whitespace.
- View style: multi-line directives; NEVER butt two Blade directives together.
- Ignore any Compozy skills (cy-*) — implement directly in the worktree.

## Task

1. `tests/Fixtures/class-emission/toast.json` (cases: base, user class) and `tests/Fixtures/class-emission/toast-stack.json` (cases: base, user class).
2. `resources/views/components/toast.blade.php` and `resources/views/components/toast-stack.blade.php` per the contracts.
3. `tests/Feature/ToastTest.php` — fixture-driven exact class assertions for both components + markup: role=status always (and user role override), icon span only when icon slot given, icon tone modifier for the three tones (default info), message span wraps the default slot, NO `lyra-toast__close` anywhere, attribute passthrough, user class last; toast-stack renders slot content (e.g. a toast inside).
4. Regenerate `resources/boost/guidelines/lyra-blade.md` with `php bin/generate-boost-guidelines` (never hand-edit it).

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green, including the guidelines freshness test).
- `<x-lyra::toast>Saved</x-lyra::toast>` → `<div class="lyra-toast" role="status"><span>Saved</span></div>` semantics, no icon span, no close button.
- Toast with icon slot and `tone="danger"` → `<span class="lyra-toast__icon lyra-toast__icon--danger">` before the message span.
- `<x-lyra::toast-stack class="x"><x-lyra::toast>Hi</x-lyra::toast></x-lyra::toast-stack>` → stack class exactly `lyra-toast-stack x` containing the rendered toast.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/toast.blade.php (new)
- resources/views/components/toast-stack.blade.php (new)
- tests/Fixtures/class-emission/toast.json (new)
- tests/Fixtures/class-emission/toast-stack.json (new)
- tests/Feature/ToastTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
