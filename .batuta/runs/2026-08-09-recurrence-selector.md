# Run — task 30, componente `recurrence-selector` (onda F, promovido)

- `task_id`: `batuta/20260809-204005-recurrence-selector`
- Rota: complex → codex `gpt-5.6-sol`, reasoning high
- Runtime: Compozy. 3 sessões: primária `sess-28dee2f93051ccb6`, `#review` `sess-eff816015dd0068e`,
  `#2` (fix) `sess-eb18dd4896a1c724` — todas encerradas
- `initial_base_sha` = `attempt_base_sha` = `promotion_base_sha` = `1183192`
- `candidate_sha` = `final_sha` = `836ca74` (fast-forward)
- Suíte: 1016 → **1072** (5872 assertions)

## LIÇÃO TRANSFERÍVEL — verificar paridade de estado servido *rodando as duas runtimes*

A task 29 ensinou que o estado servido tem que bater com o que o binding calcula no boot. Nesta
task o maestro **provou** isso em vez de afirmar: escreveu 16 regras (weekly/monthly × interval 1
e >1 × end never/count/date, mais regra nula e `byWeekday` vazio), rodou o `describeRecurrence`
do binding no **Node** e extraiu a frase servida do HTML do **Blade**, e comparou com `diff`.
16/16 idênticas, incluindo `Intl.ListFormat` (`"Monday, Wednesday, and Friday"`), ordinais
(`1st`) e datas (`Dec 24, 2026`). Repetido depois do fix para garantir que não regrediu.

**Regra:** quando o servidor tem que reproduzir um cálculo que existe em JS, a verificação é
executar os dois lados nos mesmos inputs e diffar — não ler o código dos dois e concluir que
combinam. O mesmo vale para o SSR do React: o atributo `value` nos `<select>` só virou achado
porque o maestro rodou `renderToString` sobre o componente real.

Corolário útil: os formatadores default (`formatWeekdays`, `formatOrdinal`, `formatDate`) são
**funções** no React e no binding, então não atravessam props. Foram medidos rodando o binding no
Node e os resultados entraram no brief como valores literais, em vez de deixar o executor deduzir
o comportamento do `Intl`.

## Cross-review — 2 achados High, 1 aceito como High, 1 rebaixado a Low; +1 do maestro

1. **Aceito (High) — `interval` float trocava o estado visível no boot.** `max(1, $interval)`
   preserva float e `2 === 2.0` é `false` em PHP, então com `interval => 2.0` o servidor emitia a
   seção custom **visível** enquanto o binding calculava `biweekly`/`showCustom() === false` — o
   editor desaparecia no boot. O literal JSON era idêntico nos dois casos (`"interval":2`), o que
   deixava o defeito invisível a quem só olhasse o `x-data`. Reproduzido pelo maestro numa tabela
   int vs float antes de aceitar. O reviewer trouxe a reprodução dele junto — achado bom.
2. **Rebaixado a Low — "o normalizador PHP não implementa o contrato normativo".** O reviewer
   juntou num achado High cinco comportamentos. Só um é divergência: expandir uma regra
   `{freq:'none'}` fornecida para o `NONE_RULE` completo (o `normalizeRule` do binding só troca em
   valor falsy), o que muda a forma do payload que volta pelo `wire:model` — mas não gera troca
   visível, porque os dois formatos casam o preset `none`. Os outros quatro (clamping de interval,
   dias em 0–6, dedupe, count inteiro, data ISO) são o saneamento server-side que a regra da casa
   exige. E descartar chaves desconhecidas é decisão consciente registrada em comentário: o
   binding as preserva por spread em memória, mas HTML servido é outra fronteira de confiança —
   dados arbitrários do app não vão para um literal `x-data`.
3. **Somado pelo maestro (Medium) — atributo `value` nos três `<select>`.** `<select>` não tem
   atributo de conteúdo `value`, e o React não emite: verificado com `renderToString`, que produz
   `<select class="lyra-input" aria-label="Recurrence">` e põe o estado no `selected` da `<option>`
   (que o Blade já fazia certo). Era divergência de emissão, atributo inválido e peso morto — o
   `:value` do bind já cuida da reatividade.

Lente minimalista/consumidor futuro limpa: nenhuma prop inventada (as três funções de formatação
ficaram de fora, como especificado), nenhum bloqueio para a task 32, fixture derivável do React.

**Nota sobre o fix 2:** alinhar a não-expansão exigia trocar as chaves expandidas por fallbacks
(`interval ?? 1`, `byWeekday ?? []`, `end?.type ?? 'never'`) em todo o estado derivado — risco real
de trocar um Low por um High. O brief do fix disse isso explicitamente e a matriz de 16 frases foi
re-rodada depois: sem regressão.

## Composição do date-picker

Reuso da task 20 pelo idioma de alias de escopo já estabelecido
(`date-range-picker.blade.php:154`): `<div x-data="{ get recurrenceEndDate() { return endDate() },
set recurrenceEndDate(v) { setEndDate(v) } }">` em volta do `<x-lyra::date-picker x-model=...>`,
com `min` vindo de `recurrenceStartDate()`.

Contexto que **não** é defeito e entrou no brief para o executor não caçar fantasma: o
`date-picker` do pacote renderiza os controles dentro de `x-if` porque `matchMedia` é o único
seletor autoritativo de branch (decisão fechada) — ele não serve markup estático nenhum. Os testes
asseveram o ponto de composição, não um input servido.

## Runtime — 2 quedas em 3 sessões (4ª e 5ª da onda F)

`peer disconnected` na primária e na `#2`, as duas pós-commit. Report perdido nas duas; verificação
integral do maestro nas duas. Protocolo commit-cedo-e-`--amend` preservou tudo de novo.

## Gate

| Checagem | Resultado |
| --- | --- |
| Invariantes | count=1, parent=`1183192`, árvore limpa ✅ |
| Scope | 4 caminhos ✅ |
| Suíte | 1072 passando, 5872 assertions ✅ |
| Pint / gerador | passed / idempotente ✅ |
| Paridade da frase | 16/16 JS vs PHP, antes e depois do fix ✅ |
| Fixes verificados | 3 selects sem `value`, float 2.0 com custom escondida, `{"freq":"none"}` servido sem expansão ✅ |
| Cross-review | 2 achados, 1 aceito como High, 1 rebaixado a Low, +1 do maestro ✅ |

## Verdict

Aprovado e promovido em `836ca74`. Próxima: task 31 (`slot-picker`), que compõe calendar +
time-zone-picker — a lente do consumidor futuro passa a valer em cheio.
