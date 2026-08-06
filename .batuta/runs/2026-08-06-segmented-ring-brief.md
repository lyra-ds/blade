# Brief — SegmentedRing component (React → Blade) — fase 2, onda A, item 5/13

Work only inside /home/franciscpd/Projects/lyra-ds/blade/.batuta/worktrees/segmented-ring (a git worktree of lyra-ds/blade; branch batuta/segmented-ring). Run `composer install` there first — the worktree starts without vendor/.

## Goal

Create the `<x-lyra::segmented-ring>` Blade component mirroring the React SegmentedRing — including its exact arc-computation algorithm — with data-driven class-emission tests proving exact class-string parity.

## Context

The package ships 29 components built to this exact pattern — study and mirror:
- resources/views/components/progress.blade.php (closest sibling: SVG + computed values)
- tests/Feature/ProgressTest.php, tests/Fixtures/class-emission/badge.json (fixture pattern)

React contract (verified against packages/react/src/segmented-ring/segmented-ring.tsx in the main repo — do NOT deviate; never invent API). The algorithm below IS the contract — port it exactly:

Props:
- `segments` (array, default `[]`) — items of shape `{value: number, label: string, tone?: success|accent|danger|warning|neutral, color?: string}`. `color` overrides `tone`; tone default `neutral`. Tone→CSS var map: success→`var(--success)`, accent→`var(--accent)`, danger→`var(--danger)`, warning→`var(--warning)`, neutral→`var(--border-strong)`.
- `total` (number, optional) — ring denominator; defaults to the sum of positive segments (via the scaled algorithm below).
- `centerValue` (string, optional), `centerLabel` (string, optional).
- `size` (`md`|`lg`, default `lg`) — lg: px=160, stroke=12; md: px=96, stroke=9.
- `stacked` (bool, default false), `showLegend` (bool, default true).

Algorithm (exact):
```
radius = (px - stroke) / 2
circumference = 2 * M_PI * radius
visibleSegments = segments where value is finite AND > 0
largestSegment = max(0, ...visibleSegments values)
scaledTotal = sum over visibleSegments of (value / largestSegment)
scaledDenominator = (total is finite AND total > 0) ? total / largestSegment : scaledTotal
remaining = 1
boundedSegments = for each visibleSegment in order:
    requestedFraction = value / largestSegment / scaledDenominator
    fraction = is_finite(requestedFraction) ? clamp(requestedFraction, 0, remaining) : remaining
    remaining -= fraction
  keep only fraction > 0
gap = count(boundedSegments) > 1 ? 2.5 : 0
accumulated = 0
arcs = for each boundedSegment in order:
    length = max(0, fraction * circumference - gap)
    dash = "{length} {circumference - length}"   (numbers formatted as PHP prints floats — shortest round-trip)
    offset = -accumulated * circumference + circumference / 4
    accumulated += fraction
```

Rendered markup (exact):
```html
<div class="lyra-ring lyra-ring--{size}[ lyra-ring--stacked][ user classes last]" {...passthrough}>
  <span class="lyra-ring__wrap" aria-hidden="true">
    <svg width="{px}" height="{px}" viewBox="0 0 {px} {px}" aria-hidden="true">
      <circle cx="{px/2}" cy="{px/2}" r="{radius}" fill="none" stroke="var(--surface-sunken)" stroke-width="{stroke}"></circle>
      <!-- one per arc, in order: -->
      <circle cx="{px/2}" cy="{px/2}" r="{radius}" fill="none" stroke="{segmentColor}" stroke-width="{stroke}" stroke-linecap="{gap ? round : butt}" stroke-dasharray="{dash}" stroke-dashoffset="{offset}"></circle>
    </svg>
    <span class="lyra-ring__center">
      [<span class="lyra-ring__num">{centerValue}</span>]
      [<span class="lyra-ring__cap">{centerLabel}</span>]
    </span>
  </span>
  <span class="lyra-visually-hidden">
    [{centerLabel} ]{centerValue} — ]        <!-- only when centerValue given; centerLabel prefix only when given -->
    {value} {label}[, {value} {label}...]    <!-- visibleSegments (positive only), comma-separated -->
  </span>
  [<ul class="lyra-ring__legend" aria-hidden="true">   <!-- only when showLegend -->
    <!-- one per segment of the ORIGINAL segments array (including non-positive): -->
    <li class="lyra-ring__li"><span class="lyra-ring__swatch" style="background-color: {segmentColor}"></span><span>{label}</span><span class="lyra-ring__val">{value}</span></li>
  </ul>]
</div>
```
- Root class order: `lyra-ring`, `lyra-ring--{size}`, then `lyra-ring--stacked` (only when stacked), then user classes ALWAYS LAST.
- The legend iterates ALL segments; the SVG arcs and the visually-hidden text iterate only positive ones.
- All other HTML attributes pass through to the root div.
- No default slot.

