<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

function renderBottomNav(array $props = []): string
{
    $items = $props['items'] ?? [
        ['icon' => 'H', 'label' => 'Home'],
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
        sprintf('<x-lyra::bottom-nav :items="$items" %s />', $attributes),
        compact('items'),
    );
}

function bottomNavClass(string $html): string
{
    $matched = preg_match('/<nav\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function bottomNavOpeningTag(string $html): string
{
    $matched = preg_match('/<nav\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('bottom nav class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/bottom-nav.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the bottom nav class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(bottomNavClass(renderBottomNav($case['props'])))->toBe($case['expected_class']);
})->with('bottom nav class emission');

it('renders three items in order with active state only on the active item', function (): void {
    $html = renderBottomNav([
        'items' => [
            ['id' => 'home', 'icon' => 'H', 'label' => 'Home'],
            ['id' => 'search', 'icon' => 'S', 'label' => 'Search', 'active' => true],
            ['id' => 'profile', 'icon' => 'P', 'label' => 'Profile', 'active' => false],
        ],
    ]);

    expect(substr_count($html, '<button type="button"'))->toBe(3)
        ->and(substr_count($html, 'class="lyra-bottomnav__item"'))->toBe(2)
        ->and(substr_count($html, 'class="lyra-bottomnav__item lyra-bottomnav__item--active"'))->toBe(1)
        ->and(substr_count($html, 'aria-current="page"'))->toBe(1)
        ->and($html)->toMatch('/class="lyra-bottomnav__item lyra-bottomnav__item--active"\s+aria-current="page"/')
        ->and($html)->not->toContain('onclick=')
        ->and(strpos($html, '>Home<'))->toBeLessThan(strpos($html, '>Search<'))
        ->and(strpos($html, '>Search<'))->toBeLessThan(strpos($html, '>Profile<'));
});

it('renders each icon before its label and preserves Htmlable escaping semantics', function (): void {
    $html = renderBottomNav([
        'items' => [
            ['icon' => new HtmlString('<svg data-icon="raw"></svg>'), 'label' => 'Raw'],
            ['icon' => '<svg data-icon="escaped"></svg>', 'label' => 'Escaped'],
        ],
    ]);

    expect($html)->toContain('<span class="lyra-bottomnav__icon"><svg data-icon="raw"></svg></span>')
        ->and($html)->toContain('<span class="lyra-bottomnav__icon">&lt;svg data-icon=&quot;escaped&quot;&gt;&lt;/svg&gt;</span>')
        ->and($html)->toMatch('/<span class="lyra-bottomnav__icon">.*?<\/span>\s*<span class="lyra-bottomnav__label">Raw<\/span>/s')
        ->and($html)->toMatch('/<span class="lyra-bottomnav__icon">.*?<\/span>\s*<span class="lyra-bottomnav__label">Escaped<\/span>/s');
});

it('passes root attributes through and keeps user classes last', function (): void {
    $html = renderBottomNav([
        'class' => 'x y',
        'id' => 'primary-nav',
        'data-track' => 'bottom-nav',
    ]);
    $openingTag = bottomNavOpeningTag($html);

    expect(bottomNavClass($html))->toBe('lyra-bottomnav x y')
        ->and($openingTag)->toContain('id="primary-nav"')
        ->and($openingTag)->toContain('data-track="bottom-nav"');
});
