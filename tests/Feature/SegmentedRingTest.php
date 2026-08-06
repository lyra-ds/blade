<?php

use Illuminate\Support\Facades\Blade;

function renderSegmentedRing(array $props = []): string
{
    $segments = $props['segments'] ?? [];
    $total = $props['total'] ?? null;
    $centerValue = $props['centerValue'] ?? null;
    $centerLabel = $props['centerLabel'] ?? null;
    $size = $props['size'] ?? 'lg';
    $stacked = $props['stacked'] ?? false;
    $showLegend = $props['showLegend'] ?? true;

    unset(
        $props['segments'],
        $props['total'],
        $props['centerValue'],
        $props['centerLabel'],
        $props['size'],
        $props['stacked'],
        $props['showLegend'],
    );

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf(
            '<x-lyra::segmented-ring :segments="$segments" :total="$total" :center-value="$centerValue" :center-label="$centerLabel" :size="$size" :stacked="$stacked" :show-legend="$showLegend" %s />',
            $attributes,
        ),
        compact('segments', 'total', 'centerValue', 'centerLabel', 'size', 'stacked', 'showLegend'),
    );
}

function segmentedRingClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function segmentedRingOpeningTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

/** @return list<string> */
function segmentedRingCircles(string $html): array
{
    $matched = preg_match_all('/<circle\b[^>]*><\/circle>/', $html, $matches);

    expect($matched)->not->toBeFalse();

    return $matches[0];
}

function segmentedRingHiddenText(string $html): string
{
    $matched = preg_match('/<span class="lyra-visually-hidden">(.*?)<\/span>/s', $html, $matches);

    expect($matched)->toBe(1);

    return trim((string) preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($matches[1]))));
}

function segmentedRingNumber(float $number): string
{
    return (string) $number;
}

dataset('segmented ring class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/segmented-ring.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the segmented-ring class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(segmentedRingClass(renderSegmentedRing($case['props'])))->toBe($case['expected_class']);
})->with('segmented ring class emission');

it('renders the background circle dimensions for both sizes', function (): void {
    $large = segmentedRingCircles(renderSegmentedRing())[0];
    $mediumHtml = renderSegmentedRing(['size' => 'md']);
    $medium = segmentedRingCircles($mediumHtml)[0];

    expect($large)->toContain('cx="80"')
        ->and($large)->toContain('cy="80"')
        ->and($large)->toContain('r="74"')
        ->and($large)->toContain('stroke-width="12"')
        ->and($large)->toContain('stroke="var(--surface-sunken)"')
        ->and($mediumHtml)->toContain('<svg width="96" height="96" viewBox="0 0 96 96" aria-hidden="true">')
        ->and($medium)->toContain('cx="48"')
        ->and($medium)->toContain('cy="48"')
        ->and($medium)->toContain('r="43.5"')
        ->and($medium)->toContain('stroke-width="9"');
});

it('renders one gapless butt-capped arc at the top offset', function (): void {
    $circumference = 2 * M_PI * 74;
    $circles = segmentedRingCircles(renderSegmentedRing([
        'segments' => [
            ['value' => 7, 'label' => 'Done', 'tone' => 'success'],
        ],
    ]));

    expect($circles)->toHaveCount(2)
        ->and($circles[1])->toContain('stroke="var(--success)"')
        ->and($circles[1])->toContain('stroke-linecap="butt"')
        ->and($circles[1])->toContain(sprintf(
            'stroke-dasharray="%s %s"',
            segmentedRingNumber($circumference),
            segmentedRingNumber($circumference - $circumference),
        ))
        ->and($circles[1])->toContain('stroke-dashoffset="'.segmentedRingNumber($circumference / 4).'"');
});

it('applies the inter-segment gap and accumulated offset to multiple arcs', function (): void {
    $circumference = 2 * M_PI * 74;
    $fraction = 0.5;
    $length = max(0, $fraction * $circumference - 2.5);
    $secondOffset = -$fraction * $circumference + $circumference / 4;
    $circles = segmentedRingCircles(renderSegmentedRing([
        'segments' => [
            ['value' => 1, 'label' => 'First'],
            ['value' => 1, 'label' => 'Second'],
        ],
    ]));

    expect($circles)->toHaveCount(3)
        ->and($circles[1])->toContain('stroke-linecap="round"')
        ->and($circles[2])->toContain('stroke-linecap="round"')
        ->and($circles[1])->toContain(sprintf(
            'stroke-dasharray="%s %s"',
            segmentedRingNumber($length),
            segmentedRingNumber($circumference - $length),
        ))
        ->and($circles[2])->toContain('stroke-dashoffset="'.segmentedRingNumber($secondOffset).'"');
});

it('excludes non-positive segments from arcs and hidden text but keeps them in the legend', function (): void {
    $html = renderSegmentedRing([
        'segments' => [
            ['value' => 4, 'label' => 'Visible'],
            ['value' => 0, 'label' => 'Zero'],
            ['value' => -3, 'label' => 'Negative'],
        ],
    ]);

    expect(segmentedRingCircles($html))->toHaveCount(2)
        ->and(segmentedRingHiddenText($html))->toBe('4 Visible')
        ->and(substr_count($html, 'class="lyra-ring__li"'))->toBe(3)
        ->and($html)->toContain('<span>Zero</span>')
        ->and($html)->toContain('<span class="lyra-ring__val">0</span>')
        ->and($html)->toContain('<span>Negative</span>')
        ->and($html)->toContain('<span class="lyra-ring__val">-3</span>');
});

