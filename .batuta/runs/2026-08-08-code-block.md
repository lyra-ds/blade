# Run — Componente CodeBlock (task 3 do plano das ondas B–F, onda B)

- **task_id:** `batuta/20260809-005615-code-block`
- **Data:** 2026-08-08 (UTC 2026-08-09)
- **Executor:** codex `gpt-5.6-sol`, reasoning medium — duas sessões Compozy:
  `sess-ed1b870a8d21d800` (implementação) e `sess-5a9a49762cf09953` (rodada corretiva)
- **Worktree:** `.batuta/worktrees/20260809-005615-code-block`, branch `batuta/20260809-005615-code-block` (removidos após a promoção)
- **initial_base_sha = attempt_base_sha:** `e332b29` · **candidate = final_sha:** `34457c0`
- **promotion_base_sha:** `e332b29` → fast-forward para `main`
- **Verdict:** aprovado no gate após uma rodada corretiva, integrado

## Brief

Entregue inline nas sessões Compozy. Ponto central: o brief **embutiu o fonte
React inteiro e o binding `lyraCodeBlock` inteiro** em vez de mandar o executor
ler `../../lyra/packages/react/...` fora do worktree — brief autossuficiente e
sem risco de permissão/hang na leitura de repo vizinho. Isso funcionou bem e
vira padrão para os próximos itens da onda.

## Incidentes de runtime — `peer disconnected` 3× seguidas

Nenhum ligado ao modelo ou ao brief; padrão já registrado (agora 9ª, 10ª e 11ª
ocorrências no projeto):

1. `sess-ed1b870a8d21d800` morreu **antes do commit** — os 5 arquivos ficaram
   *staged* no worktree, `HEAD` ainda em `e332b29`, report perdido.
2. A sessão corretiva `sess-5a9a49762cf09953` morreu **depois do commit** —
   `34457c0` já estava na branch com a árvore limpa, report perdido de novo.

Ambas as vezes a sessão ficou `stopped`/`health: dead`, sem reatach possível, e
o texto do report não saiu por nenhum dos três shapes conhecidos
(`.content.text`, `.text`, `recap`). Verificação integral do maestro nas duas.

Lição operacional confirmada: com o `peer disconnected` estável, **o estado da
árvore é a fonte de verdade**, não o report — inspecionar `git log`/`git status`
do worktree antes de decidir se houve entrega.

## Defeito pego no gate (causa: brief do maestro)

O critério 5 do brief dizia "não sirva `role`/`aria-live`/`aria-atomic`/
`type="button"` que o binding já fornece". Correto para os três primeiros —
sem Alpine não há nada a anunciar, então o custo é zero. **Errado para `type`**:
um `<button>` sem `type` default para `submit`, então um code block dentro de um
`<form>` submeteria o formulário ao clicar em "Copy" enquanto o Alpine não
subisse — quebra direta da promessa estática-primeiro do pacote. O precedente do
Dropdown já servia `type="button"` no markup mesmo tendo binding.

A primeira sessão seguiu o brief à risca e o teste chegou a fixar o defeito
(`->not->toContain('type=')`). Rodada corretiva: serve `type="button"`, e o teste
passou a exigir a presença **e** a não-duplicação (`substr_count(..., 'type=') === 1`).

**Regra para os próximos briefs desta onda:** distinguir atributos que o binding
fornece por *conveniência* (ARIA de live region, ids gerados) dos que mudam
comportamento sem JS (`type`, `tabindex`, `hidden`, `inert`) — estes últimos são
sempre servidos pelo Blade.

## Verificação do maestro (nada aceito do executor)

Invariantes: `git rev-list --count e332b29..34457c0` = 1; `parent(34457c0)` =
`e332b29`; `git status --porcelain` vazio. ✔

Scope: exatamente os 5 caminhos da lista fechada — `code-block.blade.php`,
`CodeBlockTest.php`, `code-block.json`, `lyra-blade.md` (regenerado), `WORK.md`. ✔

Provas re-rodadas:

- `vendor/bin/pest` → **617 passed, 2401 assertions** (601 antes) ✔
- `vendor/bin/pint --test` → passed ✔
- `php bin/generate-boost-guidelines` → idempotente (segunda execução sem diff) ✔
- **Render real conferido pelo maestro** (teste efêmero, removido depois): a árvore
  emitida bate com a do React — `.lyra-code__bar` com `<span class="lyra-code__lang">`,
  botão com `type="button"` + `x-bind="copyButton"` + swap de label por `x-text`,
  `<pre class="lyra-code__pre" tabindex="0">`, `<span class="lyra-code__status">`
  vazio; e a variante sem labels degrada limpa (sem `x-data`, sem botão, sem status,
  `<pre>` sem tab stop quando `wrap`). ✔

## Decisões de porte registradas

- **Copiar só existe com os dois rótulos** (`copy-label` + `copied-label`),
  espelhando o `canCopy` do React: com um só, nem botão nem live region saem.
- `copied` é interno — **sem `x-modelable`**, igual ao React.
- O swap de rótulo é `x-text` no botão e na status region; o HTML servido já traz
  o rótulo de cópia e a região vazia, então sem Alpine o painel continua legível.
- `copy-text` vira `data-copy-text` **na raiz** (é lá que o binding lê); sem ele o
  binding cai no `textContent` do `<pre>`.
- Rótulos são texto, não markup: o React aceita `ReactNode` em `copyLabel`/
  `copiedLabel`, mas o swap via `x-text` exige string. Divergência consciente —
  se algum consumidor precisar de ícone no botão, vira slot numa iteração futura.
