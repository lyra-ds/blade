<?php

use Illuminate\Support\Facades\Blade;

function renderRadioGroup(array $props = []): string
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

    return Blade::render(sprintf('<x-lyra::radio-group %s />', $attributes), $data);
}

function radioGroupOpeningTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\brole="[^"]+"[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function radioGroupClass(string $html, string $target): string
{
    if ($target === 'root') {
        $matched = preg_match('/\bclass="([^"]*)"/', radioGroupOpeningTag($html), $matches);

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

function radioGroupInputTags(string $html): array
{
    preg_match_all('/<input\b[^>]*>/', $html, $matches);

    return $matches[0];
}

dataset('radio group class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/radio-group.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the radio group class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class strings', function (array $case): void {
    expect(radioGroupClass(renderRadioGroup($case['props']), $case['target']))
        ->toBe($case['expected_class']);
})->with('radio group class emission');

it('renders the default column radiogroup without generated labelling', function (): void {
    $html = renderRadioGroup([
        'options' => [['value' => 'a', 'label' => 'A']],
    ]);
    $openingTag = radioGroupOpeningTag($html);

    expect(radioGroupClass($html, 'root'))->toBe('lyra-field')
        ->and($openingTag)->toContain('role="radiogroup"')
        ->and($openingTag)->not->toContain('aria-labelledby=')
        ->and(radioGroupClass($html, 'choicegroup'))->toBe('lyra-choicegroup');
});

it('wires the generated label id and appends it after consumer labelling', function (): void {
    $html = renderRadioGroup([
        'label' => 'Plan',
        'aria-labelledby' => 'account-heading',
    ]);
    $openingTag = radioGroupOpeningTag($html);

    preg_match('/<span id="([^"]+)" class="lyra-label">Plan<\/span>/', $html, $matches);

    expect($matches)->toHaveCount(2)
        ->and($matches[1])->toStartWith('lyra-radio-group-label-')
        ->and($openingTag)->toContain(sprintf('aria-labelledby="account-heading %s"', $matches[1]));
});

it('uses only the generated id when a label has no consumer labelling', function (): void {
    $html = renderRadioGroup(['label' => 'Plan']);

    preg_match('/<span id="([^"]+)" class="lyra-label">Plan<\/span>/', $html, $matches);

    expect($matches)->toHaveCount(2)
        ->and(radioGroupOpeningTag($html))->toContain(sprintf('aria-labelledby="%s"', $matches[1]));
});

it('preserves consumer labelling without generating an id when label is absent', function (): void {
    $html = renderRadioGroup(['aria-labelledby' => 'external-label']);

    expect(radioGroupOpeningTag($html))->toContain('aria-labelledby="external-label"')
        ->and($html)->not->toContain('lyra-radio-group-label-');
});

it('uses one provided name and each option value on every input', function (): void {
    $inputs = radioGroupInputTags(renderRadioGroup([
        'name' => 'plan',
        'options' => [
            ['value' => 'a', 'label' => 'A'],
            ['value' => 'b', 'label' => 'B'],
        ],
    ]));

    expect($inputs)->toHaveCount(2)
        ->and($inputs[0])->toContain('name="plan"')->toContain('value="a"')
        ->and($inputs[1])->toContain('name="plan"')->toContain('value="b"');
});

it('generates one shared fallback name for every input', function (): void {
    $inputs = radioGroupInputTags(renderRadioGroup([
        'options' => [
            ['value' => 'a', 'label' => 'A'],
            ['value' => 'b', 'label' => 'B'],
        ],
    ]));

    preg_match('/\bname="([^"]+)"/', $inputs[0], $firstName);
    preg_match('/\bname="([^"]+)"/', $inputs[1], $secondName);

    expect($firstName)->toHaveCount(2)
        ->and($secondName)->toHaveCount(2)
        ->and($firstName[1])->toStartWith('lyra-radio-group-')
        ->and($secondName[1])->toBe($firstName[1]);
});

it('lets value override defaultValue with strict checked comparison', function (): void {
    $inputs = radioGroupInputTags(renderRadioGroup([
        'options' => [
            ['value' => '1', 'label' => 'String'],
            ['value' => 1, 'label' => 'Integer'],
            ['value' => 'b', 'label' => 'B'],
        ],
        'value' => '1',
        'defaultValue' => 'b',
    ]));

    expect($inputs)->toHaveCount(3)
        ->and($inputs[0])->toContain('checked')
        ->and($inputs[1])->not->toContain('checked')
        ->and($inputs[2])->not->toContain('checked');
});

it('uses defaultValue when value is absent and applies option disabled state', function (): void {
    $inputs = radioGroupInputTags(renderRadioGroup([
        'options' => [
            ['value' => 'a', 'label' => 'A', 'disabled' => true],
            ['value' => 'b', 'label' => 'B'],
        ],
        'defaultValue' => 'a',
    ]));

    expect($inputs[0])->toContain('checked')->toContain('disabled')
        ->and($inputs[1])->not->toContain('checked')->not->toContain('disabled');
});

it('renders the exact labeled radio structure with and without an option hint', function (): void {
    $html = renderRadioGroup([
        'options' => [
            ['value' => 'a', 'label' => 'Plain'],
            ['value' => 'b', 'label' => 'Detailed', 'hint' => 'More detail'],
        ],
    ]);

    expect($html)->toMatch('/<label class="lyra-check-row">\s*<input\b[^>]*type="radio"[^>]*class="lyra-radio"[^>]*>\s*<span>Plain<\/span>\s*<\/label>/s')
        ->and($html)->toMatch('/<label class="lyra-check-row">\s*<input\b[^>]*type="radio"[^>]*class="lyra-radio"[^>]*>\s*<span><span class="lyra-choice"><span>Detailed<\/span><span class="lyra-choice__hint">More detail<\/span><\/span><\/span>\s*<\/label>/s');
});

it('replaces the hint with the error and applies the row modifier', function (): void {
    $html = renderRadioGroup([
        'direction' => 'row',
        'hint' => 'Choose one',
        'error' => 'Required',
    ]);

    expect(radioGroupClass($html, 'choicegroup'))->toBe('lyra-choicegroup lyra-choicegroup--row')
        ->and($html)->toContain('<span class="lyra-hint lyra-hint--error">Required</span>')
        ->and($html)->not->toContain('Choose one');
});

it('passes attributes to the root and keeps user classes last', function (): void {
    $html = renderRadioGroup([
        'class' => 'x y',
        'id' => 'plans',
        'data-track' => 'choices',
        'aria-label' => 'Plans',
    ]);
    $openingTag = radioGroupOpeningTag($html);

    expect(radioGroupClass($html, 'root'))->toBe('lyra-field x y')
        ->and($openingTag)->toContain('id="plans"')
        ->and($openingTag)->toContain('data-track="choices"')
        ->and($openingTag)->toContain('aria-label="Plans"')
        ->and($openingTag)->toContain('role="radiogroup"');
});
