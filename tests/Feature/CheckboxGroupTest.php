<?php

use Illuminate\Support\Facades\Blade;

function renderCheckboxGroup(array $props = []): string
{
    $data = [];
    $attributes = collect($props)
        ->map(function (mixed $value, string $name) use (&$data): string {
            if (is_array($value)) {
                $data[$name] = $value;

                return sprintf(':%s="$%s"', $name, $name);
            }

            if (is_bool($value)) {
                return $value ? $name : sprintf(':%s="false"', $name);
            }

            return sprintf('%s="%s"', $name, htmlspecialchars((string) $value, ENT_QUOTES));
        })
        ->implode(' ');

    return Blade::render(sprintf('<x-lyra::checkbox-group %s />', $attributes), $data);
}

function checkboxGroupOpeningTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\brole="[^"]+"[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function checkboxGroupClass(string $html, string $target): string
{
    if ($target === 'root') {
        $matched = preg_match('/\bclass="([^"]*)"/', checkboxGroupOpeningTag($html), $matches);

        expect($matched)->toBe(1);

        return $matches[1];
    }

    $patterns = [
        'choicegroup' => '/<div class="(lyra-choicegroup[^"]*)">/',
        'hint' => '/<span class="(lyra-hint[^"]*)">/',
        'choice' => '/<span class="(lyra-choice)">/',
        'choice_hint' => '/<span class="(lyra-choice__hint)">/',
    ];

    $matched = preg_match($patterns[$target], $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function checkboxGroupInputTags(string $html): array
{
    preg_match_all('/<input\b[^>]*>/', $html, $matches);

    return $matches[0];
}

dataset('checkbox group class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/checkbox-group.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the checkbox group class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class strings', function (array $case): void {
    expect(checkboxGroupClass(renderCheckboxGroup($case['props']), $case['target']))
        ->toBe($case['expected_class']);
})->with('checkbox group class emission');

it('renders the default column group without generated labelling', function (): void {
    $html = renderCheckboxGroup([
        'options' => [['value' => 'a', 'label' => 'A']],
    ]);
    $openingTag = checkboxGroupOpeningTag($html);

    expect(checkboxGroupClass($html, 'root'))->toBe('lyra-field')
        ->and($openingTag)->toContain('role="group"')
        ->and($openingTag)->not->toContain('aria-labelledby=')
        ->and(checkboxGroupClass($html, 'choicegroup'))->toBe('lyra-choicegroup');
});

it('wires the generated label id and appends it after consumer labelling', function (): void {
    $html = renderCheckboxGroup([
        'label' => 'Preferences',
        'aria-labelledby' => 'account-heading',
    ]);
    $openingTag = checkboxGroupOpeningTag($html);

    preg_match('/<span id="([^"]+)" class="lyra-label">Preferences<\/span>/', $html, $matches);

    expect($matches)->toHaveCount(2)
        ->and($matches[1])->toStartWith('lyra-checkbox-group-label-')
        ->and($openingTag)->toContain(sprintf('aria-labelledby="account-heading %s"', $matches[1]));
});

it('uses only the generated id when a label has no consumer labelling', function (): void {
    $html = renderCheckboxGroup(['label' => 'Preferences']);

    preg_match('/<span id="([^"]+)" class="lyra-label">Preferences<\/span>/', $html, $matches);

    expect($matches)->toHaveCount(2)
        ->and(checkboxGroupOpeningTag($html))->toContain(sprintf('aria-labelledby="%s"', $matches[1]));
});

it('lets value override defaultValue when determining checked options', function (): void {
    $html = renderCheckboxGroup([
        'name' => 'prefs',
        'options' => [
            ['value' => 'a', 'label' => 'A'],
            ['value' => 'b', 'label' => 'B'],
        ],
        'value' => ['b'],
        'defaultValue' => ['a'],
    ]);
    $inputs = checkboxGroupInputTags($html);

    expect($inputs)->toHaveCount(2)
        ->and($inputs[0])->toContain('value="a"')->not->toContain('checked')
        ->and($inputs[1])->toContain('value="b"')->toContain('checked');
});

it('uses defaultValue when value is absent and applies option disabled state', function (): void {
    $html = renderCheckboxGroup([
        'options' => [
            ['value' => 'a', 'label' => 'A', 'disabled' => true],
            ['value' => 'b', 'label' => 'B'],
        ],
        'defaultValue' => ['a'],
    ]);
    $inputs = checkboxGroupInputTags($html);

    expect($inputs[0])->toContain('checked')->toContain('disabled')
        ->and($inputs[1])->not->toContain('checked')->not->toContain('disabled');
});

it('adds native array names and option values only when name is set', function (): void {
    $named = checkboxGroupInputTags(renderCheckboxGroup([
        'name' => 'prefs',
        'options' => [
            ['value' => 'a', 'label' => 'A'],
            ['value' => 'b', 'label' => 'B'],
        ],
    ]));
    $unnamed = checkboxGroupInputTags(renderCheckboxGroup([
        'options' => [['value' => 'a', 'label' => 'A']],
    ]));

    expect($named[0])->toContain('name="prefs[]"')->toContain('value="a"')
        ->and($named[1])->toContain('name="prefs[]"')->toContain('value="b"')
        ->and($unnamed[0])->not->toContain('name=')->not->toContain('value=');
});

it('renders the exact labeled checkbox structure with and without an option hint', function (): void {
    $html = renderCheckboxGroup([
        'options' => [
            ['value' => 'a', 'label' => 'Plain'],
            ['value' => 'b', 'label' => 'Detailed', 'hint' => 'More detail'],
        ],
    ]);

    expect($html)->toMatch('/<label class="lyra-check-row">\s*<input\b[^>]*>\s*<span>Plain<\/span>\s*<\/label>/s')
        ->and($html)->toMatch('/<label class="lyra-check-row">\s*<input\b[^>]*>\s*<span><span class="lyra-choice"><span>Detailed<\/span><span class="lyra-choice__hint">More detail<\/span><\/span><\/span>\s*<\/label>/s');
});

it('replaces the hint with the error and applies the row modifier', function (): void {
    $html = renderCheckboxGroup([
        'direction' => 'row',
        'hint' => 'Choose one',
        'error' => 'Required',
    ]);

    expect(checkboxGroupClass($html, 'choicegroup'))->toBe('lyra-choicegroup lyra-choicegroup--row')
        ->and($html)->toContain('<span class="lyra-hint lyra-hint--error">Required</span>')
        ->and($html)->not->toContain('Choose one');
});

it('passes attributes to the root and keeps user classes last', function (): void {
    $html = renderCheckboxGroup([
        'class' => 'x y',
        'id' => 'preferences',
        'data-track' => 'choices',
        'aria-label' => 'Preferences',
        'role' => 'radiogroup',
    ]);
    $openingTag = checkboxGroupOpeningTag($html);

    expect(checkboxGroupClass($html, 'root'))->toBe('lyra-field x y')
        ->and($openingTag)->toContain('id="preferences"')
        ->and($openingTag)->toContain('data-track="choices"')
        ->and($openingTag)->toContain('aria-label="Preferences"')
        ->and($openingTag)->toContain('role="radiogroup"');
});
