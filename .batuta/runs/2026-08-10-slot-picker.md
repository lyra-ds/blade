# Run — task 31, componente `slot-picker` (onda F, promovido) + gancho no `calendar`

- `task_id`: `batuta/20260810-022739-slot-picker`
- Rota: complex → codex `gpt-5.6-sol`, reasoning high
- Runtime: Compozy. 3 sessões: primária `sess-fea3437cfb896dcc`, `#review` `sess-2285403859106b33`,
  `#2` (fix) `sess-cb42b89f9a388835` — todas encerradas
- `initial_base_sha` = `attempt_base_sha` = `promotion_base_sha` = `817acb0`
- `candidate_sha` = `final_sha` = `a9dd9f5` (fast-forward)
- Suíte: 1072 → **1103** (6083 assertions)

## Decisão do usuário — estender o calendar em vez de duplicar

O React passa `isDateDisabled` e `renderDayMarker` como **closures** para o `Calendar`; o
`lyraCalendar` aceita `isDateDisabled`, mas função não atravessa prop PHP em JSON. Sem gancho, o
`slot-picker` teria de duplicar a grade do calendário — o que a casa recusou na task 26
("composição em vez de duplicação"). Levado ao usuário com as duas opções e o custo de cada uma;
**ele escolheu estender o calendar**.

Correção que o maestro fez ao apresentar a pergunta: eu disse que faltavam os *dois* ganchos
(predicado e marcador). O slot `dayMarker` **já existia** no calendar, dentro do botão do dia e já
testado — faltava só o predicado. A extensão acabou sendo **uma prop**, não duas capacidades.

Implementado no espírito do `factory`/`extraOptions` do combobox: whitelist no servidor mapeando
nome permitido → expressão (`'slot-picker' => '(date) => !hasSlots(date)'`), nome desconhecido
**omite** a opção. Verificado nos quatro casos:

| entrada | `x-data` emitido |
| --- | --- |
| só predicado | `lyraCalendar({isDateDisabled: (date) => !hasSlots(date)})` |
| predicado + options | `lyraCalendar({"locale":"pt-BR",isDateDisabled: …})` |
| nome desconhecido | `lyraCalendar({})` |
| tentativa de injeção | `lyraCalendar({})` |

## LIÇÃO TRANSFERÍVEL — a composição do binding é por evento, não por modelable

O `init()` do `lyraSlotPicker` registra um listener de `lyra:change` na raiz e **intercepta** os
eventos dos aninhados casando por **classe**: `.lyra-cal` vira `setDay(detail.value)` e
`.lyra-tzpicker` vira `setTimeZone(detail.value)`, com `stopPropagation()`. Isso é contrato, não
exemplo de doc: os aninhados só precisam manter suas classes e emitir `lyra:change`. Somado ao
idioma de alias de escopo do pacote para os `x-model` aninhados não se auto-amarrarem.

**Vale para a task 32**: a lente de composição do cross-review veio **limpa** — classes intactas,
aliases distintos, interceptação preservada, e o gancho do calendar ficou uma whitelist extensível.
Nenhum bloqueio arquitetural para o `weekly-schedule-editor`.

## Fuso — decisão fundamentada rodando o SSR do React

O default de `timezone` no binding é `Intl.DateTimeFormat().resolvedOptions().timeZone`, que só
existe no browser. Em vez de decidir por dedução, o maestro rodou `renderToString` sobre o
`SlotPicker` real: o React **renderiza tudo** nos dois casos — com o `timezone` passado e com o
fuso do próprio runtime quando ausente. Então o Blade serve sempre, com o fuso default do PHP como
fallback documentado, e o boot re-renderiza no fuso detectado (a mesma divergência de hidratação
que o React tem). Sem branch escondido, sem markup vazio.

Formatos medidos no `Intl` e postos no brief como literais: `10:00 AM`, `09:00 AM` (hora é
**2 dígitos**, zero à esquerda — pega desprevenido), `01:05 PM`, `Monday, August 10`, hold `2:05`
para 125s, e a virada de dia (`2026-08-10T02:00:00Z` cai em `2026-08-09` em São Paulo).

