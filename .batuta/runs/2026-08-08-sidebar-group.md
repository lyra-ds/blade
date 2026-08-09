# Run — Componente SidebarGroup (task 5 do plano das ondas B–F, onda B)

- **task_id:** `batuta/20260809-013500-sidebar-group`
- **Data:** 2026-08-08 (UTC 2026-08-09)
- **Executor:** codex `gpt-5.6-sol`, **reasoning high**, via Compozy `sess-00968fe4cc99c422`
- **Roteamento:** o plano previa medium; escalado para complex antes do dispatch (props de array, três sub-elementos, divergência declarada). Decisão do maestro, anunciada ao usuário.
- **initial_base_sha = attempt_base_sha:** `d05ffaf` · **final_sha:** `549b264` · ff para `main`
- **Verdict:** aprovado no gate na primeira tentativa; commit feito pelo maestro (desvio declarado)

## Decisões fechadas pelo maestro ANTES do dispatch

O CSS decidiu o desenho: `.lyra-sbgroup--collapsed` no `@lyra-ds/styles` **só gira
o chevron** — não esconde os itens. Esconder é trabalho do componente (o React
desmonta). Isso obrigou quatro decisões que entraram no brief como fechadas:

1. **`x-data` sempre presente**, colapsável ou não — os botões de item precisam do
   binding para disparar `lyra:select`. `x-bind="label"` só quando colapsável
   (é assim que o binding define "colapsável", conforme o doc dele).
2. **Itens servidos no DOM com `x-show="!collapsed"`, não `<template x-if>`.**
   O binding oferece `x-if` como opção; rejeitada aqui. Conteúdo de `<template>`
   é inerte: um grupo colapsável sem Alpine não mostraria item nenhum, e —
   diferente do CookieBanner — essa quebra não é inerente, já que um grupo
   expandido é um estado no-JS completo. Divergência consciente do React, no
   docblock do componente.
3. **`x-cloak` só quando o grupo começa colapsado**, para os dois estados iniciais
   baterem com o React (expandido → visível sem Alpine; colapsado → oculto sem
   Alpine) e nada piscar durante o boot.
4. **O modificador `--collapsed` é servido** quando começa colapsado — o `:class`
   do binding usa object syntax justamente para reconciliar classe vinda do
   servidor (correção que saiu no alpine 0.1.1 para Accordion/Tabs).

Também foi fechado: seleção é **evento** (`lyra:select` com `{ id }` lido do
`data-id` servido), não prop de callback — é o contrato que o `app-sidebar` vai
consumir na task 7.

## Incidente — `peer disconnected` (5ª seguida)

Morreu antes do commit e antes até da linha do `WORK.md` (o gerador de guidelines
já tinha rodado). Report perdido pela quinta vez. Mesmo tratamento do CookieBanner:
verificação integral do maestro, linha do `WORK.md` escrita por mim, commit meu
com `Co-Authored-By` para os dois e desvio declarado no corpo.

Contexto novo desta rodada (levantado com o usuário): o daemon estava a **125% de
CPU há 23h** e o `compozy doctor` acusava `probe_timeout` — mas havia **outra
sessão do usuário em `prompting` ativo** no mesmo daemon. Ou seja, a hipótese que
sobrou é **contenção**, não daemon travado; restart mataria trabalho em voo e
provavelmente não resolveria. O usuário optou por seguir no Compozy aceitando o
custo (perder reports) em vez do fallback para subprocess direto.

## Verificação do maestro (nada aceito do executor)

Invariantes: 1 commit; `parent(549b264)` = `d05ffaf`; árvore limpa. ✔
Scope: exatamente os 5 caminhos da lista fechada. ✔

- `vendor/bin/pest` → **644 passed, 2546 assertions** (628 antes), 11 testes no componente ✔
- `vendor/bin/pint --test` → passed ✔
- `php bin/generate-boost-guidelines` → idempotente ✔
- **Render real conferido pelo maestro** (teste efêmero, removido): grupo
  colapsável+colapsado sai com `lyra-sbgroup lyra-sbgroup--collapsed`,
  `x-data="lyraSidebarGroup({ defaultCollapsed: true })"` (booleano JS de verdade,
  não `"1"`), label como `<button>` com `aria-expanded="false"` e o chevron SVG
  idêntico ao do React, itens com `x-show`+`x-cloak`; grupo simples sai com label
  como `<div>` e sem nenhum dos dois. Casos de borda conferidos à mão: **badge `0`
  renderiza** (React testa `!= null`, não truthiness) e `data-id` com aspas e `<`
  sai escapado (`a&quot;b&lt;c`). ✔

## Divergências menores aceitas (não valem rodada)

- `defaultCollapsed` só é passado como `true` quando o grupo é colapsável; no React
  o `useState(defaultCollapsed)` guarda o valor mesmo sem `collapsible`, mas o
  `showItems` o torna inobservável. Sem efeito visível.
- `icon` com o valor string `"0"` não renderiza no Blade (`(bool) "0" === false`)
  e renderizaria no React. Ícone `"0"` não é caso real; anotado e não corrigido.

## Contrato para o `app-sidebar` (task 7)

O `app-sidebar` compõe `<lyra:sidebar-group>` e **não** precisa de props de
callback: cada item já serve `data-id` e o binding dispara `lyra:select` com
`{ id }`, que o consumidor escuta no grupo (ou acima, por bubbling). Os grupos
colapsáveis já trazem o próprio `x-data`, então o `app-sidebar` não precisa de
estado Alpine próprio para eles.
