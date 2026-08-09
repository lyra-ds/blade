# Brief — FileUpload component (React → Blade) — onda C, task 14

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/file-upload (a git worktree of lyra-ds/blade; branch batuta/file-upload). Ignore any Compozy skills; implement directly.

IMPORTANT — commit protocol: when the work is verified, COMMIT FIRST, then compose your report. The commit must never wait on the report.

## Goal

Create the `<lyra:file-upload>` Blade component mirroring the React FileUpload, wired to the `lyraFileUpload` Alpine binding. This component is the DOCUMENTED EXCEPTION to the served-markup pattern: the item list is born at runtime, so the Blade serves a `<template x-for>` the binding renders into.

## Context

- Interactive precedents: bottom-sheet.blade.php / time-input.blade.php (integrated today) for wiring style; house rules in .batuta/profile.md.
- Binding contract (authoritative, read it FIRST — the factory options and x-bind objects there decide everything; if this brief contradicts it, stop and report): /home/franciscpd/Projects/lyra-ds/lyra/packages/alpine/src/file-upload.ts.
- React contract (markup/classes): /home/franciscpd/Projects/lyra-ds/lyra/packages/react/src/file-upload/file-upload.tsx. Key served state: dropzone as native `<button type="button">`, hidden file input (`hidden`, `tabindex="-1"`), hint generated from accept/maxSizeMB, item template (icon by extension, name, meta variants %/bytes/error, done check with role="img", remove button with per-name aria-label).
- The `<template x-for>` item markup must emit the EXACT class strings the React item tree emits (fixtures) — this is still class-parity territory even though rendering is client-side.
- Modelable: `items` (and whatever else the binding declares). Events (`lyra:files` etc.) come from the binding — the Blade only serves markup + wiring.

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits.
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. tests/Fixtures/class-emission/file-upload.json — exact class strings (zone + drag modifier, input, list, item tree, meta variants, remove button).
2. resources/views/components/file-upload.blade.php — served zone/input/hint + `<template x-for>` item markup + binding wiring (x-data with options from props, x-bind objects, x-modelable).
3. tests/Feature/FileUploadTest.php — fixture parity + markup: zone is a button, hidden input attrs (accept/multiple passthrough), hint generation rules, template presence with the exact item tree, wiring, Livewire modelable, defaultItems served into the factory when given, passthrough, user class last.
4. Regenerate resources/boost/guidelines/lyra-blade.md via php bin/generate-boost-guidelines.
5. COMMIT, then report.

## Acceptance criteria

- vendor/bin/pest passes (baseline 727 stays green).
- Class emissions match React exactly, including inside the x-for template.
- vendor/bin/pint --test passes; guidelines idempotent.

## Boundaries

Nothing outside Scope. No JS in the package. No new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/file-upload.blade.php (new)
- tests/Fixtures/class-emission/file-upload.json (new)
- tests/Feature/FileUploadTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint output; commit hash; uncertainties declared.

## Stop conditions

Stop and report when: the binding source contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
