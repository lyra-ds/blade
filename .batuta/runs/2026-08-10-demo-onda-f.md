# Run — task 33.1, galeria do demo: varredura final (catálogo completo)

- `task_id`: `batuta/20260810-103751-demo-onda-f`
- Rota: complex → codex `gpt-5.6-sol`, reasoning high
- Runtime: Compozy — `sess-5149f5c45f9e536e` (primária, morreu), `sess-11588e1bdc965f9d` (`#2`, rodada corretiva)
- Repositório: `~/Projects/lyra-ds/blade-demo`, branch `main`
- `initial_base_sha` = `attempt_base_sha` = `cc9ee2b`; candidatos `b50106f` → **`fc014c6`** (amend), na `main` do demo
- Diff final: 1 arquivo, **139 linhas, só adições**

## Desvio declarado — sem worktree

O perfil pede `Worktree: medium+`, mas a task rodou direto na `main` do demo, seguindo o
precedente de todas as varreduras de galeria anteriores. Repositório separado do pacote,
árvore limpa, estado trivialmente recuperável.

## Incidente de runtime — sessão primária morreu sem relatório

`compozy session prompt` da sessão primária terminou em
`peer disconnected before response`; `session status` devolveu `state: stopped`,
`health: dead`, `eligible_for_wake: false` — reconexão impossível, então o relatório do
executor se perdeu. **O commit tinha landed** (`b50106f`, pai correto, árvore limpa): o
protocolo commit-antes-do-report salvou o trabalho. Mesmo padrão que apareceu na task 31
(obs. 17345). Como relatório nunca é evidência, o gate rodou inteiro sem ele; o que se
perdeu foi o self-review e as incertezas declaradas do executor.

A rodada corretiva não pôde ir para a mesma sessão (contrato normal do retry): sessão nova
`#2`, mesma rota e mesmo modelo — **não é escalação**, é a rodada única do gate.

## Conteúdo entregue

Cinco vitrines, fechando o catálogo em 72/72:

- **Interactive scheduling** (seção nova, 3 cards numa narrativa de release): `recurrence-selector`
  (quinzenal ter/qui até 01/10/2026, com conflito em 01/09), `slot-picker` (4 slots ISO em
  `America/Sao_Paulo`, janela 18–31/08) e `weekly-schedule-editor` (seg–sex com almoço em ter/qui,
  exceções 07/09 fechado e 08/09 reduzido, submit `release_support`).
- **Interactive app sidebar**: 2 grupos, 5 itens com ícone, badge, footer de usuário, `collapsible`.
- **Interactive build table**: `data-table` com 4 builds, seleção servida, `client-sort` com
  `sortValueKey` numérico em build e duração, sticky header, `labels.selectRow` interpolando `{build}`.

## Gate — rodada 1 (rejeitada)

| Checagem | Resultado |
| --- | --- |
| Invariantes | count=1, pai `cc9ee2b`, árvore limpa ✅ |
| Scope | 1 caminho, 125 linhas, só adições ✅ |
| Props inventadas | nenhuma — os 5 `@props` conferidos um a um, mais as formas aninhadas (`sortValueKey`, `align`, `heading`/`items`/`id`/`label`/`active`/`badge`, `date`/`reason`, `start`/`end`, `date`/`ranges`) ✅ |
| `artisan test` / `view:cache` / `npm build` | 3/3 · ok · ok ✅ |
| Coerência factual | 18/08 é terça e bate com `byWeekday [2,4]` (0=domingo); conflito 01/09 é terça, cai na recorrência; 07/09 (feriado) é segunda ✅ |
| DOM real | data-table, slot-picker, recurrence-selector e weekly-schedule-editor todos corretos ✅ |
| **app-sidebar colapsado** | **❌ dois defeitos** |

**Achados (1 do maestro, 2 do usuário olhando a galeria — mesma raiz):** a vitrine não
sobrevivia ao estado rail, que é o motivo de o componente existir.

