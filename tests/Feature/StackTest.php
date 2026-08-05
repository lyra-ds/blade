<?php

use Illuminate\Support\Facades\Blade;

function renderStack(array $props = [], string $slot = 'S'): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf('<x-lyra::stack %s>%s</x-lyra::stack>', $attributes, $slot));
}

function stackClass(string $html): string
{
    $matched = preg_match('/<[^>]+\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function stackOpeningTag(string $html, string $tag = 'div'): string
{
    $matched = preg_match(sprintf('/<%s\b[^>]*>/', preg_quote($tag, '/')), $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('stack class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/stack.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the stack class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(stackClass(renderStack($case['props'])))->toBe($case['expected_class']);
})->with('stack class emission');

it('renders the default contract without a style attribute', function (): void {
    $html = renderStack();

    expect(trim($html))->toBe('<div class="lyra-stack">S</div>')
        ->and(stackOpeningTag($html))->not->toContain(' style=');
});

it('maps layout props to ordered custom properties', function (): void {
    $openingTag = stackOpeningTag(Blade::render(
        '<x-lyra::stack direction="row" :gap="6" align="center" justify="space-between" wrap>S</x-lyra::stack>',
    ));

    expect($openingTag)->toContain(
        'style="--lyra-stack-direction: row; --lyra-stack-gap: var(--space-6); --lyra-stack-align: center; --lyra-stack-justify: space-between; --lyra-stack-wrap: wrap"',
    );
});

it('uses a string gap verbatim', function (): void {
    $openingTag = stackOpeningTag(Blade::render('<x-lyra::stack gap="24px" />'));

    expect($openingTag)->toContain('style="--lyra-stack-gap: 24px"');
});

it('emits wrap only when true', function (): void {
    $wrapped = stackOpeningTag(Blade::render('<x-lyra::stack wrap />'));
    $notWrapped = stackOpeningTag(Blade::render('<x-lyra::stack :wrap="false" />'));

    expect($wrapped)->toContain('style="--lyra-stack-wrap: wrap"')
        ->and($notWrapped)->not->toContain('style=');
});

it('renders the selected root element', function (): void {
    $html = Blade::render('<x-lyra::stack as="section">S</x-lyra::stack>');

    expect(trim($html))->toBe('<section class="lyra-stack">S</section>');
});

it('appends consumer styles after computed properties', function (): void {
    $openingTag = stackOpeningTag(Blade::render(
        '<x-lyra::stack direction="row" style="--lyra-stack-direction: column; color: red">S</x-lyra::stack>',
    ));

    expect($openingTag)->toMatch(
        '/style="--lyra-stack-direction: row;?\s*--lyra-stack-direction: column; color: red;?"/',
    );
});

it('passes root attributes through and renders the slot', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::stack id="content" data-track="stack"><span>Slot content</span></x-lyra::stack>
        BLADE);
    $openingTag = stackOpeningTag($html);

    expect($openingTag)->toContain('id="content"')
        ->and($openingTag)->toContain('data-track="stack"')
        ->and(trim($html))->toContain('<span>Slot content</span>');
});

it('keeps user classes last', function (): void {
    $html = Blade::render('<x-lyra::stack class="first second">S</x-lyra::stack>');

    expect(stackClass($html))->toBe('lyra-stack first second');
});
