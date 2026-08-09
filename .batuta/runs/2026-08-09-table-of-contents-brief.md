# Brief — TableOfContents component (React → Blade) — onda C, task 11

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/table-of-contents (a git worktree of lyra-ds/blade; branch batuta/table-of-contents). Ignore any Compozy skills; implement directly.

IMPORTANT — commit protocol: when the work is verified, COMMIT FIRST, then compose your report. The commit must never wait on the report.

## Goal

Create the `<lyra:table-of-contents>` Blade component mirroring the React TableOfContents, wired to the `lyraTableOfContents` Alpine binding (scroll-spy lives in the binding — the Blade ships ZERO JS).

## Context

- Interactive-component precedent (study and mirror the wiring pattern): resources/views/components/sidebar-group.blade.php + tests/Feature/SidebarGroupTest.php + tests/Fixtures/class-emission/sidebar-group.json. House rules in .batuta/profile.md apply (short syntax `<lyra:…>` in docs/examples; served attributes vs binding attributes rule).
- Binding contract (authoritative, read it): /home/franciscpd/Projects/lyra-ds/lyra/packages/alpine/src/table-of-contents.ts — x-data `lyraTableOfContents(...)`, x-bind objects and options are what that file exports; never invent names.
- React contract (authoritative for markup/classes): /home/franciscpd/Projects/lyra-ds/lyra/packages/react/src/table-of-contents/table-of-contents.tsx — `<nav aria-label>` > list of anchor links with `data-level` on `<li>`, active link gets `aria-current="location"` + active class; items: `{id, label, level?}`; the React component is controlled by `activeId`; in Blade the binding derives it (scroll-spy) and exposes it as modelable.
- Served state: markup renders complete without Alpine (all links, no active marker unless an `activeId` prop pre-marks one server-side — mirror React's controlled prop as the served initial state).

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits.
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. tests/Fixtures/class-emission/table-of-contents.json — exact class strings from the React source (root, list, item, link, active modifier).
2. resources/views/components/table-of-contents.blade.php — served markup + binding wiring (x-data, x-bind objects per the binding source, x-modelable for `activeId`).
3. tests/Feature/TableOfContentsTest.php — fixture-driven class parity + markup: nav aria-label, data-level per item, served activeId → aria-current + active class, no activeId → none marked, binding wiring present (x-data with options, bind objects), Livewire modelable, passthrough, user class last.
4. Regenerate resources/boost/guidelines/lyra-blade.md via php bin/generate-boost-guidelines.
5. COMMIT, then report.

## Acceptance criteria

- vendor/bin/pest passes (existing suite stays green; 674 baseline).
- Class emissions match the React strings exactly (fixtures).
- vendor/bin/pint --test passes; guidelines idempotent.

## Boundaries

Nothing outside Scope. No JS in the package. No new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/table-of-contents.blade.php (new)
- tests/Fixtures/class-emission/table-of-contents.json (new)
- tests/Feature/TableOfContentsTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint output; commit hash; uncertainties declared.

## Stop conditions

Stop and report when: the binding source contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.

---

# Run result (appended)

- Executor: codex (gpt-5.6-sol, medium) via Compozy `sess-50605ff59fa56e30`, worktree `table-of-contents`. Commit-antes-do-report cumprido (`793c45d` no worktree).
- Verificação: scope exato (4 paths); diff rastreável (nav/x-data/x-modelable/x-bind link, activeId servido com escape, data-level default 2); 685/685 (2816 assertions); pint ok; gerador idempotente; classes conferidas contra o React (incl. lyra-toc__title, que minha análise original tinha comprimido).
- Verdict: ✅ approved — squash `4d2cf18` na main, worktree/branch removidos, sessão parada.
