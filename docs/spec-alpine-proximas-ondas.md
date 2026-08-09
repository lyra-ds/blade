# Spec — bindings `@lyra-ds/alpine` para as próximas ondas da fase 2

> Para: sessões conduzindo `lyra-ds/blade` e o monorepo `lyra` (packages/alpine).
> De: análise dos fontes React, 2026-08-07 (Batuta, lyra-ds/blade).
> Complementa `docs/prd-fase-2-alpine.md`; não reabre decisões fechadas.
> O lado upstream desta spec foi entregue como PRD autocontido ao monorepo:
> `lyra/.batuta/prd-alpine-ondas-b-f.md` (2026-08-08, commit `ade1488`) — a sessão do alpine
> executa por ele; este documento rege o lado Blade.
> Fonte de verdade analisada: `lyra/packages/react/src/<nome>/<nome>.tsx` de cada
> componente, contra o padrão dos 7 bindings existentes (`lyra/packages/alpine/src`).

## 1. Situação

A onda core entregou os 7 bindings (dropdown, dialog, drawer, tabs, accordion,
tooltip, popover) e os 7 Blade correspondentes (release 0.3.0). Restam **27
componentes** do catálogo React sem par Blade. Este documento especifica o que
cada um exige do `@lyra-ds/alpine` — porque **nenhum binding além dos 7 existe
ainda**: toda onda nova de interativos tem uma dependência upstream no monorepo.

Classificação por análise de estado no fonte React:

- **3 estáticos** (zero hooks): `app-sidebar`, `checkbox-group`, `radio-group` —
  não precisam de binding; entram como componentes Blade fase-1-style.
  (Correção 2026-08-08: `app-sidebar` NÃO é estático — renderiza SidebarGroup;
  spec própria do `lyraAppSidebar` entregue upstream.)
- **24 interativos** — especificados abaixo.
- **Lacuna corrigida (2026-08-09):** o `toast-provider` ficou fora da análise
  original (filtrado junto com index/internal). É ele quem dá auto-dismiss ao
  Toast no React (duration 4000, `0` desliga, dismiss por id). Spec entregue
  upstream: `lyra/.batuta/prd-alpine-toasts.md` — `Alpine.store('lyraToasts')` +
  `lyraToastStack` (candidato à onda C); o lado Blade re-liga o botão de fechar
  no fluxo dinâmico e mantém o estático como degradação.

## 2. Vereditos (fechados com o usuário, 2026-08-08)

| Veredito | Componentes |
|---|---|
| **Portar** (genéricos de DS) | bottom-sheet, combobox, command-palette, segmented-control, sidebar-group, table-of-contents, calendar, date-picker, date-range-picker, time-input, time-picker, time-zone-picker, data-table, file-upload, file-manager, code-block, cookie-banner, theme-provider, workspace-switcher |
| **Adiar** (genéricos, custo alto ou bloqueados) | recurrence-selector (bloqueado por date-picker), weekly-schedule-editor (mini-app; depende de time-input + date-picker + popover), slot-picker (mini-app de booking; depende de calendar + time-zone-picker), calendar-view (semi-genérico; maior componente do catálogo) |
| **Excluir** (composição de produto) | create-workspace-dialog (coberto por `lyraDialog` + form Blade; strings `lyra.dev/` hardcoded — vale como receita na doc, não como binding) |

## 3. Grafo de dependências entre bindings

```
lyraCalendar ──┬─→ lyraDatePicker ──→ recurrence-selector (adiado)
               ├─→ lyraDateRangePicker
               └─→ slot-picker (adiado)
lyraCombobox ────→ lyraTimeZonePicker ──→ slot-picker (adiado)
lyraBottomSheet ─→ modo mobile de date/date-range/time-picker (<640px)
lyraTimeInput ───→ weekly-schedule-editor (adiado)
lyraPopover (existente) ──→ date/date-range/time-picker (modo desktop)
lyraDropdown (existente) ─→ workspace-switcher, menus do file-manager
```

**Decisão fechada (2026-08-08) — BottomSheet mobile**: `lyraBottomSheet`
entra ANTES dos pickers (onda C precede a D). Os 3 pickers overlay (date,
date-range, time) lançam já com a troca Popover ↔ BottomSheet abaixo de
640px (`matchMedia`), com paridade mobile completa desde a v1.

## 4. Spec por componente

