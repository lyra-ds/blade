# Plan — Ondas B–F do Blade sobre o `@lyra-ds/alpine` 0.2.0

**Goal:** Fechar o catálogo interativo de `lyra-ds/blade` — 23 componentes Blade
sobre os 21 state machines novos publicados no `@lyra-ds/alpine` 0.2.0 (mais o
`app-sidebar`, estático que estava bloqueado). Uma release por onda, galeria do
demo varrida ao fim de cada onda, guidelines do Boost regeneradas a cada item.
**Created:** 2026-08-08
**Status:** approved (2026-08-08)

## Tasks

### Onda B — baratos, sem upstream pendente (release 0.4.0)

- [x] 1. Matriz de compatibilidade do README: `@lyra-ds/alpine` `^0.1.1` → `^0.2` — maestro (claude)
      Accept: `README.md:83` atualizado; `npm view @lyra-ds/alpine version` = 0.2.0 confirmado. **FEITO** — commit `b57c0e3`.
- [x] 2. Bump do `blade-demo` para `@lyra-ds/alpine` `^0.2.0` + `npm install` + `npm run build` — codex (medium)
      Accept: `blade-demo/package.json` em `^0.2.0`, lockfile atualizado, `vite build` verde, galeria atual (50 componentes) sem regressão visual óbvia; commit no repo do demo. **FEITO** — commit `1313bb8` no demo, 0.2.0 instalado, build 161ms, 3/3 testes, view:cache ok.
- [x] 3. Componente `code-block` (binding `lyraCodeBlock`, S) — codex (medium)
      Accept: markup servido com `x-bind` `copyButton`/`status`, `data-copy-text` opcional, live-region `role="status"`; class-emission contra o fixture do React; suíte verde; guidelines regeneradas idempotentes; pint limpo. **FEITO** — commit `34457c0`, suíte 601 → 617 (2401 assertions); 1 rodada corretiva (`type="button"` servido).
- [x] 4. Componente `cookie-banner` (`lyraCookieBanner`, S) — codex (medium)
      Accept: `x-cloak` obrigatório documentado (inversão do React), botões accept/essentials emitindo `lyra:accept`/`lyra:essentials`, `--closing` NUNCA servido (é runtime do binding); class-emission; suíte verde; guidelines; pint. **FEITO** — commit `974b953`, suíte 617 → 628 (2454 assertions).
- [x] 5. Componente `sidebar-group` (`lyraSidebarGroup`, S) — codex (medium)
      Accept: `collapsed` via `x-modelable`, `aria-expanded` no label-botão, itens desmontados quando colapsado, evento de seleção; class-emission; suíte verde; guidelines; pint. **Destrava a task 7.** **FEITO** — commit `549b264`, suíte 628 → 644 (2546 assertions); itens ficam servidos com `x-show` em vez do `x-if` do React (decisão fechada, ver trail).
- [x] 6. Componente `segmented-control` (`lyraSegmentedControl`, S/M) — codex (medium)
      Accept: radiogroup DOM-driven com roving tabindex, `value` modelable, setas circulares pulando disabled, Home/End; não confundir com o `segmented-ring` já existente; class-emission; suíte verde; guidelines; pint. **FEITO** — commit `2541a2e`, suíte 644 → 657 (2615 assertions); roving tabindex servido igual ao React nos 4 casos de borda.
- [ ] 7. Componente `app-sidebar` (estático, consome `sidebar-group`) — codex (medium)
      Accept: composição estática pura (zero hooks no React), renderizando `<x-lyra::sidebar-group>`; class-emission; suíte verde; guidelines; pint.
- [ ] 8. Componente `workspace-switcher` (`lyraWorkspaceSwitcher`, M) — codex complex (gpt-5.6-sol, reasoning high)
      Accept: listbox-popover com `open` modelable, navegação por foco real, flip placement, evento de troca de workspace; class-emission; suíte verde; guidelines; pint.
- [ ] 9. Varredura da galeria do `blade-demo` — seção da onda B (6 componentes) — codex (medium)
      Accept: cada componente novo com seção na galeria, `artisan test` verde, `view:cache` ok; commit no repo do demo.
- [ ] 10. Release 0.4.0 — maestro (claude)
      Accept: release PR do release-please revisado, matriz de compat conferida, checks verdes, merge, tag publicada no Packagist.

