<?php

use Illuminate\Support\Facades\Blade;

function renderInput(array $props = [], ?string $iconLeft = null): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    $iconSlot = $iconLeft === null
        ? ''
        : sprintf('<x-slot:iconLeft>%s</x-slot:iconLeft>', $iconLeft);

    return Blade::render(sprintf(
        '<x-lyra::input %s>%s</x-lyra::input>',
        $attributes,
        $iconSlot,
    ));
}

function inputOpeningTag(string $html): string
{
    $matched = preg_match('/<input\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function inputClass(string $html): string
{
    $matched = preg_match('/<input\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function inputAttribute(string $html, string $attribute): ?string
{
    $matched = preg_match(
        sprintf('/<input\b[^>]*\b%s="([^"]*)"/', preg_quote($attribute, '/')),
        $html,
        $matches,
    );

    return $matched === 1 ? $matches[1] : null;
}

dataset('input class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/input.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the input class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(inputClass(renderInput($case['props'])))->toBe($case['expected_class']);
})->with('input class emission');

it('renders the bare input contract without field-only aria', function (): void {
    $html = renderInput(['name' => 'q']);
    $openingTag = inputOpeningTag($html);

    expect(inputClass($html))->toBe('lyra-input')
        ->and($openingTag)->toContain('name="q"')
        ->and($openingTag)->not->toContain('aria-invalid=')
        ->and($html)->not->toContain('lyra-field');
});

it('wires an explicit id to the label and error message', function (): void {
    $html = renderInput([
        'id' => 'email',
        'label' => 'E-mail',
        'error' => 'Obrigatório',
        'size' => 'sm',
        'class' => 'x',
    ]);
    $describedBy = inputAttribute($html, 'aria-describedby');

    expect($html)->toContain('<div class="lyra-field">')
        ->and($html)->toContain('<label class="lyra-label" for="email">E-mail</label>')
        ->and(inputClass($html))->toBe('lyra-input lyra-input--sm lyra-input--error x')
        ->and(inputAttribute($html, 'id'))->toBe('email')
        ->and(inputAttribute($html, 'aria-invalid'))->toBe('true')
        ->and($describedBy)->not->toBeNull()
        ->and($html)->toContain(sprintf(
            '<span id="%s" class="lyra-hint lyra-hint--error">Obrigatório</span>',
            $describedBy,
        ));
});

it('generates a unique input id for each render', function (): void {
    $firstId = inputAttribute(renderInput(['label' => 'First']), 'id');
    $secondId = inputAttribute(renderInput(['label' => 'Second']), 'id');

    expect($firstId)->not->toBeNull()
        ->and($secondId)->not->toBeNull()
        ->and($secondId)->not->toBe($firstId);
});

it('replaces the hint with the error message', function (): void {
    $html = renderInput([
        'hint' => 'Use at least 8 characters',
        'error' => 'Password is required',
    ]);

    expect($html)->toContain('lyra-hint lyra-hint--error')
        ->and($html)->toContain('Password is required')
        ->and($html)->not->toContain('Use at least 8 characters');
});

it('appends the message id to a consumer aria description', function (): void {
    $html = renderInput([
        'hint' => 'Public profile',
        'aria-describedby' => 'account-help',
    ]);
    $describedBy = inputAttribute($html, 'aria-describedby');

    expect($describedBy)->toMatch('/^account-help lyra-input-message-/')
        ->and($html)->toContain(sprintf(
            '<span id="%s" class="lyra-hint">Public profile</span>',
            substr($describedBy, strlen('account-help ')),
        ));
});

it('preserves consumer aria attributes when no error or message renders', function (): void {
    $html = renderInput([
        'aria-invalid' => 'false',
        'aria-describedby' => 'external-help',
    ]);

    expect(inputAttribute($html, 'aria-invalid'))->toBe('false')
        ->and(inputAttribute($html, 'aria-describedby'))->toBe('external-help');
});

it('wraps an icon before the input and renders a plain hint', function (): void {
    $html = renderInput(
        ['label' => 'Busca', 'hint' => '3+ letras'],
        '<svg data-icon="search"></svg>',
    );

    expect($html)->toMatch(
        '/<span class="lyra-input-wrap">\s*<span class="lyra-input-wrap__icon"><svg data-icon="search"><\/svg><\/span>\s*<input\b[^>]*>\s*<\/span>/s',
    )
        ->and($html)->toContain('class="lyra-hint">3+ letras</span>')
        ->and($html)->not->toContain('lyra-hint--error');
});

it('passes native attributes to the input and keeps user classes last', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::input type="email" name="email" value="a@example.com" placeholder="Email" disabled required class="first second" data-track="signup" />
        BLADE);
    $openingTag = inputOpeningTag($html);

    expect(inputClass($html))->toBe('lyra-input first second')
        ->and($openingTag)->toContain('type="email"')
        ->and($openingTag)->toContain('name="email"')
        ->and($openingTag)->toContain('value="a@example.com"')
        ->and($openingTag)->toContain('placeholder="Email"')
        ->and($openingTag)->toContain('disabled')
        ->and($openingTag)->toContain('required')
        ->and($openingTag)->toContain('data-track="signup"');
});
