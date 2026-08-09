# Run — Componente WorkspaceSwitcher (task 8, ÚLTIMO da onda B)

- **task_id:** `batuta/20260809-020500-workspace-switcher`
- **Data:** 2026-08-08/09 (UTC 2026-08-09)
- **Executor:** codex `gpt-5.6-sol`, reasoning high, via Compozy `sess-3a5694702df6dd3c`
- **initial_base_sha = attempt_base_sha:** `95bb9f7` · **final_sha:** `1844f51` · ff para `main`
- **Verdict:** aprovado no gate na primeira tentativa. **Primeira sessão da noite que commitou sozinha** — a conexão caiu depois do commit, então só o report se perdeu.

## Decisões fechadas pelo maestro antes do dispatch

1. **Blade serve os ids ARIA.** Diferente de `lyraPopover`/`lyraTooltip`, este binding
   não gera id nenhum e não seta `aria-controls`/`aria-labelledby` — então o servidor
   precisa fazê-lo, espelhando o React: id da raiz = o do consumidor quando existir,
   senão gerado; listbox = `{raiz}-listbox`; label = `{raiz}-listbox-label`.
   Precedente de geração: `input`, `select`, `radio-group` (`uniqid()`).
2. **Popover sempre servido, escondido pelo `x-show` do binding** (o React o renderiza
   condicionalmente), com `x-cloak` quando não começa aberto — igual ao Dropdown.
3. **`aria-selected` servido em toda opção.** O binding **lê** esse atributo para
   escolher qual opção recebe o foco ao abrir com Enter; sem ele, abre no lugar errado.
4. **A ação "criar" reaproveita o binding `option`.** O React tinha um segundo callback
   (`onCreate`); o Alpine tem um evento só. Então o botão de criar leva
   `x-bind="option"` com `data-id` próprio (`create-id`, default `create`): ganha
   navegação por teclado e fechamento com restauração de foco de graça, e o consumidor
   distingue pelo id no `lyra:change`. Documentado no docblock.
5. **`x-modelable="open"`** na raiz, canon do pacote.

## Verificação do maestro (nada aceito do executor)

Invariantes: 1 commit; `parent(1844f51)` = `95bb9f7`; árvore limpa. ✔
Scope: exatamente os 5 caminhos da lista fechada. ✔

- `vendor/bin/pest` → **674 passed, 2735 assertions** (657 antes), 14 testes no componente ✔
- `vendor/bin/pint --test` → passed ✔
- `php bin/generate-boost-guidelines` → idempotente ✔
- **Render real conferido pelo maestro:** ids encadeados corretamente
  (`…-listbox`, `…-listbox-label`) e o `aria-controls` do trigger apontando para o
  listbox; `x-cloak` presente por não começar aberto; `lyra-wssw__pop--up` **não**
  servido; `aria-selected="true"` só na selecionada; `data-id` escapado
  (`a&quot;&lt;b`); avatar e ícones saindo da composição de `<lyra:avatar>` e
  `<lyra:icon>` (chevrons-up-down com `stroke="var(--text-faint)"`, 15×15). O caso que
  mais me interessava: **`members: 0` renderiza `Pro · 0 members`** — o React testa
  `!== undefined`, então zero conta. ✔

## Nota de estilo (não corrigida)

A composição interna usa `<x-lyra::avatar>`/`<x-lyra::icon>` em vez da forma curta
adotada como padrão da casa. O padrão vale para exemplos, briefs e prosa; dentro de um
componente as duas formas são idênticas e o CookieBanner já tinha usado a curta. Fica a
inconsistência cosmética entre os dois arquivos — candidata a uma varredura futura, não
a uma rodada de gate.

## Fecha a onda B

Cinco componentes entregues: `code-block`, `cookie-banner`, `sidebar-group`,
`segmented-control`, `workspace-switcher`. O sexto (`app-sidebar`) saiu da onda por
falta de binding upstream e está sendo desenvolvido no monorepo
(`docs/spec-alpine-app-sidebar.md`, entregue como `lyra/.batuta/prd-alpine-app-sidebar.md`).
Suíte: 601 → **674** testes no total da onda.