### Onda C — médios independentes + infra (release 0.5.0)

- [ ] 11. Componente `table-of-contents` (`lyraTableOfContents`, M) — codex (medium)
      Accept: links âncora servidos, `activeId` modelable, `aria-current="location"`; scroll-spy é do binding (nada de JS no Blade); class-emission; suíte verde; guidelines; pint.
- [ ] 12. Componente `time-input` (`lyraTimeInput`, M) — codex complex
      Accept: input `role="spinbutton"` com aria-valuemin/max/now/text servidos, steppers `tabindex="-1"`, `selected` modelable (`HH:mm`|null), semântica tripla null/undefined/string preservada nos props; class-emission; suíte verde; guidelines; pint.
- [ ] 13. Componente `bottom-sheet` (`lyraBottomSheet`, M) — codex complex
      Accept: irmão estrutural de dialog/drawer (`overlay`/`panel`/`title`/`close`), `open` modelable, `x-cloak`, markup in-place sem portal; class-emission; suíte verde; guidelines; pint.
- [ ] 14. Componente `file-upload` (`lyraFileUpload`, M) — codex complex
      Accept: dropzone + input file oculto, `items`/`dragging` modelable, lista em runtime via `<template x-for>` servido pelo Blade (exceção ao markup-servido, documentada no README); evento `lyra:files`; class-emission; suíte verde; guidelines; pint.
- [ ] 15. Componente `file-manager` (`lyraFileManager`, M) — codex complex
      Accept: busca DOM-driven por `data-name`, `view` (`list|grid`) modelable com as duas árvores servidas, empty state, breadcrumb server-side, menus por item reusando `<x-lyra::dropdown>`; class-emission; suíte verde; guidelines; pint.
- [ ] 16. Infra de tema: store `theme` + diretiva Blade de anti-flash — maestro decide a API, codex complex implementa
      Accept: decisão fechada sobre o nome/forma (`@lyraThemeScript` vs parcial publicável) registrada no plano antes de delegar; script inline bloqueante no `<head>` setando `data-theme` a partir de `localStorage['lyra-theme']`; seção no README; testes cobrindo o HTML emitido pela diretiva; suíte verde; pint. **Único item com decisão de API aberta.**
- [ ] 17. Varredura da galeria do `blade-demo` — seção da onda C + toggle de tema — codex (medium)
      Accept: 5 componentes novos + demonstração do theme store na galeria; `artisan test` verde; commit no demo.
- [ ] 18. Release 0.5.0 — maestro (claude)
      Accept: idem task 10.

### Onda D — datas (release 0.6.0)

- [ ] 19. Componente `calendar` (`lyraCalendar`, L) — codex complex
      Accept: grade de 42 dias e views days/months/years servidas via `x-for` sobre os getters do binding, roving tabindex, `selected` modelable (Date ou `{start,end}`), min/max, slot para `renderDayMarker`; class-emission; suíte verde; guidelines; pint. **Destrava 20 e 21.**
- [ ] 20. Componente `date-picker` (`lyraDatePicker`, M) — codex complex
      Accept: casca fina compondo `calendar` + popover (desktop) / bottom-sheet (mobile <640px) sem duplicar markup; `selected` e `open` modelable; label/hint/error de formulário; input hidden para submit form-nativo; class-emission; suíte verde; guidelines; pint.
- [ ] 21. Componente `date-range-picker` (`lyraDateRangePicker`, M) — codex complex
      Accept: mesma casca da task 20 com `{start,end}`, fecha só com range completo, `incompleteRange` no label; class-emission; suíte verde; guidelines; pint.
- [ ] 22. Componente `time-picker` (`lyraTimePicker`, M) — codex complex
      Accept: listbox de opções servido via `x-for` sobre `options()`, `selected`/`open` modelable, composição popover/bottom-sheet, scroll da opção selecionada ao abrir; class-emission; suíte verde; guidelines; pint.
- [ ] 23. Varredura da galeria do `blade-demo` — seção Datas e horas — codex (medium)
      Accept: 4 componentes na galeria incluindo o modo mobile; `artisan test` verde; commit no demo.
- [ ] 24. Release 0.6.0 — maestro (claude)
      Accept: idem task 10.

### Onda E — busca APG (release 0.7.0)

