# Run — task 40, chave de storage no `@lyraThemeScript`

- `task_id`: `batuta/20260810-205830-themescript-key`
- Rota: medium → codex `gpt-5.6-sol`, reasoning medium
- Runtime: Compozy, `sess-700c21f5c0200127` (uma sessão; morreu em `peer disconnected`
  **depois** de commitar — o commit-antes-do-report salvou a task)
- `initial_base_sha` = `attempt_base_sha` = `e4df115`; `promotion_base_sha` = `7e5f58a`
  (rebase sobre a task 39, promovida primeiro — arquivos disjuntos, rebase limpo)
- `final_sha` = **`fe5ff29`**, promovido por fast-forward
- Diff: 3 arquivos, 88 inserções / 10 remoções

## Por que a task existia

O `@lyraThemeScript` nasceu **zero-config** porque o store `$store.theme` lia uma chave fixa:
aceitar um argumento que o store ignora produziria uma app que lê de uma chave e persiste em
outra — pior que não ter o argumento (decisão da task 16, 2026-08-09). O `@lyra-ds/alpine`
**0.4.0** resolveu pelo **caminho C** da spec, o recomendado: a chave vive no DOM,
`document.documentElement.dataset.lyraThemeKey || 'lyra-theme'`.

## Decisão do usuário

O script emitido **escreve** `dataset.lyraThemeKey` e depois lê dele. Assim o consumidor
declara a chave em **um** lugar (o argumento Blade) e o store a herda do DOM — impossível os
dois lados divergirem, que era o defeito que a spec queria evitar. Sem argumento, o script
passa a **ler** o dataset com fallback, em vez de cravar o literal `'lyra-theme'`: espelha o
store e atende quem prefira declarar `<html data-lyra-theme-key>` na própria layout. A chave
efetiva sem configuração nenhuma continua `lyra-theme`, então ninguém perde preferência salva.

## Mudança

`compile()` deixou de ignorar o `$expression` e passou a emitir
`<?php echo ThemeScript::render(<expressão>); ?>`, o que faz a **expressão PHP ser avaliada em
render** — `@lyraThemeScript($key)` e `@lyraThemeScript(config('app.theme_key'))` funcionam, e
o texto cru da expressão nunca entra no JS. A chave é embutida via `json_encode` com
`JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR`. Chave
`null`, vazia ou só espaços cai na variante que lê o dataset. Nenhum atributo HTML é emitido
pelo Blade: quem escreve o dataset é o próprio JS, a partir do literal escapado.

README: seção Theme documenta o argumento, e a matriz de compatibilidade passou de
`@lyra-ds/alpine` `^0.3.0` para **`^0.4.0`** — as duas tasks deste ciclo exigem 0.4.0.

## Gate

| Checagem | Resultado |
| --- | --- |
| Invariantes | pai `e4df115`, árvore limpa, count=1 ✅ |
| Escopo | só os 3 arquivos do brief ✅ |
| `pest` (no worktree) | **1147 passed (6337 assertions)**, de 1139/6311 ✅ |
| `pest` (na `main`, as duas juntas) | **1149 passed (6365 assertions)** — soma exata, nenhuma interação perdida ✅ |
| `pint --test` | passed ✅ |
| Prova de força 1 | removendo `JSON_HEX_TAG`, o teste de chave hostil falha ✅ |
| Prova de força 2 | escrevendo o dataset também no caso sem argumento, **4** testes falham ✅ |
| Contrato upstream | o `dist/index.js` do 0.4.0 lê `dataset.lyraThemeKey \|\| DEFAULT_STORAGE_KEY`; o Blade escreve essa mesma propriedade com o mesmo fallback — conferido no tarball npm, não no fonte do monorepo ✅ |
| Tipo do commit | `feat(theme):` ✅ |

Cinco dos sete testes existentes passaram **sem edição**. Os dois que cravavam o literal
`localStorage.getItem('lyra-theme')` foram adaptados de propósito (o contrato mudou), mantendo o
que protegiam: que a chave default efetiva segue `lyra-theme`.

## Emenda de mensagem

O corpo original dizia "verified by Codex with Pest and Pint". O gate que vale é o do maestro —
relatório nunca foi evidência, e aqui o relatório nem chegou, porque a sessão caiu depois do
commit. Emendado para nomear implementação (Codex) e verificação (maestro) separadamente,
preservando o trailer `Co-Authored-By`. Mesmo motivo da emenda da task 38: onde a mensagem é
lida por gente ou por ferramenta, ela é entrega, não estilo.
