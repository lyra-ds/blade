# Run — task 38, esconder `chore` do changelog público

- `task_id`: `batuta/20260810-152256-hide-chores`
- Rota: medium → codex `gpt-5.6-sol`, reasoning medium
- Runtime: Compozy, `sess-e8c45c92f08b42a5` (uma sessão, sobreviveu — retry na mesma)
- `initial_base_sha` = `attempt_base_sha` = `promotion_base_sha` = `9bde7cf`
- `final_sha` = **`d370570`**, promovido por fast-forward
- Diff: 1 arquivo, 2 linhas

## Mudança

A entrada `chore` do `changelog-sections` ganhou `"hidden": true`, mantendo o nome da seção.
Visibilidade final: visíveis `feat`, `fix`, `perf`, `revert`, `refactor`; ocultos `chore`,
`docs`, `style`, `test`, `build`, `ci`.

## Gate

| Checagem | Resultado |
| --- | --- |
| Invariantes | count=1, pai `9bde7cf`, árvore limpa ✅ |
| Diff | só a entrada `chore` ganhando o flag; as outras dez intactas ✅ |
| JSON | válido ✅ |
| `pest` / `pint` | 1139 passed · passed ✅ (regressão) |
| **Tipo do commit** | ❌ **rejeitado na rodada 1** — veio como `fix:` |

O `fix:` não é detalhe de estilo: o release-please tira changelog **e bump** do tipo, então
cortaria um patch anunciando "Bug Fixes" para uma mudança que não altera uma linha do pacote
publicado — o consumidor leria que um bug foi corrigido quando não houve. Corrigido por
`--amend` para `chore(release-please):`, consistente com o `7355632`.

## Verificação — desta vez discriminante

Diferente da task 37, aqui o antes/depois é observável sem esperar release futura, porque o
range pendente desde a v0.8.1 é composto **só de `chore:`**:

| Momento | Resultado do `release-please release-pr --dry-run` |
| --- | --- |
| Antes (task 37) | propunha `chore(main): release 0.8.2` com seção "Miscellaneous Chores" |
| Depois (esta task) | **"No user facing commits found since e805d4a - skipping"** |

Efeito colateral bom e não previsto no pedido: com `chore` oculto, um período de trabalho que
só produziu commits de registro **deixa de propor release**. Antes, o simples ato de registrar
o ciclo gerava um PR de release sem mudança de código.

## Pendência deixada para o usuário

O **PR #11** (`chore(main): release 0.8.2`) continua aberto: o release-please pula, mas não
fecha PR de release obsoleto. Ele foi montado sob a config antiga e propõe uma 0.8.2 cujo
changelog lista exatamente os `chore` que acabaram de ser ocultados — os quatro commits do
range são todos `chore`, nenhuma mudança de código. Fechar é limpeza, mas é ação visível no
repositório e ficou para decisão do usuário.

## Verdict

✅ Aprovado após 1 rodada corretiva (tipo do commit), promovido por fast-forward (`d370570`).