- [ ] 25. Componente `combobox` (`lyraCombobox`, L) — codex complex
      Accept: data-driven (`options` na factory + `x-for` sobre `filtered()`), `value`/`open` modelable, `aria-activedescendant` com foco fixo no input, grupos como headings; cláusula de honestidade do PRD §3 — só entra com APG completa (teste de teclado + axe); class-emission; suíte verde; guidelines; pint. **Destrava 26.**
- [ ] 26. Componente `time-zone-picker` (`lyraTimeZonePicker`, S sobre o combobox) — codex (medium)
      Accept: camada de dados sobre o combobox com as 27 zonas curadas, grupos Detected/Recent, hora local com tick de 60s; `value` IANA modelable; prop para substituir a lista; class-emission; suíte verde; guidelines; pint.
- [ ] 27. Componente `command-palette` (`lyraCommandPalette`, L) — codex complex
      Accept: overlay + filtro active-descendant + hotkey Cmd/Ctrl+K + modo inline, `open` modelable, seleção via evento; markup in-place sem portal; APG completa (teclado + axe); class-emission; suíte verde; guidelines; pint.
- [ ] 28. Varredura da galeria + release 0.7.0 — codex (medium) e maestro (claude)
      Accept: 3 componentes na galeria; release cortada como na task 10.

### Onda F — pesados e promovidos (release 0.8.0, fecha o catálogo)

- [ ] 29. Componente `data-table` (`lyraDataTable`, L) — codex complex
      Accept: seleção modelable com `data-row-id` servido, select-all indeterminate, `aria-sort` reativo; **sort dual-mode com default server-side** (headers como links/forms do app) e opt-in `client-sort` lendo `data-sort-value`; class-emission; suíte verde; guidelines; pint.
- [ ] 30. Componente `recurrence-selector` (`lyraRecurrenceSelector`, M — promovido) — codex complex
      Accept: estado JSON-safe modelable, presets + controles custom nativos, date-picker embutido reusando a task 20, `conflictsOne`/`conflictsMany` como templates (não closures); class-emission; suíte verde; guidelines; pint.
- [ ] 31. Componente `slot-picker` (`lyraSlotPicker`, L — promovido) — codex complex
      Accept: calendar + time-zone-picker embutidos, dia local e zona IANA modelable, estados empty/loading/hold, labels por template de interpolação; class-emission; suíte verde; guidelines; pint.
- [ ] 32. Componente `weekly-schedule-editor` (`lyraWeeklyScheduleEditor`, L — promovido) — codex complex
      Accept: schedule e exceções modelable, composições de time-input/popover/date-picker, cópia de dia, validação inline de range, submit via inputs hidden; templates `{day}` no lugar das closures do React; class-emission; suíte verde; guidelines; pint.
- [ ] 33. Varredura final da galeria + README (catálogo completo) — codex complex
      Accept: galeria com o catálogo inteiro, README com a seção de interatividade cobrindo os padrões novos (exceção `x-for` data-driven, tema, dual-mode do data-table) e matriz de compat revisada.
- [ ] 34. Release 0.8.0 — maestro (claude)
      Accept: idem task 10; `docs/spec-alpine-proximas-ondas.md` marcado como executado e `WORK.md` fechando a fase 2.

## Decisions and context

**O que mudou em 2026-08-08:** o `@lyra-ds/alpine` publicou **0.2.0** (npm
`latest`, commit `3bc509e` no `origin/main` do monorepo `lyra`). São 21 state
machines novos, todos aditivos — nenhuma mudança nos 7 bindings que os
componentes Blade atuais já usam, então o bump para `^0.2` é seguro para o
`main` de hoje. `@lyra-ds/styles` e `@lyra-ds/react` seguem em **0.4.1**, e o
`packages/styles/components/` já traz as pastas `scheduling`, `files` e `data`
— as classes `.lyra-*` de tudo que esta onda vai vestir **já existem em 0.4.1**;
não há dependência de uma release nova de styles.

