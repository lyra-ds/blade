# Handoff — 2026-08-08 (noite, UTC 2026-08-09)

## Cycle point

**Onda B fechada e integrada.** Nada em voo: árvore limpa em `52e0a68`, nenhum
worktree aberto, nenhuma sessão Compozy viva, suíte em **674 testes / 2735
assertions**, pint limpo, guidelines geradas e idempotentes.

O que a sessão entregou, em ordem:

| # | Entrega | Commit |
|---|---|---|
| 1 | Matriz de compat do README → `@lyra-ds/alpine ^0.2` + plano formal das ondas B–F | `b57c0e3` |
| 2 | Bump do `blade-demo` para `@lyra-ds/alpine ^0.2.0` | `1313bb8` (repo do demo) |
| 3 | Componente `code-block` | `34457c0` |
| — | `<lyra:…>` como padrão da casa + regra de atributos servidos, no profile | `65dea8b` |
| 4 | Componente `cookie-banner` | `974b953` |
| 5 | Componente `sidebar-group` | `549b264` |
| 6 | Componente `segmented-control` | `2541a2e` |
| 7 | **Bloqueado** — `app-sidebar` + spec upstream do `lyraAppSidebar` | `a90b847`, `95bb9f7` |
| 8 | Componente `workspace-switcher` (fecha a onda) | `1844f51` |

**Próximo passo do plano** (`.batuta/plan-alpine-0-2-ondas-b-f.md`, aprovado):
**task 9 — varredura da galeria do `blade-demo`** com os 5 componentes novos da
onda B, depois **task 10 — release 0.4.0**. Só então começa a onda C.

Duas coisas para lembrar ao retomar a task 9: a galeria do demo está escrita
inteira com `<x-lyra::…>` e ficou combinado migrá-la para a forma curta nessa
mesma varredura; e o demo é outro repo (`~/Projects/lyra-ds/blade-demo`), sem
worktree, commitando direto na `main` dele.

## Decisions not yet written

Nenhuma decisão acordada está fora do código ou do profile — as duas desta
sessão (`<lyra:…>` como padrão da casa e a regra de atributos servidos vs.
atributos do binding) já estão em `.batuta/profile.md`, commit `65dea8b`.

Pendências de decisão **futuras**, já registradas onde pertencem, não perdidas:

- **Task 16 (onda C) tem decisão de API aberta:** infra de tema — diretiva
  `@lyraThemeScript` versus parcial publicável. Fechar com o usuário **antes**
  de delegar. Está no plano.
- **Peso do bundle do `@lyra-ds/alpine`** (118 KB → 590 KB unpacked; o
  `Alpine.plugin(lyra)` registra os 28 state machines de uma vez, e a galeria
  usa 7). Decisão de doc ou de upstream a tomar na onda C, quando o README de
  interatividade for mexido. Trail: `.batuta/runs/2026-08-08-demo-alpine-bump.md`.
- **Inconsistência cosmética:** `cookie-banner` compõe com `<lyra:button>` e
  `workspace-switcher` com `<x-lyra::avatar>`/`<x-lyra::icon>`. As duas formas
  são idênticas em runtime; candidata a varredura futura, não a correção urgente.

## Background

**Nada rodando deste lado.** As sete sessões Compozy do ciclo
(`sess-7b056747d3ccf7fe`, `sess-ed1b870a8d21d800`, `sess-5a9a49762cf09953`,
`sess-504f84b5913c0866`, `sess-00968fe4cc99c422`, `sess-e04eced547cfc1d5`,
`sess-3a5694702df6dd3c`) estão todas `stopped` — nenhuma órfã, nada a matar.

**Fora deste repo, em andamento:** a sessão do monorepo `lyra` já pegou a spec do
`lyraAppSidebar` — além do `.batuta/prd-alpine-app-sidebar.md` que entreguei
(ainda **untracked** lá, veja Open questions), apareceu um
`.batuta/lot-g1-alpine-app-sidebar.md`, o que indica que o lote já foi aberto do
lado do alpine. Quando `lyraAppSidebar` for publicado, a **task 7 destrava** e o
`app-sidebar` volta ao plano — o usuário pediu explicitamente para voltar a ele
depois da onda B.

**Estado do runtime, para a próxima sessão não se assustar:** o
`peer disconnected before response` do Compozy caiu em **6 dos 7 dispatches**
desta noite. Diagnóstico revisado com o usuário: o daemon estava a 125% de CPU
com **outra sessão do usuário em `prompting` ativo**, então a hipótese é
**contenção**, não daemon travado — reiniciar mataria trabalho em voo e
provavelmente não resolveria. Decisão do usuário: **seguir no Compozy aceitando
o custo** (perder o report do executor), em vez do fallback para subprocess
direto. O que funciona na prática:

1. pedir no brief que o executor **escreva a linha do `WORK.md` e commite ANTES
   de compor o report** — foi o que fez o último item commitar sozinho;
2. tratar **o estado da árvore como fonte de verdade**, nunca o report;
3. quando a queda vier antes do commit, verificar integralmente e commitar como
   maestro, com `Co-Authored-By` para os dois e desvio declarado (precedente do
   Drawer; usado três vezes esta noite).

E uma correção de documentação que vale para qualquer delegação futura: no
Compozy, o provider `codex` quer o modelo **sem** o prefixo (`--model
gpt-5.6-sol`), ao contrário da regra geral do `compozy.md`, que vale para o
`opencode`.

## Open questions

1. **Commitar o PRD no monorepo?** `lyra/.batuta/prd-alpine-app-sidebar.md` está
   untracked na árvore do `lyra`. Não commitei porque não estou conduzindo aquele
   repo. Pergunta feita ao usuário, sem resposta — e como o lote `lot-g1` já
   apareceu por lá, é possível que a sessão do `lyra` resolva sozinha.
2. **Task 16 — forma da infra de tema** (`@lyraThemeScript` vs parcial). Não
   bloqueia a task 9 nem a release 0.4.0; bloqueia só o item 16, lá na onda C.
