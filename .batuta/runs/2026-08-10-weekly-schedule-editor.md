# Run — task 32, componente `weekly-schedule-editor` (onda F — último componente do catálogo)

- `task_id`: `batuta/20260810-031702-weekly-schedule-editor`
- Rota: complex → codex `gpt-5.6-sol`, reasoning high
- Runtime: Compozy. 3 sessões: primária `sess-314ad59f02a56103`, `#review` `sess-bc16c20aa5b972d2`,
  `#2` (fix) `sess-5c2e60e5018551d8` — todas encerradas
- `initial_base_sha` = `attempt_base_sha` = `promotion_base_sha` = `db34490`
- `candidate_sha` = `final_sha` = `ba7fc30` (fast-forward)
- Suíte: 1103 → **1139** (6311 assertions)
- **Catálogo completo: 72 componentes em `resources/views/components/`**

## LIÇÃO TRANSFERÍVEL — o contrato de composição varia por binding, e assumir o anterior erra

O `lyraSlotPicker` (task 31) **traduz** os eventos dos aninhados: intercepta `lyra:change`, casa por
classe e chama `setDay`/`setTimeZone`. O `lyraWeeklyScheduleEditor` faz o **oposto**: o `init()` dá
`stopImmediatePropagation()` em todo `lyra:change` cujo target não é a raiz, ou seja **engole** os
eventos dos aninhados para que não vazem como mudança do editor, e emite os seus próprios
`lyra:change` e `lyra:exceptions`. Consequência: aqui nada é auto-conectado — cada `time-input`,
o `date-picker` e o `popover` precisam ser ligados **explicitamente** aos métodos, por alias de
escopo.

Além disso este binding **não expõe nenhum `x-bind`** (só estado e ~20 métodos), então o markup
inteiro é do componente Blade.

**Regra:** ler o `init()` do binding antes de escrever o brief, e dizer no brief qual dos dois
contratos vale. Um brief que dissesse "como na task 31" teria produzido um componente quebrado.

## Verificação prévia dos alvos de composição (evitou repetir a task 31)

Antes de briefar, o maestro conferiu que `time-input` (`invalid` + `x-modelable="selected"`),
`popover` (`x-modelable="open"`, slot `trigger`, `wrapTrigger`) e `switch` (`:checked`/`@change` pelo
bag) já tinham tudo — e o brief **proibiu editá-los**, mandando parar e reportar se faltasse algo,
porque isso é decisão de arquitetura do usuário (foi o que aconteceu com o gancho do calendar na 31).
Nada faltou.

O executor escolheu bem entre as duas formas do popover: `wrapTrigger=false` + `x-bind="trigger"`,
que reproduz o `<button class="lyra-sched__ghostbtn">` do React **sem wrapper extra**.

## Decisões do maestro

1. **Ranges via `x-for`** sobre `rangesFor(day)` — a lista muda em runtime, então pertence ao Alpine,
   como as opções do `combobox`. Consequência honesta registrada em comentário: sem JS os ranges não
   aparecem.
2. **Submit por dois hiddens com valor JSON** (`name[value]` e `name[exceptions]`), servidos com o
   estado inicial e mantidos em dia por `:value`. O plano pedia "inputs hidden"; um hidden **por
   range** teria de morar dentro do `x-for` para acompanhar mudanças, e então o servido e o reativo
   duplicariam os mesmos `name`, fazendo o POST enviar valores em dobro. Dois hiddens JSON funcionam
   com e sem JS, sem duplicação.
3. `formatDate`/`formatRanges` **não são props** (funções no React); o servidor replica o default do
   binding. Medidos: `Aug 10, 2026` e `09:00–12:00, 13:00–17:00` (en dash). Os quatro labels com
   `{day}` viram templates, como o plano pede.

## Verificações do maestro

- Textos servidos de `formatDate`/`formatRanges` idênticos aos defaults do binding medidos no Node.
- Dash conferido por codepoint: **U+2013**.
- Estado servido coerente por linha: habilitado → `__ranges` visível e `__off` com `x-cloak`;
  desabilitado → o inverso; 7 popovers de cópia fechados com `x-cloak`; Apply `disabled`; checkboxes
  de cada popover excluindo o dia de origem (`0,2,3,4,5,6` na segunda; `0,1,3,4,5,6` na terça).
- `nextRange` medido para o brief: vazio → `09:00–11:00`, após 17:00 → `17:00–19:00`, após 22:30 →
  `22:30–23:59`.
- **Risco da task 30 checado e ausente:** o `mergeSchedule` do binding é permissivo (mantém chave `9`
  e valor não-array), o PHP é mais estrito — mas o PHP serve já saneado nas 7 chaves, então
  `mergeSchedule(servido) === servido` e o `$watch` do `init()` não reescreve nada no boot.

## Cross-review — 2 achados (o menor número da onda), 1 corrigido e 1 documentado

1. **Rebaixado de High a Medium, corrigido — reordenação silenciosa das exceções.** O PHP fazia
   `usort` antes de renderizar; React (`:238`) e binding preservam a ordem recebida, e a ordenação
   acontece **só ao adicionar** (`addException`). Medido: entrada `2026-09-01, 2026-08-10` saía
   servida como `Aug 10 | Sep 01`, trocando a ordem do consumidor na exibição e no JSON submetido.
   Corrigido; verificado depois: DOM e hidden preservam `Sep 01 | Aug 10`.
   **Rebaixei porque uma das consequências alegadas não procede:** o reviewer disse que os índices de
   `removeException` ficavam corrompidos. Não ficavam — o literal do `x-data` recebe o mesmo array do
   DOM servido e o handler usa o `index` do escopo do `x-for`. O brief do fix disse isso
   explicitamente para o executor não "consertar" o que estava certo.
2. **Documentado — o `min` das exceções usa o dia do servidor.** Correto: o React usa `new Date()` e
   recalcula ao hidratar, e no Alpine o `min` é opção servida que o binding não recalcula, então
   servidor e usuário em dias diferentes podem bloquear o "hoje" local. Mesma família do item que a
   task 31 fechou como documentação, e sem gancho no binding para recalcular. Documentado, com a nota
   de que a data tem de ser validada no servidor ao receber o POST — obrigatório de todo modo, já que
   o valor chega de um hidden.

Skeptic (handlers, argumentos, ordem semanal 0/1), composição e minimalist: **limpas**.

## Runtime — 1 queda em 3 sessões (8ª da onda F)

`peer disconnected` na primária, pós-commit. Report perdido, verificação integral do maestro.

## Gate

| Checagem | Resultado |
| --- | --- |
| Invariantes | count=1, parent=`db34490`, árvore limpa ✅ |
| Scope | 4 caminhos ✅ |
| Suíte | 1139 passando, 6311 assertions ✅ |
| Pint / gerador | passed / idempotente ✅ |
| Estado servido | coerente em todas as linhas e popovers ✅ |
| Formatadores | iguais aos defaults do binding, medidos no Node ✅ |
| Fix verificado | ordem das exceções preservada no DOM e no hidden ✅ |
| Cross-review | 2 achados: 1 corrigido, 1 documentado ✅ |

## Verdict

Aprovado e promovido em `ba7fc30`. **O catálogo de componentes está completo (72).** Restam a task 33
(varredura final da galeria + README) e a task 34 (release 0.8.0, que fecha a fase 2).
