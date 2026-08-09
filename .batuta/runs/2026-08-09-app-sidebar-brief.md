# Brief — AppSidebar component (React → Blade) — task 7 do plano ondas B–F

task_id: `batuta/20260809-122709-app-sidebar`
target_ref: `main` · initial_base_sha: `f18d33d84107311bfe3753a0730e30956465e2df` · attempt_base_sha: `f18d33d84107311bfe3753a0730e30956465e2df`
worktree: `/home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/20260809-122709-app-sidebar` (branch `batuta/20260809-122709-app-sidebar`)

Work only inside that worktree (a git worktree of lyra-ds/blade). Dependencies are already installed and the suite is green there (766 passed). Ignore any Compozy skills; implement directly.

IMPORTANT — commit protocol: when the work is verified, COMMIT FIRST, then compose your report. The commit must never wait on the report. Deliver EXACTLY ONE commit whose parent is `f18d33d84107311bfe3753a0730e30956465e2df`, with a clean tree afterwards (`git status --porcelain` empty). Iterate freely, but consolidate (amend/squash) into that single commit. Never use `--no-verify`, never touch git config or signing.

## Goal

Create the `<lyra:app-sidebar>` Blade component mirroring the React AppSidebar, wired to the `lyraAppSidebar` Alpine binding published in `@lyra-ds/alpine` 0.3.0. The Blade package ships ZERO JS: it serves the complete initial state (including the rail modifier and the correct `--appsidebar-width`) and delegates the expand/collapse toggle to the binding.

## Context

- **Interactive-component precedent (study and mirror the wiring pattern):** `resources/views/components/sidebar-group.blade.php` + `tests/Feature/SidebarGroupTest.php` + `tests/Fixtures/class-emission/sidebar-group.json`. This is also the component AppSidebar composes in data mode. A second, simpler precedent for modelable wiring: `resources/views/components/table-of-contents.blade.php`.
- **Binding contract (authoritative, read it):** `/home/franciscpd/Projects/lyra-ds/lyra/packages/alpine/src/app-sidebar.ts` — `x-data="lyraAppSidebar({ defaultCollapsed, width, labels })"`, exposing modelable `collapsed` and the x-bind objects `root` and `toggle`. Those are the only names; never invent others. `root` owns `:class` (`{'lyra-appsidebar--rail': collapsed}`) and `:style` (`--appsidebar-width` + `width: var(--appsidebar-width)`); `toggle` owns `type`, `:aria-label`, `:title` and `@click` (which also dispatches `lyra:collapse` with `{collapsed}`).
- **React contract (authoritative for markup/classes):** `/home/franciscpd/Projects/lyra-ds/lyra/packages/react/src/app-sidebar/app-sidebar.tsx`.
- **The spec this binding was built from:** `docs/spec-alpine-app-sidebar.md` in this repo. Its §4 decisions were all adopted upstream — they are closed, listed below as requirements. Do not reopen them.
- House rules in `.batuta/profile.md` apply: short syntax `<lyra:…>` in docs/examples; the served-attributes-vs-binding-attributes rule (anything that changes behavior without JS — `type`, `tabindex`, `hidden` — the Blade always serves, even when the binding also sets it).
- The CSS (`@lyra-ds/styles`, `.lyra-appsidebar--rail`) already hides `.lyra-sbgroup__label`, `.lyra-sbgroup__item-label` and `.lyra-sbgroup__item-badge` and centers items in rail mode. The rail is mostly CSS; the binding only changes the root class, the width and the toggle's attributes. Nothing in `lyraSidebarGroup` changes.

## Closed decisions (requirements, not options)

