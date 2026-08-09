# Brief — demo gallery: onda B components + short-syntax migration (task 9)

Work only inside /home/franciscpd/Projects/lyra-ds/blade-demo (demo Laravel app; lyra-ds/blade symlinked via path repo — the five new components are live; @lyra-ds/alpine is already at ^0.2.0 with all their bindings). Ignore any Compozy skills; implement directly.

IMPORTANT — commit protocol: when the work is verified, write the commit FIRST, then compose your report. The commit must never wait on the report.

## Goal

Two deliverables in one pass over resources/views/demo.blade.php:
1. Showcase the five onda-B components: code-block, cookie-banner, sidebar-group, segmented-control, workspace-switcher.
2. Migrate the whole gallery from `<x-lyra::name>` to the package's short form `<lyra:name>` (project's house style).

## Context

- resources/views/demo.blade.php — the gallery (all 50 components, themed sections; 141 `x-lyra::` occurrences today).
- Component API reference (authoritative — never invent props): vendor/lyra-ds/blade/resources/boost/guidelines/lyra-blade.md; cross-check sources in vendor/lyra-ds/blade/resources/views/components/.
- The five new components are INTERACTIVE (Alpine): their Blade components already emit x-data/x-bind wiring; the demo's app.js already registers the lyra plugin. Serve them like the existing interactive sections do (Dropdown, Dialog…): initial markup + realistic content.
- Short-syntax migration: `<x-lyra::button>` → `<lyra:button>`, closing tags `</x-lyra::button>` → `</lyra:button>`, self-closing likewise. Slot tags (`<x-slot:…>`) are NOT part of the migration — leave every `<x-slot:…>`/`</x-slot:…>` untouched. Both forms are runtime-identical; this is a pure syntax sweep.
- Section placement: sidebar-group belongs near Navigation; segmented-control near Forms or Navigation; workspace-switcher near Identity/Navigation; code-block gets its own or joins Data display; cookie-banner in Feedback (note: it reads localStorage `lyra-cookie-consent` — after a real decision it stays hidden; that behavior is correct, mention it in a small aside comment in the markup if useful).

## Task

1. Migrate every `x-lyra::` occurrence to the short form (grep for `x-lyra::` must return 0 after).
2. Add the five onda-B showcases in fitting sections (new sibling sections where needed; do not restructure existing ones beyond the syntax sweep).
3. Commit in the demo repo (direct on main, its convention), THEN report.

## Acceptance criteria

- `grep -c "x-lyra::" resources/views/demo.blade.php` → 0; `grep -c "<x-slot:" unchanged from before the sweep.
- Each of the five components appears (`grep "<lyra:code-block"` etc.).
- `php artisan test` passes; `php artisan view:clear && php artisan view:cache` compiles.

## Boundaries / Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/demo.blade.php

## Expected evidence

Report: sections added, occurrence counts before/after, test and view:cache output, commit hash.

## Stop conditions

Stop and report when: a component renders an error you cannot resolve with the documented API, the same command fails twice, or the change needs edits beyond Scope.

---

# Run result (appended)

- Executor: codex (gpt-5.6-sol, reasoning medium) via Compozy `sess-86f288d3cdcddd60`, cwd blade-demo. Protocolo commit-antes-do-report cumprido: commit `252cbdb` na árvore.
- Verificação (árvore como verdade): `x-lyra::` 141 → 0; `<x-slot:` 36 → 36; 5/5 vitrines presentes; remoções do diff = exatamente as 141 linhas migradas (zero fora); artisan test 3/3; view:cache compila.
- Verdict: ✅ approved
