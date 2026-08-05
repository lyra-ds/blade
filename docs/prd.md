# PRD — `lyra-ds/blade`: componentes Blade do Lyra Design System

> Para: Claude conduzindo o repositório novo `lyra-ds/blade`.
> De: sessão Batuta do repositório lyra-ds, 2026-08-05.
> Decisão de priorização: primeiro satélite de framework do Lyra (antes de
> Vue/Svelte), decidido com o usuário em 2026-08-05. O plugin Filament é um
> PRD separado e vem DEPOIS deste pacote existir.

## 1. O que é

Pacote Composer `lyra-ds/blade` (Packagist): componentes Blade que emitem as
classes `.lyra-*` do `@lyra-ds/styles`, dando ao ecossistema Laravel o mesmo
catálogo pixel-perfect, tematizável (light/dark + white-label em 4 tokens) que
o pacote React entrega — sem bundler obrigatório, sem hydration, aproveitando
que o Lyra é CSS-first por arquitetura.

Uso final desejado:

```blade
<x-lyra::button variant="primary">Salvar</x-lyra::button>
<x-lyra::card>
  <x-lyra::stat label="MRR" value="R$ 12.400" trend="+8%" />
</x-lyra::card>
```

## 2. Decisões de arquitetura (fechadas — não rediscutir sem o usuário)

1. **CSS vem do npm, nunca do Composer.** O consumidor instala
   `@lyra-ds/styles` via npm/Vite (padrão do Laravel moderno:
   `laravel/vite-plugin`) e importa `@lyra-ds/styles` uma vez no
   `resources/css/app.css` (ou js). O pacote Blade NÃO embarca nem copia CSS.
   README documenta o passo a passo. Sem exceções — duplicar CSS cria skew de
   versão, o pior bug possível num DS multi-stack.
2. **Comportamento interativo via Alpine.js como peer** (a resposta idiomática
   do ecossistema; já vem com Livewire). Alpine é dependência sugerida, nunca
   embutida — mesma filosofia dos `@fontsource/*` no React. Componentes
   estáticos funcionam sem Alpine algum.
3. **Paridade de API com o React onde fizer sentido**: mesmos nomes de
   variantes/props (`variant`, `size`, `hint`, `error`), atributos extras via
   `$attributes->merge()`. A fonte de verdade da API é o `props.json`/docs de
   lyra-ds.dev — não inventar API nova.
4. **Versionamento independente**, com matriz de compatibilidade documentada
   no README: `lyra-ds/blade 0.x` ⇄ `@lyra-ds/styles ^0.4`. Toda release
   registra contra qual styles foi testada.
5. **MIT**, CONTRIBUTING + CoC desde o início (copiar o espírito do repo
   principal; tudo em inglês — política do projeto desde 2026-08-04).

## 3. Escopo da fase 1 (estáticos, sem Alpine)

~22 componentes de maior valor que são CSS puro:

Button, IconButton (decidir estratégia de ícones: slot SVG do usuário; NÃO
embarcar Lucide no PHP), Badge, Tag, Card, Alert, Stat, Skeleton, Spinner,
Progress, Separator, Avatar, EmptyState, Breadcrumb, Pagination (links), Table,
Input, Textarea, Select (nativo), Checkbox, Radio, Switch, Fieldset, FormRow,
Container/Stack/Grid (layout).

Fase 2 (Alpine): Dropdown, Dialog, Drawer, Tabs, Accordion, Tooltip, Toast.
Fase 3 (avaliar): Combobox, CommandPalette — só se Alpine der conta com
qualidade APG; senão ficam de fora com honestidade.

## 4. Estrutura e ferramental

- `composer.json` raiz, namespace `LyraDs\Blade`, service provider registrando
  o namespace `lyra` de componentes (`<x-lyra::button>`).
- PHP >= 8.3, Laravel 11 e 12 (matriz no CI), Pest para testes, Laravel Pint
  para estilo, GitHub Actions.
- **Teste espelho do React**: cada componente tem teste de "class emission" —
  renderizar o Blade e assertar a string de classes EXATA que o React emite
  (ex.: `lyra-btn lyra-btn--primary lyra-btn--md`). Essa paridade é o gate
  central do pacote; um snapshot da emissão do React por componente serve de
  fixture (extraível das docs/testes do repo principal).
- README com quickstart completo: composer require + npm i @lyra-ds/styles +
  import no Vite + primeiro componente.
- Starter: repo `lyra-ds/starter-laravel` (template repository, como os de
  Vite/Next) fica para o fim da fase 1 — mesmo demo dos irmãos (settings card,
  toggle de tema, troca de marca em 4 tokens).

## 5. Passos manuais do usuário (guiados)

- Criar o repo `lyra-ds/blade` na org (público, vazio).
- Conta/submissão no Packagist (vendor `lyra-ds`) — o primeiro submit do repo
  reivindica o vendor; ativar o webhook de auto-update do GitHub.

## 6. Critérios de aceite da fase 1

- [ ] `composer require lyra-ds/blade` + setup npm documentado resulta num
      Laravel renderizando Button/Card/Input pixel-idênticos ao lyra-ds.dev,
      com dark mode e white-label funcionando (provado num app de exemplo).
- [ ] Todos os componentes da fase 1 com teste de class emission passando
      contra as emissões do React.
- [ ] Nenhum CSS no pacote; nenhuma dependência de runtime além de PHP/Laravel.
- [ ] CI verde na matriz Laravel 11/12 × PHP 8.3/8.4.
- [ ] README honesto: o que existe, o que é fase 2, matriz de compatibilidade.

## 7. Fontes

- Repo principal: `~/Projects/lyra-ds` — `packages/styles` (classes reais),
  `packages/react/src/*` (API e emissões), lyra-ds.dev/llms.txt (contratos).
- Starters (padrão de demo): github.com/lyra-ds/starter-vite e starter-next.
