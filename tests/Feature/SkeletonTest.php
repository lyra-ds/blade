<?php

use Illuminate\Support\Facades\Blade;

function renderSkeleton(array $props = []): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf('<x-lyra::skeleton %s />', $attributes));
}

function skeletonClass(string $html): string
{
    $matched = preg_match('/<span\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function skeletonOpeningTag(string $html): string
{
    $matched = preg_match('/<span\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('skeleton class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/skeleton.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the skeleton class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(skeletonClass(renderSkeleton($case['props'])))->toBe($case['expected_class']);
})->with('skeleton class emission');

it('renders the default skeleton contract', function (): void {
    $html = renderSkeleton();
    $openingTag = skeletonOpeningTag($html);

    expect(skeletonClass($html))->toBe('lyra-skeleton')
        ->and($openingTag)->toContain('aria-hidden="true"')
        ->and($openingTag)->toContain('width: 100%')
        ->and($openingTag)->toContain('height: 14px')
        ->and(trim($html))->toMatch('/^<span\b[^>]*><\/span>$/');
});

it('adds px to numeric dimensions and uses height for a circle width', function (): void {
    $html = Blade::render('<x-lyra::skeleton circle :height="40" class="x" />');
    $openingTag = skeletonOpeningTag($html);

    expect(skeletonClass($html))->toBe('lyra-skeleton lyra-skeleton--circle x')
        ->and($openingTag)->toContain('width: 40px')
        ->and($openingTag)->toContain('height: 40px');
});

it('adds px to numeric-string dimensions', function (): void {
    $html = Blade::render('<x-lyra::skeleton height="20" width="120" />');

    expect($html)->toContain('height: 20px')->and($html)->toContain('width: 120px');
});

it('preserves string dimensions verbatim', function (): void {
    $openingTag = skeletonOpeningTag(
        Blade::render('<x-lyra::skeleton width="2rem" height="1rem" />'),
    );

    expect($openingTag)->toContain('width: 2rem')
        ->and($openingTag)->toContain('height: 1rem');
});

it('passes root attributes through and appends consumer styles', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::skeleton :width="24" :height="12" id="placeholder" data-track="skeleton" style="opacity: 0.5" />
        BLADE);
    $openingTag = skeletonOpeningTag($html);

    expect($openingTag)->toContain('id="placeholder"')
        ->and($openingTag)->toContain('data-track="skeleton"')
        ->and($openingTag)->toContain('width: 24px')
        ->and($openingTag)->toContain('height: 12px')
        ->and($openingTag)->toContain('opacity: 0.5');
});

it('keeps user classes last', function (): void {
    $html = Blade::render('<x-lyra::skeleton circle class="first second" />');

    expect(skeletonClass($html))->toBe('lyra-skeleton lyra-skeleton--circle first second');
});
