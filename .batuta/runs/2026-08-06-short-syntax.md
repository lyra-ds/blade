# Run — sintaxe curta `<lyra:*>` (2026-08-06)

- **Lane:** complex → codex `gpt-5.6-sol`, reasoning high, via Compozy.
- **Worktree:** `.batuta/worktrees/short-syntax`, branch `batuta/short-syntax` (removidos na integração).
- **Sessões:** `sess-04a59c0a1beb319b` (tentativa 1, morreu com `peer disconnected`, `health: dead`) → `sess-7fe6afa45707eb5f` (retry). Ambas paradas.
- **Briefs:** `2026-08-06-short-syntax-brief.md` (original), `2026-08-06-short-syntax-retry.md` (retry).
- **Commit:** `497809a`.

## Decisões do usuário (fechadas antes do brief)

Whitelist derivada do diretório de componentes (tags `lyra:` desconhecidas passam intactas) e alias sempre ligado, sem config file.

## Tentativa 1

Implementou `src/ShortComponentSyntax.php` + registro via
`Blade::prepareStringsForCompilationUsing`, com 33 testes novos. A sessão
Compozy morreu antes de emitir o report — o código ficou no worktree, não
commitado, e a verificação se apoiou só no diff e nas provas do maestro.

Provas rodadas pelo maestro: `vendor/bin/pest` 293/293 (969 assertions),
`vendor/bin/pint --test` limpo, mais sondas próprias (`<div lyra:button=…>`,
web component de terceiro, entidade escapada, `<lyra:buttonish>`, maiúsculas,
atributos com tab/newline). Confirmado também que o Blade nativo compila
`<x-lyra::button>` em prosa e dentro de literais de `{{ }}` — logo esses dois
casos são paridade com o framework, não defeito.

## Cross-review (item complex)

Primeiro disparo do `codex exec` retornou vazio ("Reading additional input from
stdin") — o prompt como argumento não foi aceito; repetido com o prompt pela
stdin.

Achados aceitos:

- **Médio** — `@verbatim` e `@php` eram mutados: o rewrite roda antes de o Blade
  guardar os blocos crus. Provado pelo maestro:
  `@verbatim<lyra:button>literal</lyra:button>@endverbatim` renderizava
  `<x-lyra::button>…`. Viola a garantia do `@verbatim` e o contrato de alias exato.
- **Baixo** — o teste por componente só checava `str_contains` da saída
  compilada; PHP inválido ou atributo perdido passariam.

Achados rejeitados, com motivo: fixar a contagem 27 no teste (contradiz a
auto-cobertura pedida no brief), whitelist "stale" para componente criado após o
boot (aceito num pacote cujos componentes vêm no release), e ordenação de
callbacks de outros pacotes (teórico).

## Retry

Corrigiu os dois defeitos: proteção das regiões cruas (`@verbatim`, `@php…@endphp`
e `@php(...)`, este último resolvido com `token_get_all` para achar o parêntese de
fechamento real) e o teste por componente agora compara curto × namespaced nas
formas self-closing e pareada, para todos os 27.

**Falso negativo importante:** a primeira execução da suíte após o retry acusou 1
falha justamente no teste de `@verbatim`. Causa: a sonda do maestro na etapa
anterior havia renderizado aquela mesma string sob a implementação quebrada, e o
Blade cacheia a view compilada pelo hash do template
(`vendor/orchestra/testbench-core/laravel/storage/framework/views`). Com cache
frio a suíte fica verde. Lição para próximas verificações: limpar as views
compiladas antes de rodar a suíte quando sondas manuais renderizaram templates
sob código depois alterado.

## Veredito: ✅ aprovado

- Escopo: 4 arquivos, todos na lista fechada do brief.
- `vendor/bin/pest`: 297 passando, 973 assertions (cache frio; 260 antes da tarefa).
- `vendor/bin/pint --test`: limpo.
- Sondas do maestro na lógica nova de blocos crus: 11/11 (inclui `)` dentro de
  string em `@php(...)`, `@@verbatim` escapado, `@verbatim` sem fechamento,
  `@php` dentro de `@verbatim`, múltiplas regiões intercaladas).
- Suíte reconfirmada em `main` pós-squash: 297 passando.
