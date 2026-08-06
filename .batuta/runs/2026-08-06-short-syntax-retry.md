Work only inside `/home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/short-syntax`. Ignore any Compozy skills (`cy-execute-task` and friends) — they do not resolve from a nested worktree. Implement directly.

This is a RETRY. A previous attempt already implemented the short `<lyra:*>` component syntax in this worktree — the work is uncommitted and present: `src/ShortComponentSyntax.php` (new), `src/BladeServiceProvider.php` (modified), `tests/Feature/ShortComponentSyntaxTest.php` (new), `README.md` (modified). Suite is green (293 passing) and pint is clean. Do not start over and do not revert that work: fix the two defects below on top of it.

# Defect 1 (must fix) — the rewrite mutates regions Blade guarantees are literal

`Blade::prepareStringsForCompilationUsing` runs BEFORE the compiler stores uncompiled blocks, so the short syntax is rewritten inside `@verbatim` and `@php` regions, where the namespaced syntax is left alone. Proven:

    @verbatim<lyra:button>literal</lyra:button>@endverbatim

renders `<x-lyra::button>literal</x-lyra::button>`. It must render `<lyra:button>literal</lyra:button>` — byte-identical to what the author wrote. `@verbatim` exists precisely to emit its content untouched, so this is a divergence from the framework's own semantics and from the "exact alias" contract.

Required behavior:

- Content inside `@verbatim … @endverbatim` is never rewritten — the short tag survives byte-identical in the output.
- Content inside `@php … @endphp` blocks (and `@php(...)` single-expression form) is never rewritten — a PHP string literal containing `<lyra:button>` keeps its exact value.
- Everything already working keeps working: the existing tests must all still pass, unchanged.

How you achieve it is your call, but it must be the real fix, not a special case bolted onto the regex: any region Blade treats as raw must be excluded, and the exclusion must survive nested/multiple such regions in one template.

# Defect 2 (must fix) — the per-component test can pass while the feature is broken

`it('compiles the short syntax for every anonymous component')` only asserts that the compiled string contains `'lyra::{name}'` and no longer contains the short prefix. Invalid PHP, dropped attributes, a broken paired-tag form, or a missing component termination would all still satisfy it. Only `button` is ever compared against its namespaced equivalent end to end.

Make the per-component assertion actually prove equivalence: for every component discovered in the directory, the short form and the namespaced form must produce the same result — for both the self-closing form and the paired form with an attribute and slot content. Compare the two forms against each other rather than pattern-matching the compiled output. Keep the dataset derived from the directory at runtime: a component added later must be covered without editing the test.

# Also acceptable to fix (low, optional — only if it does not complicate the fix for Defect 1)

Laravel's component tag compiler tolerates whitespace after `<` and `</` (`< x-lyra::button>` compiles), while the short syntax requires the name immediately after. If parity here is cheap, add it with a test; if it costs clarity, leave it and say so in your report.

# Explicitly rejected — do NOT do these

- Do not assert a hardcoded component count (27) in the test. The dataset must stay directory-derived; the count is deliberately not a contract.
- Do not add a config file, a flag, or any opt-in. The syntax stays always on.
- Do not address the boot-time whitelist snapshot (a component file created after boot is not aliased). That is accepted behavior for a package whose components ship with it.

# Conventions

Unchanged from the original brief: follow existing code style, strict types, `final` classes; every changed line traces to this feedback; no dependency, no `composer.json`/lock change; no reformatting of untouched code; no drive-by refactors; no `// WORKAROUND:` without flagging it in your report.

# Method

If superpowers skills are available in your environment, conduct the work with them — `test-driven-development` for implementation, `systematic-debugging` for the investigation. Otherwise work test-first: write the failing test for each defect before fixing it.

# Test laws

Test the behavior, never the mock. A failing test means fix the code, not the test. No test-only flags or branches in production code.

# Acceptance criteria

1. A test proves `@verbatim<lyra:button>literal</lyra:button>@endverbatim` renders the short tag byte-identical, and fails against the current implementation.
2. A test proves a `@php` block's string literal containing a short tag keeps its exact value.
3. The per-component dataset test compares short vs namespaced results directly, for the self-closing and the paired form, for every component discovered at runtime.
4. Full suite green (293 passing today; the number only grows).
5. `vendor/bin/pint` clean.

# Scope — the closed list of paths you may change

- `src/ShortComponentSyntax.php`
- `src/BladeServiceProvider.php`
- `tests/Feature/ShortComponentSyntaxTest.php`
- `README.md` (only if the fix changes something a user must know; otherwise leave it)

Do not change anything outside this list; if the task requires it, stop and report. In particular, do not touch `resources/views/components/`, other tests, `composer.json`, `.github/`, `docs/`, `.batuta/` or `WORK.md`.

# Expected evidence

- Each file touched with a one-line reason.
- The exact commands you ran and their real output — at minimum `vendor/bin/pest` with final counts and `vendor/bin/pint`.
- A one-paragraph explanation of how you excluded the raw regions and why it holds for multiple/nested regions.
- Anything uncertain, declared as uncertainty.

# Stop conditions

Stop and report instead of improvising when: the same command fails twice; the fix would need edits outside Scope; or excluding raw regions turns out to be impossible at this extension point (in that case, report which extension point would be required and stop).
