# Run — task 26, componente `time-zone-picker` (onda E)

- `task_id`: `batuta/20260809-163619-time-zone-picker`
- Rota: medium → codex `gpt-5.6-sol`, reasoning medium (corretivas em high/medium)
- Runtime: Compozy. Primária, `#review`, `#2` e `#3` corretivas
- `initial_base_sha` = `attempt_base_sha` = `promotion_base_sha` = `8eeb338`
- `candidate_sha` = `final_sha` = `287d666` (fast-forward)
- Suíte: 914 → **937** (4701 assertions)

## A entrega

67 linhas de composição real: `<x-lyra::combobox factory="lyraTimeZonePicker"
:extra-options="$extraOptions">`, sem duplicar o markup do combobox e **sem uma única zona IANA
em PHP**. Decisão do maestro: a lista curada fica no binding; replicar 27 zonas criaria segunda
fonte de verdade fadada a divergir na próxima mudança de fuso.

## Três defeitos — todos no `combobox` da task 25, já promovido

O cross-review achou dois; o terceiro foi do maestro na revisão de diff. **Scope ampliado por
decisão do maestro** para corrigir o `combobox` retroativamente dentro deste ciclo.

### 1 (High) — o `.lyra-combobox` É a raiz do binding

Verificado na fonte antes de aceitar (`combobox.tsx:204-206` e `304-322`):

```jsx
const control = <span ref={rootRef} className={cx('lyra-combobox', className)}>…</span>;
if (!label && !hint && !error) return control;
return <div className="lyra-field">{label}{control}{message}</div>;
```

Três divergências de uma vez no Blade: a classe do consumidor caía no wrapper em vez do
`.lyra-combobox` (por isso `lyra-tzpicker` estava no elemento errado); label e mensagem ficavam
DENTRO da raiz do binding, então o handler de clique-externo tratava clique no hint como interno
e o popover não fechava; e sem label/hint/error o Blade emitia um `<div>` que o React não emite.

### 2 (Medium) — ARIA derivada, e uma prop inventada

`combobox.tsx:214, 248-250`: o trigger e a busca recebem `aria-describedby` da mensagem; a busca
recebe `aria-labelledby` do label quando há label, e só cai em `aria-label` com o texto do
`searchPlaceholder` quando não há.

Consequência: a prop `searchLabel` **não existe no React** e foi removida. **Erro do maestro no
brief da task 25**: especifiquei a prop com base no exemplo do comentário de doc do binding, em
vez da fonte React — violando a regra da casa que eu mesmo repito em todo brief.

### 3 (Medium) — achado do maestro na revisão de diff

O trigger não servia `lyra-input--error`, deixando para o binding aplicar em runtime
(`combobox.ts:374` devolve `{ 'lyra-input--error': error }` via `:class`). Um campo com erro
renderizava sem estilo de erro até o Alpine iniciar. Diferente de `--active`, `--up`, `__value`
e `__placeholder`, que são inerentemente dinâmicos, `error` é conhecido no servidor e estático —
e o irmão `date-picker` já servia a classe. Inconsistência interna, além da paridade.

**A fixture de class-emission codificava a expectativa errada** (`trigger` + `error` esperando
sem o modificador): foi escrita para casar com a implementação em vez do React. É deriva de
snapshot no portão de qualidade central do projeto — uma fixture que aprende com o código em vez
de ensinar o código dá aparência de cobertura sem cobertura.

## Lição de método

O cross-review da task 25 passou limpo na lente Skeptic, e eu promovi. Só a composição real na
task 26 expôs a estrutura errada. O que funcionou nas duas tasks foi mandar o revisor olhar o
**consumidor futuro** — foi assim que o achado de composabilidade da task 25 apareceu com uma
task de antecedência, e é a pergunta que faltou fazer sobre a estrutura da raiz.

Nota operacional: o maestro chegou a reportar que o commit da task 26 tinha sumido; o comando
tinha rodado no checkout principal em vez do worktree. Corrigido usando `git -C <worktree>`
explícito daqui em diante.

## Gate

| Checagem | Resultado |
| --- | --- |
| Invariantes | count=1, parent=`8eeb338`, árvore limpa ✅ (executor, nas três rodadas) |
| Scope | 8 caminhos após a ampliação autorizada ✅ |
| Suíte | 937 passando, 4701 assertions ✅ |
| Pint / gerador | limpo / idempotente ✅ |
| Cross-review | 2 achados aceitos; +1 do maestro; todos corrigidos ✅ |

## Verdict

Aprovado e promovido. Falta a task 27 (`command-palette`) e a 28 (galeria + release 0.7.0)
para fechar a onda E.
