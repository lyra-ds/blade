# Run — bump do blade-demo para @lyra-ds/alpine 0.2.0 (task 2 do plano das ondas B–F)

- **task_id:** `batuta/20260809-004612-demo-alpine-bump`
- **Data:** 2026-08-08 (UTC 2026-08-09)
- **Executor:** codex `gpt-5.6-sol`, reasoning medium, via Compozy `sess-7b056747d3ccf7fe`
- **Repo alvo:** `~/Projects/lyra-ds/blade-demo` (repo separado; sem worktree, precedente das tarefas de demo)
- **target_ref:** `main` · **initial_base_sha = attempt_base_sha:** `c160cc0`
- **candidate/final_sha:** `1313bb8` · **promotion:** N/A (trabalho direto no `main` do demo)
- **Verdict:** aprovado no gate, integrado

## Brief

Entregue inline na sessão Compozy (texto integral no histórico da sessão). Resumo:
bump de `@lyra-ds/alpine` `^0.1.0` → `^0.2.0` no `package.json` do demo, install,
e prova de que o app ainda builda e passa nos testes. Scope fechado em dois
caminhos (`package.json`, `package-lock.json`), com `resources/` explicitamente
fora — qualquer necessidade de mexer no app seria stop condition, não conserto
improvisado. Condições de parada: churn de outras dependências no lockfile,
build/teste vermelho após o bump, necessidade de sair do Scope, mesma falha 2×.

## Incidentes de dispatch (dois, ambos do runtime — nenhum do modelo)

1. **Bind recusado por id de modelo.** O primeiro `session prompt` usou
   `--model codex/gpt-5.6-sol` (forma qualificada que o `compozy.md` documenta) e
   o daemon respondeu `model "codex/gpt-5.6-sol" is unavailable in config option
   "model"`, listando nomes **sem prefixo** (`gpt-5.6-sol`, `gpt-5.4`, …). O erro
   veio marcado como `dispatch is indeterminate: dispatch failed after the
   at-most-once boundary`, então o send-state foi resolvido por inspeção antes de
   qualquer re-envio: `session history` só tinha `hook.dispatch.start` +
   `hook.dispatch.complete`, zero `agent_message`, e a árvore do demo estava
   intacta em `c160cc0` → **não enviado**, re-dispatch seguro.
   **Lição para o `compozy.md`:** a regra "`--model` sempre `provider/model`" vale
   para o provider `opencode`, mas o provider `codex` quer o **nome nu**.
2. **`peer disconnected before response`** no meio do turno (8ª ocorrência do
   padrão neste projeto). O executor já tinha commitado antes de morrer: sessão
   ficou `stopped`/`health: dead` com 380 `agent_message` no histórico, mas o
   texto do report não foi recuperável por nenhum dos três shapes conhecidos
   (`.content.text`, `.text`, `recap`) — **report perdido**. Verificação integral
   do maestro no lugar dele, como no ciclo do CheckboxGroup.

## Verificação do maestro (provas re-rodadas, nada aceito do executor)

Invariantes de entrega:

- `git rev-list --count c160cc0..1313bb8` = **1**
- `parent(1313bb8)` = `c160cc001f81b6253512e30b7e351e278588809f` ✔
- `git status --porcelain` vazio ✔

Scope: `git diff --name-only` = exatamente `package.json` + `package-lock.json` ✔
(nada fora da lista; `public/build/` continua git-ignored e fora do commit).

Diff: `^0.1.0` → `^0.2.0` no `package.json`; no lockfile, só o bloco de
`@lyra-ds/alpine` (0.1.1 → 0.2.0, resolved/integrity novos, `funding` que o
pacote passou a declarar). 7 inserções, 4 remoções — zero churn de terceiros.

Critérios:

- `node -p "require('./node_modules/@lyra-ds/alpine/package.json').version"` → `0.2.0` ✔
- `npm run build` → `✓ built in 161ms`, exit 0 ✔
- `php artisan test` → 3/3, 19 assertions ✔
- `php artisan view:cache` → `Blade templates cached successfully` ✔
- árvore limpa depois das provas ✔

Commit `1313bb8` credita o executor real (`Co-Authored-By: Codex (gpt-5.6-sol)`).

## Achado — peso do bundle

`@lyra-ds/alpine` foi de **118 KB** (0.1.1) para **590 KB** unpacked (0.2.0), e
`Alpine.plugin(lyra)` registra **todos** os `Alpine.data()` de uma vez — não há
tree-shaking possível pelo consumidor hoje. O bundle do demo ficou em 125.61 kB
(35.66 kB gzip) com os 28 state machines dentro, para uma galeria que usa 7.
Não bloqueia nada agora, mas é candidato a decisão de doc/upstream antes de
fechar o catálogo: ou o README ensina registro por componente, ou o
`@lyra-ds/alpine` passa a expor entradas granulares. **Levar para a onda C**,
junto da infra de tema, que é quando o README de interatividade é mexido.
