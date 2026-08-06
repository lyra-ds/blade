<?php

use Illuminate\Support\Facades\Blade;

function renderFormRow(array $props = [], string $slot = '<div>G</div>'): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf('<x-lyra::form-row %s>%s</x-lyra::form-row>', $attributes, $slot));
}

function formRowClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function formRowOpeningTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('form row class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/form-row.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the form-row class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(formRowClass(renderFormRow($case['props'])))->toBe($case['expected_class']);
})->with('form row class emission');

it('maps explicit columns to a repeat template', function (): void {
    $openingTag = formRowOpeningTag(Blade::render(
        '<x-lyra::form-row :columns="2"><div>A</div></x-lyra::form-row>',
    ));

    expect($openingTag)->toContain(
        'style="--lyra-formrow-columns: repeat(2, minmax(0, 1fr))"',
    );
});

it('derives columns from top-level slot elements', function (): void {
    $openingTag = formRowOpeningTag(Blade::render(<<<'BLADE'
        <x-lyra::form-row>
            <div>a</div>
            <div><span>nested</span></div>
            <div>c</div>
        </x-lyra::form-row>
        BLADE));

    expect($openingTag)->toContain(
        'style="--lyra-formrow-columns: repeat(3, minmax(0, 1fr))"',
    );
});

it('appends consumer styles after the computed property', function (): void {
    $openingTag = formRowOpeningTag(Blade::render(
        '<x-lyra::form-row :columns="2" style="color: red">G</x-lyra::form-row>',
    ));

    expect($openingTag)->toMatch(
        '/style="--lyra-formrow-columns: repeat\(2, minmax\(0, 1fr\)\);?\s*color: red;?"/',
    );
});

it('passes root attributes through and renders the slot', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::form-row :columns="1" id="content" data-track="form-row">
            <span>Slot content</span>
        </x-lyra::form-row>
        BLADE);
    $openingTag = formRowOpeningTag($html);

    expect($openingTag)->toContain('id="content"')
        ->and($openingTag)->toContain('data-track="form-row"')
        ->and(trim($html))->toContain('<span>Slot content</span>');
});

it('keeps user classes last', function (): void {
    $html = Blade::render(
        '<x-lyra::form-row :columns="1" class="first second">G</x-lyra::form-row>',
    );

    expect(formRowClass($html))->toBe('lyra-formrow first second');
});
