# Brief — demo gallery: onda C components + theme toggle — task 17

Work only inside /home/franciscpd/Projects/lyra-ds/blade-demo (demo Laravel app; lyra-ds/blade symlinked via path repo — everything below is live). Ignore any Compozy skills; implement directly.

IMPORTANT — commit protocol: when the work is verified, COMMIT FIRST, then compose your report. The commit must never wait on the report.

## Goal

Showcase the five onda-C components and the theme system in the gallery:
1. New showcases: table-of-contents, time-input, bottom-sheet, file-upload, file-manager.
2. Theme: add `@lyraThemeScript` to the layout `<head>` and a visible light/dark toggle in the gallery header driven by the Alpine theme store (`$store.lyraTheme` — read the store name/API in vendor's @lyra-ds/alpine or the package README Theme section).

## Context

- resources/views/demo.blade.php — the gallery (55 components, themed sections, house short syntax `<lyra:…>` throughout — keep it).
- Component APIs (authoritative): vendor/lyra-ds/blade/resources/boost/guidelines/lyra-blade.md + component sources in vendor/lyra-ds/blade/resources/views/components/.
- README Theme section: vendor/lyra-ds/blade/README.md (Interactivity → Theme).
- Placement: table-of-contents near Navigation (needs a few headings with ids to spy on — the gallery section headings can carry ids); time-input in Forms; bottom-sheet next to the other overlay showcases (trigger button + sheet, like the Dialog showcase); file-upload and file-manager in a Files section (file-manager takes a `files` array + `path`).
- Realistic copy in English, gallery vocabulary (release/build/workspace).

## Task

1. Add the five showcases in fitting sections (new sibling sections where needed; existing sections untouched beyond adding heading ids where the TOC needs anchors).
2. Wire the theme: `@lyraThemeScript` in the `<head>`, toggle button in the gallery header calling the store's toggle, reflecting current state (e.g. icon/label switch).
3. Commit in the demo repo (direct on main), THEN report.

## Acceptance criteria

- Each of the five appears (`grep "<lyra:table-of-contents"` etc.).
- `@lyraThemeScript` present in the head; a toggle element references the theme store.
- `php artisan test` passes; `php artisan view:clear && php artisan view:cache` compiles; `npm run build` passes (assets unchanged — only run to prove nothing broke).
- No `x-lyra::` reintroduced (grep stays 0).

## Boundaries / Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/demo.blade.php
- resources/css/app.css (only if the new sections need demo-scoped layout rules, following the existing demo-* conventions)

## Expected evidence

Report: sections added, theme wiring, grep counts, test/build output, commit hash.

## Stop conditions

Stop and report when: a component or the store contradicts this brief, the same command fails twice, or the change needs edits beyond Scope.

---

# Run result (appended)

- Executor: codex (gpt-5.6-sol, medium) via Compozy `sess-b53aaea379c8be4d`. Commit-antes-do-report cumprido (`dc91510` no demo).
- Verificação: 5/5 vitrines; @lyraThemeScript no head; toggle com $store.theme (nome conferido no index.ts — o brief chutou lyraTheme, executor corrigiu pelo fonte); x-lyra:: segue 0; artisan test 3/3; view:cache ok; npm build ok.
- Achado: store lyraToasts JÁ REGISTRADO no index do alpine — PRD dos toasts implementado upstream; lado Blade destravado.
- Verdict: ✅ approved
