<?php

use Illuminate\Support\Facades\Blade;

function renderCheckbox(array $props = []): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf('<x-lyra::checkbox %s />', $attributes));
}

function checkboxOpeningTag(string $html): string
{
    $matched = preg_match('/<input\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function checkboxClass(string $html): string
{
    $matched = preg_match('/<input\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

dataset('checkbox class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/checkbox.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the checkbox class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(checkboxClass(renderCheckbox($case['props'])))->toBe($case['expected_class']);
})->with('checkbox class emission');

it('renders a bare checkbox when no label is provided', function (): void {
    $html = renderCheckbox(['name' => 'ok']);
    $openingTag = checkboxOpeningTag($html);

    expect(trim($html))->toStartWith('<input ')
        ->and($openingTag)->toContain('type="checkbox"')
        ->and($openingTag)->toContain('name="ok"')
        ->and(checkboxClass($html))->toBe('lyra-checkbox')
        ->and($html)->not->toContain('<label')
        ->and($html)->not->toContain('<span');
});

it('wraps a labeled checkbox with an implicit label association', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::checkbox label="Aceito" checked class="x" />
        BLADE);
    $openingTag = checkboxOpeningTag($html);

    expect($html)->toMatch('/<label class="lyra-check-row">\s*<input\b[^>]*>\s*<span>Aceito<\/span>\s*<\/label>/s')
        ->and($openingTag)->toContain('type="checkbox"')
        ->and($openingTag)->toContain('checked')
        ->and(checkboxClass($html))->toBe('lyra-checkbox x')
        ->and($openingTag)->not->toContain('label=')
        ->and($html)->not->toContain('<span class=');
});

it('forces checkbox type and passes native input attributes through', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::checkbox type="text" name="terms" value="yes" disabled data-track="consent" />
        BLADE);
    $openingTag = checkboxOpeningTag($html);

    expect($openingTag)->toContain('type="checkbox"')
        ->and($openingTag)->not->toContain('type="text"')
        ->and($openingTag)->toContain('name="terms"')
        ->and($openingTag)->toContain('value="yes"')
        ->and($openingTag)->toContain('disabled')
        ->and($openingTag)->toContain('data-track="consent"');
});
