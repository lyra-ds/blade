Work only inside `/home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/compact-guidelines`. This is a git worktree of the `lyra-ds/blade` package on branch `batuta/compact-guidelines`. Ignore any Compozy skills (`cy-execute-task` and friends) — they do not resolve from a nested worktree. Implement directly. Run `composer install` in the worktree before testing.

# Goal

Make the generated Boost guidelines (`resources/boost/guidelines/lyra-blade.md`) compact without making them less true. Today the root-class section lists every observed combination verbatim: `button` enumerates 19 combinations in a single 951-character line, and the document is 14 KB for 27 components — projected ~39 KB when the remaining ~48 are migrated. Every byte goes into the context window of every LLM that uses the package.

# Context

- The generator is `src/BoostGuidelinesGenerator.php`; its entry point is `bin/generate-boost-guidelines`; its tests are `tests/Feature/BoostGuidelinesTest.php`; the committed output is `resources/boost/guidelines/lyra-blade.md`. All shipped in commit `d9c5420` — read them first; the code is yours to evolve.
- The generator derives everything from `resources/views/components/*.blade.php` (`@props`) and `tests/Fixtures/class-emission/*.json` (observed cases). Nothing is hardcoded; a freshness test regenerates in memory and fails if the committed file drifts.
- **The governing principle, closed with the project owner and non-negotiable:** the document's only job is to be true. It must never present a class combination that no fixture case produces, and it must never let a reader conclude that a valid value is invalid. The previous iteration was rejected in review precisely for merging alternatives into independent-looking optional tokens; do not regress to that.
- The compaction insight, agreed with the owner: many observed sets are exactly a cartesian product. `button`'s fixtures cover all 15 of `base × {5 variants} × {3 sizes}`, plus 4 extra modifier cases. When (and only when) the generator can PROVE that a factored form expands to exactly the observed set, it may render the factored form. When the proof fails, it falls back to the current alternatives list — `separator` (whose label mode replaces the base class) must keep rendering as alternatives.

# Requirement

Rework the root-class rendering so that:

1. A factored/compact form is emitted only when the generator has verified, by expansion and set comparison, that the compact form represents exactly the set of observed combinations — no more, no less. Partial coverage may be factored only for the exactly-covered subset, with the remainder listed explicitly (e.g. button: the 15-case product factored, the 4 modifier cases listed as additional observed combinations).
2. Anything not provably factorable keeps today's alternatives rendering.
3. The wording must keep the reader able to distinguish "all of these combinations occur" from "these additional specific combinations were observed". No phrasing that implies free composability of modifiers that were only observed in specific combinations.
4. The result is materially shorter for products: `button`'s entry must shrink substantially (no 951-character enumeration line), and the whole document must get smaller, not larger.

The exact rendering format is your design decision within these constraints.

# Conventions

Existing code style, strict types, `final` classes; every changed line traces to this brief; no dependency, no `composer.json` changes; no reformatting of untouched code; no drive-by refactors; comments only for constraints the code cannot express; deterministic tests. Never silence a signal instead of fixing its source; a genuine dead end gets `// WORKAROUND: <reason>` plus a flag in your report.

# Method

If superpowers skills are available in your environment, conduct the work with them — `test-driven-development` for implementation. Otherwise work test-first from the acceptance criteria.

# Test laws

Test the behavior, never the mock. A failing test means fix the code, not the test. No test-only flags or branches in production code.

# Acceptance criteria

1. A unit test proves factoring happens only on exact set equality: a synthetic fixture whose observed set is a full product renders factored; removing one case from that set makes the same input render as alternatives (or product-plus-remainder, if that is your design — the test pins the honest behavior).
2. A unit test pins `separator`-style mutual exclusion still rendering as alternatives.
3. The regenerated `lyra-blade.md`: `button`'s root-class content is materially shorter than the current 19-combination enumeration, and the total file size decreases versus the committed 14 KB.
4. Regeneration stays idempotent (two runs, no diff) and the freshness gate still turns red when a component is added without regenerating.
5. Full suite green (309 today; the number only grows) and `vendor/bin/pint` clean.
6. `resources/boost/guidelines/lyra-blade.md` regenerated and committed as part of the work.

# Boundaries

Do not touch: `resources/views/components/`, `tests/Fixtures/`, tests other than the guidelines test file(s), `README.md`, `CONTRIBUTING.md`, `.github/`, `docs/`, `composer.json`, `.batuta/`, `WORK.md`. The preamble section of the document must not change.

# Scope — the closed list of paths you may change

- `src/BoostGuidelinesGenerator.php` (and, if you split responsibilities, new source file(s) under `src/`)
- `tests/Feature/BoostGuidelinesTest.php` (and, if you prefer, one additional new test file under `tests/Feature/`)
- `resources/boost/guidelines/lyra-blade.md` (regenerated)
- `bin/generate-boost-guidelines` only if the entry point must change

Do not change anything outside this list; if the task requires it, stop and report.

# Expected evidence

- Files touched with a one-line reason each.
- Exact commands with real output: `vendor/bin/pest` final counts, `vendor/bin/pint`, regeneration run twice showing no change the second time, and the file's byte size before and after.
- The `button` and `separator` entries as they read after the change, quoted verbatim.
- How the exactness proof works and its cost (the fixtures are small; an expansion-based check is acceptable).
- Uncertainty declared as uncertainty.

# Stop conditions

Stop and report when: the same command fails twice; the fix would need edits outside Scope; or a truthful compact rendering turns out to be impossible for the real fixtures without weakening the exactness guarantee.
