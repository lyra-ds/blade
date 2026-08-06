<?php

use Illuminate\Support\Facades\Blade;

function renderSwitch(array $props = []): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf('<x-lyra::switch %s />', $attributes));
}

function switchInputOpeningTag(string $html): string
{
    $matched = preg_match('/<input\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function switchLabelClass(string $html): string
{
    $matched = preg_match('/<label\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

dataset('switch class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/switch.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the switch class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React root class string', function (array $case): void {
    expect(switchLabelClass(renderSwitch($case['props'])))->toBe($case['expected_class']);
})->with('switch class emission');

it('always renders the label root, input, and track without label text', function (): void {
    $html = renderSwitch(['name' => 'dark']);
    $input = switchInputOpeningTag($html);

    expect(trim($html))->toStartWith('<label class="lyra-switch">')
        ->and($html)->toMatch('/<label class="lyra-switch">\s*<input\b[^>]*>\s*<span class="lyra-switch__track" aria-hidden="true"><\/span>\s*<\/label>/s')
        ->and($input)->toContain('type="checkbox"')
        ->and($input)->toContain('role="switch"')
        ->and($input)->toContain('name="dark"')
        ->and($input)->not->toContain('class=')
        ->and(substr_count($html, '<span'))->toBe(1);
});

it('renders conditional label text after the track', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::switch label="Tema escuro" checked class="x" />
        BLADE);
    $input = switchInputOpeningTag($html);

    expect($html)->toMatch('/<label class="lyra-switch x">\s*<input\b[^>]*>\s*<span class="lyra-switch__track" aria-hidden="true"><\/span>\s*<span>Tema escuro<\/span>\s*<\/label>/s')
        ->and($input)->toContain('checked')
        ->and($input)->not->toContain('class=')
        ->and($input)->not->toContain('label=');
});

it('forces input type and role and passes native attributes through', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::switch type="text" role="checkbox" name="alerts" disabled required data-track="alerts" />
        BLADE);
    $input = switchInputOpeningTag($html);

    expect($input)->toContain('type="checkbox"')
        ->and($input)->not->toContain('type="text"')
        ->and($input)->toContain('role="switch"')
        ->and($input)->not->toContain('role="checkbox"')
        ->and($input)->toContain('name="alerts"')
        ->and($input)->toContain('disabled')
        ->and($input)->toContain('required')
        ->and($input)->toContain('data-track="alerts"')
        ->and($input)->not->toContain('class=');
});
