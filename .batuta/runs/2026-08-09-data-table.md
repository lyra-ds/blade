# Run — task 29, componente `data-table` (onda F, primeiro item)

- `task_id`: `batuta/20260809-194117-data-table`
- Rota: complex → codex `gpt-5.6-sol`, reasoning high
- Runtime: Compozy. 3 sessões: primária `sess-05c62b477f04f740`, `#review`
  `sess-8c1ed88e0ea44a7e`, `#2` (fix) `sess-933a0a1f45c4b7b4` — todas encerradas
- `initial_base_sha` = `attempt_base_sha` = `promotion_base_sha` = `f924fea`
- `candidate_sha` = `final_sha` = `0dc7648` (fast-forward)
- Suíte: 977 → **1016** (5394 assertions)

## Preflight — bindings da onda F confirmados

Todos os quatro bindings da onda F estão no `dist/index.js` do `@lyra-ds/alpine` **0.3.0**
instalado (tarball do registry em `blade-demo/node_modules`, não só o fonte do monorepo):
`lyraDataTable`, `lyraRecurrenceSelector`, `lyraSlotPicker`, `lyraWeeklyScheduleEditor`.
**A onda F inteira está destravada** — tasks 30, 31 e 32 não dependem de upstream.

Três restrições duras extraídas do dist e levadas ao brief:

- o binding acha as linhas por `tbody tr[data-row-id]` e a chave de sort por `[data-sort-key]`,
  e lê `data-sort-value` da célula → o Blade tem que servir os três;
- `syncSelectAll()` procura o checkbox pelo seletor **literal** `thead input[x-bind="selectAll"]`
  → a forma longa do atributo é obrigatória, senão o indeterminate nunca sincroniza, em silêncio;
- `toggleSort` cicla asc → desc → null e dispara `lyra:sort`; seleção dispara `lyra:selection`.

## LIÇÃO TRANSFERÍVEL — a lacuna estava no brief, não no executor

O único achado aceito do cross-review (High) foi **culpa da especificação**. Eu fechei o
dual-mode como "default server-side, opt-in client-sort lendo `data-sort-value`" e não disse que
no modo client-sort o servidor precisa **pré-ordenar** as linhas. Resultado: o HTML servido trazia
`aria-sort="ascending"` e o ícone asc ativo sobre linhas desordenadas — o binding só arruma no
`init()`, então sem JS o estado se contradiz e com JS há salto de reordenação no boot. O React
nunca serve isso (`sortedRows` é renderizado sempre que há `sorting`).

**Regra para as tasks 30–32:** quando o brief entrega uma decisão de dois modos, dizer
explicitamente o que cada modo serve no HTML estático. "Quem lê o quê" não basta; falta "quem
ordena/decide o quê antes de o JS existir".

Corrigido replicando o comparador do `applySorting` (nulos no fim nas duas direções, ordem
natural case-insensitive) para o Alpine não reordenar no boot. Verificado por render:
`client asc → Ana, Mia, Zoe, (null)`; `client desc → Zoe, Mia, Ana, (null)`; modo default
intocado.

## Cross-review — 4 achados, 1 aceito e 3 rejeitados

Aceito (High): o client-sort acima, reproduzido por render antes de aceitar.

Rejeitados, com o motivo:

1. **`sortValueKey` como "API inventada" (High).** É decisão declarada do brief: tradução do
   closure `sortValue(row)` do React, que não passa por prop de Blade, e o binding *exige* que
   alguém decida o que vai no `data-sort-value`. Mesma regra "templates, não closures" que o plano
   fixa para as tasks 30/32. O precedente do `searchLabel` (onda E) era outro caso — prop que
   duplicava algo que o React derivava sozinho.
2. **Falta equivalente de `onRowClick` (Medium).** Limitação declarada: callbacks não são props
   PHP, e a resposta honesta em static-first é link dentro da célula (o valor aceita
   `Htmlable`/`Stringable`), não handler de linha. Rendeu só o reforço do comentário de doc.
3. **Contrato de `labels` estreitado (Medium).** Não procede: conteúdo rico no estado vazio entra
   pela prop `empty` ou pelo slot `empty`, e no próprio React `empty` tem precedência
   (`empty ?? labels.empty`). Nada se perde ao limitar `labels.empty` a string.

O reviewer confirmou a fixture de class-emission limpa — expectativas deriváveis das classes
condicionais do React (lição 2 da onda E aplicada).

## Decisões do maestro implementadas no componente

- **Ícone de sort em três variantes servidas**, cada uma com `x-show` sobre `sortDir(key)` e
  `x-cloak` nas duas que não batem com o estado servido: sem JS aparece exatamente uma, com JS
  segue reativo. O binding não tem hook de ícone. Precedente: overlay do command-palette,
  chevron do app-sidebar.
- **`sortValueKey`** nomeia a chave da row cujo valor vira `data-sort-value` (default: a própria
  `key` da coluna).
- **`x-modelable`** sempre emitido, `selected` por default, `sorting` quando o consumidor pede.
- Opções do binding servidas como literal JSON com o mesmo conjunto endurecido de flags do
  command-palette; injeção coberta por teste.

## Runtime — 2 quedas em 3 sessões

`peer disconnected` na sessão primária e na `#2`, as duas depois do trabalho estar dobrado no
commit. O report se perdeu nas duas; o maestro verificou o candidato integralmente nas duas.
O protocolo commit-cedo-e-`--amend` preservou tudo — o commit rotulado `wip: data-table RED`
chegou ao gate já contendo a implementação verde inteira. Renomeado para a mensagem final na
promoção.

O stream da sessão de review gerou um log de **295 MB** no arquivo de saída; o report só se
recupera concatenando **todos** os `agent_message` do `session history` (cada um é um fragmento
do stream), não pegando o último — `... | .[-1].content.text` devolveu 39 bytes, a última linha.
Anotar para as próximas: `[.[].events[] | select(.type=="agent_message") | .content.text] | join("")`.

## Gate

| Checagem | Resultado |
| --- | --- |
| Invariantes | count=1, parent=`f924fea`, árvore limpa ✅ |
| Scope | 4 caminhos ✅ |
| Suíte | 1016 passando, 5394 assertions ✅ |
| Pint / gerador | passed / idempotente ✅ |
| Paridade React | conferida elemento por elemento pelo maestro; skeleton do loading conferido por render (`width: 16px; height: 16px; display: inline-block;`) ✅ |
| Cross-review | 4 achados, 1 aceito e corrigido, 3 rejeitados com motivo ✅ |

## Verdict

Aprovado e promovido em `0dc7648`. Próxima: task 30 (`recurrence-selector`).