## Verificação de paridade — 3 fusos, cross-runtime

`byDay()`/`firstDay()`/`dayLabel()`/`timeOf()` do binding rodados em Node contra o HTML servido,
extraído por DOM (regex não serve: `x-show="… > 0"` tem `>` dentro do atributo):

| fuso | rótulo servido | horas visíveis | cloaked |
| --- | --- | --- | --- |
| America/Sao_Paulo | Sunday, August 9 | 11:00 PM | 3 (outro dia) |
| UTC | Monday, August 10 | 02:00 AM, 12:00 PM, 01:00 PM, 04:05 PM | — |
| Asia/Tokyo | Monday, August 10 | 11:00 AM, 09:00 PM, 10:00 PM | 1 (Aug 11) |

Idêntico ao JS nos três, antes e depois do fix. Slots de dias não-ativos saem `x-cloak`ados, então
o HTML estático mostra só o dia ativo.

## Cross-review — o mais forte da onda: 4 achados, 3 aceitos como código, 1 como doc

Nada foi rebaixado por exagero desta vez.

1. **High — a nota de hold escapava do ramo "com slots".** `x-show` era só `holdLeft() > 0`; no
   React o hold vive dentro de `daySlots.length > 0`. Depois do boot apareceria em loading, sem
   slot nenhum e em dia vazio. Corrigido para `!loading && daySlots().length > 0 && holdLeft() …`.
2. **High — o CTA `goToDate` era decidido no servidor e não reagia a troca de fuso.** O React
   recalcula `byDay.has(nextAvailableDate)` a cada render, e `setTimeZone()` muda o agrupamento.
   Agora servido sempre com `x-show="nextAvailableDate && byDay().has(nextAvailableDate)"`.
3. **Medium — ordenação lexical em vez de por instante.** Medido: com `11:00Z` e `13:00+03:00`
   (= 10:00Z) a ordem servida saía `11:00 AM, 10:00 AM`, e como `x-show` **não reordena DOM** ela
   continuava errada depois do boot. Agora ordena pelo instante: `10:00 AM, 11:00 AM`.
4. **Aceito como documentação** — `lyraCalendar` deriva `view`/`focusedDate` uma única vez do valor
   inicial e o `x-model` só atualiza `selected`; sem `timezone` explícito, numa virada de mês por
   fuso o cabeçalho do calendário fica no mês do servidor enquanto o painel usa o dia do browser.
   Observação correta e específica, mas é a mesma família da divergência de hidratação do React, e o
   remédio é o consumidor passar `timezone`. Documentado em vez de reengenharia.

Lente minimalista limpa; fixture derivável do React.

Falso alarme que o maestro investigou e descartou: um `role="option"` de texto vazio na extração
por DOM era a `<template>` de opção do combobox aninhado (`lyra-combobox__option`), não uma pílula
servida vazia.

## Runtime — 2 quedas em 3 sessões (6ª e 7ª da onda F)

`peer disconnected` na primária e na `#2`, ambas pós-commit, report perdido nas duas, verificação
integral do maestro nas duas.

## Gate

| Checagem | Resultado |
| --- | --- |
| Invariantes | count=1, parent=`817acb0`, árvore limpa ✅ |
| Scope | 6 caminhos (2 componentes, 2 testes, fixture, guidelines) ✅ |
| Suíte | 1103 passando, 6083 assertions ✅ |
| Pint / gerador | passed / idempotente ✅ |
| Paridade 3 fusos | idêntica ao binding, antes e depois do fix ✅ |
| Gancho do calendar | whitelist ok, desconhecido e injeção → `{}` ✅ |
| Fixes verificados | ordem por instante, predicado do hold, CTA reativo ✅ |
| Cross-review | 4 achados: 3 corrigidos, 1 documentado ✅ |

## Verdict

Aprovado e promovido em `a9dd9f5`. Próxima: task 32 (`weekly-schedule-editor`) — último componente
do catálogo.
