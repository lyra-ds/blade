# Routing — lyra-ds/blade

Local copy (takes precedence over the plugin default). Confirmed with the user
at onboarding, 2026-08-04. Full trio installed: opencode 1.18.3 + codex 0.144.5
(ChatGPT subscription) + claude 2.1.222. Runtime: compozy (delegations run as
managed sessions per `compozy.md`).

| Complexity | Examples | Executor | Cost |
|---|---|---|---|
| Trivial | rename, config, copy change, simple unit test | opencode + `opencode/kimi-k2.7-code` | cents (API) |
| Medium | isolated feature, bugfix with clear repro | codex (default model) | ChatGPT subscription |
| Complex | multi-file feature/refactor that a precise brief can fully specify | codex `-m gpt-5.6-sol`, reasoning high (re-confirmed 2026-08-04 — daemon catalog) | ChatGPT subscription |
| Critical | architecture decisions, security-sensitive work, tasks needing the conversation's full context or real judgment | claude (session) | Claude subscription |

## Support lanes

| Role | Examples | Executor | Cost |
|---|---|---|---|
| Research | project map sweep, brief context, "where does X live?" | opencode + `opencode/deepseek-v4-flash` (swapped to primary 2026-08-06, user-confirmed — 2/2 clean reports vs bonsai's 1/5) | cents (API) |

## Rules

The rules from the plugin's default `routing.md` apply unchanged: the
orchestrator classifies and announces in one line; verbal user overrides win;
automatic escalation after 2 failed verifications moves the task one row up;
executor unavailable → next row up (Research has no ladder — the maestro does
the research itself). Model IDs above were discovered via `opencode models` on
this machine; if one disappears, re-run discovery and re-confirm before
delegating.

Research lane specifics (updated 2026-08-06): primary is
`opencode/deepseek-v4-flash` (paid API, cents per scout). Local models
(`lmstudio/prism-ml/bonsai-27b`, `lmstudio/qwen/qwen3-coder-30b` untested)
remain available as an experiment lane — only on explicit user request,
with LM Studio up (`lms server status`). Scout briefs MUST be passed inline
as the `opencode run` argument — never via a temp file in /tmp: reading
outside the project root triggers a permission request that hangs/auto-rejects
in non-interactive mode (bug found 2026-08-05; cost two hung scouts). The
relative-paths-only rule stays in every scout brief regardless of model.

Reliability update 2026-08-06 (onda A da fase 2): deepseek scout A entregou
1/1 limpo (7 contratos, âncoras 100% verificadas), mas o scout B (6
contratos + pesquisa de icon) falhou 2× em silent hang — processo vivo,
0 bytes de output por >1h, nas duas tentativas. Fallback da lane usado:
o maestro leu os 6 fontes diretamente. Padrão observado: briefs de scout
maiores/multi-pergunta parecem correlacionar com o hang; preferir scouts
com escopo de ~6-7 arquivos no máximo e desconfiar de output vazio após
~10min (matar e retry uma vez, depois fallback).

Lane history: bonsai was primary 2026-08-05→06, demoted after closing at
1 clean report vs 4 format failures — 3× absolute-path glob outside the repo
(auto-reject kills the run) even with the explicit rule in the brief, 1×
pseudo-tool-call XML as text with no report. Deepseek: 2/2 clean (wave-3
research; 48-component classification with all 13 STATIC anchors verified).
Swap confirmed by the user 2026-08-06.

Commit attribution (user request, 2026-08-05): commits of delegated work must
credit the real executor — `Co-Authored-By` trailer for the delegate (e.g.
Codex/opencode model) plus a body line naming who implemented and who
reviewed/verified. Never commit delegate output with maestro-only attribution.

Trivial lane reliability (2026-08-07): kimi-k2.7-code falhou 2/2 em silent
hang (processo vivo, 0 bytes >10min, brief inline curto, tarefa de escrita
trivial na galeria do demo) — mesmo padrão dos hangs do deepseek scout.
O hang do opencode não é específico de modelo nem de brief longo. Até
segunda ordem: tarefas triviais escalam direto para codex (exec direto ou
Compozy); a lane opencode fica só para research com escopo mínimo, com
kill após ~10min de output vazio.
