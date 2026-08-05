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
| Research | project map sweep, brief context, "where does X live?" | opencode + `opencode/deepseek-v4-flash-free` | free tier |

## Rules

The rules from the plugin's default `routing.md` apply unchanged: the
orchestrator classifies and announces in one line; verbal user overrides win;
automatic escalation after 2 failed verifications moves the task one row up;
executor unavailable → next row up (Research has no ladder — the maestro does
the research itself). Model IDs above were discovered via `opencode models` on
this machine; if one disappears, re-run discovery and re-confirm before
delegating.

Commit attribution (user request, 2026-08-05): commits of delegated work must
credit the real executor — `Co-Authored-By` trailer for the delegate (e.g.
Codex/opencode model) plus a body line naming who implemented and who
reviewed/verified. Never commit delegate output with maestro-only attribution.
