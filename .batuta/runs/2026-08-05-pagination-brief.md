# Brief — Pagination component (React → Blade) — onda 6, item 2/3

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/pagination (a git worktree of lyra-ds/blade; branch batuta/pagination).

## Goal

Create the `<x-lyra::pagination>` Blade component mirroring the React Pagination's classes and structure, adapted to LINKS (PRD §3 closed decision: phase 1 is static — React's `onChange` callback becomes href navigation; class strings stay identical).

## Context

Mirror the established trio structure. React contract verified against packages/react/src/pagination/pagination.tsx.

Props:
- `page`: int (one-based, required)
- `total`: int (total pages, required)
- `url`: string template with `{page}` placeholder (e.g. `/posts?page={page}`) — Blade adaptation replacing `onChange` (PRD-closed deviation; documented in a docblock)
- `aria-label`: default `Pagination`, consumer-overridable (merge semantics)
- `previousLabel`: default `Previous page`; `nextLabel`: default `Next page`

Windowing algorithm (EXACT port):
```
total <= 7        → [1..total]
page <= 4         → [1,2,3,4,5, gap, total]
page >= total-3   → [1, gap, total-4, total-3, total-2, total-1, total]
otherwise         → [1, gap, page-1, page, page+1, gap, total]
```

Rendered markup (exact classes; buttons → links adaptation):
```html
<nav class="lyra-pagination[ user classes last]" aria-label="Pagination" {...passthrough}>
  <!-- prev: page > 1 → --> <a class="lyra-page" href="{url for page-1}" aria-label="Previous page">‹</a>
  <!-- prev at boundary  --> <span class="lyra-page" aria-disabled="true">‹</span>
  <!-- number, not current --> <a class="lyra-page" href="{url for N}">N</a>
  <!-- current --> <a class="lyra-page lyra-page--active" href="{url for page}" aria-current="page">{page}</a>
  <!-- gap --> <span class="lyra-page lyra-page--gap" aria-hidden="true">…</span>
  <!-- next: mirror of prev with › and nextLabel -->
</nav>
```
- Class strings identical to React (`lyra-page`, `lyra-page--active`, `lyra-page--gap`); root `'lyra-pagination'` + user classes LAST.
- Prev/next content: `‹` and `›`. Gap content: `…`.

## Conventions

PHP >= 8.3, Laravel 11/12 Blade anonymous component. Pest + Pint (run both). TDD: failing tests first. Conventional commits (WIP allowed). Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.

## Task

1. `tests/Fixtures/class-emission/pagination.json` — cases: base, user class.
2. `resources/views/components/pagination.blade.php`
3. `tests/Feature/PaginationTest.php` — fixture-driven class assertions + markup: all four windowing branches (e.g. total=5; total=10 page=2; total=10 page=8; total=10 page=5), current link with --active + aria-current, gap spans, boundary prev/next as aria-disabled spans, url template substitution, aria-label defaults and overrides, passthrough, user class last.

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green); `vendor/bin/pint --test` passes.
- `<x-lyra::pagination :page="5" :total="10" url="/p?page={page}" />` → sequence 1, …, 4, 5(active, aria-current), 6, …, 10; prev href `/p?page=4`, next href `/p?page=6`.
- `<x-lyra::pagination :page="1" :total="3" url="/p?page={page}" class="x" />` → root class exactly `lyra-pagination x`; prev is `<span class="lyra-page" aria-disabled="true">`; pages 1(active) 2 3; no gaps.

## Boundaries / Scope (closed list — nothing outside it; if the task requires it, stop and report)

- resources/views/components/pagination.blade.php (new)
- tests/Fixtures/class-emission/pagination.json (new)
- tests/Feature/PaginationTest.php (new)

No CSS/JS, no new dependencies.

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
