<?php

use Illuminate\Support\Facades\Blade;

function renderNavbar(array $props = [], array $slots = [], string $slot = ''): string
{
    $sticky = $props['sticky'] ?? true;
    $stickyAttribute = array_key_exists('sticky', $props) ? ' :sticky="$sticky"' : '';
    unset($props['sticky']);

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            str($name)->kebab(),
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    $namedSlots = collect($slots)
        ->map(fn (string $value, string $name): string => sprintf(
            '<x-slot:%s>%s</x-slot:%s>',
            $name,
            $value,
            $name,
        ))
        ->implode('');

    return Blade::render(sprintf(
        '<x-lyra::navbar%s %s>%s%s</x-lyra::navbar>',
        $stickyAttribute,
        $attributes,
        $slot,
        $namedSlots,
    ), compact('sticky'));
}

function navbarClass(string $html): string
{
    $matched = preg_match('/<header\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function navbarOpeningTag(string $html): string
{
    $matched = preg_match('/<header\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('navbar class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/navbar.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the navbar class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(navbarClass(renderNavbar($case['props'])))->toBe($case['expected_class']);
})->with('navbar class emission');

it('renders only the container when no named slots are provided', function (): void {
    $html = renderNavbar(slot: '<span data-default>Ignored</span>');

    expect($html)->toContain('<header class="lyra-navbar">')
        ->and($html)->toContain('<div class="lyra-container lyra-navbar__inner">')
        ->and(substr_count($html, 'lyra-container lyra-navbar__inner'))->toBe(1)
        ->and($html)->not->toContain('lyra-navbar__brand')
        ->and($html)->not->toContain('lyra-navbar__nav')
        ->and($html)->not->toContain('lyra-navbar__actions')
        ->and($html)->not->toContain('data-default');
});

it('renders the brand wrapper only when the brand slot is provided', function (): void {
    $html = renderNavbar(slots: ['brand' => '<strong>Lyra</strong>']);

    expect($html)->toContain('<div class="lyra-navbar__brand"><strong>Lyra</strong></div>')
        ->and($html)->not->toContain('lyra-navbar__nav')
        ->and($html)->not->toContain('lyra-navbar__actions');
});

it('renders the nav wrapper only when the nav slot is provided', function (): void {
    $html = renderNavbar(slots: ['nav' => '<a href="/docs">Docs</a>']);

    expect($html)->toContain('<nav class="lyra-navbar__nav"><a href="/docs">Docs</a></nav>')
        ->and($html)->not->toContain('lyra-navbar__brand')
        ->and($html)->not->toContain('lyra-navbar__actions')
        ->and($html)->not->toContain('aria-label=');
});

it('renders the actions wrapper only when the actions slot is provided', function (): void {
    $html = renderNavbar(slots: ['actions' => '<button type="button">Sign in</button>']);

    expect($html)->toContain('<div class="lyra-navbar__actions"><button type="button">Sign in</button></div>')
        ->and($html)->not->toContain('lyra-navbar__brand')
        ->and($html)->not->toContain('lyra-navbar__nav');
});

it('omits wrappers for empty named slots', function (): void {
    $html = renderNavbar(slots: [
        'brand' => ' ',
        'nav' => "\n",
        'actions' => "\t",
    ]);

    expect($html)->not->toContain('lyra-navbar__brand')
        ->and($html)->not->toContain('lyra-navbar__nav')
        ->and($html)->not->toContain('lyra-navbar__actions');
});

it('renders named slots in React order regardless of authoring order', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::navbar nav-label="Main">
            <x-slot:actions><button type="button">Sign in</button></x-slot:actions>
            <x-slot:nav><a href="/docs">Docs</a></x-slot:nav>
            <x-slot:brand><strong>Lyra</strong></x-slot:brand>
        </x-lyra::navbar>
        BLADE);

    $brandPosition = strpos($html, 'lyra-navbar__brand');
    $navPosition = strpos($html, 'lyra-navbar__nav');
    $actionsPosition = strpos($html, 'lyra-navbar__actions');

    expect($brandPosition)->toBeInt()
        ->and($navPosition)->toBeInt()
        ->and($actionsPosition)->toBeInt()
        ->and($brandPosition)->toBeLessThan($navPosition)
        ->and($navPosition)->toBeLessThan($actionsPosition)
        ->and($html)->toContain('<nav class="lyra-navbar__nav" aria-label="Main"><a href="/docs">Docs</a></nav>');
});

it('does not render a nav label without the nav slot', function (): void {
    $html = renderNavbar(['navLabel' => 'Main']);

    expect($html)->not->toContain('<nav')
        ->and($html)->not->toContain('aria-label=');
});

it('does not emit the static modifier by default or when sticky is true', function (): void {
    expect(navbarClass(renderNavbar()))->toBe('lyra-navbar')
        ->and(navbarClass(renderNavbar(['sticky' => true])))->toBe('lyra-navbar');
});

it('passes attributes through to the root and keeps user classes last', function (): void {
    $html = renderNavbar([
        'sticky' => false,
        'class' => 'x y',
        'id' => 'site-navbar',
        'data-track' => 'navbar',
        'aria-describedby' => 'navbar-note',
    ]);
    $openingTag = navbarOpeningTag($html);

    expect(navbarClass($html))->toBe('lyra-navbar lyra-navbar--static x y')
        ->and($openingTag)->toContain('id="site-navbar"')
        ->and($openingTag)->toContain('data-track="navbar"')
        ->and($openingTag)->toContain('aria-describedby="navbar-note"');
});