**Trilho herdado:** este plano executa o lado Blade de
`docs/spec-alpine-proximas-ondas.md` §8 (ondas B–F, validadas com o usuário em
2026-08-08). A spec continua sendo a fonte de verdade por componente — §4 traz
os `x-bind` esperados, o estado modelable e as observações de porte de cada um.
Os três itens que a spec listava como **adiados** (recurrence-selector,
slot-picker, weekly-schedule-editor) foram promovidos e entregues no alpine
0.2.0, então entram na onda F. **`calendar-view` continua fora** — não foi
portado no alpine e permanece adiado.

**Estado da onda B ao começar:** os estáticos `checkbox-group` e `radio-group`
já foram entregues (`572993a`, `228e2d3`). Sobram os 5 interativos baratos +
`app-sidebar`, que estava bloqueado porque renderiza SidebarGroup — por isso a
task 7 vem depois da 5.

**Decisões fechadas que valem para toda a onda** (não reabrir sem o usuário):

- Paridade de API com o React (`props.json` / lyra-ds.dev é a fonte; nunca
  inventar API) e o gate central de **class-emission** — cada Blade emite a
  string de classes exata do React, com fixture em `tests/Fixtures/class-emission/`.
- Nada de CSS/JS no pacote: comportamento vem do `@lyra-ds/alpine` (peer do
  consumidor), aparência do `@lyra-ds/styles`.
- Sem portal: markup fica onde o consumidor pôs (bottom-sheet e
  command-palette inclusive).
- Estado controlável via `x-modelable` no root (Livewire com `wire:model`);
  callbacks do React viram eventos custom via `$dispatch`.
- Componentes data-driven (combobox, command-palette, calendar, time-picker,
  file-upload) usam `<template x-for>` servido no Blade — exceção controlada ao
  markup-servido, a documentar no README.
- BottomSheet entra **antes** dos pickers (onda C precede a D); os 3 pickers
  overlay lançam já com a troca Popover ↔ BottomSheet abaixo de 640px.
- DataTable: sort dual-mode com **default server-side**.

**Ritmo e rotina de cada item (padrão provado nas ondas A e core):** TDD
(RED → GREEN → VERIFY), um componente por delegação, worktree para medium+,
brief carregando as lições dos itens anteriores, cross-review em 3 lentes nos
complex, e sempre: `vendor/bin/pest`, `vendor/bin/pint`, regeneração idempotente
do `php bin/generate-boost-guidelines` (há teste de frescor que exige isso a
cada componente novo). Commit convencional creditando o executor real
(`Co-Authored-By` + linha de corpo dizendo quem implementou e quem verificou).

**Cadência acordada com o usuário (2026-08-08):** guidelines a cada item,
galeria do `blade-demo` uma varredura por onda, **uma release por onda**
(0.4.0 → 0.8.0), com a matriz de compat do README revisada em cada corte.

**Armadilhas conhecidas do ambiente:** a lane trivial do opencode está
suspensa (silent hang 2/2) — triviais vão direto para o codex; scouts só com
escopo de ~6 arquivos e brief inline. O Compozy já bloqueou worktrees novos por
policy: worktree "vazio" com cursor baixo = checar policy antes de culpar o
modelo, com fallback para subprocess direto. Cache de views compiladas engana
teste pós-stash — tocar os blades antes de re-rodar.

**Único ponto de decisão aberto dentro do plano:** a forma da infra de tema
(task 16) — diretiva `@lyraThemeScript` versus parcial publicável. Fechar com o
usuário antes de delegar.

**`calendar-view` — hipótese Blade-first, decidir depois da onda F**
(conversa com o usuário, 2026-08-08). Não é task deste plano. A ideia registrada
é implementá-lo **direto no Blade, sem binding upstream**, porque os eventos são
dados do servidor no momento do render: o posicionamento geométrico dos chips
(top/height por minuto, 48px/h) — a parte mais cara do componente React — vira
cálculo em PHP com style inline, e a troca de view (dia/semana/mês) vira
navegação server-side. Sobrariam de comportamento real apenas o popover do
evento (reusa `<x-lyra::popover>`), o slot-create por coordenada de mouse (um
`x-data` pequeno e local) e, opcionalmente, o indicador de "agora". Bônus: os
`kind`s de evento (`session`, `program-session`, `pending`, `block`, `external`)
são vocabulário de produto — como slots do consumidor eles não viram API pública
do design system, que era a objeção da spec §5. Se essa leitura se confirmar, o
alpine não precisa de `lyraCalendarView` e o Blade sai na frente do React aqui.
