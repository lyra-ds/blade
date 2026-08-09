<?php

use Illuminate\Support\Facades\Blade;

function renderCombobox(array $props = [], string $slots = ''): string
{
    $options = $props['options'] ?? [];
    $value = $props['value'] ?? null;
    $defaultOpen = $props['defaultOpen'] ?? false;
    $label = $props['label'] ?? null;
    $hint = $props['hint'] ?? null;
    $error = $props['error'] ?? null;
    $placeholder = $props['placeholder'] ?? null;
    $searchPlaceholder = $props['searchPlaceholder'] ?? null;
    $emptyMessage = $props['emptyMessage'] ?? null;
    $searchLabel = $props['searchLabel'] ?? null;
    $disabled = $props['disabled'] ?? false;
    $factory = $props['factory'] ?? 'lyraCombobox';
    $extraOptions = $props['extraOptions'] ?? [];
    unset(
        $props['options'],
        $props['value'],
        $props['defaultOpen'],
        $props['label'],
        $props['hint'],
        $props['error'],
        $props['placeholder'],
        $props['searchPlaceholder'],
        $props['emptyMessage'],
        $props['searchLabel'],
        $props['disabled'],
        $props['factory'],
        $props['extraOptions'],
    );

    $attributes = collect($props)
        ->map(fn (mixed $attributeValue, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $attributeValue, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf(
            '<x-lyra::combobox :options="$options" :value="$value" :default-open="$defaultOpen" :label="$label" :hint="$hint" :error="$error" :placeholder="$placeholder" :search-placeholder="$searchPlaceholder" :empty-message="$emptyMessage" :search-label="$searchLabel" :disabled="$disabled" :factory="$factory" :extra-options="$extraOptions" %s>%s</x-lyra::combobox>',
            $attributes,
            $slots,
        ),
        compact(
            'options',
            'value',
            'defaultOpen',
            'label',
            'hint',
            'error',
            'placeholder',
            'searchPlaceholder',
            'emptyMessage',
            'searchLabel',
            'disabled',
            'factory',
            'extraOptions',
        ),
    );
}

function comboboxOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<div\b(?=[^>]*\bx-modelable="value")[^>]*>/',
        'combobox' => '/<div\b(?=[^>]*\bclass="lyra-combobox")[^>]*>/',
        'trigger' => '/<button\b(?=[^>]*\bclass="lyra-input lyra-combobox__trigger")[^>]*>/',
        'pop' => '/<div\b(?=[^>]*\bclass="lyra-combobox__pop")[^>]*>/',
        'search' => '/<div\b(?=[^>]*\bclass="lyra-combobox__search")[^>]*>/',
        'search-input' => '/<input\b(?=[^>]*\bx-bind="search")[^>]*>/',
        'list' => '/<div\b(?=[^>]*\bclass="lyra-combobox__list")[^>]*>/',
        'empty' => '/<span\b(?=[^>]*\bclass="lyra-combobox__empty")[^>]*>/',
        'group' => '/<span\b(?=[^>]*\bclass="lyra-combobox__group")[^>]*>/',
        'option' => '/<button\b(?=[^>]*\bclass="lyra-combobox__option")[^>]*>/',
        'option-label' => '/<span\b(?=[^>]*\bclass="lyra-combobox__option-label")[^>]*>/',
        'option-hint' => '/<span\b(?=[^>]*\bclass="lyra-combobox__option-hint")[^>]*>/',
        'trailing' => '/<span\b(?=[^>]*\bclass="lyra-combobox__trailing")[^>]*>/',
        'label' => '/<label\b(?=[^>]*\bclass="lyra-label")[^>]*>/',
        'message' => '/<span\b(?=[^>]*\bclass="lyra-hint(?: lyra-hint--error)?")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function comboboxAttribute(string $tag, string $attribute): ?string
{
    $matched = preg_match(
        sprintf('/(?:^|\s)%s="([^"]*)"/', preg_quote($attribute, '/')),
        $tag,
        $matches,
    );

    return $matched === 1
        ? html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')
        : null;
}

function comboboxClass(string $html, string $target): string
{
    return comboboxAttribute(comboboxOpeningTag($html, $target), 'class') ?? '';
}

/** @return array<string, mixed> */
function comboboxOptions(string $html, string $factory = 'lyraCombobox'): array
{
    $expression = comboboxAttribute(comboboxOpeningTag($html, 'root'), 'x-data');

    expect($expression)->not->toBeNull()
        ->and($expression)->toStartWith($factory.'(')->toEndWith(')');

    return json_decode(
        substr($expression, strlen($factory.'('), -1),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

dataset('combobox class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/combobox.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the combobox class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact static React class strings without duplicating classes owned by the binding', function (array $case): void {
    $html = renderCombobox($case['props']);
    $tag = comboboxOpeningTag($html, $case['target']);
    preg_match_all('/(?:^|\s)class="/', $tag, $classAttributes);

    expect(comboboxClass($html, $case['target']))->toBe($case['expected_class'])
        ->and($classAttributes[0])->toHaveCount($case['expected_class'] === '' ? 0 : 1);
})->with('combobox class emission');

it('serializes the complete binding contract as valid JSON and exposes value as modelable', function (): void {
    $options = [
        [
            'value' => 'br',
            'label' => 'Brazil',
            'hint' => 'South America',
            'group' => 'Americas',
            'keywords' => 'brasil brazil',
        ],
    ];
    $html = renderCombobox([
        'id' => 'country',
        'options' => $options,
        'value' => 'br',
        'defaultOpen' => true,
        'placeholder' => 'Choose a country',
        'searchPlaceholder' => 'Filter countries',
        'emptyMessage' => 'Nothing found',
        'disabled' => true,
        'error' => 'Country is required',
    ]);
    $messageId = comboboxAttribute(comboboxOpeningTag($html, 'message'), 'id');
    $root = comboboxOpeningTag($html, 'root');

    expect(comboboxOptions($html))->toBe([
        'options' => $options,
        'value' => 'br',
        'open' => true,
        'placeholder' => 'Choose a country',
        'searchPlaceholder' => 'Filter countries',
        'emptyMessage' => 'Nothing found',
        'disabled' => true,
        'id' => 'country',
        'error' => true,
        'describedBy' => $messageId,
    ])->and($root)->toContain('x-modelable="value"');
});

it('uses lyraCombobox as the default Alpine factory', function (): void {
    $html = renderCombobox(['id' => 'country']);
    $expression = comboboxAttribute(comboboxOpeningTag($html, 'root'), 'x-data');

    expect($expression)->toStartWith('lyraCombobox(')
        ->and(substr_count($html, 'x-data='))->toBe(1);
});

it('uses the time zone factory without changing any markup outside x-data', function (): void {
    $slots = <<<'BLADE'
        <x-slot:optionIcon><span data-slot="option-icon" x-text="option.value"></span></x-slot:optionIcon>
        <x-slot:optionTrailing><span data-slot="option-trailing" x-text="option.trailing"></span></x-slot:optionTrailing>
    BLADE;
    $props = [
        'id' => 'time-zone',
        'label' => 'Time zone',
        'searchLabel' => 'Search time zones',
    ];
    $default = renderCombobox($props, $slots);
    $specialized = renderCombobox([
        ...$props,
        'factory' => 'lyraTimeZonePicker',
    ], $slots);
    $expression = comboboxAttribute(comboboxOpeningTag($specialized, 'root'), 'x-data');
    $normalizeFactory = static fn (string $html): string => preg_replace(
        '/x-data="[^"]*"/',
        'x-data="[factory]"',
        $html,
    ) ?? '';

    expect($expression)->toStartWith('lyraTimeZonePicker(')
        ->and(comboboxOptions($specialized, 'lyraTimeZonePicker'))
        ->toBe(comboboxOptions($default))
        ->and($normalizeFactory($specialized))->toBe($normalizeFactory($default));
});

it('falls back safely when the requested factory is not whitelisted', function (string $factory): void {
    $html = renderCombobox([
        'id' => 'country',
        'factory' => $factory,
    ]);
    $expression = comboboxAttribute(comboboxOpeningTag($html, 'root'), 'x-data');

    expect($expression)->toStartWith('lyraCombobox(')
        ->and($html)->not->toContain($factory)
        ->and(substr_count($html, 'x-data='))->toBe(1);
})->with([
    'call expression' => 'alert(1)',
    'expression breakout' => 'lyraCombobox); window.pwned=1; //',
]);

it('merges specialized options while component-owned binding keys take precedence', function (): void {
    $componentOptions = [['value' => 'br', 'label' => 'Brazil']];
    $zones = [['value' => 'America/Sao_Paulo', 'label' => 'São Paulo', 'region' => 'Americas']];
    $html = renderCombobox([
        'id' => 'time-zone',
        'factory' => 'lyraTimeZonePicker',
        'extraOptions' => [
            'zones' => $zones,
            'recentZones' => ['Europe/London'],
            'detectedZone' => 'America/Sao_Paulo',
            'referenceDate' => '2026-08-09',
            'locale' => 'pt-BR',
            'labels' => ['recentGroup' => 'Recentes'],
            'options' => [['value' => 'hijacked', 'label' => 'Hijacked']],
            'value' => 'hijacked',
            'open' => true,
            'placeholder' => 'Hijacked',
            'searchPlaceholder' => 'Hijacked',
            'emptyMessage' => 'Hijacked',
            'disabled' => true,
            'id' => 'hijacked',
            'error' => true,
            'describedBy' => 'hijacked',
        ],
        'options' => $componentOptions,
        'value' => 'br',
        'defaultOpen' => false,
        'placeholder' => 'Choose a zone',
        'searchPlaceholder' => 'Search zones',
        'emptyMessage' => 'No zones',
        'disabled' => false,
        'hint' => 'Choose one',
    ]);
    $messageId = comboboxAttribute(comboboxOpeningTag($html, 'message'), 'id');

    expect(comboboxOptions($html, 'lyraTimeZonePicker'))->toBe([
        'zones' => $zones,
        'recentZones' => ['Europe/London'],
        'detectedZone' => 'America/Sao_Paulo',
        'referenceDate' => '2026-08-09',
        'locale' => 'pt-BR',
        'labels' => ['recentGroup' => 'Recentes'],
        'options' => $componentOptions,
        'value' => 'br',
        'open' => false,
        'placeholder' => 'Choose a zone',
        'searchPlaceholder' => 'Search zones',
        'emptyMessage' => 'No zones',
        'disabled' => false,
        'id' => 'time-zone',
        'error' => false,
        'describedBy' => $messageId,
    ]);
});

it('keeps hostile specialized options inside the encoded JSON literal', function (): void {
    $payload = "'); window.pwned=1; //\"\\</script>";
    $extraOptions = [
        'zones' => [[
            'value' => $payload,
            'label' => $payload,
            'region' => $payload,
            'keywords' => $payload,
        ]],
        'recentZones' => [$payload],
        'detectedZone' => $payload,
        'referenceDate' => $payload,
        'locale' => $payload,
        'labels' => ['placeholder' => $payload],
    ];
    $html = renderCombobox([
        'factory' => 'lyraTimeZonePicker',
        'extraOptions' => $extraOptions,
    ]);
    $root = comboboxOpeningTag($html, 'root');
    $serialized = comboboxOptions($html, 'lyraTimeZonePicker');

    expect(array_intersect_key($serialized, $extraOptions))->toBe($extraOptions)
        ->and($root)->toContain('\\u003C/script\\u003E')
        ->and($root)->not->toContain($payload)
        ->and($html)->not->toContain('</script>');
});

it('always serves exactly one x-data attribute', function (): void {
    $combinations = [
        [],
        ['factory' => 'lyraTimeZonePicker'],
        ['factory' => 'alert(1)'],
        ['extraOptions' => ['locale' => 'pt-BR']],
        [
            'factory' => 'lyraTimeZonePicker',
            'extraOptions' => ['locale' => 'pt-BR'],
            'x-data' => 'alert(1)',
        ],
    ];

    foreach ($combinations as $props) {
        $html = renderCombobox($props);

        expect(substr_count($html, 'x-data='))->toBe(1);
    }
});

it('leaves optional text and value keys to binding defaults', function (): void {
    $options = comboboxOptions(renderCombobox(['id' => 'country']));

    expect($options)->toBe([
        'options' => [],
        'open' => false,
        'disabled' => false,
        'id' => 'country',
        'error' => false,
    ])->not->toHaveKeys([
        'value',
        'placeholder',
        'searchPlaceholder',
        'emptyMessage',
        'describedBy',
    ]);
});

it('guards malformed option collections and only exposes the Alpine option interface', function (): void {
    $malformedCollection = comboboxOptions(renderCombobox([
        'id' => 'country',
        'options' => 'not-an-array',
    ]));
    $mixedCollection = comboboxOptions(renderCombobox([
        'id' => 'country',
        'options' => [
            ['value' => 'br', 'label' => 'Brazil', 'icon' => 'invented'],
            ['value' => 'ca', 'label' => 'Canada', 'keywords' => 123],
            ['value' => 7, 'label' => 'Invalid value'],
            ['value' => 'missing-label'],
            'invalid option',
        ],
    ]));

    expect($malformedCollection['options'])->toBe([])
        ->and($mixedCollection['options'])->toBe([
            ['value' => 'br', 'label' => 'Brazil'],
            ['value' => 'ca', 'label' => 'Canada'],
        ]);
});

it('serves a stable trigger id and wires the label before Alpine boots', function (): void {
    $generated = renderCombobox(['label' => 'Country']);
    $generatedId = comboboxAttribute(comboboxOpeningTag($generated, 'trigger'), 'id');
    $explicit = renderCombobox(['id' => 'country', 'label' => 'Country']);

    expect($generatedId)->toMatch('/^lyra-combobox-/')
        ->and(comboboxOpeningTag($generated, 'label'))->toContain('for="'.$generatedId.'"')
        ->and(comboboxAttribute(comboboxOpeningTag($explicit, 'trigger'), 'id'))->toBe('country')
        ->and(comboboxOpeningTag($explicit, 'label'))->toContain('for="country"')
        ->and(comboboxOptions($explicit)['id'])->toBe('country');
});

it('renders the trigger and popup search skeleton with the exact binding hooks', function (): void {
    $html = renderCombobox(['searchLabel' => 'Search countries']);
    $trigger = comboboxOpeningTag($html, 'trigger');
    $pop = comboboxOpeningTag($html, 'pop');
    $searchInput = comboboxOpeningTag($html, 'search-input');
    $list = comboboxOpeningTag($html, 'list');
    $empty = comboboxOpeningTag($html, 'empty');

    expect($trigger)->toContain('type="button"')
        ->and($trigger)->toContain('class="lyra-input lyra-combobox__trigger"')
        ->and($trigger)->toContain('x-bind="trigger"')
        ->and($html)->toMatch('/<span\s+x-bind="triggerValue"\s*><\/span>/')
        ->and($pop)->toContain('class="lyra-combobox__pop"')
        ->and($pop)->toContain('x-bind="pop"')
        ->and(comboboxOpeningTag($html, 'search'))->toContain('class="lyra-combobox__search"')
        ->and($searchInput)->toContain('x-bind="search"')
        ->and(comboboxAttribute($searchInput, 'aria-label'))->toBe('Search countries')
        ->and($list)->toContain('class="lyra-combobox__list"')
        ->and($list)->toContain('x-bind="list"')
        ->and($empty)->toContain('class="lyra-combobox__empty"')
        ->and($empty)->toContain('x-bind="empty"')
        ->and($empty)->toContain('x-text="emptyMessage"');
});

it('renders the canonical data driven option and group bindings', function (): void {
    $html = renderCombobox();
    $group = comboboxOpeningTag($html, 'group');
    $option = comboboxOpeningTag($html, 'option');
    $hint = comboboxOpeningTag($html, 'option-hint');

    expect($html)->toContain('<template x-for="({ option, index }, filteredIndex) in filtered()" :key="option.value">')
        ->and($html)->toContain('<template x-if="showGroup(filteredIndex)">')
        ->and($group)->toContain('role="presentation"')
        ->and($group)->toContain('x-text="option.group"')
        ->and($option)->toContain('class="lyra-combobox__option"')
        ->and($option)->toContain('type="button"')
        ->and($option)->toContain('tabindex="-1"')
        ->and($option)->toContain('role="option"')
        ->and($option)->toContain(':id="optionId(index)"')
        ->and($option)->toContain(':class="optionClass(filteredIndex)"')
        ->and($option)->toContain(':aria-selected="optionSelected(option)"')
        ->and($option)->toContain('@mouseenter="setActive(filteredIndex)"')
        ->and($option)->toContain('@click="pick(option)"')
        ->and(comboboxOpeningTag($html, 'option-label'))->toContain('class="lyra-combobox__option-label"')
        ->and($html)->toContain('<span x-text="option.label"></span>')
        ->and($hint)->toContain('x-show="option.hint"')
        ->and($hint)->toContain('x-text="option.hint"');
});

it('renders named option slots in their Alpine option scope and positions', function (): void {
    $html = renderCombobox(
        slots: <<<'BLADE'
            <x-slot:optionIcon><span data-slot="option-icon" x-text="option.value"></span></x-slot:optionIcon>
            <x-slot:optionTrailing><span data-slot="option-trailing" x-text="filteredIndex"></span></x-slot:optionTrailing>
        BLADE,
    );
    $optionStart = strpos($html, comboboxOpeningTag($html, 'option'));
    $icon = strpos($html, 'data-slot="option-icon"', $optionStart);
    $label = strpos($html, 'class="lyra-combobox__option-label"', $optionStart);
    $trailing = strpos($html, 'class="lyra-combobox__trailing"', $optionStart);
    $trailingSlot = strpos($html, 'data-slot="option-trailing"', $optionStart);

    expect($optionStart)->toBeInt()
        ->and($icon)->toBeInt()->toBeGreaterThan($optionStart)->toBeLessThan($label)
        ->and($trailing)->toBeInt()->toBeGreaterThan($label)->toBeLessThan($trailingSlot)
        ->and($html)->toContain('x-text="option.value"')
        ->and($html)->toContain('x-text="filteredIndex"');
});

it('prioritizes error over hint and derives describedBy from the rendered message', function (): void {
    $error = renderCombobox([
        'hint' => 'Choose one',
        'error' => 'Country is required',
    ]);
    $hint = renderCombobox(['hint' => 'Choose one']);
    $plain = renderCombobox();
    $errorMessageId = comboboxAttribute(comboboxOpeningTag($error, 'message'), 'id');
    $hintMessageId = comboboxAttribute(comboboxOpeningTag($hint, 'message'), 'id');

    expect(comboboxClass($error, 'root'))->toBe('lyra-field')
        ->and(comboboxClass($error, 'message'))->toBe('lyra-hint lyra-hint--error')
        ->and($error)->toContain('>Country is required</span>')
        ->and($error)->not->toContain('Choose one')
        ->and(comboboxOptions($error)['error'])->toBeTrue()
        ->and(comboboxOptions($error)['describedBy'])->toBe($errorMessageId)
        ->and(comboboxClass($hint, 'message'))->toBe('lyra-hint')
        ->and($hint)->toContain('>Choose one</span>')
        ->and(comboboxOptions($hint)['error'])->toBeFalse()
        ->and(comboboxOptions($hint)['describedBy'])->toBe($hintMessageId)
        ->and(comboboxClass($plain, 'root'))->toBe('')
        ->and($plain)->not->toContain('class="lyra-hint')
        ->and(comboboxOptions($plain))->not->toHaveKey('describedBy');
});

it('treats zero strings as present field content', function (): void {
    $label = renderCombobox(['label' => '0']);
    $hint = renderCombobox(['hint' => '0']);
    $error = renderCombobox(['error' => '0']);

    expect(comboboxClass($label, 'root'))->toBe('lyra-field')
        ->and($label)->toContain('>0</label>')
        ->and(comboboxClass($hint, 'root'))->toBe('lyra-field')
        ->and($hint)->toContain('>0</span>')
        ->and(comboboxClass($error, 'root'))->toBe('lyra-field')
        ->and($error)->toContain('>0</span>')
        ->and(comboboxOptions($error)['error'])->toBeTrue();
});

it('passes disabled state to the binding', function (): void {
    expect(comboboxOptions(renderCombobox(['disabled' => true]))['disabled'])->toBeTrue()
        ->and(comboboxOptions(renderCombobox(['disabled' => false]))['disabled'])->toBeFalse();
});

it('keeps hostile data inside JSON and escapes the consumer search label attribute', function (): void {
    $payload = "'); window.pwned=1; //\"\\</script>";
    $options = [[
        'value' => $payload,
        'label' => $payload,
        'hint' => $payload,
        'group' => $payload,
        'keywords' => $payload,
    ]];
    $html = renderCombobox([
        'options' => $options,
        'value' => $payload,
        'placeholder' => $payload,
        'searchPlaceholder' => $payload,
        'emptyMessage' => $payload,
        'searchLabel' => $payload,
    ]);
    $root = comboboxOpeningTag($html, 'root');
    $serialized = comboboxOptions($html);

    expect($serialized['options'])->toBe($options)
        ->and($serialized['value'])->toBe($payload)
        ->and($serialized['placeholder'])->toBe($payload)
        ->and($serialized['searchPlaceholder'])->toBe($payload)
        ->and($serialized['emptyMessage'])->toBe($payload)
        ->and(substr_count($root, 'x-data='))->toBe(1)
        ->and($root)->toContain('\\u003C/script\\u003E')
        ->and($root)->not->toContain("'); window.pwned=1; //")
        ->and($html)->not->toContain('</script>')
        ->and(comboboxAttribute(comboboxOpeningTag($html, 'search-input'), 'aria-label'))->toBe($payload);
});

it('splits model attributes onto the modelable root and preserves native attributes', function (): void {
    $html = renderCombobox([
        'id' => 'country',
        'label' => 'Country',
        'class' => 'lyra-field wide',
        'wire:model.live' => 'country',
        'x-model.fill' => 'selectedCountry',
        'data-track' => 'country-picker',
        'aria-label' => 'Country picker',
    ]);
    $root = comboboxOpeningTag($html, 'root');

    expect(comboboxClass($html, 'root'))->toBe('lyra-field wide')
        ->and($root)->toContain('wire:model.live="country"')
        ->and($root)->toContain('x-model.fill="selectedCountry"')
        ->and($root)->toContain('data-track="country-picker"')
        ->and($root)->toContain('aria-label="Country picker"')
        ->and($root)->not->toContain('id="country"')
        ->and(comboboxAttribute(comboboxOpeningTag($html, 'trigger'), 'id'))->toBe('country')
        ->and(strpos($root, 'x-modelable="value"'))->toBeLessThan(strpos($root, 'wire:model.live="country"'));
});

it('renders namespaced and short syntax identically', function (): void {
    $namespaced = Blade::render('<x-lyra::combobox id="country" label="Country" search-label="Search countries" />');
    $short = Blade::render('<lyra:combobox id="country" label="Country" search-label="Search countries" />');

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('lyraCombobox(');
});