it('uses total as the denominator', function (): void {
    $circumference = 2 * M_PI * 74;
    $fraction = 0.25;
    $length = max(0, $fraction * $circumference - 2.5);
    $circles = segmentedRingCircles(renderSegmentedRing([
        'segments' => [
            ['value' => 25, 'label' => 'Used'],
            ['value' => 25, 'label' => 'Reserved'],
        ],
        'total' => 100,
    ]));

    expect($circles)->toHaveCount(3)
        ->and($circles[1])->toContain(sprintf(
            'stroke-dasharray="%s %s"',
            segmentedRingNumber($length),
            segmentedRingNumber($circumference - $length),
        ));
});

it('bounds segments that exceed total to one complete ring', function (): void {
    $circumference = 2 * M_PI * 74;
    $circles = segmentedRingCircles(renderSegmentedRing([
        'segments' => [
            ['value' => 75, 'label' => 'Over'],
            ['value' => 50, 'label' => 'Remainder'],
        ],
        'total' => 50,
    ]));

    expect($circles)->toHaveCount(2)
        ->and($circles[1])->toContain('stroke-linecap="butt"')
        ->and($circles[1])->toContain(sprintf(
            'stroke-dasharray="%s %s"',
            segmentedRingNumber($circumference),
            segmentedRingNumber($circumference - $circumference),
        ));
});

it('maps every tone and lets color override tone in arcs and legend swatches', function (): void {
    $segments = [
        ['value' => 1, 'label' => 'Success', 'tone' => 'success'],
        ['value' => 1, 'label' => 'Accent', 'tone' => 'accent'],
        ['value' => 1, 'label' => 'Danger', 'tone' => 'danger'],
        ['value' => 1, 'label' => 'Warning', 'tone' => 'warning'],
        ['value' => 1, 'label' => 'Neutral'],
        ['value' => 1, 'label' => 'Custom', 'tone' => 'danger', 'color' => '#123456'],
    ];
    $html = renderSegmentedRing(['segments' => $segments]);
    $circles = array_slice(segmentedRingCircles($html), 1);
    $colors = [
        'var(--success)',
        'var(--accent)',
        'var(--danger)',
        'var(--warning)',
        'var(--border-strong)',
        '#123456',
    ];

    expect($circles)->toHaveCount(count($colors));

    foreach ($colors as $index => $color) {
        expect($circles[$index])->toContain('stroke="'.$color.'"')
            ->and($html)->toContain('style="background-color: '.$color.'"');
    }
});

it('falls back to neutral for an unknown tone in the arc and legend', function (): void {
    $html = renderSegmentedRing([
        'segments' => [
            ['value' => 1, 'label' => 'Unknown', 'tone' => 'info'],
        ],
    ]);
    $circles = segmentedRingCircles($html);

    expect($circles)->toHaveCount(2)
        ->and($circles[1])->toContain('stroke="var(--border-strong)"')
        ->and($html)->toContain('style="background-color: var(--border-strong)"');
});

it('formats hidden text for every center value and label combination', function (): void {
    $segments = [
        ['value' => 3, 'label' => 'Ready'],
        ['value' => 2, 'label' => 'Waiting'],
    ];

    expect(segmentedRingHiddenText(renderSegmentedRing([
        'segments' => $segments,
        'centerValue' => '42',
        'centerLabel' => 'Completed',
    ])))->toBe('Completed 42 — 3 Ready, 2 Waiting')
        ->and(segmentedRingHiddenText(renderSegmentedRing([
            'segments' => $segments,
            'centerValue' => '42',
        ])))->toBe('42 — 3 Ready, 2 Waiting')
        ->and(segmentedRingHiddenText(renderSegmentedRing([
            'segments' => $segments,
            'centerLabel' => 'Completed',
        ])))->toBe('3 Ready, 2 Waiting')
        ->and(segmentedRingHiddenText(renderSegmentedRing([
            'segments' => $segments,
        ])))->toBe('3 Ready, 2 Waiting');
});

it('conditionally renders center spans and the legend', function (): void {
    $withCenter = renderSegmentedRing([
        'centerValue' => '64',
        'centerLabel' => 'Score',
    ]);
    $withoutCenter = renderSegmentedRing(['showLegend' => false]);

    expect($withCenter)->toContain('<span class="lyra-ring__num">64</span>')
        ->and($withCenter)->toContain('<span class="lyra-ring__cap">Score</span>')
        ->and($withCenter)->toContain('<ul class="lyra-ring__legend" aria-hidden="true">')
        ->and($withoutCenter)->not->toContain('class="lyra-ring__num"')
        ->and($withoutCenter)->not->toContain('class="lyra-ring__cap"')
        ->and($withoutCenter)->not->toContain('<ul');
});

it('passes attributes through, keeps user classes last, and ignores slot content', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::segmented-ring size="md" stacked class="first second" id="usage" data-track="ring">
            Must not render
        </x-lyra::segmented-ring>
        BLADE);
    $openingTag = segmentedRingOpeningTag($html);

    expect(segmentedRingClass($html))->toBe('lyra-ring lyra-ring--md lyra-ring--stacked first second')
        ->and($openingTag)->toContain('id="usage"')
        ->and($openingTag)->toContain('data-track="ring"')
        ->and($html)->not->toContain('Must not render');
});
