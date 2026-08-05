# Brief — Avatar component (React → Blade) — onda 3, item 5/5

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/avatar (a git worktree of lyra-ds/blade; branch batuta/avatar).

## Goal

Create the `<x-lyra::avatar>` Blade component mirroring the React Avatar, with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 12 components built to one pattern — study and mirror:
- resources/views/components/skeleton.blade.php (attribute-forcing + @php block)
- resources/views/components/badge.blade.php (modifier classes)
- tests/Feature/SkeletonTest.php + tests/Fixtures/class-emission/skeleton.json

React contract (verified against packages/react/src/avatar/avatar.tsx — do NOT deviate; never invent API):

Props:
- `src`: string — optional image URL
- `name`: string — optional; used for initials, `alt` and native `title`
- `size`: 'sm' | 'md' | 'lg' | 'xl' — default 'md'
- `shape`: 'circle' | 'square' — default 'circle'; modifier class ONLY when 'square'
- `status`: 'online' | 'busy' | 'away' — optional
- `statusLabel`: string — optional; defaults to the `status` value

Class computation (root): `'lyra-avatar' + ' lyra-avatar--{size}'` + (`' lyra-avatar--square'` only when shape=square) + user classes ALWAYS LAST.

Rendered markup (exact):
```html
<span class="lyra-avatar lyra-avatar--{size}[ lyra-avatar--square][ user]" title="{name}" {...passthrough attrs}>
  {src present: <img src="{src}" alt="{name}">}
  {src absent: <span aria-hidden="true">{initials}</span>}
  {status: <span class="lyra-avatar__status lyra-avatar__status--{status}" role="status" aria-label="{statusLabel ?? status}"></span>}
</span>
```

Initials algorithm (exact PHP equivalent of the React logic):
- split `name` on whitespace runs, drop empty parts, take the FIRST TWO words, uppercase the first character of each, join with no separator. Empty string when name is null/empty.
- Examples: "Ana Souza" → "AS"; "ana" → "A"; "Ana Maria Souza" → "AM"; null → "".

Notes:
- `title="{name}"` on root only when name provided (React sets title={name}; undefined name → no attribute).
- `alt` is always present on the img (empty alt when no name).
- The inner initials span has aria-hidden="true" and NO class.
- Status dot only when status present, always AFTER img/initials, with role="status" and aria-label defaulting to the status value when statusLabel absent.
- All other HTML attributes pass through to the root span.

## Conventions

- PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/avatar.json` — cases: default (md/circle), each size (4), square, square+user class.
2. `resources/views/components/avatar.blade.php` — anonymous component per the contract.
3. `tests/Feature/AvatarTest.php` — fixture-driven exact class assertions + markup: img branch (src+alt), initials branch (algorithm cases incl. single word, three words, no name), title presence/absence, status dot conditional with role/aria-label fallback, order, passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green).
- `<x-lyra::avatar name="Ana Souza" />` → class exactly `lyra-avatar lyra-avatar--md`, `title="Ana Souza"`, inner span aria-hidden containing `AS`, no img, no status dot.
- `<x-lyra::avatar src="/a.png" name="Ana" size="xl" shape="square" status="online" class="x" />` → class exactly `lyra-avatar lyra-avatar--xl lyra-avatar--square x`, img with src and alt="Ana", status span class exactly `lyra-avatar__status lyra-avatar__status--online` with `role="status"` and `aria-label="online"`.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/avatar.blade.php (new)
- tests/Fixtures/class-emission/avatar.json (new)
- tests/Feature/AvatarTest.php (new)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
