<?php

/**
 * Tooltip intentionally has no Livewire smoke test because it exposes no controllable state.
 */

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

function renderTooltip(
    string $tip = 'Helpful context',
    array $props = [],
    string|Htmlable $slot = 'Target',
): string {
    $placement = $props['placement'] ?? 'top';
    unset($props['placement']);

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf(
            '<x-lyra::tooltip :tip="$tip" :placement="$placement" %s>{{ $slot }}</x-lyra::tooltip>',
            $attributes,
        ),
        compact('tip', 'placement', 'slot'),
    );
}

function tooltipOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<span\b(?=[^>]*\bx-data="lyraTooltip\()[^>]*>/',
        'target' => '/<span\b(?=[^>]*\bx-bind="target")[^>]*>/',
        'bubble' => '/<span\b(?=[^>]*\brole="tooltip")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function tooltipClass(string $html): string
{
    $matched = preg_match('/<span\b[^>]*\bclass="(lyra-tooltip(?: [^"]*)?)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

dataset('tooltip class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/tooltip.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the tooltip class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React root class string', function (array $case): void {
    $html = renderTooltip(props: $case['props']);

    expect(tooltipClass($html))->toBe($case['expected_class']);
})->with('tooltip class emission');

it('emits the default served state and passes attributes through on the root', function (): void {
    $html = renderTooltip(
        tip: 'Save & continue',
        props: [
            'class' => 'x y',
            'id' => 'save-help',
            'data-track' => 'tooltip',
        ],
    );
    $root = tooltipOpeningTag($html, 'root');

    expect($root)->toContain("x-data=\"lyraTooltip({ tip: 'Save &amp; continue', placement: 'top' })\"")
        ->and($root)->toContain('x-bind="root"')
        ->and($root)->toContain('data-tip="Save &amp; continue"')
        ->and($root)->toContain('data-state="closed"')
        ->and($root)->toContain('id="save-help"')
        ->and($root)->toContain('data-track="tooltip"')
        ->and(tooltipClass($html))->toBe('lyra-tooltip x y');
});

it('emits a non-default placement in the exact Alpine literal and served class', function (): void {
    $html = renderTooltip(props: ['placement' => 'bottom']);
    $root = tooltipOpeningTag($html, 'root');

    expect($root)->toContain("x-data=\"lyraTooltip({ tip: 'Helpful context', placement: 'bottom' })\"")
        ->and(tooltipClass($html))->toBe('lyra-tooltip lyra-tooltip--bottom');
});

it('substitutes malformed UTF-8 in the Alpine tip literal', function (): void {
    $root = tooltipOpeningTag(renderTooltip(tip: "\xC3\x28"), 'root');

    expect($root)->toContain("x-data=\"lyraTooltip({ tip: '�(', placement: 'top' })\"");
});

it('JavaScript-escapes quotes, slashes, and line breaks in the Alpine tip literal', function (): void {
    $tip = "a'b\\c\r\nd";
    $root = tooltipOpeningTag(renderTooltip(tip: $tip), 'root');

    expect($root)->toContain("x-data=\"lyraTooltip({ tip: 'a\\'b\\\\c\\r\\nd', placement: 'top' })\"");
});

it('keeps component-owned root attributes ahead of a consumer x-data attribute', function (): void {
    $root = tooltipOpeningTag(renderTooltip(props: [
        'x-data' => 'consumerState',
    ]), 'root');
    $componentDataPosition = strpos($root, "x-data=\"lyraTooltip({ tip: 'Helpful context', placement: 'top' })\"");
    $rootBindingPosition = strpos($root, 'x-bind="root"');
    $tipPosition = strpos($root, 'data-tip="Helpful context"');
    $statePosition = strpos($root, 'data-state="closed"');
    $consumerDataPosition = strpos($root, 'x-data="consumerState"');

    expect($componentDataPosition)->toBeInt()
        ->and($rootBindingPosition)->toBeInt()
        ->and($tipPosition)->toBeInt()
        ->and($statePosition)->toBeInt()
        ->and($consumerDataPosition)->toBeInt()
        ->and($componentDataPosition)->toBeLessThan($consumerDataPosition)
        ->and($rootBindingPosition)->toBeLessThan($consumerDataPosition)
        ->and($tipPosition)->toBeLessThan($consumerDataPosition)
        ->and($statePosition)->toBeLessThan($consumerDataPosition);
});

it('coerces an unknown placement to top without a placement modifier', function (): void {
    $html = renderTooltip(props: ['placement' => 'diagonal']);
    $root = tooltipOpeningTag($html, 'root');

    expect($root)->toContain("x-data=\"lyraTooltip({ tip: 'Helpful context', placement: 'top' })\"")
        ->and(tooltipClass($html))->toBe('lyra-tooltip')
        ->and($root)->not->toContain('lyra-tooltip--');
});

it('wraps Htmlable and plain target content with the target binding', function (): void {
    $htmlable = renderTooltip(slot: new HtmlString('<button type="button">Raw target</button>'));
    $plain = renderTooltip(slot: '<button type="button">Plain target</button>');

    expect(tooltipOpeningTag($htmlable, 'target'))->toBe('<span x-bind="target">')
        ->and($htmlable)->toContain('<span x-bind="target"><button type="button">Raw target</button></span>')
        ->and($plain)->toContain('<span x-bind="target">&lt;button type=&quot;button&quot;&gt;Plain target&lt;/button&gt;</span>');
});

it('renders an escaped assistive bubble and no server ids', function (): void {
    $tip = 'Use <strong>care</strong> & "focus"';
    $html = renderTooltip(tip: $tip);
    $root = tooltipOpeningTag($html, 'root');
    $target = tooltipOpeningTag($html, 'target');
    $bubble = tooltipOpeningTag($html, 'bubble');

    expect($root)->toContain('data-tip="Use &lt;strong&gt;care&lt;/strong&gt; &amp; &quot;focus&quot;"')
        ->and($bubble)->toContain('role="tooltip"')
        ->and($bubble)->toContain(' hidden')
        ->and($bubble)->toContain('x-bind="bubble"')
        ->and($html)->toContain('<span role="tooltip" hidden x-bind="bubble">Use &lt;strong&gt;care&lt;/strong&gt; &amp; &quot;focus&quot;</span>')
        ->and($root)->not->toContain(' id=')
        ->and($target)->not->toContain(' id=')
        ->and($target)->not->toContain('aria-describedby=')
        ->and($bubble)->not->toContain(' id=');
});
