<?php

use Illuminate\Support\Facades\Blade;

function renderBreadcrumb(array $props = []): string
{
    $items = $props['items'] ?? [
        ['label' => 'Home', 'href' => '/'],
        ['label' => 'Docs'],
    ];
    unset($props['items']);

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf('<x-lyra::breadcrumb :items="$items" %s />', $attributes),
        compact('items'),
    );
}

function breadcrumbClass(string $html): string
{
    $matched = preg_match('/<nav\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function breadcrumbOpeningTag(string $html): string
{
    $matched = preg_match('/<nav\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('breadcrumb class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/breadcrumb.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the breadcrumb class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(breadcrumbClass(renderBreadcrumb($case['props'])))->toBe($case['expected_class']);
})->with('breadcrumb class emission');

it('renders links, separators, and the current item in React order', function (): void {
    $html = renderBreadcrumb([
        'items' => [
            ['label' => 'Home', 'href' => '/'],
            ['label' => 'Docs', 'href' => '/docs'],
            ['label' => 'Blade'],
        ],
    ]);

    expect(substr_count($html, 'class="lyra-breadcrumb__sep"'))->toBe(2)
        ->and($html)->toMatch('/<a href="\/">Home<\/a>\s*<span class="lyra-breadcrumb__sep" aria-hidden="true"><\/span>\s*<a href="\/docs">Docs<\/a>\s*<span class="lyra-breadcrumb__sep" aria-hidden="true"><\/span>\s*<span class="lyra-breadcrumb__current" aria-current="page">Blade<\/span>/s')
        ->and($html)->not->toContain('<ol')
        ->and($html)->not->toContain('<li');
});

it('falls back to a hash for a non-current item without an href', function (): void {
    $html = renderBreadcrumb([
        'items' => [
            ['label' => 'Home', 'href' => null],
            ['label' => 'Current'],
        ],
    ]);

    expect($html)->toContain('<a href="#">Home</a>');
});

it('uses the default aria label and allows consumers to override it', function (): void {
    $defaultTag = breadcrumbOpeningTag(renderBreadcrumb());
    $overrideTag = breadcrumbOpeningTag(renderBreadcrumb(['aria-label' => 'Trilha']));

    expect($defaultTag)->toContain('aria-label="Breadcrumb"')
        ->and($overrideTag)->toContain('aria-label="Trilha"')
        ->and($overrideTag)->not->toContain('aria-label="Breadcrumb"')
        ->and(substr_count($overrideTag, 'aria-label='))->toBe(1);
});

it('renders a single item as only the current span', function (): void {
    $html = renderBreadcrumb([
        'items' => [
            ['label' => 'Só'],
        ],
    ]);

    expect($html)->toContain('<span class="lyra-breadcrumb__current" aria-current="page">Só</span>')
        ->and($html)->not->toContain('lyra-breadcrumb__sep')
        ->and($html)->not->toContain('<a ');
});

it('passes root attributes through and keeps user classes last', function (): void {
    $html = renderBreadcrumb([
        'class' => 'x y',
        'id' => 'docs-path',
        'data-track' => 'breadcrumb',
    ]);
    $openingTag = breadcrumbOpeningTag($html);

    expect(breadcrumbClass($html))->toBe('lyra-breadcrumb x y')
        ->and($openingTag)->toContain('id="docs-path"')
        ->and($openingTag)->toContain('data-track="breadcrumb"');
});
