# Run — renderização compacta das guidelines (2026-08-06)

- **Lane:** complex → codex `gpt-5.6-sol`, reasoning high, via Compozy.
- **Worktree:** `.batuta/worktrees/compact-guidelines` (removido na integração).
- **Sessão:** `sess-eba44e549fd3529c` (morreu após commitar — report vazio; 5ª ocorrência do padrão `peer disconnected`; o trabalho sobreviveu no worktree, como nas anteriores).
- **Brief:** `2026-08-06-compact-guidelines-brief.md`.
- **Commit:** (squash do branch, ver `git log` — feat: factor exact class products).

## Invariante (fechado com o usuário)

Forma fatorada só quando o gerador prova por expansão que ela equivale
exatamente ao conjunto observado; sobras listadas à parte como observações
específicas; sem prova → lista de alternativas. O documento continua incapaz
de afirmar combinação que nenhuma fixture produz.

## Entrega

- `button`: linha de 951 chars → produto fatorado ("All root class
  combinations in this product were observed: `lyra-btn` + one of (...) + one
  of (...)") + 4 combinações extras como observações específicas.
- `separator`: intacto como alternativas (fatoração não fecha — modo label
  substitui a base).
- Guard adicional do executor: só fatora se o resultado ficar mais curto.
- 4 testes novos pinando o comportamento: produto completo fatora; produto com
  um caso removido NÃO fatora; sobras viram observações específicas; fallback
  quando fatorar alongaria.
- Arquivo: 14183 → 13783 bytes. Ganho modesto hoje; a mecânica escala para os
  ~48 componentes da fase 2.

## Verificação do maestro

- Escopo: 3 arquivos, todos na lista fechada.
- `vendor/bin/pest`: 313 passando (1005 assertions); pint limpo.
- Idempotência: duas execuções, sem diff.
- Frescor: vermelho com 28º componente adicionado, árvore limpa depois.
- Suíte reconfirmada em `main` pós-squash: 313.

## Cross-review

Nenhum defeito de correção: comparação posicional e exata, eixos arbitrários
expandidos por completo, eixo único rejeitado, wording sem composabilidade
implícita. Dois achados **Low rejeitados como bloqueio** pelo maestro, com
justificativa: (1) bordas sem teste (3+ eixos, tokens repetidos/reordenados) —
"safe by inspection" nas palavras do próprio revisor, a comparação é exata por
construção; (2) asserções positivas que não pegariam uma linha contraditória
extra — estruturalmente impossível, o gerador emite ou a forma fatorada ou a
lista, nunca ambas. Endurecimento fica como melhoria opcional, não dívida.
