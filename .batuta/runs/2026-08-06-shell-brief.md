# Brief — Shell component (React → Blade) — fase 2, onda A, item 12/13

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/shell (a git worktree of lyra-ds/blade; branch batuta/shell). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create the `<x-lyra::shell>` Blade component mirroring the React Shell (three-rail app frame), with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 36 components built to this exact pattern — study and mirror:
- resources/views/components/brand.blade.php (CSS-variable + user-style merge precedent, dynamic tag)
- resources/views/components/navbar.blade.php (fresh sibling: named slots with trim checks)
- tests/Feature/BrandTest.php, tests/Fixtures/class-emission/badge.json (fixture pattern)

React contract (verified against packages/react/src/shell/shell.tsx in the main repo — do NOT deviate; never invent API):

Props:
- Named slots: `sidebar`, `topbar`, `aside` — optional; wrappers render ONLY when provided (`isset && trim !== ''`).
- `sidebarAs` (`aside`|`nav`, default `aside`), `asideAs` (`aside`|`nav`, default `aside`) — element of each rail.
- `sidebarLabel`, `asideLabel` (string, optional) — `aria-label` of the respective rail; omitted when absent.
- `mainAs` (`main`|`div`, default `main`) — element of the main region.
- `scroll` (`page`|`content`, default `page`) — modifier `lyra-shell--{scroll}` ALWAYS emitted.
- `sidebarWidth` (int, optional) → CSS var `--shell-sidebar: {n}px`; `asideWidth` (int, optional) → `--shell-aside: {n}px`; `top` (int, optional) → `--shell-top: {n}px`. Variables render in that fixed order on the root `style`, BEFORE a user-supplied style (mirror brand's attribute-bag merge; user style appended after).
- Default slot — the main content.

Rendered markup (exact):
```html
<div class="lyra-shell lyra-shell--{scroll}[ lyra-shell--has-sidebar][ lyra-shell--has-aside][ user classes last]" [style="--shell-sidebar: npx; --shell-aside: npx; --shell-top: npx[; user style]"] {...passthrough attrs}>
  [<aside|nav class="lyra-shell__sidebar" [aria-label="{sidebarLabel}"]>{sidebar slot}</aside|nav>]
  <main|div class="lyra-shell__main">
    [<div class="lyra-shell__topbar">{topbar slot}</div>]
    <div class="lyra-shell__content">{default slot}</div>
  </main|div>
  [<aside|nav class="lyra-shell__aside" [aria-label="{asideLabel}"]>{aside slot}</aside|nav>]
</div>
```
- Root class order: `lyra-shell`, `lyra-shell--{scroll}`, `--has-sidebar` (only when sidebar slot given), `--has-aside` (only when aside slot given), user classes ALWAYS LAST.
- The content div always wraps the default slot; the topbar div only when the topbar slot is given.
- All other HTML attributes pass through to the root div.

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest for tests, Pint for style (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.
- Test assertions: per-element containment (toContain) / strpos ordering, never exact whole-output equality.
- View style: multi-line directives; NEVER butt two Blade directives together.
- Ignore any Compozy skills (cy-*) — implement directly in the worktree.

## Task

1. `tests/Fixtures/class-emission/shell.json` — cases: base (`lyra-shell lyra-shell--page`), scroll content, user class appended.
2. `resources/views/components/shell.blade.php` — anonymous component per the contract.
3. `tests/Feature/ShellTest.php` — fixture-driven exact class assertions + markup: has-sidebar/has-aside modifiers only with their slots; rail elements default `<aside>` and switch to `<nav>` via sidebarAs/asideAs; rail aria-labels only when given; mainAs default `<main>` switches to `<div>`; topbar div only when given; content div always wraps the default slot; CSS vars in order with and without user style; rail order sidebar→main→aside (strpos); attribute passthrough; user class last.
4. Regenerate `resources/boost/guidelines/lyra-blade.md` with `php bin/generate-boost-guidelines` (never hand-edit it).

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green, including the guidelines freshness test).
- `<x-lyra::shell>Body</x-lyra::shell>` → `<div class="lyra-shell lyra-shell--page"><main class="lyra-shell__main"><div class="lyra-shell__content">Body</div></main></div>` semantics (no rails, no topbar, no style).
- Full example (both rails as nav, labels, topbar, scroll=content, :sidebar-width="280" :top="64", class="x", style="color:red") → root class exactly `lyra-shell lyra-shell--content lyra-shell--has-sidebar lyra-shell--has-aside x`, style `--shell-sidebar: 280px; --shell-top: 64px; color:red` ordering.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/shell.blade.php (new)
- tests/Fixtures/class-emission/shell.json (new)
- tests/Feature/ShellTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
