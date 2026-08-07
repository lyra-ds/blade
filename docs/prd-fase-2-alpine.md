# PRD fase 2 — integração Alpine.js dos componentes interativos

> Para: sessões conduzindo `lyra-ds/blade` e o monorepo `lyra`.
> De: conversa de desenho com o usuário, 2026-08-06 (Batuta, lyra-ds/blade).
> Complementa `docs/prd.md` (§2.2 e §3 fase 2); não reabre decisões fechadas.

## 1. Decisões desta fase (fechadas com o usuário, 2026-08-06)

1. **Escopo**: o desenho vale para TODOS os interativos do catálogo (35
   classificados INTERACTIVE), mas a onda 1 implementa só o núcleo:
   **Dropdown, Dialog, Drawer, Tabs, Accordion, Tooltip, Popover**.
   Pesados (data-table, date-picker, combobox, command-palette, calendar…)
   vêm em ondas seguintes com o padrão provado. Combobox/CommandPalette
   mantêm a cláusula de honestidade do PRD §3: só entram se Alpine der
   conta com qualidade APG.
2. **Mecanismo**: o comportamento vive num pacote npm novo
   **`@lyra-ds/alpine`**, um plugin Alpine com um `Alpine.data()` por
   componente (`lyraDialog`, `lyraTabs`, `lyraAccordion`…). O Blade emite
   apenas markup inicial + bindings (`x-data`, `x-bind`s nomeados,
   `x-cloak`). Nenhum JS no pacote Composer — mesma filosofia do
   CSS-via-npm (PRD §2.1).
3. **Repo**: `@lyra-ds/alpine` nasce no monorepo principal, em
   `lyra/packages/alpine`, ao lado de `styles` e `react` — reusa
   changesets, vitest browser e as fixtures/`props.json` na fonte. O
   trabalho lá sai pela sessão Batuta do repo principal.
4. **Livewire é primeira classe na onda 1**: todo estado controlável expõe
   `x-modelable`, e este repo ganha `livewire/livewire` como dev-dep com
   testes de integração reais (entangle) na suíte.

## 2. Arquitetura

Dois pacotes, um contrato:

- **`@lyra-ds/alpine`** (monorepo `lyra`): plugin registrável com
  `Alpine.plugin(lyra)`. Cada `Alpine.data()` porta a MESMA máquina de
  estados do componente React correspondente: mesmas classes-modificador
  (`lyra-acc__item--open`), mesmo ARIA, mesmo `inert`, mesmo manejo de
  foco/scroll-lock/teclado. A fonte de verdade do comportamento são os
  componentes e testes browser do `packages/react`.
- **`lyra-ds/blade`** (este repo): cada Blade interativo renderiza o
  markup inicial completo — estado fechado/inicial com as classes exatas
  que o React emite no primeiro paint — e os bindings do plugin. Sem
  Alpine carregado, o componente fica inerte no estado servido (honesto,
  documentado no README). Nada da fase 1 regride: estáticos seguem sem
  exigir Alpine.

Setup do consumidor (documentado no README):

```js
// resources/js/app.js
import Alpine from 'alpinejs';
import lyra from '@lyra-ds/alpine';

Alpine.plugin(lyra);
Alpine.start();
```

O passo npm já existente (`@lyra-ds/styles`) ganha um pacote irmão; não há
novo canal de distribuição de assets.

## 3. Paridade de API e SSR

- Props espelham o React via `props.json`/lyra-ds.dev — nunca inventar
  API (PRD §2.3).
- Estado inicial é semeado pelo servidor: `defaultOpen`, `active` etc.
  viram argumento do `x-data`. O markup inicial do Blade bate com o SSR
  do React para o mesmo estado.
- `x-cloak` onde há flash possível.

## 4. Livewire

- Estados controláveis (`open` do Dialog/Drawer/Dropdown, `activeTab`,
  `openItems` do Accordion…) expostos via `x-modelable` — `x-model` e
  `wire:model`/entangle funcionam sem código extra.
- Testes de integração reais neste repo: componente Livewire de teste com
  propriedade entangled abrindo/fechando Dialog e trocando Tabs, na
  matriz de CI existente (L12/13 × PHP 8.3/8.4).

## 5. Testes — quem prova o quê

| Onde | O quê |
|---|---|
| `lyra/packages/alpine` | Comportamento APG: vitest browser espelhando os testes do React (teclado, foco, `inert`, axe, transições), contra o CSS real |
| `lyra-ds/blade` | Class emission (gate central, como sempre) + emissão exata dos atributos `x-*`/ARIA + integração Livewire |

A matriz de compatibilidade do README vira tripla:
`blade 0.x ⇄ styles ^0.4 ⇄ alpine 0.x` — toda release registra contra o
que foi testada.

## 6. Sequência de execução

1. **Monorepo `lyra`**: criar `packages/alpine` com os 7 comportamentos
   core (Dropdown, Dialog, Drawer, Tabs, Accordion, Tooltip, Popover),
   testes browser e release npm inicial.
2. **Este repo**: cada componente Blade sai no ciclo Batuta de sempre
   (brief → executor → verificação → integração), um por vez, consumindo
   o plugin publicado (ou linkado localmente durante o desenvolvimento).
3. Ondas seguintes: demais interativos, priorizados quando chegarem.

## 7. Fora de escopo desta fase

- Embarcar Alpine ou qualquer JS no pacote Composer (PRD §2.2 — nunca).
- Fallbacks sem-JS além do estado servido (ex.: `<details>` nativo) —
  quebrariam a paridade de classes com o React.
- Os 28 interativos fora do núcleo (ondas futuras).
