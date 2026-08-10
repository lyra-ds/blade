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

Sessão dedicada ao commit (2026-08-10, fase 2 fechada + tasks 35–38): com o runtime
Compozy, **o commit é o elo frágil do ciclo, não a implementação**. No dia:
**5 mortes confirmadas** de sessão por `peer disconnected`
(`sess-5149f5c45f9e536e`, `sess-26d1ac4c682dcf4f`, `sess-f9303603fb39ddc3`,
`sess-7cfe81bf91382463`, `sess-39f842c47a1f8c6b`), quase todas **depois** do trabalho
pronto e **antes** do commit — em duas delas o conteúdo ficou correto e *staged*, fazendo
o invariante de entrega falhar por motivo que não é do modelo. Houve ainda um caso
distinto (task 33.2) em que a sessão seguiu viva e simplesmente não commitou.

Consequências práticas, a aplicar sem tratar como recuperação de falha:

- **Commit-antes-do-report continua obrigatório** em todo brief: é ele que preserva o
  trabalho quando a sessão cai. Funcionou nas 5 quedas.
- **Sessão dedicada ao commit é fluxo normal**, não exceção: quando a primária cair com o
  trabalho pronto, abrir `<task_id>#N` cujo único pedido é commitar. Ela recebe o veredito
  do gate já feito pelo maestro (o que passou, com números) e a instrução de **não tocar em
  arquivo**. Custa pouco e mantém o commit com o executor, como o ciclo exige.
- **Repetir o caminho absoluto do worktree no corpo do prompt**, sempre. `--cwd` não
  garantiu o cwd do agente: em pelo menos um caso a sessão rodou no checkout principal e
  reportou "nada a commitar" olhando `main...origin/main`, com o trabalho intacto no
  worktree. A mitigação (caminho absoluto + `git branch --show-current` como primeira
  ordem) acertou em todas as vezes seguintes.
- **Repetir as convenções no brief**, inclusive conventional commits. Duas rodadas foram
  reprovadas só pela mensagem — uma fora do padrão (task 36) e uma tipada `fix:` para
  mudança que não altera o pacote publicado (task 38), o que cortaria patch anunciando
  "Bug Fixes" inexistente. Onde o release-please lê o tipo, a mensagem é entrega, não estilo.
- **Relatório truncado é comum** quando a sessão cai: o gate não depende dele (relatório
  nunca foi evidência), mas o que se perde é o self-review e as incertezas declaradas —
  então provas de força (quebra deliberada) passam a ser do maestro.
