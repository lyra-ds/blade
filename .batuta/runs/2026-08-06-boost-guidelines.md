# Run — guidelines do laravel/boost (2026-08-06)

- **Lane:** complex → codex `gpt-5.6-sol`, reasoning high, via Compozy.
- **Worktree:** `.batuta/worktrees/boost-guidelines`, branch `batuta/boost-guidelines` (removidos na integração).
- **Sessões:** `sess-e8ac6f286a6337d8` (tentativa 1, morta depois de entregar) → `sess-372612d142023e54` (retry). Ambas paradas.
- **Briefs:** `2026-08-06-boost-guidelines-brief.md`, `2026-08-06-boost-guidelines-retry.md`.
- **Commit:** `d9c5420`.

## Decisões do usuário (fechadas antes do brief)

Conteúdo derivado do próprio pacote Blade (`@props` + fixtures de class-emission),
nunca do repo React — assim o documento só descreve o que existe de fato aqui e
não induz o LLM a usar um dos ~50 componentes ainda não migrados. Arquivo
commitado + teste de frescor no CI.

## Levantamento que precedeu o brief

- **Descoberta do boost é por convenção, não por config.**
  `Laravel\Boost\Support\Composer::packagesDirectoriesWithBoostSubpath()`
  (`vendor/laravel/boost/src/Support/Composer.php`) varre cada pacote instalado
  atrás de `resources/boost/guidelines` e `resources/boost/skills`. Não existe
  chave `extra.boost` a declarar. Verificado na fonte instalada, não de memória
  nem da doc do Context7 (que só documenta `extra.boost.skills` e induziria ao erro).
- **Escala do problema:** scout no repo `~/Projects/lyra-ds/lyra` (bonsai local)
  encontrou 75 diretórios de componente em `packages/react/src` e
  `tools/docgen/output/props.json` com 78 entradas ricas. Âncoras verificadas
  pelo maestro; guard read-only conferido (árvore do repo React intacta).
- 24 dos 27 componentes declaram `@props`; `empty-state`, `fieldset` e `tag` não.

## Tentativa 1

Entregou gerador (`src/BoostGuidelinesGenerator.php`), entrypoint
(`bin/generate-boost-guidelines`), teste de frescor e o documento gerado.
Suíte 299 verde, pint limpo, gerador idempotente, portão de frescor provado
vermelho pelo maestro (28º componente).

## Cross-review (item complex) — achados aceitos

- **O documento afirmava combinações de classe impossíveis.** Provado com
  `separator`: saía `[lyra-separator] [lyra-separator--{orientation}]
  [lyra-separator--label]` como três tokens independentes, quando o modo label
  emite só `lyra-separator--label` (substitui a base).
- **Valores de fixture vendidos como allow-list.** Enganava nos dois sentidos:
  `avatar.shape` mostrava só `"square"` sendo `'circle'` o default, e `progress`
  listava `30` e o texto `"Invalid"` como "valores permitidos".
- **Parser podia descrever a API errada em silêncio:** `@props (` com espaço
  virava "sem props"; `@props(...)` dentro de comentário Blade era lido como a
  API real; `parseLiteral` chutava tipos (`1e-3` → `0`).
- **Guardas e testes:** zero componentes descobertos gerava documento vazio de
  aparência válida; o teste de frescor comparava o gerador com ele mesmo, então
  todos os defeitos acima passaram por ele; guardas defensivas mortas no teste.

Rejeitados com motivo: precisão de float (`serialize_precision`) e CRLF —
nenhuma entrada atual toca neles; e construir parser PHP genérico ou adicionar
dependência para resolver o item do parser.

Verificações do maestro sobre os achados antes de aceitar: o caso `separator`
confere com o componente; nenhuma fixture passa `class` com token `lyra-*`
(colisão consumidor/intrínseco é risco latente, não defeito presente); nenhum
placeholder por coincidência textual se manifesta na saída atual.

## Retry

Corrigiu os quatro. Saída agora: alternativas em vez de padrão fundido,
`shape` incluindo `"circle"`, `value: 30` marcado como exemplo e não constraint,
`@props` localizado sem ambiguidade (falha alto quando ambíguo), default
indecifrável reportado como `unknown`, e guardas para zero componentes e fixture
vazia. Testes unitários reais para cada borda (11 no arquivo).

## Veredito: ✅ aprovado

- Escopo: 5 arquivos, todos na lista fechada.
- `vendor/bin/pest`: 309 passando, 997 assertions.
- `vendor/bin/pint --test`: limpo.
- Gerador idempotente (duas execuções, sem diff).
- Portão de frescor provado vermelho ao adicionar um 28º componente.
- Suíte reconfirmada em `main` pós-squash: 309 passando.

## Observação para decisão futura (não é defeito)

Trocar o padrão fundido por alternativas honestas custou verbosidade: `button`
enumera 19 combinações numa linha de 951 caracteres. O documento inteiro tem
14 KB para 27 componentes, projetando ~39 KB nos 75 — vai para o contexto de
todo LLM que usar o pacote. Uma renderização compacta e ainda verdadeira
("base + um de {variants} + um de {sizes} + modificadores opcionais, conforme
fixtures") é mudança de desenho, não correção, e ficou para o usuário decidir.
