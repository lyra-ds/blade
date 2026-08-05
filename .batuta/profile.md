# Batuta profile — lyra-ds/blade

Stack template: `templates/laravel.md` (Laravel package — Blade components, no app scaffold)

## Answers (onboarding 2026-08-04)

- Stack: Composer package `lyra-ds/blade` — PHP >= 8.3, Laravel 11/12 (Blade
  components), namespace `LyraDs\Blade`. Machine has PHP 8.4.24RC1 + Composer 2.10.
- Methodology: TDD; conventional commits; trunk-based.
- Test: `vendor/bin/pest`
- Lint: `vendor/bin/pint`
- Build: none (no CSS/JS ships in this package — CSS comes from npm `@lyra-ds/styles`)
Execution: sequential
Worktree: medium+
Install: composer install
Runtime: compozy

## Project constraints (from PRD, closed decisions — don't reopen without the user)

- PRD source: `~/Documents/prd-lyra-blade.md`. Architecture decisions in §2 are
  closed: no CSS in the package (npm-only via `@lyra-ds/styles`), Alpine.js as
  suggested peer (never bundled; phase 1 is static-only), API parity with the
  React package (`props.json`/lyra-ds.dev is the source of truth — never invent
  API), independent versioning with a compat matrix in the README, MIT +
  CONTRIBUTING + CoC in English.
- Central quality gate: class-emission tests — each Blade component renders the
  EXACT class string the React component emits (fixtures extracted from the
  main repo).
- CI matrix: Laravel 11/12 × PHP 8.3/8.4 (GitHub Actions).

## Project map

Greenfield: the directory is empty as of onboarding (2026-08-04). Nothing to
map yet — this section gets a real sweep once the package skeleton exists.

Where things will come from, meanwhile:

- `~/Documents/prd-lyra-blade.md` — the PRD driving phase 1 (~22 static
  components, structure, acceptance criteria).
- `~/Projects/lyra-ds` — main repo: `packages/styles` (the real `.lyra-*`
  classes), `packages/react/src/*` (API and class emissions to mirror),
  lyra-ds.dev/llms.txt (contracts).
- Planned layout (PRD §4): `composer.json` at root, service provider
  registering the `lyra` component namespace (`<x-lyra::button>`), Pest tests,
  Pint, GitHub Actions.
