<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

function renderSegmentedControl(array $props = []): string
{
    $options = $props['options'] ?? [
        ['value' => 'day', 'label' => 'Day'],
        ['value' => 'week', 'label' => 'Week'],
        ['value' => 'month', 'label' => 'Month'],
    ];
    $value = $props['value'] ?? 'week';
    $label = $props['label'] ?? 'Period';
    unset($props['options'], $props['value'], $props['label']);

    $attributes = collect($props)
        ->map(fn (mixed $attributeValue, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $attributeValue, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf(
            '<x-lyra::segmented-control :options="$options" :value="$value" :label="$label" %s />',
            $attributes,
        ),
        compact('options', 'value', 'label'),
    );
}

function segmentedControlRootTag(string $html): string
{
    $matched = preg_match('/<div\b(?=[^>]*\bclass="lyra-segmented(?: [^"]*)?")[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

/**
 * @return list<string>
 */
function segmentedControlOptionTags(string $html): array
{
    $matched = preg_match_all(
        '/<button\b(?=[^>]*\bclass="lyra-segmented__option(?: [^"]*)?")[^>]*>/',
        $html,
        $matches,
    );

    expect($matched)->not->toBeFalse();

    return $matches[0];
}

function segmentedControlClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="(lyra-segmented(?: [^"]*)?)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

dataset('segmented control class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/segmented-control.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the segmented-control class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React root class string', function (array $case): void {
    expect(segmentedControlClass(renderSegmentedControl($case['props'])))->toBe($case['expected_class']);
})->with('segmented control class emission');

it('renders namespaced and short syntax identically', function (): void {
    $options = [
        ['value' => 'day', 'label' => 'Day'],
        ['value' => 'week', 'label' => 'Week'],
    ];
    $namespaced = Blade::render(
        '<x-lyra::segmented-control :options="$options" value="day" label="Period" />',
        compact('options'),
    );
    $short = Blade::render(
        '<lyra:segmented-control :options="$options" value="day" label="Period" />',
        compact('options'),
    );

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('class="lyra-segmented"');
});

it('serves the radiogroup and modelable Alpine state contract', function (): void {
    $root = segmentedControlRootTag(renderSegmentedControl([
        'value' => 'week',
        'label' => 'Choose a period',
        'wire:model.live' => 'period',
        'id' => 'period-control',
    ]));

    expect($root)->toContain('role="radiogroup"')
        ->and($root)->toContain('aria-label="Choose a period"')
        ->and($root)->toContain('x-data="lyraSegmentedControl({ value: \'week\' })"')
        ->and($root)->toContain('x-modelable="value"')
        ->and($root)->toContain('wire:model.live="period"')
        ->and($root)->toContain('id="period-control"');
});

it('JavaScript-escapes the initial value and safely escapes the accessible label', function (): void {
    $value = "a'b\\c\r\n<d>";
    $root = segmentedControlRootTag(renderSegmentedControl([
        'options' => [
            ['value' => $value, 'label' => 'Selected'],
            ['value' => 'other', 'label' => 'Other'],
        ],
        'value' => $value,
        'label' => 'A "quoted" <label>',
    ]));

    expect($root)->toContain("x-data=\"lyraSegmentedControl({ value: 'a\\'b\\\\c\\r\\n&lt;d&gt;' })\"")
        ->and($root)->toContain('aria-label="A &quot;quoted&quot; &lt;label&gt;"');
});

it('renders every option with the complete served binding selector contract', function (): void {
    $html = renderSegmentedControl(['value' => 'week']);
    $options = segmentedControlOptionTags($html);

    expect($options)->toHaveCount(3)
        ->and($options[0])->toContain('type="button"')
        ->and($options[0])->toContain('role="radio"')
        ->and($options[0])->toContain('class="lyra-segmented__option"')
        ->and($options[0])->toContain('data-value="day"')
        ->and($options[0])->toContain('x-bind="option"')
        ->and($options[0])->toContain('aria-checked="false"')
        ->and($options[0])->toContain('tabindex="-1"')
        ->and($options[1])->toContain('class="lyra-segmented__option lyra-segmented__option--active"')
        ->and($options[1])->toContain('aria-checked="true"')
        ->and($options[1])->toContain('tabindex="0"')
        ->and($html)->toMatch('/<button\b[^>]*data-value="day"[^>]*>\s*Day\s*<\/button>/s')
        ->and($html)->toMatch('/<button\b[^>]*data-value="week"[^>]*>\s*Week\s*<\/button>/s');
});

