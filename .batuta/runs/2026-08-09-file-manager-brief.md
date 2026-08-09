# Brief — FileManager component (React → Blade) — onda C, task 15

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/file-manager (a git worktree of lyra-ds/blade; branch batuta/file-manager). Ignore any Compozy skills; implement directly.

IMPORTANT — commit protocol: when the work is verified, COMMIT FIRST, then compose your report. The commit must never wait on the report.

## Goal

Create the `<lyra:file-manager>` Blade component mirroring the React FileManager, wired to the `lyraFileManager` Alpine binding: search filters served rows by `data-name`, view toggles between two served trees, empty state by match count.

## Context

- Interactive precedents: file-upload.blade.php (integrated today), bottom-sheet.blade.php; house rules in .batuta/profile.md.
- Binding contract (authoritative, read it FIRST; if this brief contradicts it, stop and report): /home/franciscpd/Projects/lyra-ds/lyra/packages/alpine/src/file-manager.ts.
- React contract (markup/classes): /home/franciscpd/Projects/lyra-ds/lyra/packages/react/src/file-manager/file-manager.tsx. Served state to mirror:
  - toolbar: search input, view toggle buttons (`aria-pressed`, `role="group"` + aria-label wrapper);
  - breadcrumb `<nav aria-label>` with per-segment buttons (server-side content, consumer navigates via links/forms — slot);
  - BOTH trees (list rows and grid cards) rendered server-side from the `files` prop (ordering pastas-primeiro is the SERVER's job — document in the component docblock), alternated by the binding via view state; rows/cards carry `data-name` for the client filter;
  - per-item action menus reuse the existing `<lyra:dropdown>` component (slot or default items per the React source);
  - empty state served with the exact class, shown by the binding when no matches.
- Modelable: whatever the binding declares (`view`, `query` per the spec). Files data comes as a prop array; icons by extension follow the React mapping.

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits.
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. tests/Fixtures/class-emission/file-manager.json — exact class strings (root, toolbar, search, toggle buttons + on modifier, breadcrumb, list row tree, grid card tree, empty).
2. resources/views/components/file-manager.blade.php — served markup (both trees) + binding wiring.
3. tests/Feature/FileManagerTest.php — fixture parity + markup: both trees served with data-name per item, aria-pressed initial state per defaultView, breadcrumb, dropdown composition per item, empty state present, wiring (x-data options, bind objects, modelables), passthrough, user class last.
4. Regenerate resources/boost/guidelines/lyra-blade.md via php bin/generate-boost-guidelines.
5. COMMIT, then report.

## Acceptance criteria

- vendor/bin/pest passes (baseline 740 stays green).
- Class emissions match React exactly across both trees.
- vendor/bin/pint --test passes; guidelines idempotent.

## Boundaries

Nothing outside Scope. No JS in the package. No new dependencies. Do not modify dropdown.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/file-manager.blade.php (new)
- tests/Fixtures/class-emission/file-manager.json (new)
- tests/Feature/FileManagerTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint output; commit hash; uncertainties declared.

## Stop conditions

Stop and report when: the binding source contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