Formato: **nome (complexidade)** — estado modelable; o que o binding faz;
x-bind objects; observações de porte.

### 4.1 Overlays e navegação

- **lyraBottomSheet (M)** — modelable `open`. Clone estrutural do
  dialog/drawer: presence/`--closing` com fallback 250ms, focus trap, scroll
  lock, restore do opener, Escape, backdrop com guard de mousedown (drag do
  painel até o overlay não fecha). x-bind: `overlay`, `panel`, `close`,
  `title`. Sem portal (markup in-place, decisão já estabelecida).
- **lyraCombobox (L)** — modelable `value` (e `open`). Select pesquisável
  APG por `aria-activedescendant` (foco fixo no input); filtro NFD sem
  diacríticos sobre `label`+`keywords`; grupos como headings; clamp
  ArrowUp/Down, Home/End, Enter, Escape; scroll manual da opção ativa;
  outside-mousedown; flip placement (helper existente). **Data-driven**:
  `options` na factory + `x-for` sobre `filtered()` — mais fiel que
  DOM-driven (filtro + grupos + trailing). x-bind: `trigger`, `pop`,
  `search`, `list`, `option`.
- **lyraCommandPalette (L)** — modelable `open`; seleção via `$dispatch`.
  Soma dialog (presence/trap/scroll-lock) + combobox (filtro, active-
  descendant, scroll) + hotkey global Cmd/Ctrl+K armado em `init`/removido
  em `destroy` + modo `inline`. `groups` como dados + `x-for`. x-bind:
  `overlay`, `panel`, `input`, `list`, `group`, `groupLabel`, `option`,
  `trigger`.
- **lyraSegmentedControl (S/M)** — modelable `value`. Radiogroup com roving
  tabindex e selection-follows-focus; setas circulares pulando disabled,
  Home/End. DOM-driven (lê `[role="radio"]` + data-attribute, como o
  dropdown lê menuitems). x-bind: `group`, `option`.
- **lyraSidebarGroup (S)** — modelable `collapsed`. Disclosure de um
  booleano: `aria-expanded` no label-botão, classe `--collapsed`,
  itens desmontados (`x-if`) quando colapsado; seleção via evento custom.
  x-bind: `root`, `labelButton`, `items`, `item`.
- **lyraTableOfContents (M)** — modelable `activeId`. Markup trivial
  (links âncora, `aria-current="location"`); o trabalho real é o
  scroll-spy: `IntersectionObserver` com banda de 30% superior
  (`rootMargin 0 0 -70%`), fallbacks de borda (fim do documento → último;
  banda vazia → heading precedente → primeiro), listeners scroll/resize,
  seed via `queueMicrotask`, SSR-safe. Porta como helper em `internal/`.
  DOM-driven (lê `a[href^="#"]` do markup). x-bind: `nav`, `link`.

### 4.2 Data e hora

- **lyraCalendar (L)** — modelable `selected` (Date ou `{start, end}`).
  Grade fixa de 42 dias; 3 views (days/months/years); roving tabindex com
  foco pós-render (padrão pendingFocus); teclado completo (setas ±1/±7,
  Home/End na semana, PageUp/Down ±mês com clamp); range com reordenação
  de pontas; min/max/`isDateDisabled`; tudo `Intl.DateTimeFormat` + `Date`
  nativo (zero libs). Células via `x-for` sobre getters `days()`/
  `months()`/`years()`. `renderDayMarker` vira slot/template do consumidor.
  x-bind: `prevButton`, `nextButton`, `viewLabel`, `weekday`, `day`,
  `monthCell`, `yearCell`, `todayButton`. **Destrava os dois pickers de data.**
- **lyraDatePicker (M)** — modelable `selected` (e `open`). Casca fina:
  compõe `lyraCalendar` + padrão `lyraPopover` (desktop) / `lyraBottomSheet`
  (mobile, ver §3); fecha ao selecionar; texto do trigger via Intl;
  label/hint/error de formulário. x-bind: `trigger`, `panel`.
- **lyraDateRangePicker (M)** — modelable `selected` (`{start, end}`).
  Mesma casca do date-picker (candidato a factory comum interna); só fecha
  com range completo; `onChange` a cada ponta; separador/`incompleteRange`
  nos labels.
