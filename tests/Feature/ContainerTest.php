<?php

use Illuminate\Support\Facades\Blade;

function renderContainer(array $props = [], string $slot = 'C'): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf('<x-lyra::container %s>%s</x-lyra::container>', $attributes, $slot));
}

function containerClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function containerOpeningTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('container class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/container.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the container class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(containerClass(renderContainer($case['props'])))->toBe($case['expected_class']);
})->with('container class emission');

it('renders the default contract without a style attribute', function (): void {
    $html = renderContainer();

    expect(trim($html))->toBe('<div class="lyra-container">C</div>')
        ->and(containerOpeningTag($html))->not->toContain(' style=');
});

it('sets the container max custom property in pixels', function (): void {
    $openingTag = containerOpeningTag(
        Blade::render('<x-lyra::container :max="960">C</x-lyra::container>'),
    );

    expect($openingTag)->toContain('style="--container-max: 960px"');
});

it('appends consumer styles after the container max custom property', function (): void {
    $openingTag = containerOpeningTag(
        Blade::render('<x-lyra::container :max="960" style="color: red">C</x-lyra::container>'),
    );

    expect($openingTag)->toMatch('/style="--container-max: 960px;?\s*color: red;?"/');
});

it('preserves a consumer style when max is absent', function (): void {
    $openingTag = containerOpeningTag(
        Blade::render('<x-lyra::container style="color: red">C</x-lyra::container>'),
    );

    expect($openingTag)->toContain('style="color: red;"');
});

it('renders the slot and passes root attributes through', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::container id="content" data-track="container"><span>Slot content</span></x-lyra::container>
        BLADE);
    $openingTag = containerOpeningTag($html);

    expect($openingTag)->toContain('id="content"')
        ->and($openingTag)->toContain('data-track="container"')
        ->and(trim($html))->toContain('<span>Slot content</span>');
});

it('keeps user classes last', function (): void {
    $html = Blade::render('<x-lyra::container class="first second">C</x-lyra::container>');

    expect(containerClass($html))->toBe('lyra-container first second');
});
