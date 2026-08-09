# Handoff — 2026-08-09

## Cycle point

Parada em ponto limpo, **entre ciclos**. Nada delegado, nada em voo, nada por
verificar.

A **onda E fechou de ponta a ponta**: release 0.7.0 mergeada (PR #8), tag `v0.7.0`,
publicação no Packagist confirmada via API. `main` do blade em `8d189a9`, tudo
empurrado, árvore limpa, zero worktrees. O `blade-demo` está em `cc9ee2b`, limpo
(repositório local, sem remoto — nada a empurrar).

Suíte: **977 testes, 5039 assertions**. Pint limpo. Gerador de guidelines idempotente.

Onde retomar: **task 29 da onda F** — a última onda, 6 tasks:

| Task | Item | Rota prevista |
| --- | --- | --- |
| 29 | `data-table` (`lyraDataTable`) — sort dual-mode, default server-side | codex complex |
| 30 | `recurrence-selector` (promovido) | codex complex |
| 31 | `slot-picker` (promovido) — compõe calendar + time-zone-picker | codex complex |
| 32 | `weekly-schedule-editor` (promovido) — compõe time-input/popover/date-picker | codex complex |
| 33 | Varredura final da galeria + README (catálogo completo) | codex complex |
| 34 | Release 0.8.0 — fecha a fase 2 | maestro (claude) |

O plano completo está em `.batuta/plan-alpine-0-2-ondas-b-f.md` (tasks 29-34, linhas
86-98). Os bindings da onda F **já foram verificados no `dist/index.js` do
`@lyra-ds/alpine` 0.3.0 publicado** — `lyraDataTable` está lá; confirmar os outros três
antes de briefar cada um.

Eu tinha acabado de anunciar que ia conferir os bindings e levantar as classes **do
React** para a task 29. Esse levantamento não foi feito — comece por ele.

## Decisions not yet written

Nenhuma decisão pendente de escrita: tudo o que foi decidido nesta sessão já está no
`WORK.md`, nos trails de `.batuta/runs/` ou no código promovido.

Três lições ficaram registradas nos trails e **precisam entrar nos briefs da onda F**:

1. **O markup canônico no comentário de doc do binding NÃO é normativo.** Ele mostra
   como amarrar os binds, não o que emitir. Custou três defeitos na onda E: a prop
   `searchLabel` inventada no combobox (erro do maestro no brief), a estrutura errada da
   raiz do combobox, e o atalho do command-palette num `<kbd>` único quando o React faz
   `split(' ')` e emite um por tecla. Classe e estrutura vêm **sempre** do React.
   (trail: `.batuta/runs/2026-08-09-command-palette.md`)

2. **A fixture de class-emission ensina o código, não aprende com ele.** Na task 26 uma
   fixture escrita a partir da implementação passou verde escondendo um defeito real
   (`lyra-input--error` não servido no trigger). Toda expectativa de fixture tem que ser
   derivada do React. (trail: `.batuta/runs/2026-08-09-time-zone-picker.md`)

3. **Mandar o cross-review olhar o consumidor futuro do componente.** Foi assim que o
   bloqueio de composabilidade do combobox apareceu com uma task inteira de
   antecedência, e a ausência dessa pergunta sobre a estrutura da raiz é que deixou o
   defeito passar. Vale muito nas tasks 31 e 32, que compõem componentes já entregues.

Protocolo operacional que funcionou e deve continuar: **commitar assim que os testes
estiverem vermelhos e ir dando `git commit --amend --no-edit`**. As sessões deste runtime
morrem com `peer disconnected` com muita frequência (praticamente todas as tasks das
ondas D e E); com esse protocolo, a queda deixa um candidato inspecionável em vez de
árvore suja. Na task 27 o commit RED separou contrato de implementação e o diagnóstico
foi imediato.

## Background

**Nada rodando.** Nenhum executor pendente, nenhuma verificação em aberto.

Sessões Compozy das tasks concluídas foram encerradas. Sobra uma sessão viva que **não é
desta linha de trabalho** e foi deixada intacta de propósito:

- `sess-184a6f4b48ccbe92` — `batuta/e2-composer-attach` (de outra sessão/experimento; não
  mexer)

## Open questions

Nenhuma pergunta pendente para o usuário.

Duas coisas para o usuário decidir **quando a onda F chegar ao fim**, não antes:

1. A release 0.8.0 (task 34) repete o fluxo que o usuário autorizou nas 0.6.0 e 0.7.0:
   push, merge do PR de release por **bypass de admin** (a política do repo exige review
   aprovada e o Copilot só comenta), tag e Packagist. O usuário autorizou explicitamente
   esse fluxo completo nesta sessão; confirmar de novo antes de publicar, já que é ação
   de mão única.
2. O backlog tem duas candidatas upstream registradas, ambas sem bloquear nada:
   - bind `list` do `lyraTimePicker` aceitar `aria-label` traduzível (hoje hardcoded
     `Time options`, o que impediu expor `labels.timeOptions` no Blade)
   - `lyraTheme()` aceitar storage key configurável (destrava o argumento opcional do
     `@lyraThemeScript`)
