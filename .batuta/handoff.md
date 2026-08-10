# Handoff — 2026-08-10

## Cycle point

**Nenhum ciclo em voo.** O último terminou fechado: `docs: spec the two upstream alpine
gaps; record commit-session policy` (`4c88737`), commitado e empurrado. `WORK.md` tem a
seção "In progress" vazia de propósito — o próximo ciclo começa do zero.

A fase 2 fechou hoje: cinco ondas, uma release por onda (0.4.0 → 0.8.0), mais a 0.8.1 de
patch. Catálogo Blade completo em **72 componentes**, class-emission 72/72 contra o React,
galeria do demo cobrindo os 72, README alinhado, suíte em **1139 testes** (6311 assertions).
`v0.8.1` publicada e confirmada no Packagist.

Estado dos três repositórios no momento da pausa:

| Repo | HEAD | Árvore |
| --- | --- | --- |
| `lyra-ds/blade` | `4c88737`, igual ao `origin/main` | limpa, sem worktrees |
| `lyra-ds/blade-demo` | `fc014c6` | limpa |
| `lyra-ds/lyra` | `e973e67` | **1 arquivo untracked** — ver Background |

## Decisions not yet written

Nada pendente de registro: tudo o que foi decidido hoje já está em `WORK.md`, nas trilhas de
`.batuta/runs/` ou no `.batuta/routing.md`. Em particular, já estão escritas:

- a política de **sessão dedicada ao commit** sob o runtime Compozy (`routing.md`, seção
  nova) — commit é o elo frágil, não a implementação; 5 mortes confirmadas de sessão por
  `peer disconnected` no dia, quase todas entre trabalho pronto e commit;
- a regra de **repetir o caminho absoluto do worktree no corpo do prompt**, porque `--cwd`
  não garantiu o cwd do agente;
- a ressalva de verificação da task 37: a seção "Code Refactoring" do changelog **ainda não
  foi observada renderizando**, porque não houve `refactor:` no range. Quem conduzir o
  próximo release que contiver um `refactor:` deve conferir a seção no PR antes de mesclar.

## Background

**Nenhum executor rodando.** Zero sessões Compozy ativas, zero tarefas em background, zero
worktrees, zero PRs abertos. Nada será orfanado pela pausa.

O que fica esperando **você**, fora deste repositório:

- `~/Projects/lyra-ds/lyra/.batuta/prd-alpine-pendencias-blade.md` — **untracked**, na `main`
  limpa do monorepo. É a spec dos dois itens de upstream que você vai implementar lá:
  1. `lyraTimePicker`: `aria-label` da listbox traduzível (`packages/alpine/src/time-picker.ts`,
     binding `list`, ~linha 228, hoje literal `'Time options'`). A fiação do lado Blade já
     existe inteira — falta só o destino do rótulo.
  2. `lyraTheme()`: chave de storage configurável (`packages/alpine/src/theme.ts:22` e `:61`).
     **Atenção ao miolo**: parametrizar a factory não basta sozinho, porque o plugin registra
     o store por conta própria (`packages/alpine/src/index.ts:124`) e `Alpine.plugin(lyra)` só
     recebe a instância do Alpine. A spec lista três caminhos e recomenda o terceiro (chave
     declarada no DOM, lida pelo store e pelo script bloqueante), porque nos outros dois a
     chave passa a existir em dois lugares que podem divergir.
  O original fica em `docs/spec-alpine-pendencias-upstream.md` neste repo; a cópia difere só
  no cabeçalho e nos caminhos qualificados com `lyra-ds/blade:`.

Quando o upstream sair, o lado Blade destrava duas props hoje impossíveis: `labels.timeOptions`
no `time-picker` e o argumento opcional de chave no `@lyraThemeScript`. Os dois itens seguem
no backlog do `WORK.md`, agora apontando para a spec.

## Open questions

1. **Commitar a spec no repo `lyra`?** Deixei untracked de propósito — aquele repositório tem
   ciclo próprio e não commitei nele sem seu aval.
2. **Forma da option no `lyraTimePicker`** (decisão de vocês no upstream): option plana
   `optionsLabel`, consistente com o `placeholder` que já existe naquele arquivo, ou o padrão
   `labels: { ... }` dos bindings mais novos (`lyraAppSidebar`, `lyraWeeklyScheduleEditor`).
   O Blade se adapta a qualquer uma das duas.
3. **Caminho da chave de tema** — se escolherem A ou B em vez do recomendado C, a documentação
   precisa dizer explicitamente que a chave muda em **dois** lugares (script bloqueante do
   Blade e config do JS), senão o sintoma da divergência é tema piscando ou preferência
   perdida.
