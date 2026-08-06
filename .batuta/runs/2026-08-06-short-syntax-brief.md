Work only inside `/home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/short-syntax`. This is a git worktree of the `lyra-ds/blade` package on branch `batuta/short-syntax`. Ignore any Compozy skills (`cy-execute-task` and friends) — they do not resolve from a nested worktree. Implement the task directly.

# Goal

Add a short component syntax to the package: `<lyra:button>` must work as an exact alias of the existing `<x-lyra::button>`. Both syntaxes coexist permanently — this is additive sugar, not a replacement, and no existing template may break.

# Context

- Package: `lyra-ds/blade`, namespace `LyraDs\Blade`, PHP >= 8.3, Laravel 12/13. Anonymous Blade components only — no PHP component classes.
- `src/BladeServiceProvider.php` is the whole runtime surface today: it calls `Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'lyra')`, which is what makes `<x-lyra::button>` resolve.
- `resources/views/components/*.blade.php` holds 27 flat, kebab-case component files (`button.blade.php`, `empty-state.blade.php`, `form-row.blade.php`, `icon-button.blade.php`, …). There are no subdirectories.
- Tests: Pest + Orchestra Testbench. `tests/TestCase.php` registers the provider; `tests/Feature/BladeServiceProviderTest.php` shows the house style for provider-level tests and uses `Blade::render(...)` for end-to-end assertions. Current suite: 260 passing.
- `README.md` documents usage: the intro line at the top, the "4. Render your first components" section (~line 39) and the "Components" section (~line 57, which ends with "Use each name with the package namespace—for example, `button` becomes `<x-lyra::button>`").
- Two design decisions are already closed by the project owner — implement them as stated, do not reopen:
  1. **Whitelist**, not a blanket regex: only tags whose name matches an actual component file in `resources/views/components` are rewritten. Anything else (`<lyra:foo>`, third-party web components, XML namespaces using a `lyra:` prefix) must pass through byte-identical.
  2. **Always on**: no config file, no publishable config, no opt-in flag. The package has no config today and must not gain one here.

# Conventions

- Follow the existing code style of the files you touch — naming, formatting, import order. Strict types and `final` classes as the existing `src/` code does.
- Change only what this brief asks. Every changed line must trace directly back to it.
- Clean up only your own mess: remove imports/variables/functions that YOUR change made unused. Leave pre-existing dead code alone — mention it in your report instead of deleting it.
- Keep functions small and names descriptive; prefer clarity over cleverness.
- Comments only for constraints the code cannot express — never to narrate what a line does.
- Tests must be deterministic: no real network, no time-dependent assertions.
- Framework conventions before invention: use the Blade extension points Laravel provides rather than hand-rolling around them. Tests use the project's runner (Pest) and its existing Testbench setup.
- Never: reformat code you were not asked to change; add a dependency (no `composer.json`/lock changes); drive-by refactors outside scope; touch CI config or license; silence a signal instead of fixing its source (empty catch, type-silencing casts, sleeps to fix ordering). If a root cause is genuinely out of reach, mark `// WORKAROUND: <reason>` and flag it in your report.

# Method

If superpowers skills are available in your environment, conduct the work with them — `test-driven-development` for implementation, `systematic-debugging` for bug investigation. Otherwise, work test-first from the acceptance criteria.

# Test laws

Test the behavior, never the mock. A failing test means fix the code, not the test. No test-only flags or branches in production code.

# Acceptance criteria

Each of these must be proven by a test that fails before your change and passes after:

1. `<lyra:button variant="primary">Save</lyra:button>` renders byte-identical output to `<x-lyra::button variant="primary">Save</x-lyra::button>`.
2. Coverage is total, not sampled: a dataset-driven test derives the component name list from `resources/views/components/*.blade.php` at runtime and asserts every one of them is rewritten by the short syntax. A component added to that directory later must be covered by this test without editing it.
3. Self-closing works: `<lyra:separator />` and `<lyra:separator/>` both behave like `<x-lyra::separator />`.
4. A name that is not a component is left byte-identical — `<lyra:foo>bar</lyra:foo>` must survive the transformation untouched, and so must a self-closing `<lyra:not-a-component />`.
5. Attributes survive intact, including Blade-bound ones and multi-line tags: `:variant="$variant"`, `@class([...])`, `x-data="…"`, attributes containing `>` inside quotes, and a tag whose attributes span several lines.
6. Nesting works: a short-syntax component inside another short-syntax component (e.g. a badge inside a card) renders the same as the equivalent nested `<x-lyra::…>` markup.
7. The two syntaxes coexist in one template: mixing `<lyra:card>` with `<x-lyra::badge>` in the same view renders correctly.
8. No regression: the full existing suite passes (260 tests before your change; the number only grows).
9. `vendor/bin/pint` reports no style issues.
10. `README.md` documents the short syntax as the equivalent of the namespaced one: the intro line, the "Render your first components" example, and the closing line of the "Components" section all make clear both forms work. Do not restructure the README beyond that.

# Boundaries

Do not touch: any file under `resources/views/components/`, any existing test other than as allowed by Scope, `composer.json`, `composer.lock`, `.github/`, `docs/`, `LICENSE`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, `.batuta/`, `WORK.md`. Do not add a config file. Do not add a dependency. Do not change the existing `<x-lyra::…>` behavior in any way.

# Scope — the closed list of paths you may change

- `src/BladeServiceProvider.php`
- one new class file under `src/` if you need one (your choice of name)
- one new test file under `tests/Feature/`
- `README.md`

Do not change anything outside this list; if the task requires it, stop and report.

# Expected evidence

Report back:

- Every file you touched, with a one-line reason each.
- The exact commands you ran and their real output — at minimum `vendor/bin/pest` (full suite, with the final counts) and `vendor/bin/pint` (or `--test`).
- How you resolved the component whitelist, and whether it is resolved once per process or on every template compile.
- Anything you were uncertain about, declared as uncertainty rather than smoothed over.

# Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief; the same command fails twice; the fix would need edits outside Scope or Boundaries; or the whitelist decision turns out to be unimplementable through Blade's extension points as specified.
