# Brief — Livewire test groundwork (fase 2, interativos — pré-requisito)

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/livewire-groundwork (a git worktree of lyra-ds/blade; branch batuta/livewire-groundwork). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Add `livewire/livewire` as a dev dependency and wire it into the package test suite (Testbench), proving with a smoke test that a Livewire component can render a Lyra Blade component inside the suite. This is groundwork for phase-2 interactive components whose CI adds real Livewire/entangle integration tests.

## Context

- Composer package `lyra-ds/blade` (no app skeleton): `illuminate/support|view ^12|^13`, dev deps orchestra/testbench ^10.6|^11.1, Pest, Pint. See composer.json at the worktree root.
- tests/TestCase.php extends Orchestra Testbench and registers three providers via `getPackageProviders()` — Livewire's service provider must be added there.
- tests/Pest.php binds TestCase to tests/Feature.
- 45+ existing feature tests render components via Blade; the suite currently passes 456 tests / 1552 assertions.
- The CI matrix is Laravel 12/13 × PHP 8.3/8.4 — the livewire version constraint must resolve under BOTH illuminate ^12 and ^13. If no livewire release supports illuminate ^13, stop and report (do not loosen minimum-stability or fork constraints).

## Conventions

- PHP >= 8.3. Pest for tests, Pint for style (run both).
- TDD: failing test first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.
- Follow the existing code style of the files you touch; change only what the brief asks; no drive-by refactors; no reformatting; comments only for constraints the code cannot express.
- No new dependencies beyond the one this brief allows (`livewire/livewire`); lockfile is untracked — do not add it to git.
- Deterministic tests: no real network, no time-dependent assertions.
- Test assertions: per-element containment (toContain)/strpos ordering, never exact whole-output equality — Blade emits whitespace.
- Ignore any Compozy skills (cy-*) — implement directly in the worktree.
- Method: if superpowers skills are available in your environment, conduct the work with them — `test-driven-development` for implementation, `systematic-debugging` for bug investigation. Otherwise, work test-first.

## Task

1. composer.json: add `livewire/livewire` to require-dev with a constraint that installs under illuminate ^12 and ^13 (verify with the actual `composer require --dev` resolution; sort-packages is on).
2. tests/TestCase.php: register `Livewire\LivewireServiceProvider` in `getPackageProviders()`.
3. tests/Feature/LivewireSmokeTest.php: a smoke test using a minimal test-only Livewire component (inline/anonymous class in the test file or a class under tests/ — NOT under src/ or resources/) whose view renders `<x-lyra::button>` (or another existing static component). Assert via `Livewire\Livewire::test(...)` that the render succeeds and the output contains the exact base class (`lyra-button`) — proving class emission survives the Livewire render path.
4. Regenerate `resources/boost/guidelines/lyra-blade.md` with `php bin/generate-boost-guidelines` ONLY IF the freshness test fails without it (no new component is added, so it likely stays unchanged — do not hand-edit it).

## Acceptance criteria

- `composer install` (fresh) resolves with livewire present; `composer why livewire/livewire` shows it as a dev requirement.
- `vendor/bin/pest` passes — full existing suite stays green plus the new LivewireSmokeTest.
- The smoke test genuinely goes through Livewire (`Livewire::test`), not a plain Blade render.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS. No changes to src/ or resources/views/. No CI workflow changes. Do not commit composer.lock.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- composer.json (require-dev addition only)
- tests/TestCase.php
- tests/Feature/LivewireSmokeTest.php (new; a support class under tests/ is allowed if the inline approach fails — declare it)
- resources/boost/guidelines/lyra-blade.md (regenerated only, and only if the freshness test demands it)

## Expected evidence

Report: files touched; the resolved livewire version and proof it supports illuminate ^13 (composer output); pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: no livewire release resolves under illuminate ^13; the code's shape contradicts this brief; the same command fails twice; or the fix needs edits beyond Scope.
