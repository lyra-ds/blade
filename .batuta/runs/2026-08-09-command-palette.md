# Run — task 27, componente `command-palette` (onda E — último componente)

- `task_id`: `batuta/20260809-171218-command-palette`
- Rota: complex → codex `gpt-5.6-sol`, reasoning high
- Runtime: Compozy. 5 sessões: primária, `#2` e `#3` (continuações após queda), `#review`, `#4`
- `initial_base_sha` = `attempt_base_sha` = `promotion_base_sha` = `773b38a`
- `candidate_sha` = `final_sha` = `ddd59c6` (fast-forward)
- Suíte: 937 → **977** (5039 assertions)

## LIÇÃO TRANSFERÍVEL — o markup canônico do binding NÃO é normativo

Apareceu **duas vezes** nesta onda, e vai reaparecer na F se não for registrada:

1. Task 25: o maestro especificou a prop `searchLabel` no `combobox` baseado no exemplo do
   comentário de doc do binding. O React não tem essa prop — deriva `aria-labelledby` do label.
   Prop inventada, removida na task 26.
2. Task 27: o markup canônico do `lyraCommandPalette` (`command-palette.ts:166, 198`) renderiza
   o atalho num `<kbd>` único. O React (`command-palette.tsx:318-324`) faz
   `item.shortcut.split(' ').map(...)` — **um `<kbd>` por tecla**. O componente seguiu o
   exemplo e divergiu.

**Regra:** o comentário de doc do binding mostra *como amarrar os binds*; ele não é fonte de
verdade sobre *o que emitir*. Classe e estrutura vêm sempre do React. Colocar isso em todo brief
que envolva um binding novo.

## Um teste que estava errado (e por quê isso é raro)

A lei da casa é "teste vermelho significa consertar o código". Aplicada corretamente na
rodada `#2`: `hotkey` falsy caía em `'k'`, tirando do consumidor a capacidade de desligar o
atalho — capacidade que o React (`if (!hotkey || !onOpen) return`) e o binding
(`hotkey?: string | false`) suportam. Código consertado.

Mas um dataset do mesmo teste (`'null' => null`) pedia o impossível: `@props` do Blade compila
para `$$key = $$key ?? $default`, então um `null` explícito é **indistinguível** de omitir a
prop, e depois do `@props` a chave já saiu do attribute bag. O executor tinha contornado com
`get_defined_vars()` antes do bloco `@props` — hack que depende de detalhe de compilação do
framework e quebraria em silêncio numa atualização.

Decisão do maestro: remover o hack e o dataset impossível, documentando que se desliga o atalho
com `false` ou string vazia. Recusada a saída fácil de trocar o default para `null`, que mudaria
o contrato público (é `'k'` no React e no binding).

## Cross-review — 1 achado Medium, aceito

Skeptic: o `<kbd>` único (acima). Architect e Minimalist limpas — a estrutura da raiz e a
classe do consumidor saíram certas de primeira, resultado de ter levado a lição da task 26 para
dentro do brief como item obrigatório de conferência.

## Desvio declarado

A sessão `#4` morreu depois de aplicar o fix e antes do `--amend` final, deixando 4 arquivos
fora do commit. O maestro revisou o diff (mínimo e correto), dobrou no commit e promoveu.
Nenhum conteúdo novo foi escrito pelo maestro — só o ato mecânico de fechar o candidato.

Nota: 5 sessões nesta task. Todas as quedas foram `peer disconnected`; nenhuma foi falha de
julgamento do modelo. O protocolo commit-cedo-e-amend preservou o trabalho em todas —
em especial na primeira queda, quando o commit RED separou contrato de implementação e o
diagnóstico foi imediato.

## Gate

| Checagem | Resultado |
| --- | --- |
| Invariantes | count=1, parent=`773b38a`, árvore limpa ✅ |
| Scope | 5 caminhos ✅ |
| Suíte | 977 passando, 5039 assertions ✅ |
| Pint / gerador | limpo / idempotente ✅ |
| Cross-review | 1 achado aceito e corrigido ✅ |

## Verdict

Aprovado e promovido. **Componentes da onda E completos.** Falta a task 28 (galeria do demo +
release 0.7.0).
