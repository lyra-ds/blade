<?php

use Illuminate\Support\Facades\Blade;

function renderGrid(array $props = [], string $slot = 'G'): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf('<x-lyra::grid %s>%s</x-lyra::grid>', $attributes, $slot));
}

function gridClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function gridOpeningTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('grid class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/grid.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the grid class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(gridClass(renderGrid($case['props'])))->toBe($case['expected_class']);
})->with('grid class emission');

it('renders the default contract without a style attribute', function (): void {
    $html = renderGrid();

    expect(trim($html))->toBe('<div class="lyra-grid">G</div>')
        ->and(gridOpeningTag($html))->not->toContain(' style=');
});

it('maps a numeric columns value to a repeat template', function (): void {
    $openingTag = gridOpeningTag(Blade::render('<x-lyra::grid :columns="3">G</x-lyra::grid>'));

    expect($openingTag)->toContain(
        'style="--lyra-grid-columns: repeat(3, minmax(0, 1fr))"',
    );
});

it('maps numeric-string columns and gap like their bound numeric forms', function (): void {
    $openingTag = gridOpeningTag(Blade::render('<x-lyra::grid columns="3" gap="5" />'));

    expect($openingTag)->toContain('--lyra-grid-columns: repeat(3, minmax(0, 1fr))')
        ->and($openingTag)->toContain('--lyra-grid-gap: var(--space-5)');
});

it('uses a string columns value verbatim', function (): void {
    $openingTag = gridOpeningTag(Blade::render(
        '<x-lyra::grid columns="200px 1fr">G</x-lyra::grid>',
    ));

    expect($openingTag)->toContain('style="--lyra-grid-columns: 200px 1fr"');
});

it('maps minItem to the auto-fit template and gives it precedence over columns', function (): void {
    $openingTag = gridOpeningTag(Blade::render(
        '<x-lyra::grid :columns="3" :min-item="240">G</x-lyra::grid>',
    ));

    expect($openingTag)
        ->toContain(
            'style="--lyra-grid-columns: repeat(auto-fit, minmax(min(240px, 100%), 1fr))"',
        )
        ->not->toContain('repeat(3');
});

it('maps numeric and string gap values', function (): void {
    $numericGap = gridOpeningTag(Blade::render('<x-lyra::grid :gap="6">G</x-lyra::grid>'));
    $stringGap = gridOpeningTag(Blade::render('<x-lyra::grid gap="24px">G</x-lyra::grid>'));

    expect($numericGap)->toContain('style="--lyra-grid-gap: var(--space-6)"')
        ->and($stringGap)->toContain('style="--lyra-grid-gap: 24px"');
});

it('orders columns before gap', function (): void {
    $openingTag = gridOpeningTag(Blade::render(
        '<x-lyra::grid :columns="3" :gap="6">G</x-lyra::grid>',
    ));

    expect($openingTag)->toContain(
        'style="--lyra-grid-columns: repeat(3, minmax(0, 1fr)); --lyra-grid-gap: var(--space-6)"',
    );
});

it('appends consumer styles after computed properties', function (): void {
    $openingTag = gridOpeningTag(Blade::render(
        '<x-lyra::grid :columns="3" style="--lyra-grid-columns: 1fr; color: red">G</x-lyra::grid>',
    ));

    expect($openingTag)->toMatch(
        '/style="--lyra-grid-columns: repeat\(3, minmax\(0, 1fr\)\);?\s*--lyra-grid-columns: 1fr; color: red;?"/',
    );
});

it('passes root attributes through and renders the slot', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::grid id="content" data-track="grid"><span>Slot content</span></x-lyra::grid>
        BLADE);
    $openingTag = gridOpeningTag($html);

    expect($openingTag)->toContain('id="content"')
        ->and($openingTag)->toContain('data-track="grid"')
        ->and(trim($html))->toContain('<span>Slot content</span>');
});

it('keeps user classes last', function (): void {
    $html = Blade::render('<x-lyra::grid class="first second">G</x-lyra::grid>');

    expect(gridClass($html))->toBe('lyra-grid first second');
});
