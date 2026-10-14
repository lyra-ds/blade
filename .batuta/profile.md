# Batuta profile — lyra-ds/blade

## Current conducting context — 2026-10-14

The package is implemented and the reviewed 1.0.0 release PR is pending.
Old greenfield and Compozy runtime assumptions are superseded by this setup.
Preserve the original decisions below and use current manifests/CI for support.

## Answers (onboarding 2026-08-04)

Stack: PHP >=8.3 Composer package lyra-ds/blade; Laravel 12/13; Blade templates with Alpine and CSS peers from npm.
Methodology: TDD; conventional commits; trunk-based.
Test: vendor/bin/pest
Lint: vendor/bin/pint --test
Build: npm run build:browser (test fixture only; package ships no CSS/JS)
Execution: sequential
Worktree: medium+
Worktree-home: cache
Install: composer install
Template: templates/laravel.md

## Project constraints (from PRD, closed decisions — don't reopen without the user)

- PRD source: `docs/prd.md` (moved into the repo 2026-08-05). Architecture decisions in §2 are
  closed: no CSS in the package (npm-only via `@lyra-ds/styles`), Alpine.js as
  suggested peer (never bundled; phase 1 is static-only), API parity with the
  React package (`props.json`/lyra-ds.dev is the source of truth — never invent
  API), independent versioning with a compat matrix in the README, MIT +
  CONTRIBUTING + CoC in English.
- Central quality gate: class-emission tests — each Blade component renders the
  EXACT class string the React component emits (fixtures extracted from the
  main repo).
- CI matrix: Laravel 12/13 × PHP 8.3/8.4 (GitHub Actions). L11 dropped 2026-08-06 (security EOL; composer advisory policy blocks it in CI).
- Sintaxe de exemplo — padrão da casa (decisão do usuário, 2026-08-08): usar a forma
  curta `<lyra:nome>` em briefs, demo e prosa. As duas formas continuam sendo
  primeira classe no pacote (há teste de equivalência por componente) e as
  guidelines geradas seguem documentando ambas — isso é serviço ao consumidor,
  não estilo nosso. A galeria do `blade-demo` migra na varredura da onda B.
- Atributos servidos vs. atributos do binding (lição do CodeBlock, 2026-08-08):
  o que o binding Alpine fornece por _conveniência_ (ARIA de live region, ids
  gerados) pode ficar só no binding; o que **muda comportamento sem JS**
  (`type`, `tabindex`, `hidden`, `inert`) o Blade sempre serve, senão a promessa
  estática-primeiro quebra. Auditado no catálogo: onde o React hardcoda
  `type="button"` o Blade já serve; `button`/`icon-button` omitem em ambos, por
  paridade — `type` passa pelo bag de atributos nativos e é escolha do consumidor.
- Icon SVGs (closed 2026-08-06, user choice): blade-lucide-icons composer dependency (blade-icons ecosystem) — NOT a generated in-package registry. The 79-name curation from the React icon-registry is enforced on top; parity with lucide-react output asserted in tests.

## Project map

Refreshed on 2026-10-14 from the current main checkout. The controller completed
this bounded sweep after the configured OpenCode scout returned a provider 402 error.

`composer.json` defines the PHP package, Laravel peers and development tooling.
`src/BladeServiceProvider.php` registers views, aliases and framework integration.
`src/ShortComponentSyntax.php` implements the public `<lyra:...>` syntax.
`src/ThemeScript.php` owns the server-rendered theme bootstrap directive.
`src/FocusResolver.php` supplies overlay focus references.
`src/OwnedRoot.php` protects Alpine-owned component roots.
`src/ToastStackComponent.php` owns the toast stack integration.
`src/IconRegistry.php` owns the curated icon contract.
`resources/views/components/` contains public Blade component templates.
`resources/docs-examples/` contains rendered examples for the public API artifact.
Every new component needs its corresponding curated documentation example.
`src/BladePropParser.php` parses component props for the API generator.
`src/DocsApiGenerator.php` derives the component documentation contract.
`bin/generate-docs-api` regenerates the repository API snapshot.
`docs/api.json` is generated; the release asset carries the released version.
`src/BoostGuidelinesGenerator.php` derives integration guidance.
`bin/generate-boost-guidelines` regenerates the Boost guidelines.
`resources/boost/guidelines/lyra-blade.md` is generated integration prose.
`tests/Feature/` contains Pest package, component, API and integration tests.
`tests/TestCase.php` and `tests/Pest.php` configure Testbench and Pest.
`tests/Fixtures/class-emission/` preserves the cross-framework class contract.
`browser/` holds real-browser fixtures and Playwright behavior tests.
`browser/runtime.js` is bundled by the browser build script.
`browser/.generated/` is generated browser tooling; do not edit it manually.
`playwright.config.js` limits discovery to `browser/` and declares three engines.
`phpunit.xml` limits PHP test discovery to `tests/Feature/`.
`.github/workflows/ci.yml` defines PHP/Laravel and browser checks.
`.github/workflows/release.yml` owns release-please and API release assets.
`docs/prd.md` and README document public scope and compatibility.
`WORK.md` records current tasks; `.batuta/` owns conducting state.
The owning CSS and Alpine runtime live in the sibling Lyra monorepo.
Never bundle a separate CSS/runtime implementation into this PHP package.

## Conventions

- Follow AGENTS.md, docs/prd.md and the README compatibility contract.
- Keep the package static-first; CSS and interactive behavior come from the Lyra npm peers.
- Every component change preserves class/API parity and includes its documentation example and generated API update.
- Preserve both public component syntaxes and server-rendered behavioral attributes.
- Use TDD and Conventional Commits; credit the real executor and verify outside its session.
