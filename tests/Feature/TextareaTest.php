<?php

use Illuminate\Support\Facades\Blade;

function renderTextarea(array $props = [], string $slot = ''): string
{
    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(sprintf(
        '<x-lyra::textarea %s>%s</x-lyra::textarea>',
        $attributes,
        $slot,
    ));
}

function textareaOpeningTag(string $html): string
{
    $matched = preg_match('/<textarea\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function textareaClass(string $html): string
{
    $matched = preg_match('/<textarea\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function textareaAttribute(string $html, string $attribute): ?string
{
    $matched = preg_match(
        sprintf('/<textarea\b[^>]*\b%s="([^"]*)"/', preg_quote($attribute, '/')),
        $html,
        $matches,
    );

    return $matched === 1 ? $matches[1] : null;
}

dataset('textarea class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/textarea.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the textarea class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class string', function (array $case): void {
    expect(textareaClass(renderTextarea($case['props'])))->toBe($case['expected_class']);
})->with('textarea class emission');

it('renders the bare textarea contract without field-only aria', function (): void {
    $html = renderTextarea(['name' => 'bio']);
    $openingTag = textareaOpeningTag($html);

    expect(textareaClass($html))->toBe('lyra-input lyra-textarea')
        ->and($openingTag)->toContain('name="bio"')
        ->and($openingTag)->not->toContain('aria-invalid=')
        ->and($html)->not->toContain('lyra-field');
});

it('wires an explicit id to the label and error message', function (): void {
    $html = renderTextarea([
        'id' => 'bio',
        'label' => 'Bio',
        'error' => 'Curto demais',
        'class' => 'x',
    ], 'texto');
    $describedBy = textareaAttribute($html, 'aria-describedby');

    expect($html)->toContain('<div class="lyra-field">')
        ->and($html)->toContain('<label class="lyra-label" for="bio">Bio</label>')
        ->and(textareaClass($html))->toBe('lyra-input lyra-textarea lyra-input--error x')
        ->and(textareaAttribute($html, 'id'))->toBe('bio')
        ->and(textareaAttribute($html, 'aria-invalid'))->toBe('true')
        ->and($describedBy)->not->toBeNull()
        ->and($html)->toContain(sprintf(
            '<span id="%s" class="lyra-hint lyra-hint--error">Curto demais</span>',
            $describedBy,
        ));
});

it('generates a unique textarea id for each render', function (): void {
    $firstId = textareaAttribute(renderTextarea(['label' => 'First']), 'id');
    $secondId = textareaAttribute(renderTextarea(['label' => 'Second']), 'id');

    expect($firstId)->not->toBeNull()
        ->and($secondId)->not->toBeNull()
        ->and($secondId)->not->toBe($firstId);
});

it('replaces the hint with the error message', function (): void {
    $html = renderTextarea([
        'hint' => 'Tell us about yourself',
        'error' => 'Bio is required',
    ]);

    expect($html)->toContain('lyra-hint lyra-hint--error')
        ->and($html)->toContain('Bio is required')
        ->and($html)->not->toContain('Tell us about yourself');
});

it('appends the message id to a consumer aria description', function (): void {
    $html = renderTextarea([
        'hint' => 'Public profile',
        'aria-describedby' => 'account-help',
    ]);
    $describedBy = textareaAttribute($html, 'aria-describedby');

    expect($describedBy)->toMatch('/^account-help lyra-textarea-message-/')
        ->and($html)->toContain(sprintf(
            '<span id="%s" class="lyra-hint">Public profile</span>',
            substr($describedBy, strlen('account-help ')),
        ));
});

it('preserves consumer aria attributes when no error or message renders', function (): void {
    $html = renderTextarea([
        'aria-invalid' => 'false',
        'aria-describedby' => 'external-help',
    ]);

    expect(textareaAttribute($html, 'aria-invalid'))->toBe('false')
        ->and(textareaAttribute($html, 'aria-describedby'))->toBe('external-help');
});

it('renders the default slot as the textarea initial value', function (): void {
    $html = renderTextarea(['name' => 'bio'], 'Initial biography');

    expect($html)->toMatch('/<textarea\b[^>]*>Initial biography<\/textarea>/');
});

it('passes native attributes to the textarea and keeps user classes last', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::textarea name="bio" rows="5" cols="40" placeholder="Biography" disabled required maxlength="500" class="first second" data-track="profile">texto</x-lyra::textarea>
        BLADE);
    $openingTag = textareaOpeningTag($html);

    expect(textareaClass($html))->toBe('lyra-input lyra-textarea first second')
        ->and($openingTag)->toContain('name="bio"')
        ->and($openingTag)->toContain('rows="5"')
        ->and($openingTag)->toContain('cols="40"')
        ->and($openingTag)->toContain('placeholder="Biography"')
        ->and($openingTag)->toContain('disabled')
        ->and($openingTag)->toContain('required')
        ->and($openingTag)->toContain('maxlength="500"')
        ->and($openingTag)->toContain('data-track="profile"')
        ->and($html)->toMatch('/<textarea\b[^>]*>texto<\/textarea>/');
});
