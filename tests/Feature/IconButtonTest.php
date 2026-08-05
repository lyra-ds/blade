<?php

use Illuminate\Support\Facades\Blade;

function renderIconButton(array $props = [], string $slot = '<svg data-icon="close"></svg>'): string
{
    $attributes = collect(['label' => 'Close', ...$props])
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf(
        '<x-lyra::icon-button %s>%s</x-lyra::icon-button>',
        $attributes,
        $slot,
    ));
}

function iconButtonClass(string $html): string
{
    $matched = preg_match('/<button\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function iconButtonOpeningTag(string $html): string
{
    $matched = preg_match('/<button\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('icon button class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/icon-button.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the icon-button class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(iconButtonClass(renderIconButton($case['props'])))->toBe($case['expected_class']);
})->with('icon button class emission');

it('forces the accessible name from label and passes root attributes through', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::icon-button label="Close" aria-label="Wrong" title="Wrong" type="submit" form="editor" id="close-button" data-track="close">
            <svg data-icon="close"></svg>
        </x-lyra::icon-button>
        BLADE);
    $openingTag = iconButtonOpeningTag($html);

    expect($openingTag)->toContain('aria-label="Close"')
        ->and($openingTag)->toContain('title="Close"')
        ->and(substr_count($openingTag, 'aria-label='))->toBe(1)
        ->and(substr_count($openingTag, 'title='))->toBe(1)
        ->and($openingTag)->toContain('type="submit"')
        ->and($openingTag)->toContain('form="editor"')
        ->and($openingTag)->toContain('id="close-button"')
        ->and($openingTag)->toContain('data-track="close"');
});

it('renders the icon slot directly inside the button without a wrapper', function (): void {
    $html = renderIconButton(slot: '<svg data-icon="close"><path d="M1 1"></path></svg>');

    expect($html)->toMatch('/<button\b[^>]*>\s*<svg data-icon="close"><path d="M1 1"><\/path><\/svg>\s*<\/button>/s')
        ->and($html)->not->toContain('<span');
});
