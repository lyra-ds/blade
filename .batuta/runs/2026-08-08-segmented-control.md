# Run — Componente SegmentedControl (task 6 do plano das ondas B–F, onda B)

- **task_id:** `batuta/20260809-014800-segmented-control`
- **Data:** 2026-08-08/09 (UTC 2026-08-09)
- **Executor:** codex `gpt-5.6-sol`, reasoning medium, via Compozy `sess-e04eced547cfc1d5`
- **initial_base_sha = attempt_base_sha:** `a77435a` · **final_sha:** `2541a2e` · ff para `main`
- **Verdict:** aprovado no gate na primeira tentativa; commit feito pelo maestro (desvio declarado)

## Decisões fechadas pelo maestro antes do dispatch

1. **`x-modelable="value"` na raiz** — canon do pacote para estado controlável
   (`open` no Dropdown/Dialog/Drawer/Popover, `active` no Tabs, `openItems` no
   Accordion). É o que faz `wire:model` funcionar.
2. **`onChange` do React vira o evento `lyra:change`** com `{ value }`; sem prop
   de callback no Blade.
3. **Sem `name`/input hidden nesta versão.** O CheckboxGroup ganhou uma prop
   `name` porque checkbox é input nativo; aqui as opções são `<button>` sem valor
   de formulário nativo, e o React não tem essa prop. Livewire vai por
   `x-modelable`. Declarado como stop condition se o executor achasse essencial —
   não achou.
4. **Estado inicial computado no servidor**, incluindo o roving tabindex — nada de
   servir `tabindex="0"` em tudo e esperar o binding consertar, senão o grupo fica
   com N tab stops enquanto o Alpine não sobe.

Também registrado no brief: **não confundir com `segmented-ring`**, que já existe
e é outra coisa (visualização de dados).

## Incidente — `peer disconnected` (6ª seguida)

Morreu antes do commit, mas depois de escrever a linha do `WORK.md` e regenerar
as guidelines. Report perdido. Mesmo tratamento das duas anteriores.

O brief desta rodada passou a pedir explicitamente *"escreva a linha do WORK.md e
commite ANTES de compor o report"* — ainda não foi suficiente, mas reduziu o que
se perde: aqui só faltou o `git commit`.

## Verificação do maestro (nada aceito do executor)

Invariantes: 1 commit; `parent(2541a2e)` = `a77435a`; árvore limpa. ✔
Scope: exatamente os 5 caminhos da lista fechada. ✔

- `vendor/bin/pest` → **657 passed, 2615 assertions** (644 antes), 11 testes no componente ✔
- `vendor/bin/pint --test` → passed ✔
- `php bin/generate-boost-guidelines` → idempotente ✔
- **Render real conferido pelo maestro** nos três casos que erram em silêncio:
  - seleção normal habilitada → só ela com `tabindex="0"`;
  - **opção selecionada desabilitada** → foco cai na primeira habilitada, e a
    desabilitada continua com `aria-checked="true"` e o modificador ativo (igual
    ao React, que separa "selecionado" de "focável");
  - **todas desabilitadas** → ninguém recebe `tabindex="0"` (o `false` do
    `firstEnabledIndex` nunca casa com um índice inteiro na comparação estrita).
  Também conferido: `data-value` escapado (`a&quot;&lt;b`) e `aria-checked`
  sempre emitido como string, inclusive `"false"` — o React não omite. ✔

## Observação de revisão aceita sem rodada

Se o consumidor passar `aria-label` pelo bag além da prop `label`, o atributo sai
duplicado no HTML. O parser fica com a primeira ocorrência, que é a servida pela
prop — mesmo resultado efetivo do React, onde o `aria-label={label}` vem depois do
spread e vence. Feio no markup, inofensivo no comportamento; a prop `label` é o
caminho documentado. Anotado, não corrigido.
