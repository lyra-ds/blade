<?php

use Illuminate\Support\Facades\Blade;

function renderSpinner(array $props = []): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf('<x-lyra::spinner %s />', $attributes));
}

function spinnerClass(string $html): string
{
    $matched = preg_match('/<span\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function spinnerOpeningTag(string $html): string
{
    $matched = preg_match('/<span\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('spinner class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/spinner.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the spinner class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(spinnerClass(renderSpinner($case['props'])))->toBe($case['expected_class']);
})->with('spinner class emission');

it('renders the default spinner contract', function (): void {
    $html = renderSpinner();
    $openingTag = spinnerOpeningTag($html);

    expect(spinnerClass($html))->toBe('lyra-spinner lyra-spinner--md')
        ->and($openingTag)->toContain('role="status"')
        ->and($openingTag)->toContain('aria-label="Loading"')
        ->and(trim($html))->toMatch('/^<span\b[^>]*><\/span>$/');
});

it('overrides the aria label once and passes root attributes through', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::spinner size="lg" class="x" role="progressbar" aria-label="Carregando" id="loader" data-track="loading" />
        BLADE);
    $openingTag = spinnerOpeningTag($html);

    expect(spinnerClass($html))->toBe('lyra-spinner lyra-spinner--lg x')
        ->and($openingTag)->toContain('role="status"')
        ->and($openingTag)->not->toContain('role="progressbar"')
        ->and(substr_count($openingTag, 'role='))->toBe(1)
        ->and($openingTag)->toContain('aria-label="Carregando"')
        ->and(substr_count($openingTag, 'aria-label='))->toBe(1)
        ->and($openingTag)->toContain('id="loader"')
        ->and($openingTag)->toContain('data-track="loading"')
        ->and(trim($html))->toMatch('/^<span\b[^>]*><\/span>$/');
});
