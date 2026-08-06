<?php

use Illuminate\Support\Facades\Blade;

function renderFieldset(array $props = [], string $slot = 'Field'): string
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
        '<x-lyra::fieldset %s>%s</x-lyra::fieldset>',
        $attributes,
        $slot,
    ));
}

function fieldsetClass(string $html): string
{
    $matched = preg_match('/<fieldset\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function fieldsetOpeningTag(string $html): string
{
    $matched = preg_match('/<fieldset\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('fieldset class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/fieldset.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the fieldset class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(fieldsetClass(renderFieldset($case['props'])))->toBe($case['expected_class']);
})->with('fieldset class emission');

it('renders only the fields wrapper when legend and description are absent', function (): void {
    $html = renderFieldset(slot: '<input>');

    expect(preg_replace('/\s+/', '', $html))->toBe(
        '<fieldsetclass="lyra-fieldset"><divclass="lyra-fieldset__fields"><input></div></fieldset>',
    )
        ->and($html)->not->toContain('lyra-fieldset__legend')
        ->and($html)->not->toContain('lyra-fieldset__desc');
});

it('renders legend then description then the fields wrapper in React order', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::fieldset>
            <x-slot:legend><span data-part="legend">Legend</span></x-slot:legend>
            <x-slot:description><span data-part="description">Description</span></x-slot:description>
            <span data-part="field">Field</span>
        </x-lyra::fieldset>
        BLADE);

    $legendPosition = strpos($html, 'lyra-fieldset__legend');
    $descPosition = strpos($html, 'lyra-fieldset__desc');
    $fieldsPosition = strpos($html, 'lyra-fieldset__fields');

    expect(fieldsetClass($html))->toBe('lyra-fieldset')
        ->and($html)->toContain('<legend class="lyra-fieldset__legend"><span data-part="legend">Legend</span></legend>')
        ->and($html)->toContain('<p class="lyra-fieldset__desc"><span data-part="description">Description</span></p>')
        ->and($legendPosition)->toBeInt()
        ->and($descPosition)->toBeInt()
        ->and($fieldsPosition)->toBeInt()
        ->and($legendPosition)->toBeLessThan($descPosition)
        ->and($descPosition)->toBeLessThan($fieldsPosition);
});

it('always renders the fields wrapper around the default slot', function (): void {
    $html = renderFieldset(['legend' => 'Legend'], slot: '<input name="email">');

    expect($html)->toContain('<div class="lyra-fieldset__fields"><input name="email"></div>');
});

it('treats empty legend and description slots as absent', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::fieldset>
            <x-slot:legend></x-slot:legend>
            <x-slot:description></x-slot:description>
            Field
        </x-lyra::fieldset>
        BLADE);

    expect(fieldsetClass($html))->toBe('lyra-fieldset')
        ->and($html)->not->toContain('lyra-fieldset__legend')
        ->and($html)->not->toContain('lyra-fieldset__desc');
});

it('passes attributes through to the root and keeps user classes last', function (): void {
    $html = Blade::render('<x-lyra::fieldset class="x y" id="account-fieldset" data-track="fieldset" aria-label="Account">Field</x-lyra::fieldset>');
    $openingTag = fieldsetOpeningTag($html);

    expect(fieldsetClass($html))->toBe('lyra-fieldset x y')
        ->and($openingTag)->toContain('id="account-fieldset"')
        ->and($openingTag)->toContain('data-track="fieldset"')
        ->and($openingTag)->toContain('aria-label="Account"');
});
