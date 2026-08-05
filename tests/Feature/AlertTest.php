<?php

use Illuminate\Support\Facades\Blade;

function renderAlert(array $props = [], string $slot = 'Body'): string
{
    $title = $props['title'] ?? null;
    $icon = $props['icon'] ?? null;
    unset($props['title'], $props['icon']);

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    $slots = collect([
        'icon' => $icon,
        'title' => $title,
    ])->filter(fn (?string $value): bool => $value !== null)
        ->map(fn (string $value, string $name): string => sprintf(
            '<x-slot:%s>%s</x-slot:%s>',
            $name,
            $value,
            $name,
        ))
        ->implode('');

    return Blade::render(sprintf(
        '<x-lyra::alert %s>%s%s</x-lyra::alert>',
        $attributes,
        $slots,
        $slot,
    ));
}

function alertClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function alertOpeningTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('alert class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/alert.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the alert class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(alertClass(renderAlert($case['props'])))->toBe($case['expected_class']);
})->with('alert class emission');

it('renders the default alert contract', function (): void {
    $html = renderAlert(slot: 'Saved');
    $openingTag = alertOpeningTag($html);

    expect(alertClass($html))->toBe('lyra-alert lyra-alert--info')
        ->and($openingTag)->toContain('role="status"')
        ->and($html)->toContain('<p class="lyra-alert__body">Saved</p>')
        ->and($html)->not->toContain('lyra-alert__icon')
        ->and($html)->not->toContain('lyra-alert__title');
});

it('renders icon title and body in the React order with an unclassed inner div', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::alert tone="danger" class="x">
            <x-slot:icon><svg data-part="icon"></svg></x-slot:icon>
            <x-slot:title>Erro</x-slot:title>
            Falhou
        </x-lyra::alert>
        BLADE);
    preg_match_all('/<div\b[^>]*>/', $html, $divTags);

    $iconPosition = strpos($html, 'lyra-alert__icon');
    $innerDivPosition = strpos($html, '<div>', $iconPosition + 1);
    $titlePosition = strpos($html, 'lyra-alert__title');
    $bodyPosition = strpos($html, 'lyra-alert__body');

    expect(alertClass($html))->toBe('lyra-alert lyra-alert--danger x')
        ->and($divTags[0])->toHaveCount(2)
        ->and($divTags[0][1])->toBe('<div>')
        ->and($html)->toContain('<span class="lyra-alert__icon"><svg data-part="icon"></svg></span>')
        ->and($html)->toContain('<p class="lyra-alert__title">Erro</p>')
        ->and($html)->toContain('<p class="lyra-alert__body">')
        ->and($html)->toContain('Falhou')
        ->and($iconPosition)->toBeInt()
        ->and($innerDivPosition)->toBeInt()
        ->and($titlePosition)->toBeInt()
        ->and($bodyPosition)->toBeInt()
        ->and($iconPosition)->toBeLessThan($innerDivPosition)
        ->and($innerDivPosition)->toBeLessThan($titlePosition)
        ->and($titlePosition)->toBeLessThan($bodyPosition);
});

it('passes attributes through while forcing role status and keeping user classes last', function (): void {
    $html = Blade::render('<x-lyra::alert class="x y" role="alert" id="notice" data-track="alert" aria-live="polite">Body</x-lyra::alert>');
    $openingTag = alertOpeningTag($html);

    expect(alertClass($html))->toBe('lyra-alert lyra-alert--info x y')
        ->and($openingTag)->toContain('role="status"')
        ->and($openingTag)->not->toContain('role="alert"')
        ->and(substr_count($openingTag, 'role='))->toBe(1)
        ->and($openingTag)->toContain('id="notice"')
        ->and($openingTag)->toContain('data-track="alert"')
        ->and($openingTag)->toContain('aria-live="polite"');
});

it('always renders the body paragraph', function (): void {
    $html = Blade::render('<x-lyra::alert></x-lyra::alert>');

    expect($html)->toContain('<p class="lyra-alert__body"></p>');
});
