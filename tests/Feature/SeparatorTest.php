<?php

use Illuminate\Support\Facades\Blade;

function renderSeparator(array $props = []): string
{
    $label = $props['label'] ?? null;
    unset($props['label']);

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    if ($label !== null) {
        return Blade::render(sprintf(
            '<x-lyra::separator %s><x-slot:label>%s</x-slot:label></x-lyra::separator>',
            $attributes,
            $label,
        ));
    }

    return Blade::render(sprintf('<x-lyra::separator %s />', $attributes));
}

function separatorClass(string $html): string
{
    $matched = preg_match('/<(?:hr|span|div)\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function separatorOpeningTag(string $html): string
{
    $matched = preg_match('/<(?:hr|span|div)\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('separator class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/separator.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the separator class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(separatorClass(renderSeparator($case['props'])))->toBe($case['expected_class']);
})->with('separator class emission');

it('renders the default horizontal separator contract', function (): void {
    $html = Blade::render('<x-lyra::separator class="x" id="section-break" data-track="separator" />');
    $openingTag = separatorOpeningTag($html);

    expect(separatorClass($html))->toBe('lyra-separator x')
        ->and(trim($html))->toMatch('/^<hr\b[^>]*>$/')
        ->and($openingTag)->toContain('id="section-break"')
        ->and($openingTag)->toContain('data-track="separator"')
        ->and($openingTag)->not->toContain('role=')
        ->and($openingTag)->not->toContain('aria-orientation=');
});

it('renders the vertical separator contract', function (): void {
    $html = Blade::render('<x-lyra::separator orientation="vertical" class="x" id="rail-break" data-track="separator" />');
    $openingTag = separatorOpeningTag($html);

    expect(separatorClass($html))->toBe('lyra-separator lyra-separator--vertical x')
        ->and(trim($html))->toMatch('/^<span\b[^>]*><\/span>$/')
        ->and($openingTag)->toContain('role="separator"')
        ->and($openingTag)->toContain('aria-orientation="vertical"')
        ->and($openingTag)->toContain('id="rail-break"')
        ->and($openingTag)->toContain('data-track="separator"');
});

it('renders a non-empty label slot with priority over orientation', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::separator orientation="vertical" class="x" id="label-break" data-track="separator">
            <x-slot:label><strong>ou</strong></x-slot:label>
        </x-lyra::separator>
        BLADE);
    $openingTag = separatorOpeningTag($html);

    expect(separatorClass($html))->toBe('lyra-separator--label x')
        ->and(trim($html))->toMatch('/^<div\b[^>]*>.*<\/div>$/s')
        ->and($openingTag)->toContain('role="separator"')
        ->and($openingTag)->not->toContain('aria-orientation=')
        ->and($openingTag)->toContain('id="label-break"')
        ->and($openingTag)->toContain('data-track="separator"')
        ->and($html)->toContain('<strong>ou</strong>')
        ->and($html)->not->toContain('lyra-separator lyra-separator--vertical');
});

it('ignores an empty label slot and renders the orientation path', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::separator orientation="vertical">
            <x-slot:label></x-slot:label>
        </x-lyra::separator>
        BLADE);

    expect(separatorClass($html))->toBe('lyra-separator lyra-separator--vertical')
        ->and(trim($html))->toMatch('/^<span\b[^>]*><\/span>$/');
});
