<?php

use Illuminate\Support\Facades\Blade;

function renderStat(array $props = []): string
{
    $label = $props['label'] ?? 'MRR';
    $value = $props['value'] ?? 'R$ 10k';
    $delta = $props['delta'] ?? null;
    unset($props['label'], $props['value'], $props['delta']);

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    $deltaSlot = $delta === null
        ? ''
        : sprintf('<x-slot:delta>%s</x-slot:delta>', $delta);

    return Blade::render(sprintf(
        '<x-lyra::stat %s><x-slot:label>%s</x-slot:label><x-slot:value>%s</x-slot:value>%s</x-lyra::stat>',
        $attributes,
        $label,
        $value,
        $deltaSlot,
    ));
}

function statOpeningTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function statRootClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function statDelta(string $html): ?array
{
    $matched = preg_match(
        '/<span\b[^>]*\bclass="([^"]*\blyra-stat__delta\b[^"]*)"[^>]*>(.*?)<\/span>/s',
        $html,
        $matches,
    );

    if ($matched === 0) {
        return null;
    }

    expect($matched)->toBe(1);

    return [
        'class' => $matches[1],
        'content' => $matches[2],
    ];
}

dataset('stat class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/stat.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the stat class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class strings', function (array $case): void {
    $html = renderStat($case['props']);

    expect(statRootClass($html))->toBe($case['expected_class']);

    if (isset($case['expected_delta_class'])) {
        expect(statDelta($html))->toBe([
            'class' => $case['expected_delta_class'],
            'content' => $case['expected_delta_content'],
        ]);
    }
})->with('stat class emission');

it('renders label and value spans in order without a delta by default', function (): void {
    $html = renderStat();
    $labelPosition = strpos($html, '<span class="lyra-stat__label">MRR</span>');
    $valuePosition = strpos($html, '<span class="lyra-stat__value">R$ 10k</span>');

    expect(statRootClass($html))->toBe('lyra-stat')
        ->and($labelPosition)->toBeInt()
        ->and($valuePosition)->toBeInt()
        ->and($labelPosition)->toBeLessThan($valuePosition)
        ->and(statDelta($html))->toBeNull();
});

it('renders the default flat direction and arrow when delta is present', function (): void {
    $html = renderStat(['delta' => '+1%']);
    $valueBeforeDelta = preg_match(
        '/<span class="lyra-stat__value">.*<span class="lyra-stat__delta/s',
        $html,
    );

    expect(statDelta($html))->toBe([
        'class' => 'lyra-stat__delta lyra-stat__delta--flat',
        'content' => '→ +1%',
    ])->and($valueBeforeDelta)->toBe(1);
});

it('passes attributes through and keeps user classes last', function (): void {
    $html = renderStat([
        'class' => 'x y',
        'id' => 'revenue',
        'data-track' => 'stat',
        'aria-live' => 'polite',
    ]);
    $openingTag = statOpeningTag($html);

    expect(statRootClass($html))->toBe('lyra-stat x y')
        ->and($openingTag)->toContain('id="revenue"')
        ->and($openingTag)->toContain('data-track="stat"')
        ->and($openingTag)->toContain('aria-live="polite"');
});
