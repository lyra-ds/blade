# Brief — PersonCell component (React → Blade) — fase 2, onda A, item 4/13

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/person-cell (a git worktree of lyra-ds/blade; branch batuta/person-cell). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create the `<x-lyra::person-cell>` Blade component mirroring the React PersonCell, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 28 components built to this exact pattern — study and mirror:
- resources/views/components/avatar.blade.php (reused inside this component; its API: src, name, size, shape, status)
- resources/views/components/footer.blade.php (fresh sibling that composes another component)
- tests/Feature/AvatarTest.php, tests/Fixtures/class-emission/badge.json (fixture pattern)

React contract (verified against packages/react/src/person-cell/person-cell.tsx in the main repo — do NOT deviate; never invent API):

Props:
- `name` (required) — primary person name. In React it is a ReactNode; in Blade it is a string prop.
- `detail` (optional) — secondary line; its span renders ONLY when provided.
- `src` (string, optional) — avatar image URL, forwarded to the avatar component.

Rendered markup (exact):
```html
<div class="lyra-personcell[ user classes last]" {...passthrough attrs}>
  <!-- reuse the existing avatar component: <x-lyra::avatar :src="$src" :name="$name" /> -->
  <span class="lyra-avatar lyra-avatar--md" ...>…</span>
  <span class="lyra-personcell__text">
    <span class="lyra-personcell__name">{name}</span>
    [<span class="lyra-personcell__detail">{detail}</span>]
  </span>
</div>
```
- The avatar is rendered with ONLY `src` and `name` forwarded (default size/shape) — mirroring React's `<Avatar src={src} name={avatarName} />`. It MUST reuse `<x-lyra::avatar>`, never duplicate its markup.
- Root class computation: `'lyra-personcell'` + user classes ALWAYS LAST. No modifier classes.
- All other HTML attributes pass through to the root div.
- No default slot.

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest for tests, Pint for style (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.
- Test assertions: per-element containment (toContain) / strpos ordering, never exact whole-output equality — Blade emits whitespace.
- View style: multi-line directives per sibling views; NEVER butt two Blade directives together (`@endif@if` does not compile).
- Ignore any Compozy skills (cy-*) — implement directly in the worktree.

## Task

1. `tests/Fixtures/class-emission/person-cell.json` — cases: base, user class appended.
2. `resources/views/components/person-cell.blade.php` — anonymous component per the contract.
3. `tests/Feature/PersonCellTest.php` — fixture-driven exact class assertions + markup: avatar present (initials from name when no src, `<img>` when src given — behavior already proven in AvatarTest, assert only the reuse surface), name span always, detail span only when given, attribute passthrough, user class last.
4. Regenerate `resources/boost/guidelines/lyra-blade.md` with `php bin/generate-boost-guidelines` (never hand-edit it).

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green, including the guidelines freshness test).
- `<x-lyra::person-cell name="Ada Lovelace" />` → root class exactly `lyra-personcell`, contains `lyra-avatar` with initials `AL`, `<span class="lyra-personcell__name">Ada Lovelace</span>`, NO `lyra-personcell__detail`.
- `<x-lyra::person-cell name="Ada" detail="ada@ex.io" src="/a.png" class="x" data-id="1" />` → root class exactly `lyra-personcell x`, avatar renders `<img` with `/a.png`, detail span with `ada@ex.io`, `data-id="1"` present.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies. Do not modify avatar.blade.php.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/person-cell.blade.php (new)
- tests/Fixtures/class-emission/person-cell.json (new)
- tests/Feature/PersonCellTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
