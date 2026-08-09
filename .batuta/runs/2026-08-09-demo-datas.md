# Run — task 23, galeria do demo: seção "Dates and times" (onda D)

- `task_id`: `batuta/20260809-demo-datas`
- Rota: medium → codex `gpt-5.6-sol`, reasoning medium
- Runtime: Compozy, `sess-e887f17b2fdb56b9`
- Repositório: `~/Projects/lyra-ds/blade-demo`, branch `main`
- `initial_base_sha` = `176ba3f`; candidato/final na `main` do demo
- Diff: 1 arquivo, **70 linhas, só adições**

## Desvio declarado — sem worktree

O perfil pede `Worktree: medium+`, mas esta task rodou direto na `main` do repositório do demo,
seguindo o precedente de todas as varreduras de galeria anteriores. É repositório separado do
pacote, com árvore limpa e estado trivialmente recuperável.

## Conteúdo entregue

Uma narrativa única (cronograma de release) amarrando os quatro componentes: data do deploy,
janela de observação, e horário da manutenção. `calendar` simples com `today-button`,
`min`/`max` e `disabledDates` no fim de semana; `calendar` em modo `range`; `date-picker` e
`date-range-picker` com `name` (submit form-nativo); `time-picker` com `step`/`min`/`max`.
Mais um parágrafo explicando a troca popover→bottom-sheet abaixo de 640px.

## Gate

| Checagem | Resultado |
| --- | --- |
| Invariantes | count=1, pai correto, árvore limpa ✅ (executor commitou sozinho — protocolo novo) |
| Scope | 1 caminho, só adições, nenhuma seção existente alterada ✅ |
| `view:clear && view:cache` | sem erro — o Blade novo compila ✅ |
| `artisan test` | 3/3, 19 assertions ✅ |
| `npm run build` | ok, 163ms ✅ |
| Coerência factual | 22 e 23/08/2026 são mesmo sábado e domingo (a prosa sobre fim de semana bate com os `disabledDates`); 18/08 é terça ✅ |
| Props reais | `card` tem slot `title`; `demo-grid` já existe no CSS ✅ |

## Verificação de runtime no browser (a que importava)

Estes quatro componentes **não renderizam nada sem Alpine** — são todos `template x-if`/`x-for`,
pelas exceções declaradas das tasks 19-22. Um gate só de comandos passaria com a seção vazia.
Lição direta do ciclo do toggle de tema. Então subi o demo e conferi o DOM real:

| Verificação | Resultado |
| --- | --- |
| Raízes de calendar | 4 |
| Células de dia | **168** = 4 × 42 (a grade fixa de seis semanas) |
| Células de dia da semana | 28 = 4 × 7 |
| Botão "hoje" | 1 — só no calendar que pediu `today-button` |
| Cabeçalhos via `Intl` | "August 2026" nos quatro |
| Texto dos triggers | `8/18/2026`, `8/18/2026 – 8/20/2026`, `22:00` |
| Inputs hidden | `deployment_date`, `observation_period[start]`, `observation_period[end]` com os ISO certos |
| Opções de horário | 8 = 20:00→23:30 de 30 em 30 |
| Erros de console | 0 |

E a troca responsiva, que é a única afirmação da prosa que o DOM desktop não cobria: em viewport
de 500px os 3 `lyra-popover-anchor` viram 3 `lyra-bottomsheet-overlay`, **ao vivo, sem reload** —
o listener `matchMedia` do binding reagindo. Isso valida de uma vez o separador ` – ` do range,
a convenção de array do submit e os dois ramos responsivos dos três pickers.

## Verdict

Aprovado. Falta só a task 24 (release 0.6.0) para fechar a onda D.
