# Brief — Laravel 13 support — pós-fase 1

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/laravel13 (a git worktree of lyra-ds/blade; branch batuta/laravel13).
CRITICAL PATH RULE: every file you create or edit MUST be under the current working directory using RELATIVE paths.

## Goal

Add Laravel 13 to the supported range: composer constraints, CI matrix, README compat matrix — with the suite green under Laravel 13 locally.

## Facts (verified on Packagist 2026-08-06 — do not invent)

- `illuminate/support` / `illuminate/view` v13 exist (latest v13.24.0).
- `orchestra/testbench` 11 targets Laravel 13: v11.1.0 requires `laravel/framework ^13.1.1`, `php ^8.3`.
- Current composer.json: `illuminate/*: ^11.0|^12.0`, `orchestra/testbench: ^9.15|^10.6`, `pestphp/pest: ^3.8.4|^4.1.5`, php >= 8.3.
- Local PHP is 8.4 — after widening, a plain `composer update` should resolve to Laravel 13 + Testbench 11, making the local suite run AGAINST Laravel 13 (that is the acceptance proof).

## Task

1. composer.json: widen `illuminate/support` and `illuminate/view` to `^11.0|^12.0|^13.0`; widen `orchestra/testbench` to `^9.15|^10.6|^11.1`. If `composer update` reveals a needed pest/phpunit constraint bump for the L13/Testbench-11 resolution, apply the minimal widening and report it.
2. `.github/workflows/ci.yml`: add two matrix rows — `php 8.3 / laravel 13 / illuminate ^13.24 / testbench ^11.1` and `php 8.4 / laravel 13 / illuminate ^13.24 / testbench ^11.1` (same shape as the existing rows).
3. README.md: update the Compatibility table to include Laravel 13 as Supported.
4. Run `composer update --no-interaction` (lock is gitignored), confirm resolution lands on illuminate 13 + testbench 11, then `vendor/bin/pest` (must stay 260 passed) and `vendor/bin/pint --test`.

## Boundaries / Scope (closed list — nothing outside it; stop and report if needed)

- composer.json
- .github/workflows/ci.yml
- README.md

## Expected evidence

Report: files touched; the resolved versions (`composer show illuminate/support orchestra/testbench | head`); pest and pint output; any constraint bump made and why.

## Stop conditions

Stop and report when: composer cannot resolve without changes beyond Scope, the same command fails twice, or the suite breaks under Laravel 13 (that is a finding, not something to patch around).