- **lyraTimeInput (M)** — modelable `selected` (`HH:mm` ou null). Input
  mascarado `role="spinbutton"` (aria-valuemin/max/now/text); parsing
  tolerante ("9", "0930", "9h30") com semântica tripla null/undefined/
  string preservada; ArrowUp/Down ±step, Shift=±60; clamp em min/max;
  steppers `tabIndex=-1`. Standalone (time-picker NÃO o usa). x-bind:
  `input`, `stepUp`, `stepDown`.
- **lyraTimePicker (M)** — modelable `selected`, `open`. Lista listbox
  gerada de min→max por step (default 30); clamp nas setas (não circular),
  Home/End; scroll da opção selecionada ao abrir (`$watch('open')` +
  `whenVisible`, padrão do dropdown); exibição via Intl h23. Compõe
  Popover/BottomSheet. x-bind: `trigger`, `list`, `option` (x-for sobre
  `options()`).
- **lyraTimeZonePicker (S se lyraCombobox existir; L sem ele)** — modelable
  `value` (IANA). Camada de dados sobre o Combobox: lista curada de 27
  zonas com grupos pinados Detected/Recent, offset GMT via
  `formatToParts`/`shortOffset`, hora local por opção com tick de 60s
  (`init`/`destroy`). Keywords curadas têm viés pt-BR — substituíveis via
  `zones`. **Pré-requisito duro: lyraCombobox.**

### 4.3 Dados e arquivos

- **lyraDataTable (L)** — modelable `sorting` (`{key, dir}|null`) e
  `selected` (ids). Seleção é toggle puro (select-all com indeterminate via
  `$watch`, checkbox por linha com `@click.stop`, classes de linha,
  ids via `data-row-id` servido pelo Blade). **Sort — decisão fechada
  (2026-08-08): dual-mode com default server-side.** Default: headers
  renderizam links/forms do app e o Alpine só gerencia seleção +
  `aria-sort`/classes — modo honesto para tabelas paginadas (sort
  client-side ordenaria só a fatia visível). Opt-in `clientSort: true`
  para tabelas pequenas sem paginação: reordena os `<tr>` já servidos via
  `insertBefore` lendo `data-sort-value` (reuso de nós, comparador
  `localeCompare` numeric/base portado do React — paridade exata).
  x-bind: `selectAll`, `rowCheckbox`, `row`, `sortButton`, `header`.
- **lyraFileUpload (M)** — modelable `items`, `dragging`. Dropzone
  (botão nativo + input file oculto), drag-and-drop com preventDefault,
  validação `maxSizeMB`, progresso **simulado** por setInterval 120ms
  (cleanup em `destroy`), remoção por item. A lista nasce em runtime →
  `x-for` sobre template servido no Blade (idiomático, documentar como
  exceção ao markup-servido). Arquivos reais expostos via evento
  `lyra:files`; upload real é integração do produto por cima. x-bind:
  `zone`, `input`, `removeButton`.
- **lyraFileManager (M)** — modelable `view` (`list|grid`), `query`.
  Shell genérico: busca filtra por `data-name` (toggle de `hidden`, sem
  re-render), duas árvores servidas alternadas por `x-show`, contagem de
  vazio, breadcrumb server-side; menus de ação por item reusam
  `lyraDropdown`. Ações (open/rename/delete) são slots com links/forms do
  app. x-bind: `searchInput`, `listButton`, `gridButton`, `listPane`,
  `gridPane`, `item`, `empty`.
- **lyraCodeBlock (S)** — estado `copied` + timeout 1500ms. Clipboard API
  com fallback silencioso; live-region `role="status"`; texto de
  `data-copy-text` ou `textContent` do pre. Todo o painel é server-side.
  x-bind: `copyButton`, `status`.
- **lyraCookieBanner (S)** — modelable `visible`. `init()` lê localStorage
  (try/catch → mostra no erro); decide → persiste + `--closing` +
  `animationend` esconde; eventos `lyra:accept`/`lyra:essentials`.
  **Requer `x-cloak`** para não piscar em quem já consentiu (inversão do
  React, que não monta até checar). x-bind: `root`, `essentialsButton`,
  `acceptButton`.

### 4.4 Infra de tema (forma diferente)

- **theme store (S)** — não é `Alpine.data()`: vira `Alpine.store('theme')`
  (chosen/resolved/dark/setTheme/toggle) + `data-theme` no `<html>` +
  localStorage com sync cross-tab (`storage` listener) + `matchMedia`
  para system. O anti-flash exige script inline bloqueante no `<head>` —
  que o Blade fornece naturalmente (`@lyraThemeScript` ou parcial),
  melhor que o React consegue. Chave `lyra-theme`.

