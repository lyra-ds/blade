# Brief — demo gallery sweep: the 33 missing components

Work only inside /home/franciscpd/Projects/lyra-ds/blade-demo (demo Laravel app; lyra-ds/blade symlinked via path repository — every component below is already available). Ignore any Compozy skills; implement directly.

## Goal

Make the gallery a complete catalog: every lyra-ds/blade component rendered at least once, organized in themed sections.

## Context

- resources/views/demo.blade.php — the gallery. Today it renders 17 of the package's 50 components. Existing section pattern: `<section class="demo-section"><h2>…</h2><div class="demo-grid">…</div></section>` (`demo-section--wide` exists for wide content like Tabs — reuse it for wide items like table/navbar/shell/footer).
- Component API reference (generated, authoritative — never invent props): vendor/lyra-ds/blade/resources/boost/guidelines/lyra-blade.md. Cross-check any doubt against the component source in vendor/lyra-ds/blade/resources/views/components/.
- Keep every existing section untouched; add new sections after "Choice groups" and before "Cards and stats", except where a component naturally belongs to an existing section's theme — still do NOT edit existing sections; create a sibling section instead.

Missing components (closed list of 33 — all must appear):
action-bar, avatar, bottom-nav, brand, breadcrumb, checkbox, container, empty-state, fieldset, form-row, grid, icon-button, input, navbar, nav-link, page-header, pagination, person-cell, progress, radio, segmented-ring, select, separator, shell, skeleton, spinner, stack, stepper, switch, table, textarea, toast-stack.

Suggested section grouping (adjust if a component's API demands it, and say so in the report):
- Forms: input, textarea, select, checkbox, radio, switch, fieldset, form-row
- Navigation: navbar, nav-link, breadcrumb, pagination, bottom-nav, stepper
- Page structure (wide): shell, page-header, action-bar, footer-like… (shell/navbar/footer are wide)
- Data display: table (wide), person-cell, avatar, empty-state, segmented-ring, stat-like leftovers
- Feedback & status: progress, spinner, skeleton, toast-stack
- Layout primitives: container, stack, grid, separator
- Identity: brand, icon-button

Realistic copy in English, same release/build/workspace vocabulary as the current gallery. Small honest examples — no lorem ipsum.

## Acceptance criteria

- All 33 components from the closed list render at least once (grep `x-lyra::<name>` finds each).
- `php artisan test` passes; `php artisan view:clear && php artisan view:cache` compiles without error.
- No existing section modified (diff shows only additions between/around them).

## Boundaries / Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/demo.blade.php

If a component genuinely cannot render standalone with its documented API, include it with its minimal valid usage; if even that fails, leave it out and report exactly why — do not guess props.

## Expected evidence

Report: sections added, any grouping deviations, components that resisted and why; test and view:cache output.

## Stop conditions

Stop and report when: a component renders an error you cannot resolve with the documented API, the same command fails twice, or the change needs edits beyond Scope.

---

# Run result (appended)

- Executor: codex (gpt-5.6-sol, reasoning medium) via Compozy `sess-249be0b6d36e36a2`, cwd blade-demo
- Primeira tentativa limpa. Verificação: 33/33 componentes presentes (grep por item da lista fechada), diff só com adições (seções existentes intactas), composição natural nas seções (Forms/Navigation/Page structure/Data display/Feedback/Layout/Identity), `php artisan test` 3/3 (19 assertions), `view:cache` compila. Commit `1e6ac09` no repo do demo.
- Verdict: ✅ approved
