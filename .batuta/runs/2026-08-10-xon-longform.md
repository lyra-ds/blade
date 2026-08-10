# Run — task 35, padronizar handlers Alpine em `x-on:` (fecha o item de backlog da 0.8.0)

- `task_id`: `batuta/20260810-134655-xon-longform`
- Rota: complex → codex `gpt-5.6-sol`, reasoning high
- Runtime: Compozy — 4 sessões (primária, `#2` corretiva, `#3` só para commitar; a `#3` foi a única que sobreviveu)
- Worktree: `.batuta/worktrees/20260810-134655-xon-longform`
- `initial_base_sha` = `attempt_base_sha` = `promotion_base_sha` = `e9ec962`
- `final_sha` = **`6ea2d2d`**, promovido por fast-forward
- Diff: 16 arquivos, 39 inserções / **117 remoções** — o saldo negativo é o ponto da task

## Decisão (maestro): opção A do backlog, não a B

O item de backlog dava duas saídas: padronizar `x-on:` no pacote, ou transformar o contorno
de regex em helper compartilhado de teste. Escolhida a A, com base em números levantados
antes de briefar:

- 23 ocorrências em 9 componentes — pequeno e mecânico;
- `file-upload` **já** usava `x-on:`, e o demo também: a forma curta é que era a exceção;
- README e guidelines não têm um único exemplo com `@` — zero churn de documentação;
- e o decisivo: a opção A **apaga** o contorno da `fb00a66` em vez de institucionalizá-lo.

Fora de escopo por decisão explícita no brief: o shorthand `:` do bind (`:class`,
`:aria-pressed`). `:` é caractere legal de nome de atributo (separador de namespace),
sobrevive ao parse em qualquer libxml e essas asserções passam no CI hoje — mexer nelas
seria escopo inventado.

## Gate

| Checagem | Resultado |
| --- | --- |
| Invariantes | count=1, pai `e9ec962`, árvore limpa ✅ (só na 3ª sessão — ver incidentes) |
| Scope | 16 caminhos, todos na lista (9 componentes + 7 testes) ✅ |
| Forma curta remanescente | **0** em componentes e em testes ✅ |
| `x-on:` no pacote | 24 (23 convertidos + o `file-upload` que já era) ✅ |
| Helpers de regex da `fb00a66` | **removidos**; os `preg_match` que sobraram são pré-existentes e extraem `x-data`, outro propósito ✅ |
| Fixtures de class-emission | intocadas — nenhuma string de classe se moveu ✅ |
| `vendor/bin/pest` | **1139 passed (6311 assertions)** — a contagem volta ao valor pré-`fb00a66`, coerente com a remoção dos helpers ✅ |
| `vendor/bin/pint --test` | passed ✅ |
| Guidelines | gerador idempotente; regenerar não produz diff (esperado: guidelines nunca expuseram handlers) ✅ |
| Prova de quebra deliberada (maestro, não o relatório) | removido `x-on:click="pickMonth(month)"` do calendar → `CalendarTest` falha; restaurado → verde ✅ |

## Verificação em runtime — o que a suíte não podia provar

A suíte só olha o HTML servido; nada nela prova que o Alpine **liga** a forma longa neste
setup. Conferido no browser, no demo:

| Verificação | Resultado |
| --- | --- |
| Handlers `x-on:click` vivos no DOM | 30 distintos, 88 ocorrências na página |
| `calendar` — clique no dia 11 | seleção move de `20` → `11` |
| `slot-picker` — clique num horário | o par selecionado aparece |
| `weekly-schedule-editor` — `removeRange` | 7 → 6 faixas |

## Achado — erros de console **pré-existentes**, não causados por esta task

Clicar em `removeRange` dispara 6 `TypeError` (`Cannot read properties of undefined
(reading 'start'/'end')`) mais 6 warnings de expressão do Alpine. Atribuição feita com
experimento, não por suposição: um worktree em `e9ec962` foi montado, o symlink do demo
apontado para ele (forma curta `@click` confirmada no HTML servido) e o mesmo clique
reproduziu **exatamente** os mesmos 6 erros + 6 warnings e o mesmo 7 → 6. Symlink e
worktree restaurados depois.

Ou seja: defeito latente na remoção de faixa (algo continua referenciando a faixa removida
enquanto o `x-for` reavalia), presente desde a task 32 e nunca observado — as verificações
anteriores mediram o estado servido e o boot, não a interação de remoção. Vai para o
backlog; não bloqueia esta task, que não o introduziu nem o agravou.

## Incidentes de runtime

**Cinco mortes de sessão** por `peer disconnected` ao longo do dia, três só nesta task
(primária, `#2`, e a `#2` de novo). Em todas o trabalho sobreviveu no worktree — em duas
delas *staged mas não commitado*, o que faz o invariante de entrega falhar por motivo que
não é do modelo. A `#3`, criada só para commitar, foi a única a completar.

Padrão acumulado do dia, que vale para o `routing.md`: com Compozy, **o commit é o ponto
frágil do ciclo**, não a implementação. O protocolo commit-antes-do-report já existe por
causa disso e continua sendo o que salva o trabalho.

## Verdict

✅ Aprovado após 1 rodada corretiva, promovido por fast-forward (`6ea2d2d`).
Item de backlog da 0.8.0 fechado. Novo item aberto: os erros de console do `removeRange`.
