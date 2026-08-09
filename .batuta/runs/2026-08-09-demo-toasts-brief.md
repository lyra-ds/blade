# Brief — demo: bump @lyra-ds/alpine ^0.3.0 + vitrine dos toasts dinâmicos

Work only inside /home/franciscpd/Projects/lyra-ds/blade-demo (demo Laravel app; lyra-ds/blade symlinked — ToastStack wiring is live). Ignore any Compozy skills; implement directly.

IMPORTANT — commit protocol: when the work is verified, COMMIT FIRST, then compose your report. The commit must never wait on the report.

## Goal

1. Bump `@lyra-ds/alpine` to `^0.3.0` (published today — brings the `lyraToasts` store and `lyraToastStack` binding) and rebuild assets.
2. Showcase the dynamic toast system in the gallery.

## Context

- resources/views/demo.blade.php — the gallery (house short syntax `<lyra:…>`).
- The store is registered as `lyraToasts` (`$store.lyraToasts`) with API: `toast/success/error/info(message, options?) → id`, `dismiss(id)`; auto-dismiss default 4000ms, `duration: 0` disables. Confirm in node_modules/@lyra-ds/alpine/dist after the bump.
- `<lyra:toast-stack>` (from the symlinked package) now carries the dynamic wiring automatically — place ONE instance near the end of the body (it renders the store queue; static children optional).
- Showcase: in the Feedback section area, add an "Interactive Toasts" sibling section with 3 buttons (success / danger via error() / info) pushing realistic gallery-vocabulary messages, one of them with `{ duration: 0 }` + note that it stays until closed. Buttons need an Alpine tree: wrap the row in `x-data` (lesson from the theme toggle: directives without an x-data ancestor are never processed).

## Task

1. package.json: `@lyra-ds/alpine": "^0.3.0"`; run `npm install` and `npm run build`.
2. Gallery: the "Interactive Toasts" section + one `<lyra:toast-stack />` near `</body>`-end of the main content.
3. Commit (direct on main), THEN report.

## Acceptance criteria

- node_modules/@lyra-ds/alpine at 0.3.0; `grep lyraToasts node_modules/@lyra-ds/alpine/dist/index.js` non-zero.
- `npm run build` passes; `php artisan test` passes; `php artisan view:clear && php artisan view:cache` compiles.
- Buttons wrapped in an x-data tree; no `x-lyra::` reintroduced.

## Boundaries / Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- package.json + package-lock.json (bump only)
- resources/views/demo.blade.php

## Expected evidence

Report: bump proof, section added, test/build output, commit hash.

## Stop conditions

Stop and report when: the store API contradicts this brief, the same command fails twice, or the change needs edits beyond Scope.

---

# Run result (appended)

- Executor: codex (gpt-5.6-sol, medium) via Compozy `sess-af7f35dd747fbac4`. Commit-antes-do-report cumprido (`78702de` no demo).
- Verificação: alpine 0.3.0 instalado (lyraToasts no dist), seção Interactive Toasts com row enraizada em x-data (lição do toggle aplicada), 3 botões (success/error/info com duration:0), 1 toast-stack no fim; artisan test 3/3; npm build ok; view:cache ok.
- Verdict: ✅ approved
