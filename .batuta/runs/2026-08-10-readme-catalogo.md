# Run — task 33.2, README: catálogo completo, interatividade e matriz de compat

- `task_id`: `batuta/20260810-103752-readme-catalogo`
- Rota: medium → codex `gpt-5.6-sol`, reasoning medium
- Runtime: Compozy, `sess-e8332a63b86e0175` (uma sessão, sobreviveu — o retry foi para ela mesma)
- Worktree: `.batuta/worktrees/20260810-103752-readme-catalogo`, branch `batuta/20260810-103752-readme-catalogo`
- `initial_base_sha` = `attempt_base_sha` = `promotion_base_sha` = `7cee042`
- `final_sha` = **`75f7731`**, promovido por fast-forward
- Diff: 1 arquivo, 26 inserções / 12 remoções

## O que estava defasado

O README descrevia um pacote muito menor do que o que existe:

- tabela de componentes com ~35 dos 72;
- frase de abertura anunciando "27 static and 7 interactive";
- seção Interactivity nomeando 7 interativos (Dropdown, Dialog, Drawer, Tabs, Accordion, Tooltip, Popover);
- parágrafo de Livewire citando 3 estados modelable, quando 24 componentes expõem `x-modelable`;
- matriz de compat fixando `@lyra-ds/alpine` em `^0.2` e styles em `^0.4`.

## Entregue

- Tabela reagrupada em 9 categorias cobrindo **72/72**.
- Interactivity reescrita: os 30 componentes Alpine-backed nomeados; a garantia static-first
  reafirmada e **qualificada** pela exceção data-driven (`calendar`, `combobox`,
  `command-palette`, `file-upload`, `time-picker`, `toast-stack`, `weekly-schedule-editor`
  estampam por `x-for`, então essas regiões não existem no HTML servido); dual-mode do
  `data-table` documentado (server-side por padrão via `lyra:sort`, `clientSort` como opt-in
  lendo `data-sort-value`).
- Superfície modelable real: 24 componentes, agrupados por estado (`open`, `selected`,
  `value`) mais os específicos, com a nota de que alternativas se escolhem pelo atributo
  `x-modelable`.
- Matriz: `@lyra-ds/styles` `^0.4.2`, `@lyra-ds/alpine` `^0.3.0`.
- Frase de abertura corrigida para **42 static and 30 interactive**.

## Gate — rodada 1 (rejeitada)

| Checagem | Resultado |
| --- | --- |
| **Invariantes de entrega** | **❌ o executor não commitou** — árvore com `M README.md`, 0 commits acima de `7cee042` |
| Conteúdo | verificado à parte e aprovado (ver abaixo) |

O conteúdo estava certo; a falha foi de processo. O executor também **parou e reportou** a
frase obsoleta da linha 5 em vez de decidir sozinho ampliar o escopo — comportamento certo:
o brief dizia "as três seções nomeadas". A ampliação do Scope é decisão do maestro e foi
concedida na rodada corretiva. Lacuna do brief (minha): o Scope listou só `README.md`, sem
`WORK.md`, então a linha do `WORK.md` não pôde nascer dentro do commit candidato — foi
registrada no commit de trilha, seguindo o precedente das tasks do demo.

Retry na **mesma sessão** (estava `idle`/`healthy`), preservando o contexto do executor —
o contrato normal, que as tasks 33.1 e 31 não puderam usar por morte da sessão.

## Gate — rodada 2 (aprovada)

| Checagem | Resultado |
| --- | --- |
| Invariantes | count=1, pai `7cee042`, árvore limpa ✅ |
| Scope | 1 caminho (`README.md`) ✅ |
| Cobertura da tabela | **72/72** por loop próprio do maestro; 0 duplicatas, 0 nomes inventados ✅ |
| Contagem da abertura | 42 + 30 = 72; os 30 conferidos por `grep -l x-data` (29 arquivos) + `time-zone-picker`, que não tem `x-data` próprio mas compõe o `combobox` — divergência disclosed, não erro ✅ |
| Superfície modelable | 24 arquivos com `x-modelable`, e o mecanismo de opt-in é real: `$attributes->get('x-modelable')` em `slot-picker`, `weekly-schedule-editor` e `data-table` ✅ |
| Dual-mode do data-table | `lyra:sort` e `data-sort-value` conferidos no fonte ✅ |
| `vendor/bin/pest` | **1139 passed (6311 assertions)** — igual à task 32, como esperado num diff só de doc ✅ |
| `vendor/bin/pint --test` | passed ✅ |

Worktree sem `vendor/` na primeira tentativa de rodar a suíte: `composer install` (linha
`Install:` do perfil) resolveu, como o Passo 4 prevê.

## Verdict

✅ Aprovado após 1 rodada corretiva, promovido por fast-forward (`75f7731`).
Onda F com um item restante: task 34 — release 0.8.0, que fecha a fase 2.
