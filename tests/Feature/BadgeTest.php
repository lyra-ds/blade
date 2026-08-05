<?php

use Illuminate\Support\Facades\Blade;

function renderBadge(array $props = [], string $slot = 'Badge'): string
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
        '<x-lyra::badge %s>%s</x-lyra::badge>',
        $attributes,
        $slot,
    ));
}

function badgeClass(string $html): string
{
    $matched = preg_match('/<span\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function badgeOpeningTag(string $html): string
{
    $matched = preg_match('/<span\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('badge class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/badge.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the badge class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(badgeClass(renderBadge($case['props'])))->toBe($case['expected_class']);
})->with('badge class emission');

it('renders the default badge contract', function (): void {
    $html = renderBadge(slot: 'New');
    $openingTag = badgeOpeningTag($html);

    expect(badgeClass($html))->toBe('lyra-badge lyra-badge--neutral')
        ->and($openingTag)->not->toContain('role=')
        ->and($openingTag)->not->toContain('aria-')
        ->and($html)->not->toContain('lyra-badge__dot')
        ->and($html)->toContain('New');
});

it('renders the dot before the slot and passes root attributes through', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::badge tone="success" dot class="x" id="status" data-track="badge" aria-label="Available">Ok</x-lyra::badge>
        BLADE);
    $openingTag = badgeOpeningTag($html);
    $dotPosition = strpos($html, 'lyra-badge__dot');
    $slotPosition = strpos($html, 'Ok');

    expect(badgeClass($html))->toBe('lyra-badge lyra-badge--success x')
        ->and($openingTag)->toContain('id="status"')
        ->and($openingTag)->toContain('data-track="badge"')
        ->and($openingTag)->toContain('aria-label="Available"')
        ->and($html)->toContain('<span class="lyra-badge__dot" aria-hidden="true"></span>')
        ->and($html)->toContain('Ok')
        ->and($dotPosition)->toBeInt()
        ->and($slotPosition)->toBeInt()
        ->and($dotPosition)->toBeLessThan($slotPosition);
});
