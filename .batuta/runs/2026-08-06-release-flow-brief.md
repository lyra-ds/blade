# Brief — Release flow (release-please) — pós-fase 1

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/release-flow (a git worktree of lyra-ds/blade; branch batuta/release-flow).
CRITICAL PATH RULE: every file you create or edit MUST be under the current working directory using RELATIVE paths.

## Goal

Create the automated release flow mirroring the lyra monorepo's changesets UX, using release-please (the Composer-world equivalent): a bot-maintained release PR that, when merged, tags vX.Y.Z and creates the GitHub Release with the changelog. Packagist picks the tag via webhook — there is NO publish step.

## Deliverables

1. `.github/workflows/release.yml`:
   - Trigger: `push` to `main`. Permissions: `contents: write`, `pull-requests: write`. Concurrency group `release`, no cancel-in-progress.
   - Guard: `if: github.repository == 'lyra-ds/blade'` (never run on forks).
   - Single job using `googleapis/release-please-action@v4` with `config-file` + `manifest-file` (below); `token: ${{ secrets.RELEASE_PLEASE_PAT || secrets.GITHUB_TOKEN }}`.
   - Header comment (English) adapted from the lyra release.yml lesson: PRs created with GITHUB_TOKEN never trigger workflows (GitHub anti-recursion), so the release PR would sit without its required CI checks; a fine-grained PAT (Contents + Pull requests, this repo only, owner franciscpd) fixes it; on PAT expiry the symptom is release PRs with no checks. Also state: no publish step — Packagist's GitHub webhook publishes on tag push.
2. `release-please-config.json` (repo root): release-type `php`, single package `"."`, `"package-name": "lyra-ds/blade"`, `"release-as": "0.1.0"` with a comment-equivalent note in the README section (JSON has no comments) — document in the workflow header that `release-as` MUST be removed after the first release PR merges, or every release repeats 0.1.0.
3. `.release-please-manifest.json`: `{}` (empty — no releases yet; release-please bootstraps from it).
4. README.md: add a short "Releasing" section (English): conventional commits drive the changelog; the release PR is merged to cut a release; the tag triggers Packagist via webhook; compat matrix must be reviewed at each release.

## Facts / constraints

- composer.json has NO `version` field — keep it that way (Packagist versions from tags; release-please php type tolerates the absent field).
- Conventional commits are already the repo standard (history is clean since the first commit).
- Do NOT create any tag or release yourself — the workflow does that after merge.
- Run `vendor/bin/pest` (must stay 260 passed) and `vendor/bin/pint --test` to prove nothing broke.

## Boundaries / Scope (closed list — nothing outside it; stop and report if needed)

- .github/workflows/release.yml (new)
- release-please-config.json (new)
- .release-please-manifest.json (new)
- README.md (add Releasing section only)

## Expected evidence

Report: files created; pest/pint output; any deviation declared.

## Stop conditions

Stop and report when: a needed fact is missing (do not invent), the same command fails twice, or the change needs files beyond Scope.
