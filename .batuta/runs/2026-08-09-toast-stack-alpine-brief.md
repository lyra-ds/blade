# Brief — ToastStack Alpine wiring (toasts dinâmicos) — item avulso pós-onda C

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/toast-stack-alpine (a git worktree of lyra-ds/blade; branch batuta/toast-stack-alpine). Ignore any Compozy skills; implement directly.

IMPORTANT — commit protocol: when the work is verified, COMMIT FIRST, then compose your report. The commit must never wait on the report.

## Goal

Give `<lyra:toast-stack>` the dynamic-queue wiring: the `lyraToastStack` binding renders store-driven toasts (auto-dismiss, close button) into the served stack, while statically served toast children keep working exactly as today.

## Context

- Binding contract (authoritative, read it FIRST; if this brief contradicts it, stop and report): /home/franciscpd/Projects/lyra-ds/lyra/packages/alpine/src/toasts.ts — the `lyraToasts` store (queue items `{id, message, tone}`, `dismiss(id)`, closeLabel config) and the `lyraToastStack` Alpine.data (x-bind objects, how it reads the store). Also its browser test toasts.browser.test.ts for the expected DOM.
- Current static components (the served baseline that MUST keep working unchanged): resources/views/components/toast-stack.blade.php and toast.blade.php + their tests/fixtures. toast.blade.php itself is OUT of scope — do not touch it.
- React reference for the dynamic item markup: /home/franciscpd/Projects/lyra-ds/lyra/packages/react/src/toast-provider/toast-provider.tsx — each queued toast renders as a Toast with: tone class, inlined ToneIcon SVGs (circle/check/alert/info paths, 17×17, aria-hidden, stroke 2 — port the EXACT paths; do NOT use the icon registry), close button with closeLabel and dismiss on click, message as text.
- Decision (house pattern, closed): the wiring is ALWAYS emitted (x-data on the stack root, like every interactive component); without Alpine it is inert and the static children render as today. Dynamic template renders via `<template x-for>` over the store queue with class parity to the static toast emission (`lyra-toast`, tone modifiers, `lyra-toast__icon/__body/__close` etc. — read toast.blade.php and the fixtures for the exact strings).
- Dynamic messages render with `x-text` (adaptation declared in the upstream PRD — no HTML by event).

## Conventions

- PHP >= 8.3, Laravel 12/13. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits.
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. Update resources/views/components/toast-stack.blade.php: x-data wiring per toasts.ts, `<template x-for>` dynamic item with exact class parity + ToneIcon SVGs + close button (aria-label from the store's closeLabel), slot for static children preserved.
2. Update tests/Feature/ToastStackTest.php (extend, do not delete existing assertions): wiring present, template present with the exact item tree (tone classes, icon per tone, close button, x-text message), static children slot untouched, passthrough, user class last.
3. Update tests/Fixtures/class-emission/toast-stack.json only if new emission cases are needed (existing cases stay).
4. Regenerate resources/boost/guidelines/lyra-blade.md via php bin/generate-boost-guidelines.
5. COMMIT, then report.

## Acceptance criteria

- vendor/bin/pest passes (baseline 762 stays green; existing toast/toast-stack tests unchanged and green).
- Dynamic template classes match the static toast emission exactly.
- vendor/bin/pint --test passes; guidelines idempotent.

## Boundaries

Nothing outside Scope. toast.blade.php untouched. No JS files in the package. No new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/toast-stack.blade.php
- tests/Feature/ToastStackTest.php
- tests/Fixtures/class-emission/toast-stack.json (only if needed)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint output; commit hash; uncertainties declared.

## Stop conditions

Stop and report when: toasts.ts contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.

---

# Run result (appended)

- Tentativa 1: sessão morreu no meio do TDD (arquivos prontos, 1 vermelho por ordem de atributos do SVG, sem commit).
- Tentativa 2 (mesma sessão, feedback cirúrgico): template alinhado ao contrato React sem afrouxar o teste; commit `04e7749`.
- Verificação: scope 2 arquivos (fixtures/guidelines sem mudança, previsto); 766/766 (3461 assertions); pint ok; idempotente; template x-for com tone icons por x-show, x-text na message, closeButton delegado ao binding.
- Verdict: ✅ approved — squash na main, cleanup completo. Vitrine no demo BLOQUEADA até a próxima release do @lyra-ds/alpine (lyraToasts fora do 0.2.0).
