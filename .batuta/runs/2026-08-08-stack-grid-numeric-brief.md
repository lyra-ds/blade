# Brief — bugfix: string-numeric gap/columns collapse in Stack and Grid

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/stack-grid-numeric (a git worktree of lyra-ds/blade; branch batuta/stack-grid-numeric). Ignore any Compozy skills; implement directly.

## Goal

Blade-idiomatic numeric attributes (`gap="5"`, `columns="3"` — strings) must map to spacing tokens/column counts exactly like their `:gap="5"` int counterparts, instead of being emitted as raw invalid CSS.

## Context

Bug (root-caused in the demo gallery, confirmed against the React contract):
- resources/views/components/stack.blade.php:18 and grid.blade.php:13,20 branch on `is_int($gap) || is_float($gap)`. Blade passes plain attributes as STRINGS, so `gap="5"` falls into the raw-CSS branch and emits `--lyra-stack-gap: 5` — unitless, invalid for `gap`, discarded by the browser (spacing collapses to zero).
- React contract (packages/react/src/stack/stack.tsx:38-39): `number` → `var(--space-N)`; `string` → raw CSS value (e.g. `"1rem"`). The correct Blade port of that contract: NUMERIC value (int, float, or numeric string) → token; non-numeric string → raw CSS. Same for grid's `gap` and `columns`.

Fix: replace the `is_int(...) || is_float(...)` checks with `is_numeric(...)` in both files (3 sites: stack gap, grid gap, grid columns). Verify how grid columns uses the numeric branch and keep its emission unchanged for ints.

## Conventions

- PHP >= 8.3, Laravel 12/13. Pest + Pint (run both).
- TDD: failing regression tests FIRST — they must fail on current main's logic, then pass with the fix.
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. Regression tests in tests/Feature/StackTest.php and tests/Feature/GridTest.php: string `gap="5"` emits `--lyra-stack-gap: var(--space-5)` (resp. grid gap), string `columns="3"` emits the same as `:columns="3"`, non-numeric string (`gap="1rem"`) still emits raw `1rem`, int via `:gap="5"` unchanged.
2. Apply the `is_numeric` fix in stack.blade.php and grid.blade.php.
3. Regenerate `resources/boost/guidelines/lyra-blade.md` via `php bin/generate-boost-guidelines` (only if its content changes; otherwise report unchanged).

## Acceptance criteria

- New regression tests pass; `vendor/bin/pest` fully green.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No other components, no CSS/JS.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/stack.blade.php
- resources/views/components/grid.blade.php
- tests/Feature/StackTest.php
- tests/Feature/GridTest.php
- resources/boost/guidelines/lyra-blade.md (regenerated only, if changed)

## Expected evidence

Report: files touched; the red-then-green run of the new tests; pest and pint output.

## Stop conditions

Stop and report when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.

---

# Run result (appended)

- Tentativas 1–2: codex (gpt-5.6-sol, medium) via Compozy `sess-c765ac753bc95567` — bloqueado 2× pela POLICY de escrita do workspace (não é falha do modelo; executor diagnosticou o bug e parou pela stop condition corretamente). Fallback loudly para subprocess por contrato do runtime.
- Tentativa 3 (subprocess `codex exec --sandbox workspace-write`): entregou fix + regressões; maestro verificou red→green (via cache-stale reproduzindo as 3 falhas esperadas) e 601/601 no worktree.
- Na integração: CONFLITO com `716c344` (PR #5) — fix equivalente e mais amplo (inclui skeleton height/width) já mergeado na main por outra sessão durante o ciclo (corrida entre sessões; mesmo diagnóstico da sessão do demo, mesmo is_numeric). A árvore vence: squash abortado, worktree descartado, main verificada verde (601/601, pint ok).
- Verdict: ⚠ superseded — nada perdido (cobertura da PR ⊇ cobertura do ciclo); lição de infra registrada (policy Compozy bloqueando worktrees novos; investigar antes da próxima delegação managed).
