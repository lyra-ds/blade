# Brief — theme infrastructure: @lyraThemeScript directive — onda C, task 16

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/theme-infra (a git worktree of lyra-ds/blade; branch batuta/theme-infra). Ignore any Compozy skills; implement directly.

IMPORTANT — commit protocol: when the work is verified, COMMIT FIRST, then compose your report. The commit must never wait on the report.

## Goal

Ship the Blade side of the theme system: a `@lyraThemeScript` Blade directive that emits the inline blocking anti-flash script for the `<head>`, synchronized with the `theme` store of @lyra-ds/alpine.

CLOSED DECISION (user, 2026-08-09 — do not reopen): directive form (`@lyraThemeScript`), registered by the service provider, optional argument for a custom storage key, default `'lyra-theme'` (the store's key). No vendor:publish.

## Context

- Store contract (authoritative — the script MUST stay in lockstep with it, read it): /home/franciscpd/Projects/lyra-ds/lyra/packages/alpine/src/theme.ts — storage key, the `data-theme` dataset contract on `document.documentElement`, the light/dark/system semantics and how `system` resolves via `matchMedia('(prefers-color-scheme: dark)')`.
- React reference (the comment block documenting the required inline script): /home/franciscpd/Projects/lyra-ds/lyra/packages/react/src/theme-provider/theme-provider.tsx.
- Service provider (where the directive is registered): src/BladeServiceProvider.php — follow its existing registration style (short syntax, component namespace) when adding the directive.
- README.md has an Interactivity section — add a Theme subsection there: layout snippet (`@lyraThemeScript` in `<head>`), toggle example via `$store.theme.toggle()`, custom storage key usage.

Script requirements (inline, blocking, no dependencies, wrapped in try/catch so storage/matchMedia failures never break the page):
1. Read localStorage[key] (`'light' | 'dark' | 'system'`; absent/invalid → treat as the store's default).
2. Resolve `system` via matchMedia.
3. Set `document.documentElement.dataset.theme` to the resolved value BEFORE first paint (that is the whole point — it must be a synchronous inline script).
4. The optional directive argument overrides the storage key: `@lyraThemeScript('my-key')` — the emitted script uses that key; the README documents that the store must then be configured with the same key.

## Conventions

- PHP >= 8.3, Laravel 12/13. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits.
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. tests/Feature/ThemeScriptDirectiveTest.php — the directive compiles and emits: a `<script>` with the default key; custom key when argument given; dataset.theme assignment; matchMedia fallback for system; try/catch present; output is a single inline script with no external refs.
2. Directive implementation registered in src/BladeServiceProvider.php (implementation file placement follows the project's src/ conventions).
3. README.md — Theme subsection under Interactivity.
4. Regenerate resources/boost/guidelines/lyra-blade.md ONLY if the generator's output changes (report if unchanged).
5. COMMIT, then report.

## Acceptance criteria

- vendor/bin/pest passes (baseline 755 stays green).
- The emitted script's storage key and dataset contract match theme.ts exactly.
- vendor/bin/pint --test passes.

## Boundaries

Nothing outside Scope. No JS files shipped (the script is emitted by the directive as a string). No new dependencies. Do not touch components.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- src/BladeServiceProvider.php (directive registration)
- src/* (one new class/file for the directive/script if the project style calls for it)
- tests/Feature/ThemeScriptDirectiveTest.php (new)
- README.md (Theme subsection only)
- resources/boost/guidelines/lyra-blade.md (regenerated only, if changed)

## Expected evidence

Report: files touched; pest and pint output; the emitted script text; commit hash; uncertainties declared.

## Stop conditions

Stop and report when: theme.ts contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