## 5. Adiados — o que custariam (referência para decisão futura)

- **recurrence-selector (M)** — RecurrenceRule é subconjunto de RRULE
  (RFC 5545), genérico; controles nativos, estado JSON, markup servível.
  Único bloqueio: o DatePicker embutido (fim "on a date"). Melhor candidato
  a promover assim que `lyraDatePicker` existir. `describeRecurrence` porta
  como utilitário.
- **weekly-schedule-editor (L)** — domínio "working hours" genérico
  (shape Cal.com/Google), mas composto rico: depende de time-input,
  date-picker e popover de cópia, e as listas (ranges por dia, exceções)
  são client-side → `x-for` sobre JSON, com submit via input hidden.
- **slot-picker (L)** — mini-app de booking (holds com expiry, countdown
  1s, next-available, revalidação server-side). Depende de calendar +
  time-zone-picker; pílulas 100% derivadas de dados com re-agrupamento
  Intl por timezone. Fronteira com excluir: só se justifica se o produto
  Blade-side renderizar essa tela.
- **calendar-view (L)** — 566 linhas, maior do catálogo: 3 views,
  posicionamento geométrico de chips por minuto (48px/h), slot-create por
  coordenada de mouse, popover caseiro com 3 listeners globais. Mecanismo
  genérico, mas os `kind`s de evento (`session`, `program-session`,
  `pending`, `block`, `external`) são vocabulário do produto. Sem teclado
  próprio no grid (delegado ao app, documentado no fonte).

## 6. Helpers compartilhados a extrair em `internal/`

1. **presence** — mounted/closing + `animationend` + fallback 250ms
   (bottom-sheet, command-palette, cookie-banner; já implícito em
   dialog/drawer).
2. **focus trap + restore de opener** (bottom-sheet, command-palette; já
   existe para dialog/drawer — unificar).
3. **activedescendant listbox** — foco fixo no input, reset de índice ao
   filtrar, scroll manual do ativo (combobox, command-palette).
4. **scroll-spy** (table-of-contents).
5. **flip-placement e when-visible** — já existem; reusar.
6. **date utils** — grade de 42 dias, clamp de mês, range ordering,
   formatters Intl (calendar, pickers). Zero libs externas em todo o lote.

## 7. Padrões e decisões transversais

- `useControllableState` do React ↔ `x-modelable` — mapeamento direto já
  provado na onda core; vale para todos os itens acima.
- **Sem portal**: bottom-sheet e command-palette são portalados no React;
  em Alpine o markup fica onde o consumidor pôs (decisão da onda core,
  manter).
- **Render props → slots**: `renderDayMarker`, `renderEventPopover`,
  `toolbarActions` viram slots/templates Blade; o binding só expõe estado.
- **Data-driven vs DOM-driven**: combobox, command-palette, calendar,
  time-picker e file-upload precisam de `x-for` sobre dados/getters
  (documentar como exceção controlada ao markup-servido); segmented-control,
  sidebar-group, table-of-contents, data-table (seleção) e todo o resto
  são DOM-driven puros.
- **Callbacks → eventos custom** (`$dispatch`): padrão para onSelect/
  onFiles/onAccept etc.; Livewire continua via `x-modelable`/entangle.
- Cláusula de honestidade do PRD §3 segue valendo para combobox/
  command-palette: só entram com qualidade APG completa.

## 8. Ondas (validadas com o usuário, 2026-08-08)

| Onda | Itens | Racional |
|---|---|---|
| **B — baratos sem upstream novo** | 3 estáticos (app-sidebar, checkbox-group, radio-group) + code-block, cookie-banner, sidebar-group, segmented-control, workspace-switcher | 8 componentes; bindings S, um M pequeno; destrava volume rápido |
| **C — médios independentes** | table-of-contents, time-input, bottom-sheet, file-upload, file-manager, theme store | Helpers novos (scroll-spy, presence unificado) + infra de tema |
| **D — datas** | calendar → date-picker, date-range-picker, time-picker | Depende de lyraCalendar e da decisão BottomSheet (onda C a resolve) |
| **E — busca APG** | combobox → time-zone-picker, command-palette | Os dois L de busca + helper activedescendant |
| **F — pesados/opcionais** | data-table (dual-mode, default server-side), promovidos dos adiados | Fecha o catálogo |
