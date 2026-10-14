# Routing — lyra-ds/blade

<!-- inputs: profile.md@sha256:469f388a3fb3 -->

Confirmed by the maintainer on 2026-10-14 after full installed-adapter inventory. OpenCode's Zen
models are catalogued but live GLM/DeepSeek calls returned insufficient-funds
402 errors. Codex routes use the installed ChatGPT subscription. Cursor is
not installed. Agy remains an available unrouted alternative.

| Lane     | Domain | Executor | Model                            | Reasoning / cost     |
| -------- | ------ | -------- | -------------------------------- | -------------------- |
| low      | *      | codex    | gpt-6-luna                       | low; subscription    |
| medium   | *      | codex    | gpt-6.1-sol                      | medium; subscription |
| high     | *      | codex    | gpt-6-astra                      | high; subscription   |
| critical | *      | self     | Current conducting session model | Host session         |

## Support lanes

| Role     | Lane   | Executor | Model       | Reasoning / cost             |
| -------- | ------ | -------- | ----------- | ---------------------------- |
| research | low    | codex    | gpt-6-luna  | low; read-only               |
| research | medium | codex    | gpt-6.1-sol | medium; read-only            |
| review   | —      | claude   | sonnet      | Read-only independent review |

## Rules

Honor user overrides and announce the selected lane, executor, model and effort.
Escalate after two failed verifications or an unavailable executor. External
CLI dispatch is the default; use the installed Batuta core. Close executor
stdin with /dev/null. Use read-only research/review commands and verify the
working tree guard. Research with no available next row falls back to the
conductor. Current exact model IDs were discovered from installed adapters.
Do not infer credentials from catalogue presence. Never add an unrouted CLI
or replace an unavailable model without the routing policy or user override.

## Retained delivery rules

Commit types describe published delivery: use `fix:` or `feat:` only for a
package behavior change; setup, test tooling and release chores use the
appropriate non-release type so release-please does not announce a false fix.
Credit the real executor in delegated commits with a Co-Authored-By trailer
and state who implemented and who independently reviewed or verified.
Preserve candidate work in an owned commit before risky verification, then
commit verified delivery before the final report. The conductor owns these
commits in the current CLI workflow; old Compozy commit sessions are historical.
Repeat the absolute worktree path in the brief body and verify the branch
before editing; `--cwd` alone was not reliable in historical sessions.
Repeat conventions in every brief; truncated worker output is never proof.
Compozy is an installed historical runtime, intentionally unrouted. Only the
five shipped adapters inventoried above are eligible without a user override.
