<?php

use Illuminate\Support\Facades\Blade;

function renderButton(array $props = [], string $slot = 'Button'): string
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
        '<x-lyra::button %s>%s</x-lyra::button>',
        $attributes,
        $slot,
    ));
}

function buttonClass(string $html): string
{
    $matched = preg_match('/<button\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function buttonOpeningTag(string $html): string
{
    $matched = preg_match('/<button\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('button class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/button.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the button class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(buttonClass(renderButton($case['props'])))->toBe($case['expected_class']);
})->with('button class emission');

it('renders the default button contract', function (): void {
    $html = renderButton(slot: 'Go');
    $openingTag = buttonOpeningTag($html);

    expect(buttonClass($html))->toBe('lyra-btn lyra-btn--primary lyra-btn--md')
        ->and($openingTag)->not->toMatch('/\sdisabled(?:\s|=|>)/')
        ->and($openingTag)->not->toContain('aria-busy=')
        ->and($html)->not->toContain('lyra-btn__spinner')
        ->and($html)->toContain('<span class="lyra-btn__label">Go</span>');
});

it('renders loading state and passes root attributes through', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::button variant="danger" size="sm" loading class="extra" type="submit" form="editor" id="save-button" data-track="save" aria-label="Save changes">Save</x-lyra::button>
        BLADE);
    $openingTag = buttonOpeningTag($html);
    $spinnerPosition = strpos($html, 'lyra-btn__spinner');
    $labelPosition = strpos($html, 'lyra-btn__label');

    expect(buttonClass($html))->toBe('lyra-btn lyra-btn--danger lyra-btn--sm lyra-btn--loading extra')
        ->and($openingTag)->toMatch('/\sdisabled(?:\s|=|>)/')
        ->and($openingTag)->toContain('aria-busy="true"')
        ->and($openingTag)->toContain('type="submit"')
        ->and($openingTag)->toContain('form="editor"')
        ->and($openingTag)->toContain('id="save-button"')
        ->and($openingTag)->toContain('data-track="save"')
        ->and($openingTag)->toContain('aria-label="Save changes"')
        ->and($html)->toContain('<span class="lyra-btn__spinner" aria-hidden="true"></span>')
        ->and($html)->toContain('<span class="lyra-btn__label">Save</span>')
        ->and($spinnerPosition)->toBeInt()
        ->and($labelPosition)->toBeInt()
        ->and($spinnerPosition)->toBeLessThan($labelPosition);
});

it('renders spinner, icons, and label in React order', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::button loading>
            <x-slot:iconLeft><span data-icon="left">Left</span></x-slot:iconLeft>
            Save
            <x-slot:iconRight><span data-icon="right">Right</span></x-slot:iconRight>
        </x-lyra::button>
        BLADE);

    $spinnerPosition = strpos($html, 'lyra-btn__spinner');
    $leftPosition = strpos($html, 'data-icon="left"');
    $labelPosition = strpos($html, 'lyra-btn__label');
    $rightPosition = strpos($html, 'data-icon="right"');

    expect($spinnerPosition)->toBeInt()
        ->and($leftPosition)->toBeInt()
        ->and($labelPosition)->toBeInt()
        ->and($rightPosition)->toBeInt()
        ->and($spinnerPosition)->toBeLessThan($leftPosition)
        ->and($leftPosition)->toBeLessThan($labelPosition)
        ->and($labelPosition)->toBeLessThan($rightPosition);
});

it('disables without exposing loading markup when only disabled', function (): void {
    $html = renderButton(['disabled' => true], 'Unavailable');
    $openingTag = buttonOpeningTag($html);

    expect($openingTag)->toMatch('/\sdisabled(?:\s|=|>)/')
        ->and($openingTag)->not->toContain('aria-busy=')
        ->and($html)->not->toContain('lyra-btn__spinner');
});

it('omits the label wrapper when the default slot is empty', function (): void {
    $html = Blade::render('<x-lyra::button />');

    expect($html)->not->toContain('lyra-btn__label');
});