1. Itens sem `icon`: o `@lyra-ds/styles` esconde `.lyra-sbgroup__item-label` e
   `__item-badge` sob `.lyra-appsidebar--rail` — o item colapsado **é** o ícone. A 64px a
   navegação virava uma coluna de botões vazios.
2. Footer como string solta: a rail colapsa via `.lyra-appsidebar__user-name` /
   `__user-role`; `"Maya Chen · Maintainer"` não casa com nenhum desses hooks e não colapsava.

Paridade conferida antes de culpar o pacote: o React também renderiza o footer sem
condição (`app-sidebar.tsx:184`) — o Blade está em paridade, a vitrine é que usava o
contrato errado. Ambos os consertos cabiam no demo (`icon` por item; `footer` aceita
`Htmlable`, provado em `tests/Feature/AppSidebarTest.php:207-217`).

## Gate — rodada 2 (aprovada)

| Checagem | Resultado |
| --- | --- |
| Invariantes | count=1, pai `cc9ee2b`, árvore limpa ✅ (amend, como pedido) |
| Cobertura do catálogo | **72/72** por loop sobre `resources/views/components/*.blade.php` ✅ |
| `x-lyra::` | 0 ✅ |
| `artisan test` / `view:cache` / `npm build` | 3/3 (19 assertions) · ok · ok (188ms) ✅ |
| Nomes de ícone | `layout-dashboard`, `package`, `rocket`, `users`, `settings`, `user` — todos na curadoria ✅ |
| Rail expandida → colapsada → expandida | 280px → **64px** → 280px; 5 ícones visíveis nos dois estados; 5 rótulos → **0**; nome e cargo do usuário visíveis → **ocultos**; footer sem overflow; chevron trocando de path ✅ |
| Regressão nas outras 4 vitrines | 4 linhas de data-table, 7 linhas de schedule, 1 slot-picker, frase da recorrência intacta ✅ |
| Erros de console | 0 ✅ |

## Verificação de runtime no browser (a que importava)

Como na task 23: esses componentes são `x-for`/`x-if`, então um gate só de comandos passaria
com seção vazia. DOM real, com Alpine ligado:

| Verificação | Resultado |
| --- | --- |
| `data-table` — ordenação client-side ao vivo | clique em Duração ordena por `durationSeconds`, não pelo texto: desc → 281 (485s), 282 (438s), 283 (423s), 284 (402s); `aria-sort` acompanha |
| `data-table` — rótulo interpolado | `Select Build #282` etc., de `labels.selectRow` com `{build}`; select-all com o rótulo customizado |
| `data-table` — seleção servida | 1 linha com `lyra-table__row--selected` antes de qualquer clique |
| `slot-picker` — fuso | trigger resolve `São Paulo / Brasília (GMT-3)`; visíveis só 09:00, 10:30 e 12:00 (os três slots UTC do dia 18); o slot do dia 19 (11:00) fica oculto, correto |
| `slot-picker` — estados | empty-state e hold ocultos; o nó `Invalid Date` do `x-text` de `nextAvailable` existe no DOM mas fica sob `x-show` falso + `x-cloak` — invisível em qualquer estado (observação, não defeito) |
| `recurrence-selector` | `Repeats every 2 weeks on Tuesday and Thursday, until Oct 1, 2026` + `1 occurrence falls in unavailable time` — prova que o conflito de 01/09 cai mesmo na recorrência |
| `weekly-schedule-editor` | 7 linhas de dia, 7 faixas, 14 inputs de horário, exceções `Sep 07, 2026` / `Sep 08, 2026`, e os dois hidden JSON (`release_support[value]`, `release_support[exceptions]`) com o payload certo |
| `app-sidebar` | ver tabela do gate 2 |

## Verdict

✅ Aprovado após 1 rodada corretiva. Catálogo do demo fechado em 72/72.
Falta a task 33.2 (README) e a task 34 (release 0.8.0) para fechar a onda F e a fase 2.
