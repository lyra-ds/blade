<?php

use Illuminate\Support\Facades\Blade;

function renderTag(array $props = [], string $slot = 'Tag'): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf(
        '<x-lyra::tag %s>%s</x-lyra::tag>',
        $attributes,
        $slot,
    ));
}

function tagClass(string $html): string
{
    $matched = preg_match('/<span\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function tagOpeningTag(string $html): string
{
    $matched = preg_match('/<span\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('tag class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/tag.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the tag class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(tagClass(renderTag($case['props'])))->toBe($case['expected_class']);
})->with('tag class emission');

it('renders slot content without an interactive remove button', function (): void {
    $html = renderTag(slot: 'PHP');

    expect(tagClass($html))->toBe('lyra-tag')
        ->and($html)->toContain('PHP')
        ->and($html)->not->toContain('lyra-tag__remove');
});

it('passes root attributes through and appends the user class last', function (): void {
    $html = Blade::render('<x-lyra::tag class="x" data-id="1">Go</x-lyra::tag>');
    $openingTag = tagOpeningTag($html);

    expect(tagClass($html))->toBe('lyra-tag x')
        ->and($openingTag)->toContain('data-id="1"')
        ->and($html)->toContain('Go')
        ->and($html)->not->toContain('lyra-tag__remove');
});
