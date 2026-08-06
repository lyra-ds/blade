<?php

use Illuminate\Support\Facades\Blade;

function renderSelect(array $props = [], string $slot = ''): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf(
        '<x-lyra::select %s>%s</x-lyra::select>',
        $attributes,
        $slot,
    ));
}

function selectOpeningTag(string $html): string
{
    $matched = preg_match('/<select\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function selectClass(string $html): string
{
    $matched = preg_match('/<select\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function selectAttribute(string $html, string $attribute): ?string
{
    $matched = preg_match(
        sprintf('/<select\b[^>]*\b%s="([^"]*)"/', preg_quote($attribute, '/')),
        $html,
        $matches,
    );

    return $matched === 1 ? $matches[1] : null;
}

dataset('select class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/select.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the select class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(selectClass(renderSelect($case['props'])))->toBe($case['expected_class']);
})->with('select class emission');

it('always wraps the bare select and renders option children', function (): void {
    $html = renderSelect(['name' => 'uf'], '<option value="SP">SP</option>');
    $openingTag = selectOpeningTag($html);

    expect($html)->toMatch('/^\s*<span class="lyra-select-wrap">\s*<select\b[^>]*>.*<\/select>\s*<\/span>\s*$/s')
        ->and(selectClass($html))->toBe('lyra-input')
        ->and($openingTag)->toContain('name="uf"')
        ->and($openingTag)->not->toContain('aria-invalid=')
        ->and($html)->toContain('<option value="SP">SP</option>')
        ->and($html)->not->toContain('lyra-field');
});

it('renders the same wrapper inside the field branch and wires the label and error', function (): void {
    $html = renderSelect([
        'id' => 'uf',
        'label' => 'UF',
        'error' => 'Escolha',
        'size' => 'lg',
        'class' => 'x',
    ], '<option>SP</option>');
    $describedBy = selectAttribute($html, 'aria-describedby');

    expect($html)->toContain('<div class="lyra-field">')
        ->and($html)->toContain('<label class="lyra-label" for="uf">UF</label>')
        ->and($html)->toMatch('/<span class="lyra-select-wrap">\s*<select\b[^>]*>.*<\/select>\s*<\/span>/s')
        ->and(selectClass($html))->toBe('lyra-input lyra-input--lg lyra-input--error x')
        ->and(selectAttribute($html, 'id'))->toBe('uf')
        ->and(selectAttribute($html, 'aria-invalid'))->toBe('true')
        ->and($describedBy)->not->toBeNull()
        ->and($html)->toContain(sprintf(
            '<span id="%s" class="lyra-hint lyra-hint--error">Escolha</span>',
            $describedBy,
        ));
});

it('generates a unique select id for each render', function (): void {
    $firstId = selectAttribute(renderSelect(['label' => 'First']), 'id');
    $secondId = selectAttribute(renderSelect(['label' => 'Second']), 'id');

    expect($firstId)->not->toBeNull()
        ->and($secondId)->not->toBeNull()
        ->and($secondId)->not->toBe($firstId);
});

it('replaces the hint with the error message', function (): void {
    $html = renderSelect([
        'hint' => 'Choose one option',
        'error' => 'Selection is required',
    ]);

    expect($html)->toContain('lyra-hint lyra-hint--error')
        ->and($html)->toContain('Selection is required')
        ->and($html)->not->toContain('Choose one option');
});

it('appends the message id to a consumer aria description', function (): void {
    $html = renderSelect([
        'hint' => 'Your home state',
        'aria-describedby' => 'address-help',
    ]);
    $describedBy = selectAttribute($html, 'aria-describedby');

    expect($describedBy)->toMatch('/^address-help lyra-select-message-/')
        ->and($html)->toContain(sprintf(
            '<span id="%s" class="lyra-hint">Your home state</span>',
            substr($describedBy, strlen('address-help ')),
        ));
});

it('preserves consumer aria attributes when no error or message renders', function (): void {
    $html = renderSelect([
        'aria-invalid' => 'false',
        'aria-describedby' => 'external-help',
    ]);

    expect(selectAttribute($html, 'aria-invalid'))->toBe('false')
        ->and(selectAttribute($html, 'aria-describedby'))->toBe('external-help');
});

it('passes native attributes and optgroups to the select and keeps user classes last', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::select name="state" disabled required multiple class="first second" data-track="address">
            <optgroup label="Sudeste"><option value="SP" selected>SP</option></optgroup>
        </x-lyra::select>
        BLADE);
    $openingTag = selectOpeningTag($html);

    expect(selectClass($html))->toBe('lyra-input first second')
        ->and($openingTag)->toContain('name="state"')
        ->and($openingTag)->toContain('disabled')
        ->and($openingTag)->toContain('required')
        ->and($openingTag)->toContain('multiple')
        ->and($openingTag)->toContain('data-track="address"')
        ->and($html)->toContain('<optgroup label="Sudeste"><option value="SP" selected>SP</option></optgroup>');
});
