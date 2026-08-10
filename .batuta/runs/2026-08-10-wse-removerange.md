# Run — task 36, `weekly-schedule-editor`: erros de console ao remover faixa

- `task_id`: `batuta/20260810-143554-wse-removerange`
- Rota: medium → codex `gpt-5.6-sol`, reasoning medium
- Runtime: Compozy — 2 sessões (primária, morreu; `#2` só para corrigir a mensagem do commit)
- Worktree: `.batuta/worktrees/20260810-143554-wse-removerange`
- `initial_base_sha` = `attempt_base_sha` = `promotion_base_sha` = `296d012`
- `final_sha` = **`22f4c56`**, promovido por fast-forward
- Diff: 2 arquivos, 4 linhas (2 no componente, 2 nas asserções que fixam a expressão)

## Diagnóstico — feito pelo maestro, no browser, porque o executor não tem um

Achado durante a task 35 e atribuído lá como pré-existente. A investigação aqui:

**Mecanismo.** As faixas são renderizadas por
`<template x-for="(range, index) in rangesFor(day)" :key="index">`, e cada linha tem dois
escopos-alias que ligam os `time-input` aninhados por getter/setter. Os getters **reliam o
array vivo por índice** (`rangesFor(day)[index].start`). Quando o `removeRange` filtra o
array, a linha em desmonte avalia o getter mais uma vez com um índice que já não existe →
`undefined.start` → `TypeError`.

**Delimitação por experimento**, antes de qualquer conserto:

| Ação | Entradas de console |
| --- | --- |
| Remover a **primeira** faixa | 12 (6 warns + 6 errors) |
| Remover a **última** faixa | 12 |
| **Adicionar** faixa | **0** |

Só remoção dispara — o array só encolhe aí. Isso descartou re-mapeamento de índice como causa.

**Dois candidatos, os dois testados no browser contra o demo real:**

| Candidato | Erros ao remover | Editar depois de remover |
| --- | --- | --- |
| Atual, `rangesFor(day)[index].start` | 12 | ✅ funciona |
| (a) `range.start` — espelha o markup documentado do binding | **0** | ❌ **quebra** |
| (b) `rangesFor(day)[index]?.start` | **0** | ✅ funciona |

O candidato (a) é a forma que o `@lyra-ds/alpine` documenta (`weekly-schedule-editor.ts:170-176`),
e parecia o conserto "correto por paridade" — mas o alias capturado fica preso à iteração
antiga e a linha sobrevivente para de escrever no payload. **Só apareceu porque a sonda testou
a sequência inteira** (editar → remover → editar) em vez de parar no primeiro verde.

Conclusão que inverte a leitura inicial: o desvio da task 32 em relação ao markup documentado
**não é gratuito** — é a releitura viva que faz a edição pós-remoção funcionar. O conserto é
manter a releitura e torná-la segura, não voltar ao doc.

**Sonda descartada, para registro:** a primeira tentativa de medir edição disparava `input`/
`change` sintéticos no `<input>` e não propagava para o hidden. Testada contra o baseline,
não propagava lá também — sonda inválida, não regressão. Trocada pelos botões de step do
`time-input`, que são afford real e movem o payload no baseline.

## Gate

| Checagem | Resultado |
| --- | --- |
| Invariantes | count=1, pai `296d012`, árvore limpa ✅ |
| Scope | 2 caminhos, exatamente os previstos ✅ |
| Mudança | optional chaining nos dois getters; setters intocados; `:key` e `invalid(range)` intocados ✅ |
| `vendor/bin/pest` | 1139 passed (6311 assertions) ✅ |
| `vendor/bin/pint --test` | passed ✅ |
| Guidelines | gerador idempotente ✅ |
| **Mensagem do commit** | ❌ **rejeitada na rodada 1** — "Fix schedule range removal console errors", fora do conventional commits |

O erro da mensagem é lacuna do brief (do maestro): este brief não repetiu o bloco de
convenções. Neste repositório a convenção não é cosmética — o release-please tira changelog e
bump dela, então a mensagem original não geraria entrada nem patch. Corrigida por `--amend`
em sessão nova (a primária tinha morrido), sem tocar em arquivo.

## Verificação em runtime, no código promovido

A suíte **não consegue** guardar este bug: erro de console em runtime é invisível para teste
de renderização server-side. O único guarda que ela oferece é a asserção que fixa a expressão
servida — o resto é browser.

| Passo | Resultado |
| --- | --- |
| Markup servido | contém `rangesFor(day)[index]?.start` |
| Sequência editar → remover 1ª → editar sobrevivente → adicionar → remover última (outro dia) | **0 entradas de console** |
| Editar antes de remover | `09:00` → `09:15` |
| Remover a primeira | sobra `13:00–17:00`, correto |
| Editar a sobrevivente | `13:00` → `13:15` — o caso que o candidato (a) quebrava |
| Adicionar faixa | nova faixa `17:00–19:00` |
| Remover a última (quinta) | sobra `09:00–12:00`, correto |

## Verdict

✅ Aprovado após 1 rodada corretiva (mensagem), promovido por fast-forward (`22f4c56`).
Item de backlog fechado. Como é um `fix:`, o release-please vai abrir PR de **0.8.1** no
próximo push — cortar ou não é decisão do usuário.
