Work only inside `/home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/boost-guidelines`. Ignore any Compozy skills — they do not resolve from a nested worktree. Implement directly.

This is a RETRY on your own uncommitted work in this worktree (`src/BoostGuidelinesGenerator.php`, `bin/generate-boost-guidelines`, `tests/Feature/BoostGuidelinesTest.php`, `resources/boost/guidelines/lyra-blade.md`, `CONTRIBUTING.md`). Suite is green (299) and pint is clean. Do not start over and do not revert: fix the defects below on top of it, and regenerate the committed document at the end.

The guiding principle for this round: **the document's only job is to be true.** A statement an LLM cannot rely on is worse than a statement that is absent. Prefer being explicitly incomplete over being confidently wrong, and prefer failing generation loudly over emitting a plausible guess.

# Defect 1 (must fix) — the document states class combinations that cannot occur

The root-class-pattern inference merges every fixture case into one line of independent optional tokens, which loses mutual exclusion. Proven with `separator`:

- The document says: `[lyra-separator] [lyra-separator--{orientation}] [lyra-separator--label]`.
- `resources/views/components/separator.blade.php` actually emits exactly one of: `lyra-separator--label` (label mode — note it does NOT emit the base class), `lyra-separator lyra-separator--vertical`, or `lyra-separator`.

As written, the reference invites `class="lyra-separator lyra-separator--label"`, a combination the component never produces, and hides that label mode replaces the base class.

Requirement: the reference must never present a class combination that no fixture case produces. Combinations that are mutually exclusive must read as alternatives, not as independent optional tokens. Inference that cannot be justified by an actual case is not allowed — showing the distinct combinations the fixtures actually produce is entirely acceptable and preferable to a clever merged pattern.

Related, same root cause: a class the component always emits must never disappear from the reference because some fixture case happened to pass the same token as a consumer `class` value. No fixture does that today, so this is a guard, not a visible bug — do not let the fix for it complicate the fix above.

# Defect 2 (must fix) — fixture inputs are presented as an API allow-list

Every value seen in a fixture is rendered as an "observed allowed value". The fixtures verify class emission; they are not a validation contract. The result misleads in two opposite directions:

- **Too narrow.** `avatar`'s `shape` reads `default: 'circle'; observed allowed values: "square"` — `circle` is valid and is the default, but the line reads like it is not an option. Any prop whose default never appears explicitly in a case has this problem.
- **Too broad / nonsense.** `progress` lists value `30` and an input error text `"Invalid"` as "allowed values". Those are incidental test inputs, not enumerable constraints.

Requirement: the reference must distinguish between a prop whose values form a real closed set (they select a class modifier — variant, size, tone, orientation, shape) and a free-form prop whose fixture values are merely examples (label, error, name, src, width, statusLabel, numeric values…). The first may be presented as the values that exist; the second must be presented as examples and must not read as an enumeration. In both cases the default value must be part of the picture, never contradicted by the values shown. Word it so that an LLM reading only this document cannot conclude a valid value is invalid, nor that an arbitrary test input is a constraint.

# Defect 3 (must fix) — the parser can silently describe the wrong API

`strpos($template, '@props(')` is too naive in two reproducible ways, and both produce a wrong document that the freshness test then blesses:

- `@props ([...])` with whitespace before the parenthesis — valid Blade — is not found, and the component is silently reported as having no props.
- A Blade comment containing a `@props(...)` example before the real directive is parsed as if it were the API.

Also, `parseLiteral()` guesses instead of admitting ignorance: it decides numeric type by looking for a dot (`1e-3` becomes integer `0`), reads octal as decimal, and `parseStringLiteral()` accepts a concatenation like `"in"."fo"` as the literal text `in"."fo`.

Requirement: a default the parser cannot understand with certainty must be reported as unknown — never guessed. A template whose `@props` cannot be located unambiguously must fail generation with a clear message naming the file, not silently yield "no props". Do not build a general PHP expression evaluator; the correct behavior at the edges is to stop or to say "unknown".

