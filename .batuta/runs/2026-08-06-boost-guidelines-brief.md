Work only inside `/home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/boost-guidelines`. This is a git worktree of the `lyra-ds/blade` package on branch `batuta/boost-guidelines`. Ignore any Compozy skills (`cy-execute-task` and friends) — they do not resolve from a nested worktree. Implement the task directly. Run `composer install` in the worktree before testing.

# Goal

Ship AI guidelines with the package so that an LLM working in a Laravel app that installs `lyra-ds/blade` knows which Lyra components exist, which props each one takes, and which rules must never be broken. The guidelines are generated from this package's own source of truth and kept honest by a test, because ~50 more components are still to be migrated and a hand-maintained document would rot within weeks.

# Context

- Package: `lyra-ds/blade`, namespace `LyraDs\Blade`, PHP >= 8.3, Laravel 12/13. Anonymous Blade components only. Tests: Pest + Orchestra Testbench (`vendor/bin/pest`). Style: `vendor/bin/pint`. `laravel/boost` is already a dev dependency (`^2.4`).
- **How Boost discovers third-party guidelines — verified in the installed source, not from memory:** `Laravel\Boost\Support\Composer::packagesDirectoriesWithBoostSubpath()` (`vendor/laravel/boost/src/Support/Composer.php`, ~line 78) globs every installed package directory for `resources/boost/guidelines` and `resources/boost/skills`. Discovery is by **convention only** — there is no `extra.boost` key to declare, and `composer.json` needs no change for this. A package is picked up by `Laravel\Boost\Install\ThirdPartyPackage::discover()` purely because that directory exists.
- The two sources of truth inside this package:
  - `resources/views/components/*.blade.php` — 27 flat, kebab-case anonymous components. 24 declare their API in `@props([...])` with defaults, e.g. `button.blade.php` starts with `@props(['variant' => 'primary', 'size' => 'md', 'loading' => false, 'disabled' => false, 'full' => false])`. **Three declare no `@props` at all** (`empty-state`, `fieldset`, `tag`) — they take only slots and pass-through attributes. They must appear in the output with no prop list, not crash the generator and not be skipped.
  - `tests/Fixtures/class-emission/<name>.json` — one file per component, 1:1 with the components today. Each is a JSON array of cases: `{"props": {"variant": "primary", "size": "sm"}, "expected_class": "lyra-btn lyra-btn--primary lyra-btn--sm"}`. `@props` gives prop names and defaults but NOT the allowed values; the fixtures do, because the cases enumerate the real variants and sizes. The two sources are complementary and both must be read.
- Component syntax: both `<x-lyra::button>` and the short `<lyra:button>` work — the short form was shipped in commit `497809a` and is an exact alias.
- Closed project decisions the guidelines must reflect (from `docs/prd.md` §2, and from the owner): no CSS ships in this package (all appearance comes from the npm package `@lyra-ds/styles`); API parity with the React package is mandatory and the React contracts are the source of truth, so a prop must never be invented; phase 1 is static-only (no Alpine.js, no interactivity).
- Only 27 of ~75 React components are ported. An LLM must never be led to use a component that does not exist here — this is precisely why the generated list comes from this package's own files and not from the React repo.

# Conventions

- Follow existing code style: strict types, `final` classes, the formatting Pint enforces.
- Change only what this brief asks; every changed line traces back to it.
- Clean up only your own mess. Leave pre-existing dead code alone and mention it in your report.
- Keep functions small, names descriptive; prefer clarity over cleverness.
- Comments only for constraints the code cannot express.
- Tests deterministic: no real network, no time-dependent assertions.
- Framework conventions before invention: use what Laravel/Composer already provide rather than hand-rolling equivalents.
- Never: add a dependency or change `composer.json`'s require sections; reformat untouched code; drive-by refactors; touch CI config, license, or anything under Boundaries; silence a signal instead of fixing its source (empty catch, type-silencing casts). Genuine dead end → `// WORKAROUND: <reason>` plus a flag in your report.

# Method

If superpowers skills are available in your environment, conduct the work with them — `test-driven-development` for implementation, `systematic-debugging` for bug investigation. Otherwise, work test-first from the acceptance criteria.

# Test laws

Test the behavior, never the mock. A failing test means fix the code, not the test. No test-only flags or branches in production code.

# Acceptance criteria

1. `resources/boost/guidelines/` exists and holds the generated guidelines as committed markdown, discoverable by Boost's convention above with no `composer.json` change.
2. The generated document has two clearly separated parts:
   - a **static preamble**, authored by you, carrying the rules that do not vary per component: both tag syntaxes with an example of each; all styling comes from the npm `@lyra-ds/styles` package and no CSS ever ships here, so an LLM must not write `.lyra-*` CSS or override it; never invent a prop or a component — only what the document lists exists in this package; phase 1 is static-only, so no interactivity/Alpine directives should be attributed to these components; components of the React design system that are absent from the list are simply not available in Blade yet.
   - a **generated section**, one entry per component discovered on disk, carrying: the component name, both tag forms, its props with defaults from `@props`, the observed allowed values for each prop derived from the fixture cases, and the root class pattern the component emits (also derived from the fixtures).
3. A single documented command regenerates the file. Running it on a clean checkout reproduces the committed file byte-for-byte (idempotent — no timestamps, no absolute paths, no ordering that varies between machines; sort deterministically).
4. A Pest test regenerates the guidelines in memory and fails when the committed file differs. This is the freshness gate: adding a 28th component without regenerating must turn the suite red. Prove it — the test must be shown failing for that scenario during development, and the report must say how you proved it.
5. A component with no `@props` (`empty-state`, `fieldset`, `tag`) appears in the output with no prop list and does not break generation.
6. The generated section is derived at generation time from whatever is on disk — no hardcoded component list, no hardcoded count anywhere in the generator or the test.
7. Full existing suite still green (297 passing today; the number only grows) and `vendor/bin/pint` clean.
8. `CONTRIBUTING.md` documents the regeneration command in the workflow for adding a component, so a contributor knows the step exists. Keep it to the minimum edit that makes the step discoverable — do not restructure the document.

# Boundaries

Do not touch: `resources/views/components/`, `tests/Fixtures/`, existing tests, `.github/`, `docs/`, `README.md`, `LICENSE`, `CODE_OF_CONDUCT.md`, `.batuta/`, `WORK.md`. Do not add or change dependencies. Do not add an artisan command or any other consumer-facing runtime surface — the only thing consumers gain is the guidelines file. Do not copy anything from the React repository; it is not available at build time.

# Scope — the closed list of paths you may change

- `resources/boost/guidelines/` (the generated markdown; name the file yourself)
- new source file(s) under `src/` for the generator
- one new script entry point (your choice of `bin/` or a `composer.json` `scripts` entry — a `scripts` entry is the only `composer.json` change allowed)
- one new test file under `tests/Feature/`
- `CONTRIBUTING.md`

Do not change anything outside this list; if the task requires it, stop and report.

# Expected evidence

- Every file touched, with a one-line reason.
- The exact commands you ran with their real output — at minimum `vendor/bin/pest` with final counts, `vendor/bin/pint`, and the regeneration command run twice showing the file unchanged the second time.
- How you proved criterion 4 (the freshness test actually fails when a component is added without regenerating).
- How you derived allowed prop values from the fixtures, and any prop whose values could not be derived.
- Anything uncertain, declared as uncertainty.

# Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief; the same command fails twice; the work would need edits outside Scope or Boundaries; or Boost's discovery convention turns out to differ from what this brief states (report what the installed source actually does).
