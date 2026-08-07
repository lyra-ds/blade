# Run trail — Demo Laravel app (blade-demo, playground de teste manual)

- **Data:** 2026-08-07
- **Rota:** medium → codex (gpt-5.6-sol, reasoning medium) via Compozy `sess-33f098e53fe5f4bc`, diretório standalone `~/Projects/lyra-ds/blade-demo` (greenfield, sem worktree)
- **Brief:** `.batuta/runs/2026-08-07-blade-demo-brief.md`
- **Commit:** `430c0cc` (amend do maestro para atribuição ao executor)

## Ciclo

1. Delegação; o executor parou num checkpoint de aprovação de design (skill de brainstorming do lado dele) — maestro aprovou a opção recomendada e instruiu a não parar mais.
2. Entrega em 1 tentativa: Laravel 13.24 + `lyra-ds/blade dev-main` via path-repo symlink; npm `@lyra-ds/styles@0.4.1` (entrypoint real `@lyra-ds/styles/styles.css`), `alpinejs@3.15.12`, `@lyra-ds/alpine@0.1.0`; regra `[x-cloak]` adicionada ao app.css (styles não a embarca — nota para o README do pacote); galeria em `/` com 26 usos de `<x-lyra::*>` e Dropdown data-driven (label/commands/separator/danger).

## Provas reexecutadas pelo maestro

- `vendor/lyra-ds/blade` é symlink real para o checkout local.
- `php artisan test`: 3 testes / 7 assertions, verde.
- `npm run build`: verde (Vite, 174–191ms).
- Nenhum markup lyra-* escrito à mão na view (só componentes reais); nada fora do diretório do demo foi tocado.

## Veredito

Aprovado. Achado colateral registrado: `@lyra-ds/styles` não embarca regra `[x-cloak]` — candidata a entrar no pacote styles ou no README do blade (documentação de consumo).
