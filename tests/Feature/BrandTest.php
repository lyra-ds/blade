<?php

use Illuminate\Support\Facades\Blade;

function renderBrand(array $props = [], string $slot = ''): string
{
    $attributes = collect(['mark' => '/m.svg', ...$props])
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf('<x-lyra::brand %s>%s</x-lyra::brand>', $attributes, $slot));
}

function brandClass(string $html): string
{
    $matched = preg_match('/<(?:a|span)\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function brandOpeningTag(string $html): string
{
    $matched = preg_match('/<(?:a|span)\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('brand class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/brand.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the brand class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(brandClass(renderBrand($case['props'])))->toBe($case['expected_class']);
})->with('brand class emission');

it('renders a mark-only span as an accessible image', function (): void {
    $html = Blade::render('<x-lyra::brand mark="/m.svg" aria-label="Acme" />');
    $openingTag = brandOpeningTag($html);

    expect($openingTag)->toStartWith('<span ')
        ->and(brandClass($html))->toBe('lyra-brand')
        ->and($openingTag)->toContain('role="img"')
        ->and($openingTag)->toContain('aria-label="Acme"')
        ->and($html)->toContain('<img class="lyra-brand__mark" src="/m.svg" alt="">')
        ->and($html)->not->toContain('lyra-brand__word')
        ->and($html)->not->toContain('lyra-brand__mark--light')
        ->and($html)->not->toContain('lyra-brand__mark--dark');
});

it('renders dual theme marks and a wordmark inside a link', function (): void {
    $html = Blade::render(
        '<x-lyra::brand mark="/m.svg" mark-dark="/d.svg" href="/">Acme</x-lyra::brand>',
    );
    $openingTag = brandOpeningTag($html);

    expect($openingTag)->toStartWith('<a ')
        ->and($openingTag)->toContain('href="/"')
        ->and($openingTag)->not->toContain('role=')
        ->and($html)->toContain('<img class="lyra-brand__mark lyra-brand__mark--light" src="/m.svg" alt="">')
        ->and($html)->toContain('<img class="lyra-brand__mark lyra-brand__mark--dark" src="/d.svg" alt="">')
        ->and($html)->toContain('<span class="lyra-brand__word">Acme</span>')
        ->and(substr_count($html, '<img '))->toBe(2);
});

it('renders a wordmark on a span without assigning an image role', function (): void {
    $html = renderBrand(slot: 'Acme');

    expect(brandOpeningTag($html))->not->toContain('role=')
        ->and($html)->toContain('<span class="lyra-brand__word">Acme</span>');
});

it('sets the mark size custom property in pixels', function (): void {
    $openingTag = brandOpeningTag(
        Blade::render('<x-lyra::brand mark="/m.svg" :size="24">A</x-lyra::brand>'),
    );

    expect($openingTag)->toContain('style="--brand-mark-size: 24px"');
});

it('appends consumer styles after the mark size custom property', function (): void {
    $html = Blade::render(
        '<x-lyra::brand mark="/m.svg" :size="24" class="x" style="color:red">A</x-lyra::brand>',
    );
    $openingTag = brandOpeningTag($html);

    expect(brandClass($html))->toBe('lyra-brand x')
        ->and($openingTag)->toMatch('/style="--brand-mark-size: 24px;?\s*color:red;?"/');
});

it('preserves consumer styles when size is absent', function (): void {
    $openingTag = brandOpeningTag(renderBrand(['style' => 'color:red'], 'A'));

    expect($openingTag)->toContain('style="color:red;"');
});

it('passes arbitrary attributes through to the root and keeps user classes last', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::brand mark="/m.svg" class="first second" id="identity" data-track="brand">Acme</x-lyra::brand>
        BLADE);
    $openingTag = brandOpeningTag($html);

    expect(brandClass($html))->toBe('lyra-brand first second')
        ->and($openingTag)->toContain('id="identity"')
        ->and($openingTag)->toContain('data-track="brand"');
});