it('serves disabled natively and falls focus back when the selected option is disabled', function (): void {
    $options = segmentedControlOptionTags(renderSegmentedControl([
        'options' => [
            ['value' => 'day', 'label' => 'Day'],
            ['value' => 'week', 'label' => 'Week', 'disabled' => true],
            ['value' => 'month', 'label' => 'Month'],
        ],
        'value' => 'week',
    ]));

    expect($options[0])->toContain('tabindex="0"')->not->toContain(' disabled')
        ->and($options[1])->toContain('disabled')->toContain('tabindex="-1"')
        ->and($options[1])->toContain('aria-checked="true"')
        ->and($options[1])->toContain('lyra-segmented__option--active')
        ->and($options[2])->toContain('tabindex="-1"')->not->toContain(' disabled');
});

it('falls focus to the first enabled option when value matches no option', function (): void {
    $options = segmentedControlOptionTags(renderSegmentedControl(['value' => 'missing']));

    expect($options[0])->toContain('tabindex="0"')
        ->and($options[1])->toContain('tabindex="-1"')
        ->and($options[2])->toContain('tabindex="-1"')
        ->and(implode('', $options))->not->toContain('aria-checked="true"')
        ->and(implode('', $options))->not->toContain('lyra-segmented__option--active');
});

it('skips a disabled first option when choosing the focusable fallback', function (): void {
    $options = segmentedControlOptionTags(renderSegmentedControl([
        'options' => [
            ['value' => 'day', 'label' => 'Day', 'disabled' => true],
            ['value' => 'week', 'label' => 'Week'],
            ['value' => 'month', 'label' => 'Month'],
        ],
        'value' => 'missing',
    ]));

    expect($options[0])->toContain('disabled')->toContain('tabindex="-1"')
        ->and($options[1])->toContain('tabindex="0"')
        ->and($options[2])->toContain('tabindex="-1"');
});

it('gives no option tabindex zero when every option is disabled', function (): void {
    $options = segmentedControlOptionTags(renderSegmentedControl([
        'options' => [
            ['value' => 'day', 'label' => 'Day', 'disabled' => true],
            ['value' => 'week', 'label' => 'Week', 'disabled' => true],
        ],
        'value' => 'missing',
    ]));

    expect($options)->toHaveCount(2)
        ->and($options[0])->toContain('disabled')->toContain('tabindex="-1"')
        ->and($options[1])->toContain('disabled')->toContain('tabindex="-1"')
        ->and(implode('', $options))->not->toContain('tabindex="0"');
});

it('normalizes non-sequential option keys before computing selected and focusable indexes', function (): void {
    $options = segmentedControlOptionTags(renderSegmentedControl([
        'options' => [
            3 => ['value' => 'day', 'label' => 'Day'],
            7 => ['value' => 'week', 'label' => 'Week'],
        ],
        'value' => 'week',
    ]));

    expect($options)->toHaveCount(2)
        ->and($options[0])->toContain('tabindex="-1"')
        ->and($options[1])->toContain('tabindex="0"')->toContain('aria-checked="true"');
});

it('escapes option values and string labels while preserving Htmlable labels', function (): void {
    $html = renderSegmentedControl([
        'options' => [
            ['value' => 'raw', 'label' => new HtmlString('<strong>Raw</strong>')],
            ['value' => 'quote"<unsafe>', 'label' => '<strong>Escaped</strong>'],
        ],
        'value' => 'raw',
    ]);
    $options = segmentedControlOptionTags($html);

    expect($options[1])->toContain('data-value="quote&quot;&lt;unsafe&gt;"')
        ->and($html)->toContain('<strong>Raw</strong>')
        ->and($html)->toContain('&lt;strong&gt;Escaped&lt;/strong&gt;')
        ->and($html)->not->toContain('data-value="quote"<unsafe>"');
});
