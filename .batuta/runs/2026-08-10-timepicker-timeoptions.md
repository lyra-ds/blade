# Run — task 39, `labels.timeOptions` no `time-picker`

- `task_id`: `batuta/20260810-205829-timepicker-timeoptions`
- Rota: medium → codex `gpt-5.6-sol`, reasoning medium
- Runtime: Compozy, **duas sessões**: `sess-588a47ad32184f95` (primária, morreu em
  `peer disconnected` com o trabalho pronto e **não** commitado) e
  `sess-926ca13e847ab62d` (`#2`, dedicada só ao commit)
- `initial_base_sha` = `attempt_base_sha` = `promotion_base_sha` = `e4df115`
- `final_sha` = **`7e5f58a`**, promovido por fast-forward
- Diff: 2 arquivos, 46 inserções / 5 remoções

## Por que a task existia

O binding `lyraTimePicker` fixava o nome acessível da listbox em `'Time options'`, sem opção
de configuração. Por isso o Blade **não** expôs a prop (decisão da task 22, 2026-08-09): uma
app em português serviria `aria-label="Opções de horário"` e veria o Alpine trocar por
`Time options` no boot — a regra fechada do projeto é que **uma prop que funciona no HTML
servido e é revertida no boot do Alpine é pior que prop nenhuma**. O upstream saiu no
`@lyra-ds/alpine` **0.4.0** com `labels: { timeOptions }` (o padrão-objeto dos bindings
novos, não a option plana `optionsLabel` que a spec listava como alternativa), então o destino
do rótulo passou a existir.

## Mudança

`timeOptions` entrou nos defaults de `$resolvedLabels`, é encaminhada ao binding dentro do
`$optionsLiteral` como `labels: { timeOptions: ... }`, e serve os **dois** `aria-label` antes
hardcoded — ramo popover e ramo bottom-sheet. O comentário do topo do arquivo, que registrava
a ressalva, foi reescrito para descrever o contrato novo.

## Gate

| Checagem | Resultado |
| --- | --- |
| Invariantes | pai `e4df115`, árvore limpa, count=1 ✅ |
| Escopo | só os 2 arquivos do brief; README e fixture de class-emission intocados ✅ |
| `pest` | **1141 passed (6339 assertions)**, de 1139/6311 ✅ |
| `pint --test` | passed ✅ |
| Prova de força | revertendo **um** dos dois `aria-label` ao literal, o teste de paridade falha em `TimePickerTest.php:257`; restaurado, passa ✅ |
| Tipo do commit | `feat(time-picker):` ✅ |

Nenhuma classe emitida muda, então o fixture de class-emission não foi tocado — conferido, não
assumido.

## Lição — a sessão de commit dedicada como fluxo normal

A primária caiu **depois** do trabalho pronto e **antes** do commit, exatamente o padrão do dia
(6ª morte confirmada). Ela não era reatachável (`health: dead`, `eligible_for_wake: false`), então
não houve tentativa de reconexão a fazer: o gate passou a ser do maestro (relatório nunca foi
evidência) e a `#2` recebeu o veredito já pronto, com números, e a ordem de **não tocar em
arquivo**. Cumpriu: diff idêntico ao que foi verificado, mensagem no padrão, árvore limpa.
Custo baixo, e o commit fica com o executor como o ciclo exige.

O que **não** funcionou nesta task foi o commit-antes-do-report — a sessão morreu antes de
chegar nele. Isso não enfraquece a regra: ela salvou a task 40, que caiu na mesma rodada.