# Defect 4 (must fix) — missing guards and weak tests

- Zero components discovered (a wrong path, a failed glob) currently produces a valid-looking empty document that would be committed and then pass the freshness test forever. Generation must fail loudly instead.
- An empty fixture array produces a blank class pattern. Same treatment.
- The freshness test compares the generator against itself, so every defect above passed it. Add real unit tests for the parser edges (whitespace directive, comment-before-directive, unknown default) and for the class-reference honesty (the `separator` alternatives, a declared free-form prop whose value coincides with a class modifier suffix, a consumer `class` token equal to an intrinsic class).
- Remove the defensive dead branches in `tests/Feature/BoostGuidelinesTest.php` — `expect(...)->toBeTrue()` already fails the test, so the `if (...) { return; }` guards after them are unreachable noise that makes the test read as if it can degrade.

# Explicitly rejected — do NOT spend effort here

- Float rendering precision (`serialize_precision`) and CRLF/multiline default determinism. No current input hits them; do not add machinery for it. If you want, one sentence in your report is enough.
- Do not add a general PHP parser or a dependency to solve Defect 3.
- Do not change the two closed decisions: generation from this package's own files, committed file plus freshness test.

# Conventions

Unchanged: existing code style, strict types, `final` classes; every changed line traces to this feedback; no dependency and no `composer.json` require changes; no reformatting of untouched code; no drive-by refactors; a genuine dead end gets `// WORKAROUND: <reason>` plus a flag in your report.

# Method

If superpowers skills are available in your environment, conduct the work with them — `test-driven-development` for implementation, `systematic-debugging` for investigation. Otherwise work test-first: each defect gets its failing test before the fix.

# Test laws

Test the behavior, never the mock. A failing test means fix the code, not the test. No test-only flags or branches in production code.

# Acceptance criteria

1. Each of Defects 1–4 has a test that fails before your fix and passes after.
2. The regenerated `resources/boost/guidelines/lyra-blade.md` contains no class combination that no fixture case produces — `separator` specifically must read as alternatives.
3. No prop line can lead a reader to think the default value is invalid, and free-form values are not presented as an enumeration.
4. Generation fails with a clear message on: unlocatable/ambiguous `@props`, zero components discovered, empty fixture array.
5. The regeneration command is still idempotent — running it twice leaves the file unchanged — and the freshness test still turns red when a component is added without regenerating.
6. Full suite green and `vendor/bin/pint` clean.
7. `resources/boost/guidelines/lyra-blade.md` is regenerated and committed as part of your work (the file on disk must match what the generator produces).

# Scope — the closed list of paths you may change

- `src/` (the generator and any new source file it needs)
- `bin/generate-boost-guidelines`
- `tests/Feature/BoostGuidelinesTest.php` and, if you prefer them separated, one additional new test file under `tests/Feature/`
- `resources/boost/guidelines/lyra-blade.md`
- `CONTRIBUTING.md` only if the regeneration instructions change

Do not change anything outside this list. In particular: `resources/views/components/`, `tests/Fixtures/`, existing tests, `README.md`, `.github/`, `docs/`, `composer.json`, `.batuta/`, `WORK.md` are all off limits.

# Expected evidence

- Files touched with a one-line reason each.
- Exact commands and real output: `vendor/bin/pest` with final counts, `vendor/bin/pint`, and the regeneration command run twice showing no change the second time.
- The `separator` entry as it reads after your fix, quoted verbatim.
- How you decided which props have a closed value set and which are free-form, and where that decision could be wrong.
- Uncertainty declared as uncertainty.

# Stop conditions

Stop and report when: the same command fails twice; the fix would need edits outside Scope; or distinguishing closed-set props from free-form props turns out to be impossible from the available sources without inventing knowledge (in that case report what additional source would be needed).
