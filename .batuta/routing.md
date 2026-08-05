# Routing — lyra-ds/blade

Local copy (takes precedence over the plugin default). Confirmed with the user
at onboarding, 2026-08-04. Full trio installed: opencode 1.18.3 + codex 0.144.5
(ChatGPT subscription) + claude 2.1.222. Runtime: compozy (delegations run as
managed sessions per `compozy.md`).

| Complexity | Examples | Executor | Cost |
|---|---|---|---|
| Trivial | rename, config, copy change, simple unit test | opencode + `opencode/kimi-k2.7-code` | cents (API) |
| Medium | isolated feature, bugfix with clear repro | codex (default model) | ChatGPT subscription |
| Complex | multi-file feature/refactor that a precise brief can fully specify | codex `-m gpt-5.6-sol`, reasoning high (re-confirmed 2026-08-04 — daemon catalog) | ChatGPT subscription |
| Critical | architecture decisions, security-sensitive work, tasks needing the conversation's full context or real judgment | claude (session) | Claude subscription |

## Support lanes

| Role | Examples | Executor | Cost |
|---|---|---|---|
| Research | project map sweep, brief context, "where does X live?" | opencode + `lmstudio/prism-ml/bonsai-27b` (local; confirmed 2026-08-05 after live test — full report in 144s) | free (local GPU) |

## Rules

The rules from the plugin's default `routing.md` apply unchanged: the
orchestrator classifies and announces in one line; verbal user overrides win;
automatic escalation after 2 failed verifications moves the task one row up;
executor unavailable → next row up (Research has no ladder — the maestro does
the research itself). Model IDs above were discovered via `opencode models` on
this machine; if one disappears, re-run discovery and re-confirm before
delegating.

Research lane specifics (2026-08-05): requires the LM Studio server up
(`lms server status`; start with `lms server start`). Server down or model
unloaded → fall back to opencode + `opencode/deepseek-v4-flash` (paid API),
loudly. Scout briefs MUST be passed inline as the `opencode run` argument —
never via a temp file in /tmp: reading outside the project root triggers a
permission request that hangs/auto-rejects in non-interactive mode (bug
found 2026-08-05; cost two hung scouts). Bonsai needs an explicit
relative-paths-only rule in the brief — its first attempt globbed an
absolute path outside the repo and died on auto-reject.
`lmstudio/qwen/qwen3-coder-30b` is also available locally, untested — a
candidate if bonsai ever underdelivers.

Commit attribution (user request, 2026-08-05): commits of delegated work must
credit the real executor — `Co-Authored-By` trailer for the delegate (e.g.
Codex/opencode model) plus a body line naming who implemented and who
reviewed/verified. Never commit delegate output with maestro-only attribution.
