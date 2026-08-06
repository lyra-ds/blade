<?php

use Illuminate\Support\Facades\Blade;

function renderPersonCell(array $props = [], string $slot = ''): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf(
        '<x-lyra::person-cell %s>%s</x-lyra::person-cell>',
        $attributes,
        $slot,
    ));
}

function personCellClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function personCellOpeningTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('person cell class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/person-cell.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the person-cell class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(personCellClass(renderPersonCell($case['props'])))->toBe($case['expected_class']);
})->with('person cell class emission');

it('renders the default avatar surface and name without detail', function (): void {
    $html = renderPersonCell(['name' => 'Ada Lovelace'], '<strong data-default>Ignored</strong>');

    expect(personCellClass($html))->toBe('lyra-personcell')
        ->and($html)->toContain('class="lyra-avatar lyra-avatar--md"')
        ->and($html)->toContain('<span aria-hidden="true">AL</span>')
        ->and($html)->toContain('<span class="lyra-personcell__text">')
        ->and($html)->toContain('<span class="lyra-personcell__name">Ada Lovelace</span>')
        ->and($html)->not->toContain('lyra-personcell__detail')
        ->and($html)->not->toContain('data-default');
});

it('forwards src and name to the avatar and renders detail when provided', function (): void {
    $html = renderPersonCell([
        'name' => 'Ada',
        'detail' => 'ada@ex.io',
        'src' => '/a.png',
    ]);

    expect($html)->toContain('class="lyra-avatar lyra-avatar--md"')
        ->and($html)->toContain('title="Ada"')
        ->and($html)->toContain('<img src="/a.png" alt="Ada">')
        ->and($html)->toContain('<span class="lyra-personcell__name">Ada</span>')
        ->and($html)->toContain('<span class="lyra-personcell__detail">ada@ex.io</span>')
        ->and($html)->not->toContain('aria-hidden="true"');
});

it('passes attributes through to the root and keeps user classes last', function (): void {
    $html = Blade::render(
        '<x-lyra::person-cell name="Ada" class="x y" id="person" data-id="1" aria-label="Account" />',
    );
    $openingTag = personCellOpeningTag($html);

    expect(personCellClass($html))->toBe('lyra-personcell x y')
        ->and($openingTag)->toContain('id="person"')
        ->and($openingTag)->toContain('data-id="1"')
        ->and($openingTag)->toContain('aria-label="Account"');
});
