# Brief — BottomSheet component (React → Blade) — onda C, task 13

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/bottom-sheet (a git worktree of lyra-ds/blade; branch batuta/bottom-sheet). Ignore any Compozy skills; implement directly.

IMPORTANT — commit protocol: when the work is verified, COMMIT FIRST, then compose your report. The commit must never wait on the report.

## Goal

Create the `<lyra:bottom-sheet>` Blade component mirroring the React BottomSheet, wired to the `lyraBottomSheet` Alpine binding — the structural sibling of the existing dialog/drawer components.

## Context

- STRUCTURAL PRECEDENT (this is the closest sibling — study it first): resources/views/components/dialog.blade.php and drawer.blade.php + their tests and fixtures. BottomSheet follows the same anatomy: overlay/panel/title/close, `open` modelable, `x-cloak`, markup in-place (no portal — closed decision).
- House rules in .batuta/profile.md (short syntax; served vs binding attributes).
- Binding contract (authoritative, read it): /home/franciscpd/Projects/lyra-ds/lyra/packages/alpine/src/bottom-sheet.ts — factory options, x-bind objects; never invent names.
- React contract (authoritative for markup/classes/ARIA): /home/franciscpd/Projects/lyra-ds/lyra/packages/react/src/bottom-sheet/bottom-sheet.tsx. Key served state: panel `role="dialog"` + `aria-modal="true"`, `aria-labelledby` when title present vs `aria-label` (exclusive union — mirror how dialog.blade.php resolved this: labelId decisions are closed precedents), close button with `closeLabel` default, overlay/panel classes exact.

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits.
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. tests/Fixtures/class-emission/bottom-sheet.json — exact class strings (overlay, panel, header/title/close/body per the React source).
2. resources/views/components/bottom-sheet.blade.php — served markup + binding wiring (x-data with defaultOpen/labelId per the dialog/drawer precedent, x-bind objects, x-modelable open, x-cloak).
3. tests/Feature/BottomSheetTest.php — fixture parity + markup: dialog role/aria-modal, labelledby vs aria-label union, close button + label, x-cloak, wiring, Livewire modelable (wire:model routed to root), slots, passthrough, user class last.
4. Regenerate resources/boost/guidelines/lyra-blade.md via php bin/generate-boost-guidelines.
5. COMMIT, then report.

## Acceptance criteria

- vendor/bin/pest passes (baseline 714 stays green).
- Class emissions match React exactly; anatomy consistent with dialog/drawer precedents.
- vendor/bin/pint --test passes; guidelines idempotent.

## Boundaries

Nothing outside Scope. No JS in the package. No new dependencies. Do not touch dialog/drawer.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/bottom-sheet.blade.php (new)
- tests/Fixtures/class-emission/bottom-sheet.json (new)
- tests/Feature/BottomSheetTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint output; commit hash; uncertainties declared.

## Stop conditions

Stop and report when: the binding source contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
