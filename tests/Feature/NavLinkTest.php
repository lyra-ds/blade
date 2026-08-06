<?php

use Illuminate\Support\Facades\Blade;

function renderNavLink(array $props = [], string $slot = 'Link'): string
{
    $attributes = collect($props)
        ->map(function (mixed $value, string $name): string {
            if (is_bool($value)) {
                return $value ? $name : sprintf(':%s="false"', $name);
            }

            return sprintf('%s="%s"', $name, htmlspecialchars((string) $value, ENT_QUOTES));
        })
        ->implode(' ');

    return Blade::render(sprintf(
        '<x-lyra::nav-link %s>%s</x-lyra::nav-link>',
        $attributes,
        $slot,
    ));
}

function navLinkClass(string $html): string
{
    $matched = preg_match('/<a\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function navLinkOpeningTag(string $html): string
{
    $matched = preg_match('/<a\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('nav link class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/nav-link.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the nav-link class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(navLinkClass(renderNavLink($case['props'])))->toBe($case['expected_class']);
})->with('nav link class emission');

it('renders the default link contract', function (): void {
    $html = renderNavLink(['href' => '/docs'], 'Docs');
    $openingTag = navLinkOpeningTag($html);

    expect(navLinkClass($html))->toBe('lyra-navlink')
        ->and($openingTag)->toContain('href="/docs"')
        ->and($openingTag)->not->toContain('aria-current')
        ->and($html)->toContain('Docs');
});

it('marks active links as the current page', function (): void {
    $html = renderNavLink(['active' => true], 'Now');

    expect(navLinkClass($html))->toBe('lyra-navlink lyra-navlink--active')
        ->and(navLinkOpeningTag($html))->toContain('aria-current="page"')
        ->and($html)->toContain('Now');
});

it('passes root attributes through and appends user classes last', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::nav-link href="/account" active class="x y" id="account" data-track="nav">Account</x-lyra::nav-link>
        BLADE);
    $openingTag = navLinkOpeningTag($html);
    $basePosition = strpos(navLinkClass($html), 'lyra-navlink');
    $activePosition = strpos(navLinkClass($html), 'lyra-navlink--active');
    $userClassPosition = strpos(navLinkClass($html), 'x y');

    expect(navLinkClass($html))->toBe('lyra-navlink lyra-navlink--active x y')
        ->and($openingTag)->toContain('href="/account"')
        ->and($openingTag)->toContain('id="account"')
        ->and($openingTag)->toContain('data-track="nav"')
        ->and($basePosition)->toBeInt()
        ->and($activePosition)->toBeInt()
        ->and($userClassPosition)->toBeInt()
        ->and($basePosition)->toBeLessThan($activePosition)
        ->and($activePosition)->toBeLessThan($userClassPosition);
});
