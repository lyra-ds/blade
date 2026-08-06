<?php

use Illuminate\Support\Facades\Blade;

function renderRadio(array $props = []): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf('<x-lyra::radio %s />', $attributes));
}

function radioOpeningTag(string $html): string
{
    $matched = preg_match('/<input\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function radioClass(string $html): string
{
    $matched = preg_match('/<input\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

dataset('radio class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/radio.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the radio class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(radioClass(renderRadio($case['props'])))->toBe($case['expected_class']);
})->with('radio class emission');

it('renders a bare radio when no label is provided', function (): void {
    $html = renderRadio(['name' => 'plan', 'value' => 'pro']);
    $openingTag = radioOpeningTag($html);

    expect(trim($html))->toStartWith('<input ')
        ->and($openingTag)->toContain('type="radio"')
        ->and($openingTag)->toContain('name="plan"')
        ->and($openingTag)->toContain('value="pro"')
        ->and(radioClass($html))->toBe('lyra-radio')
        ->and($html)->not->toContain('<label')
        ->and($html)->not->toContain('<span');
});

it('wraps a labeled radio with an implicit label association', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::radio label="Pro" name="plan" checked class="x" />
        BLADE);
    $openingTag = radioOpeningTag($html);

    expect($html)->toMatch('/<label class="lyra-check-row">\s*<input\b[^>]*>\s*<span>Pro<\/span>\s*<\/label>/s')
        ->and($openingTag)->toContain('type="radio"')
        ->and($openingTag)->toContain('name="plan"')
        ->and($openingTag)->toContain('checked')
        ->and(radioClass($html))->toBe('lyra-radio x')
        ->and($openingTag)->not->toContain('label=')
        ->and($html)->not->toContain('<span class=');
});

it('forces radio type and passes native input attributes through', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::radio type="text" name="plan" value="team" disabled required data-track="plan" />
        BLADE);
    $openingTag = radioOpeningTag($html);

    expect($openingTag)->toContain('type="radio"')
        ->and($openingTag)->not->toContain('type="text"')
        ->and($openingTag)->toContain('name="plan"')
        ->and($openingTag)->toContain('value="team"')
        ->and($openingTag)->toContain('disabled')
        ->and($openingTag)->toContain('required')
        ->and($openingTag)->toContain('data-track="plan"');
});