1. **Chevron:** serve BOTH `<path>` elements inside one inline SVG and toggle them with `x-show` — `m15 18-6-6 6-6` with `x-show="!collapsed"`, `m9 18 6-6-6-6` with `x-show="collapsed"`. No `:d` bind, no CSS rotation. SVG shape matches React: `aria-hidden="true"`, 15×15, `viewBox="0 0 24 24"`, `fill="none"`, `stroke="currentColor"`, stroke-width 2, round caps/joins. The initially hidden path carries `x-cloak` so the served state matches React without a flash while Alpine boots.
2. **Item `title`:** in data mode, ALWAYS serve `title` = the item's label on every item, in both states — not only when collapsed as React does. The rail CSS hides the visual label and a permanent native tooltip is harmless when expanded. Alpine deliberately does not walk served markup.
3. **Group heading:** ALWAYS serve the group heading (as the `label` of the composed `<lyra:sidebar-group>`), including when `defaultCollapsed` is true — React omits it when collapsed, but the rail CSS hides it, and Alpine cannot re-add markup that was never served. This is a deliberate, documented divergence in served DOM; the rendered result is identical.
4. **`addRailLinkLabels` is not ported.** Consumers composing links through the slot serve their own `title`/`aria-label`. Document this in the component's comment block.
5. **Collapsible:** the sidebar is collapsible exactly when the toggle button is served — i.e. when the `collapsible` prop is true, mirroring React. `lyraSidebarGroup`'s label-binding model is the precedent.

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest + Pint (run both).
- TDD: failing tests first. Conventional commits.
- Follow the existing style of the files you touch; change only what this brief asks — every changed line must trace back to it. No drive-by refactors, no reformatting untouched code.
- Comments only for constraints the code cannot express (the precedent's comment block is the model: it explains *why* the served DOM diverges, not what each line does).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.
- Tests must be deterministic: no network, no time-dependent assertions.

## Task

1. `tests/Fixtures/class-emission/app-sidebar.json` — exact root class strings from the React source (`lyra-appsidebar`, `lyra-appsidebar lyra-appsidebar--rail`, plus a consumer-class case proving the user class comes last), in the shape the existing fixtures use.
2. `resources/views/components/app-sidebar.blade.php` — props `brand`, `groups` (default `[]`), `footer`, `width` (default `260`), `collapsible` (default `false`), `defaultCollapsed` (default `false`), `labels` (default `[]`), plus the composition slot. Served markup per React: `<nav class="lyra-appsidebar …">` with `lyra-appsidebar__brand`, `lyra-appsidebar__groups`, `lyra-appsidebar__footer` and `lyra-appsidebar__toggle`. Data mode composes `<x-lyra::sidebar-group>` per group (`heading` → its `label`, items forwarded with the permanent `title` from decision 2). Wire `x-data`, `x-bind="root"`, `x-bind="toggle"` and `x-modelable="collapsed"`.
3. `tests/Feature/AppSidebarTest.php` — fixture-driven class parity plus markup behavior: served rail state (class + `--appsidebar-width: 64px` when `defaultCollapsed`, `{width}px` otherwise, always with `width: var(--appsidebar-width)`); both chevron paths served with the right `x-show`/`x-cloak`; toggle served only when `collapsible`, carrying `type="button"` and the served `aria-label`/`title` matching the initial state (`labels` overriding the `Collapse sidebar`/`Expand sidebar` defaults); brand/footer sections omitted when their content is absent; data mode composing sidebar-group with headings and permanent item titles; slot composition rendered inside `lyra-appsidebar__groups`; binding wiring present (`x-data` options, both binds, modelable); consumer attribute passthrough (`class` last, `id`/`data-*` preserved, a consumer `style` not dropped, a consumer `x-data` not clobbering the component's).
4. Regenerate `resources/boost/guidelines/lyra-blade.md` via `php bin/generate-boost-guidelines` (there is a test asserting the committed file stays in sync).
5. Add the `WORK.md` Done line (see below), then COMMIT, then report.

## WORK.md line (exact, inside the candidate commit)

Add under `## Done`, in Portuguese, matching the surrounding prose style, carrying no SHA:

`- [x] Task 7: componente app-sidebar sobre o binding lyraAppSidebar (alpine 0.3.0) — dívida da onda B destravada [batuta/20260809-122709-app-sidebar] → codex (gpt-5.6-sol, reasoning high)`

## Acceptance criteria

- `vendor/bin/pest` passes; the existing suite stays green (baseline 766) and the new tests are additive.
- Root class emissions match the React strings exactly, driven by the fixture.
- The served `--appsidebar-width` is `64px` when `defaultCollapsed` is true and `{width}px` otherwise, in both cases alongside `width: var(--appsidebar-width)`.
- `<lyra:app-sidebar />` and `<x-lyra::app-sidebar />` compile identically (the existing short-syntax dataset picks the component up automatically — keep it passing).
- `vendor/bin/pint --test` passes.
- `php bin/generate-boost-guidelines` is idempotent: running it again after the commit produces no diff.

## Boundaries

Nothing outside Scope. No JS in the package. No CSS in the package. No new dependencies, no lockfile changes. Do not modify `sidebar-group.blade.php` or any other existing component. Do not touch CI config, README, CHANGELOG or the spec doc.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- `resources/views/components/app-sidebar.blade.php` (new)
- `tests/Fixtures/class-emission/app-sidebar.json` (new)
- `tests/Feature/AppSidebarTest.php` (new)
- `resources/boost/guidelines/lyra-blade.md` (regenerated only)
- `WORK.md` (the one Done line above)

## Expected evidence

Report back: `task_id`; CLI, provider, model, reasoning; `attempt_base_sha`, `candidate_sha` and the candidate's parent; confirmation that `git status --porcelain` is empty; files touched; every command run with its exit code and actual output (pest, pint, the guidelines generator run twice for idempotency); the targeted tests; a self-review; uncertainties and any stop condition hit, declared as such.

## Stop conditions

Stop and report instead of improvising when: the binding or React source contradicts this brief, the same command fails twice with the same cause, or the fix would need edits beyond Scope or Boundaries.

---

# Run result (appended)

- **Executor:** codex (gpt-5.6-sol, reasoning high) via Compozy, worktree `20260809-122709-app-sidebar`.
  Sessões: `sess-9241fce3e7fc0f78` (entrega) · `sess-c53bfd12337100e4` (correção 1) · `sess-5cd3f66a90928c43` (correção 2).
- **SHAs:** initial_base `f18d33d` · attempt_base `f18d33d` (inalterado — nunca houve refresh) ·
  candidatos `173213c` → `f2527c1` → `394078f` · promotion_base `f18d33d` · final `394078f` (fast-forward, sem rewrite).
- **Quirk do runtime (3/3 rodadas):** `session prompt` morreu com
  `peer disconnected before response` e a sessão foi para `health: dead` — **mas o executor entregou
  nas três vezes**. Contrato do runtime seguido: nunca reenviei o brief, reconectei uma vez pelo id
  (`session status`) e o candidato já estava commitado. O report do executor se perdeu junto com o
  stream nas três rodadas; o gate é mecânico e não depende dele.
- **Gate, rodada 1 (`173213c`):** invariantes ok, scope exato (5 paths), diff rastreável,
  higiene de testes ok, 779 passed, pint ok, gerador idempotente. **Cross-review (3 lentes) reprovou.**
- **Cross-review 1** (codex gpt-5.6-sol high, `--sandbox read-only`; sandbox rejeitou a escrita do
  próprio artefato, findings transcritos verbatim pelo maestro em `cross-review-findings.md`):
  - ACEITO (major) — `array_merge` deixava um `null` explícito em `labels` sobrescrever o default;
    o binding usa `?? `, então o Alpine recuperava o rótulo no boot mas o HTML **servido** saía com
    `aria-label=""` no toggle. Quebra a promessa estático-primeiro (regra do profile, lição do CodeBlock).
  - ACEITO (minor) — `(int) $width` truncava `312.5` → `312`; React tipa `width?: number` e a
    paridade de emissão é o gate central do pacote.
  - ARCHITECT e MINIMALIST: none.
- **Gate, rodada 2 (`f2527c1`):** os dois findings corrigidos e cobertos por teste (780 passed,
  pint ok, idempotente). **Reprovado por causa NOVA, introduzida pela própria correção:** remover o
  cast deixou `width` interpolado cru na expressão JS do `x-data` e no `style`. Provado no worktree
  com `:width="1); window.pwned=1; //"` — payload literal nos dois atributos, Alpine executa.
  **Erro de julgamento do maestro no aceite do finding minor**, não do executor: aceitei a forma
  "remover o cast" sem ver que ele era a única sanitização daquele valor.
- **Gate, rodada 3 (`394078f`) — APROVADO:**
  - invariantes: 1 commit, parent `f18d33d`, árvore limpa;
  - scope: exatamente os 5 paths declarados; 414 inserções, nenhuma remoção;
  - guard de `width`: `is_numeric` → `(float)` → `is_finite` → `json_encode`. Sondado com
    `1); window.pwned=1; //`, `260" onload="alert(1)`, `1e400`, `0x10`, `260;--appsidebar-width:evil`
    → todos caem para `260`; `312.5` e `'312.5'` preservam `312.5px` e `width: 312.5`.
    `json_encode` usa repr de round-trip mais curta, então `260.0` emite `260` — paridade mantida;
  - 781 passed (3546 assertions), baseline 766 → +15; pint passed; gerador idempotente;
  - **cross-review 2** sobre o candidato final, instruído a caçar a mesma classe de defeito em
    qualquer valor do consumidor: SKEPTIC/ARCHITECT/MINIMALIST = none (`cross-review-2.md`);
  - read-only guard verificado nas duas rodadas de review (status do worktree idêntico antes/depois).
- **Verdict: ✅ approved** — fast-forward `f18d33d..394078f` na main, worktree e branch removidos,
  três sessões paradas.
- **Lição (vale para o próximo cross-review):** um finding de paridade cosmética cuja correção é
  "remover uma normalização" precisa ser julgado perguntando o que aquela normalização segurava.
  O aceite deve descrever o *comportamento* exigido, não a edição sugerida pelo revisor.
