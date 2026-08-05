# WORK — lyra-ds/blade

## In progress
- [ ] Onda 1 (irmãos do Button, sequencial): Spinner ✅ → Badge ✅ → Tag ✅ → Separator → Skeleton → Progress — lane medium (codex default) via Compozy, um ciclo/commit por item; scout único (bonsai) extraindo contrato dos 6 (disparada 2026-08-05); próximo item: Separator

## Backlog
- [ ] Alias curto `<lyra:button>` (estilo Flux) como açúcar sintático sobre `<x-lyra::button>` — exige precompiler Blade customizado; os dois conviveriam, sem breaking change. Avaliar depois da fase 1 (anotado 2026-08-05)
- [ ] Orientações para LLMs usarem os componentes Blade em projetos Laravel: guidelines embarcadas via laravel/boost, derivadas dos mesmos contratos das fixtures (props.json/llms.txt do lyra-ds.dev) — planejar depois dos primeiros componentes (anotado 2026-08-05)

## Done
- [x] Componente Tag (onda 1, item 3/6) → codex (default model) via Compozy `sess-78659027d929a624`, worktree `tag`. Verificado pelo maestro (pest 47/47, pint ok), squash merge em `main` (`85b10a7`), sessão encerrada (trail: `.batuta/runs/2026-08-05-tag.md`) (2026-08-05)
- [x] Componente Badge (onda 1, item 2/6) → codex (default model) via Compozy `sess-54c963fa20020999`, worktree `badge`. Verificado pelo maestro (pest 43/43, pint ok), squash merge em `main` (`aa0a328`), sessão encerrada (trail: `.batuta/runs/2026-08-05-badge.md`) (2026-08-05)
- [x] Componente Spinner (onda 1, item 1/6) → codex (default model) via Compozy `sess-80f49b33bedc327f`, worktree `spinner`. Verificado pelo maestro (pest 33/33, pint ok), squash merge em `main` (`a8e4624`), sessão encerrada (trail: `.batuta/runs/2026-08-05-spinner.md`) (2026-08-05)
- [x] Componente Button (conversão React → Blade, fixtures de emissão de classes) → codex (gpt-5.6-sol, reasoning high) via Compozy `sess-a5604518407d30df`, worktree `button`. Verificado pelo maestro (pest 26/26, pint ok), squash merge em `main` (`67920bb`), sessão encerrada (trail: `.batuta/runs/2026-08-05-button.md`) (2026-08-05)
- [x] Esqueleto do pacote (composer.json, service provider, Pest, Pint, CI, docs, laravel/boost em dev) → codex (gpt-5.6-sol, reasoning high) via Compozy `sess-0b72d62cb9d9aa88`, worktree `esqueleto-pacote`. Verificado pelo maestro (pest 2/2, pint ok), merge ff em `main` (`8dda105`), sessão encerrada (2026-08-05)
