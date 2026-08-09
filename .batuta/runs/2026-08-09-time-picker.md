# Run — task 22, componente `time-picker` (onda D — último componente)

- `task_id`: `batuta/20260809-142453-time-picker`
- Rota: complex → codex `gpt-5.6-sol`, reasoning high
- Runtime: Compozy. `sess-26499f00e67831ec` (primária), `sess-f52ed600689cebaa` (cross-review),
  `sess-df9300114cda38bb` (rodada corretiva, `#2`)
- `initial_base_sha` = `attempt_base_sha` = `promotion_base_sha` = `07ceb34`
- `candidate_sha` = `final_sha` = `ff40a35` (fast-forward)
- Suíte: 844 → **873** (4207 assertions)

## Decisão de API — `labels.timeOptions` fica FORA do componente

O bind `list` do `lyraTimePicker` hardcoda `aria-label: 'Time options'`
(`time-picker.ts:231-233`) e não aceita label traduzível, enquanto o React tem
`labels.timeOptions`. Expor a prop criaria algo pior que a ausência dela: funcionaria no HTML
servido e seria revertida pelo Alpine no boot. Escolha: servir o `aria-label` fixo no markup
(mesma string do binding, então não há inconsistência antes/depois do boot), documentar a
limitação num comentário Blade, e registrar candidata upstream.

Mesmo precedente da task 16 (`lyraTheme()` sem storage key configurável).

**CANDIDATA UPSTREAM registrada:** o bind `list` do `lyraTimePicker` deveria aceitar um
`aria-label` traduzível pelas options da factory.

## Cross-review — 2 achados: 1 ACEITO, 1 RECUSADO

### ACEITO (High) — `step` fracionário trava a aba

O binding faz `const interval = Number.isFinite(step) && step > 0 ? Math.floor(step) : 30`.
Com `step = 0.5`: passa nas duas guardas e `Math.floor(0.5)` dá **0**; o laço
`for (current = lower; current <= upper; current += interval)` nunca avança e trava a thread
principal.

Verificado na fonte que **o React tem o código idêntico** (`time-picker.tsx:120`) — ou seja,
reproduzir isso seria paridade estrita. Aceitei mesmo assim: paridade é o alvo para *classe e
API*, não um contrato para reproduzir travamento de aba quando o servidor pode barrar de graça.
Precedente direto da casa: na task 7 a prop `width` do app-sidebar foi endurecida com
`is_numeric`/`is_finite` antes de entrar no `x-data`.

Fix: só emite `step` quando é número finito cujo `(int)` é >= 1; caso contrário **omite a chave**
e deixa o binding aplicar o próprio default (30) — sem default duplicado no PHP. Regressões
cobrindo `0.5`, `0`, `-5`, `'abc'`, `INF` (omitidos) e `15`, `'15'`, `15.9` (viram inteiro 15).

Nota de método: os 865 testes passavam. Esse defeito é invisível ao gate mecânico — só o
cross-review pega.

### RECUSADO (Medium) — `aria-selected="false"` pós-boot

O revisor apontou, corretamente, que `:aria-selected="time === selected ? 'true' : false"`
materializa `aria-selected="false"` depois do boot, enquanto o React omite o atributo.
Verificado na fonte do Alpine 3.15.12: `attributeShouldntBePreservedIfFalsy` exclui de propósito
`aria-pressed`, `aria-checked`, `aria-expanded` e `aria-selected`, então o falsy é preservado.

**Recusado pelo mérito:** é o markup canônico do próprio binding; o `calendar` já entregue faz o
mesmo com `aria-pressed` (também na lista de preservados); e `aria-selected="false"` é ARIA
válido — para `role="option"` é arguivelmente melhor que omitir. Mudar aqui divergiria do binding
e deixaria o time-picker inconsistente com o calendar, sem ganho de acessibilidade.
Recusa registrada no re-brief para o executor não "corrigir" por conta própria.

## Mudança de protocolo que funcionou

As três tasks anteriores da onda morreram com `peer disconnected` **antes de commitar**, e o
maestro teve que commitar nas três. A instrução "commite antes do relatório" não estava
sobrevivendo — a sessão morre na fase final de verificação, não por escolha do modelo.

Esta task estreou o protocolo novo no brief: **commitar assim que os testes estiverem vermelhos
e ir dando `git commit --amend --no-edit` a cada avanço**. Resultado: a sessão morreu de novo
(`peer disconnected`, 4ª seguida), mas desta vez entregou um candidato commitado, com árvore
limpa e invariantes válidos — sem intervenção do maestro no commit.

**Adotar em todos os briefs seguintes.**

## Gate

| Checagem | Resultado |
| --- | --- |
| Invariantes de entrega | count=1, parent=`07ceb34`, árvore limpa ✅ (pelo executor, sem intervenção) |
| Scope | 5 caminhos, todos na lista ✅ |
| Testes alvo | 20 → 28 após a correção ✅ |
| Suíte completa | 873 passando, 4207 assertions ✅ |
| Pint | limpo ✅ |
| Gerador de guidelines | idempotente ✅ |
| Cross-review | 2 achados: 1 aceito e corrigido, 1 recusado com justificativa ✅ |

## Verdict

Aprovado e promovido. **Onda D completa nos componentes** — faltam a galeria do demo (task 23)
e a release 0.6.0 (task 24).
