# Run — task 20, componente `date-picker` (onda D)

- `task_id`: `batuta/20260809-133730-date-picker`
- Rota: complex → codex `gpt-5.6-sol`, reasoning high
- Runtime: Compozy. Sessões: `sess-9769302c068da6a3` (primária, morta),
  `sess-b51b04a94f3bb89f` (cross-review), `sess-71b0de24789c1d5c` (rodada corretiva, `#2`)
- `target_ref`: `main`
- `initial_base_sha` = `attempt_base_sha` = `promotion_base_sha` = `b586dca`
- `candidate_sha` = `final_sha` = `285a474` (fast-forward)
- Suíte: 796 → **820** (3827 assertions)

## Decisões do maestro fechadas no brief

1. **Compor os componentes Blade, não duplicar markup** — desktop usa `<lyra:popover>`,
   mobile usa `<lyra:bottom-sheet>`, ambos com `<lyra:calendar>` dentro.
2. **Os dois ramos responsivos são servidos em `<template x-if>`** (`!mobile` / `mobile`).
   Exceção declarada ao estático-primeiro: o ramo depende de `matchMedia`, que não existe no
   servidor, e escolher um no servidor entregaria o layout errado para metade dos usuários.
   O ramo `disabled` é servido estaticamente, porque não depende de `mobile`.
3. **Input hidden é opt-in pela prop `name`, e sincroniza pelo evento `lyra:change`** —
   `detail.value` já é ISO. Proibido escrever aritmética de data em JS no template.
4. **Wrapper de alias de escopo obrigatório** (`pickerOpen`, `pickerSelected`): `x-model`
   resolve primeiro no escopo do componente aninhado, então encadear o mesmo nome entrelaça
   o componente consigo mesmo. São `<div>` sem classe, inexistentes no DOM do React —
   divergência estrutural exigida pelo binding, sem efeito na paridade de classe.

## Cross-review — 2 achados, ambos ACEITOS

- **Medium, `date-picker.blade.php:23`** — `(bool) $label` trata a string `"0"` como ausente.
  Em PHP `"0"` é falsy; em JS é truthy. `label="0"` renderiza `<label>0</label>` + `lyra-field`
  no React e sumia no Blade. **Aceito**: correção real e barata, com teste de regressão.
- **High, `date-picker.blade.php:121`** — o trigger `<button>` era passado ao slot do
  `<lyra:popover>`, que **sempre** o envolvia num `<span role="button" tabindex="0">`:
  controle interativo dentro de `role="button"`, dois tab stops para um controle, Escape
  devolvendo foco ao wrapper sem rótulo. **Aceito e verificado na fonte**: o React
  (`popover.tsx:120-124`) usa `<Slot {...triggerProps}>{trigger}</Slot>` quando o trigger é
  elemento válido — funde, não embrulha; o `<span role="button">` só existe para trigger textual.
  O bind `trigger` do `lyraPopover` ainda fornece `type: 'button'`, `aria-haspopup`,
  `:aria-expanded` e `:aria-controls`, confirmando que o binding pressupõe um `<button>` real.

### Ampliação de Scope autorizada pelo maestro

O fix do High exigia editar `popover.blade.php`, fora do Scope original. Ampliação autorizada
explicitamente no re-brief (é decisão do maestro, nunca do executor), limitada a essa correção.
Forma escolhida: prop `wrapTrigger` com **default `true`**, preservando o comportamento de todos
os consumidores existentes; com `false` o slot sai direto e o consumidor põe `x-bind="trigger"`
no próprio elemento. É o espelho do ramo `isValidElement` do React. Teste de não-regressão do
wrapper default exigido no brief e entregue.

## Achado levantado pelo maestro e RECUSADO

O texto do trigger sai só por `x-text="triggerText()"`, então sem Alpine o botão fica vazio.
Considerei servir o placeholder estaticamente, mas recusei: a data formatada exige `Intl`
(sem `ext-intl` no pacote), então servir só o placeholder criaria estado inconsistente entre
"sem seleção" e "com seleção". Mesma exceção declarada do `calendar`. Recusa registrada no
re-brief para o executor não "corrigir" por conta própria.

## Gate

| Checagem | Resultado |
| --- | --- |
| Invariantes de entrega | count=1, parent=`b586dca`, árvore limpa ✅ |
| Scope | 7 caminhos — os 5 originais + `popover.blade.php` e `PopoverTest.php` da ampliação ✅ |
| Testes alvo | 19 → mais os de regressão; suíte 820 passando, 3827 assertions ✅ |
| Pint | limpo ✅ |
| Gerador de guidelines | idempotente ✅ |
| Cross-review | 2 achados, ambos aceitos e corrigidos ✅ |

## Desvio declarado

A sessão primária morreu com `peer disconnected` mid-TDD, **de novo** (2ª seguida na onda D,
11ª+ da série), depois de escrever tudo e antes de commitar. Como a sessão estava `dead` e sem
elegibilidade para wake, a rodada corretiva foi para uma **sessão nova** (`#2`) com o mesmo
executor e o mesmo modelo. Isso **não é escalada** — a linha de roteamento não mudou; é
recriação forçada de sessão, e não conta na régua de escalada.

Nota de risco para as próximas tasks: a mudança no `popover` toca um componente consumido por
outros; a suíte inteira verde (820) é o que cobre essa superfície.

## Verdict

Aprovado e promovido.
