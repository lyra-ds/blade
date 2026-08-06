<?php

use Illuminate\Support\Facades\Blade;

function renderToast(array $props = [], string $slot = 'Message', ?string $icon = null): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    $iconSlot = $icon === null ? '' : sprintf("<x-slot:icon>%s</x-slot:icon>\n", $icon);

    return Blade::render(sprintf(
        '<x-lyra::toast %s>%s%s</x-lyra::toast>',
        $attributes,
        $iconSlot,
        $slot,
    ));
}

function renderToastStack(array $props = [], string $slot = ''): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf(
        '<x-lyra::toast-stack %s>%s</x-lyra::toast-stack>',
        $attributes,
        $slot,
    ));
}

function rootClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function rootOpeningTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function classEmissionCases(string $component): array
{
    $contents = file_get_contents(dirname(__DIR__)."/Fixtures/class-emission/{$component}.json");

    if ($contents === false) {
        throw new RuntimeException("Unable to read the {$component} class-emission fixture.");
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
}

dataset('toast class emission', fn (): array => classEmissionCases('toast'));
dataset('toast stack class emission', fn (): array => classEmissionCases('toast-stack'));

it('emits the exact React toast class string', function (array $case): void {
    expect(rootClass(renderToast($case['props'])))->toBe($case['expected_class']);
})->with('toast class emission');

it('emits the exact React toast stack class string', function (array $case): void {
    expect(rootClass(renderToastStack($case['props'])))->toBe($case['expected_class']);
})->with('toast stack class emission');

it('renders the default toast contract', function (): void {
    $html = renderToast(slot: 'Saved');
    $openingTag = rootOpeningTag($html);

    expect(rootClass($html))->toBe('lyra-toast')
        ->and($openingTag)->toContain('role="status"')
        ->and($html)->toContain('<span>Saved</span>')
        ->and($html)->not->toContain('lyra-toast__icon')
        ->and($html)->not->toContain('lyra-toast__close');
});

it('lets a user role override the default and passes root attributes through', function (): void {
    $html = Blade::render('<x-lyra::toast class="x y" role="alert" id="notice" data-track="toast">Saved</x-lyra::toast>');
    $openingTag = rootOpeningTag($html);

    expect(rootClass($html))->toBe('lyra-toast x y')
        ->and($openingTag)->toContain('role="alert"')
        ->and($openingTag)->not->toContain('role="status"')
        ->and(substr_count($openingTag, 'role='))->toBe(1)
        ->and($openingTag)->toContain('id="notice"')
        ->and($openingTag)->toContain('data-track="toast"');
});

it('renders an icon before the message with the default info tone', function (): void {
    $html = renderToast(slot: 'Saved', icon: '<svg data-part="icon"></svg>');
    $iconPosition = strpos($html, 'lyra-toast__icon');
    $messagePosition = strpos($html, 'Saved</span>');

    expect($html)->toContain('<span class="lyra-toast__icon lyra-toast__icon--info"><svg data-part="icon"></svg></span>')
        ->and($iconPosition)->toBeInt()
        ->and($messagePosition)->toBeInt()
        ->and($iconPosition)->toBeLessThan($messagePosition);
});

it('emits each React icon tone modifier', function (string $tone): void {
    $html = renderToast(['tone' => $tone], icon: 'Icon');

    expect($html)->toContain("<span class=\"lyra-toast__icon lyra-toast__icon--{$tone}\">Icon</span>");
})->with(['info', 'success', 'danger']);

it('does not render an icon for an absent or empty icon slot', function (): void {
    expect(renderToast(['tone' => 'danger']))->not->toContain('lyra-toast__icon')
        ->and(renderToast(['tone' => 'danger'], icon: '   '))->not->toContain('lyra-toast__icon');
});

it('renders toast stack attributes and nested toast content', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::toast-stack class="x" id="notifications" data-track="stack">
            <x-lyra::toast>Hi</x-lyra::toast>
        </x-lyra::toast-stack>
        BLADE);
    $openingTag = rootOpeningTag($html);

    expect(rootClass($html))->toBe('lyra-toast-stack x')
        ->and($openingTag)->toContain('id="notifications"')
        ->and($openingTag)->toContain('data-track="stack"')
        ->and($html)->toContain('class="lyra-toast"')
        ->and($html)->toContain('role="status"')
        ->and($html)->toContain('<span>Hi</span>');
});
