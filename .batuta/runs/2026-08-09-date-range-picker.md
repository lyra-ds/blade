# Run — task 21, componente `date-range-picker` (onda D)

- `task_id`: `batuta/20260809-140705-date-range-picker`
- Rota: complex → codex `gpt-5.6-sol`, reasoning high
- Runtime: Compozy. `sess-dbb5f3b742fe0ac2` (primária, morta), `sess-5695c369bc0b84d0` (cross-review)
- `initial_base_sha` = `attempt_base_sha` = `promotion_base_sha` = `e95f06c`
- `candidate_sha` = `final_sha` = `c353914` (fast-forward)
- Suíte: 820 → **844** (4018 assertions)

## O que mudou em relação ao irmão `date-picker`

O componente é quase uma cópia da task 20 — o React faz o mesmo. As diferenças reais:
binding `lyraDateRangePicker`, `defaultValue` como `{start,end}`, defaults de label do range
(`Select period`, `Date range picker`, ` – `, `…`), `range` verdadeiro nos dois calendars
compostos, e o submit form-nativo com **dois** inputs hidden.

## Decisões do maestro fechadas no brief

1. **Duplicar o template, não fatorar um parcial compartilhado.** O React duplica, e um parcial
   acoplaria o markup de dois componentes públicos: uma mudança num mudaria o outro em silêncio.
   Fechado no brief para que nem o executor invente a abstração nem a lente Minimalist a cobre.
2. **O input hidden do range são dois campos**, `name="x[start]"` e `name="x[end]"`, na convenção
   de array do Laravel — um campo só não representa um intervalo num submit form-nativo. A
   sincronização vem do `lyra:change`, que no modo range já traz `{start,end}` em ISO.
   Proibido escrever aritmética de data em JS no template.
3. **Lições da task 20 embutidas de saída**, para não pagar o mesmo cross-review duas vezes:
   `:wrap-trigger="false"` com `x-bind="trigger"` no próprio `<button>`, e presença de
   `label`/`hint`/`error` por `!== null && !== ''` em vez de coerção booleana.

## Gate

| Checagem | Resultado |
| --- | --- |
| Invariantes de entrega | count=1, parent=`e95f06c`, árvore limpa ✅ (após o commit do maestro — ver desvio) |
| Scope | 5 caminhos, todos na lista ✅ |
| Testes alvo | 23 passando, 189 assertions ✅ |
| Suíte completa | 844 passando, 4018 assertions ✅ |
| Pint | limpo ✅ |
| Gerador de guidelines | idempotente ✅ |
| Cross-review (3 lentes) | **nenhum achado** ✅ |

**Primeira task da onda D sem rodada corretiva** — efeito direto de embutir no brief as duas
lições que o cross-review da task 20 tinha custado.

Legitimidade do cross-review conferida antes de aceitar o veredito limpo: 33 tool calls,
94 thoughts, leu o brief, o candidato, `date-range-picker.ts`, o React, o irmão `date-picker`
(componente e teste) e o `calendar`. Guard read-only intacto.

## Desvio declarado

`peer disconnected` mid-TDD pela **3ª vez seguida** na onda D, sempre depois de escrever tudo e
antes de commitar. Maestro verificou integralmente e commitou.

O padrão agora é estável o bastante para mudar a mitigação: a instrução "commite antes do
relatório" não está sendo alcançada, o que sugere que a sessão morre durante a fase final de
verificação, não por escolha do modelo. A partir da task 22 o brief passa a mandar **commitar
já no RED e ir dando `--amend`**, para que qualquer morte deixe um candidato em vez de árvore
suja.

## Verdict

Aprovado e promovido.