## Conventions

- PHP >= 8.3, Laravel 12/13 Blade anonymous component. Pest for tests, Pint for style (run both).
- TDD: failing tests first. Conventional commits (WIP allowed; history rewritten at integration).
- Test laws: test the behavior, never the mock; a failing test means fix the code, not the test; no test-only flags or branches in production code.
- Test assertions: per-element containment (toContain) / strpos ordering, never exact whole-output equality — Blade emits whitespace.
- View style: multi-line directives; NEVER butt two Blade directives together (`@endif@if` does not compile). Heavy computation belongs in the `@php` block.
- Ignore any Compozy skills (cy-*) — implement directly in the worktree.

## Task

1. `tests/Fixtures/class-emission/segmented-ring.json` — cases: base (lg), md, stacked, user class appended.
2. `resources/views/components/segmented-ring.blade.php` — anonymous component per the contract.
3. `tests/Feature/SegmentedRingTest.php` — fixture-driven exact class assertions + markup/algorithm tests:
   - background circle attrs for lg (r=74, stroke-width=12) and md (r=43.5, stroke-width=9);
   - single positive segment: one arc, `stroke-linecap="butt"`, dash starts at full fraction (no gap), offset = circumference/4;
   - two segments: `stroke-linecap="round"`, gap 2.5 applied, second arc offset = -fraction1*circumference + circumference/4;
   - non-positive segment values (0, negative) excluded from arcs and hidden text but present in the legend;
   - `total` denominator respected; segment exceeding total bounded (fractions never exceed 1 overall);
   - tone→CSS var mapping and `color` override in both arc stroke and legend swatch;
   - hidden-text formats: with and without centerValue/centerLabel;
   - `showLegend=false` removes the `<ul>`;
   - center spans conditional; attribute passthrough; user class last.
   Compute expected numbers in the test with the same PHP float-to-string formatting the view uses.
4. Regenerate `resources/boost/guidelines/lyra-blade.md` with `php bin/generate-boost-guidelines` (never hand-edit it).

## Acceptance criteria

- `vendor/bin/pest` passes (existing suite stays green, including the guidelines freshness test).
- All markup/algorithm behaviors above are pinned by tests.
- `vendor/bin/pint --test` passes.

## Boundaries

Do not touch anything outside Scope. No CSS/JS, no new dependencies.

## Scope (closed list — do not change anything outside it; if the task requires it, stop and report)

- resources/views/components/segmented-ring.blade.php (new)
- tests/Fixtures/class-emission/segmented-ring.json (new)
- tests/Feature/SegmentedRingTest.php (new)
- resources/boost/guidelines/lyra-blade.md (regenerated only)

## Expected evidence

Report: files touched; pest and pint runs with actual output; uncertainties declared as such.

## Stop conditions

Stop and report instead of improvising when: the code's shape contradicts this brief, the same command fails twice, or the fix needs edits beyond Scope.
