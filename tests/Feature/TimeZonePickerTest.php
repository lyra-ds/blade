<?php

use Illuminate\Support\Facades\Blade;

function renderTimeZonePicker(array $props = [], bool $short = false): string
{
    $value = $props['value'] ?? null;
    $zones = $props['zones'] ?? null;
    $recentZones = $props['recentZones'] ?? [];
    $detectedZone = $props['detectedZone'] ?? null;
    $referenceDate = $props['referenceDate'] ?? null;
    $label = $props['label'] ?? null;
    $hint = $props['hint'] ?? null;
    $error = $props['error'] ?? null;
    $placeholder = $props['placeholder'] ?? null;
    $locale = $props['locale'] ?? 'en-US';
    $labels = $props['labels'] ?? [];
    $disabled = $props['disabled'] ?? false;
    unset(
        $props['value'],
        $props['zones'],
        $props['recentZones'],
        $props['detectedZone'],
        $props['referenceDate'],
        $props['label'],
        $props['hint'],
        $props['error'],
        $props['placeholder'],
        $props['locale'],
        $props['labels'],
        $props['disabled'],
    );

    $attributes = collect($props)
        ->map(fn (mixed $attributeValue, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $attributeValue, ENT_QUOTES),
        ))
        ->implode(' ');
    $component = $short ? 'lyra:time-zone-picker' : 'x-lyra::time-zone-picker';

    return Blade::render(
        sprintf(
            '<%1$s :value="$value" :zones="$zones" :recent-zones="$recentZones" :detected-zone="$detectedZone" :reference-date="$referenceDate" :label="$label" :hint="$hint" :error="$error" :placeholder="$placeholder" :locale="$locale" :labels="$labels" :disabled="$disabled" %2$s />',
            $component,
            $attributes,
        ),
        compact(
            'value',
            'zones',
            'recentZones',
            'detectedZone',
            'referenceDate',
            'label',
            'hint',
            'error',
            'placeholder',
            'locale',
            'labels',
            'disabled',
        ),
    );
}

function timeZonePickerOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<div\b(?=[^>]*\bx-modelable="value")[^>]*>/',
        'field' => '/<div\b(?=[^>]*\bclass="lyra-field")[^>]*>/',
        'trigger' => '/<button\b(?=[^>]*\bclass="lyra-input lyra-combobox__trigger(?: lyra-input--error)?")[^>]*>/',
        'search' => '/<input\b(?=[^>]*\bx-bind="search")[^>]*>/',
        'message' => '/<span\b(?=[^>]*\bclass="lyra-hint(?: lyra-hint--error)?")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function timeZonePickerAttribute(string $tag, string $attribute): ?string
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

/** @return array<string, mixed> */
function timeZonePickerOptions(string $html): array
{
    $expression = timeZonePickerAttribute(timeZonePickerOpeningTag($html, 'root'), 'x-data');

    expect($expression)->not->toBeNull()
        ->and($expression)->toStartWith('lyraTimeZonePicker(')->toEndWith(')');

    return json_decode(
        substr($expression, strlen('lyraTimeZonePicker('), -1),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

dataset('time zone picker class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/time-zone-picker.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the time-zone-picker class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React root class string without duplicates', function (array $case): void {
    $root = timeZonePickerOpeningTag(renderTimeZonePicker($case['props']), 'root');
    $class = timeZonePickerAttribute($root, 'class');

    expect($class)->toBe($case['expected_class'])
        ->and(substr_count($root, 'class='))->toBe(1)
        ->and(array_values(array_unique(explode(' ', $class))))->toBe(explode(' ', $class));
})->with('time zone picker class emission');

it('uses the time zone factory and serves exactly one Alpine scope', function (): void {
    $html = renderTimeZonePicker(['id' => 'zone']);
    $expression = timeZonePickerAttribute(timeZonePickerOpeningTag($html, 'root'), 'x-data');
    $deduplicated = timeZonePickerAttribute(
        timeZonePickerOpeningTag(renderTimeZonePicker(['class' => 'lyra-tzpicker consumer']), 'root'),
        'class',
    );

    expect($expression)->toStartWith('lyraTimeZonePicker(')
        ->and($expression)->not->toStartWith('lyraCombobox(')
        ->and(substr_count($html, 'x-data='))->toBe(1)
        ->and($deduplicated)->toBe('lyra-combobox lyra-tzpicker consumer');
});

it('puts the time zone and combobox classes on the same bound control, never the field wrapper', function (): void {
    $html = renderTimeZonePicker([
        'id' => 'zone',
        'label' => 'Time zone',
        'hint' => 'Choose one',
        'class' => 'consumer',
    ]);
    $root = timeZonePickerOpeningTag($html, 'root');
    $field = timeZonePickerOpeningTag($html, 'field');

    expect(timeZonePickerAttribute($root, 'class'))->toBe('lyra-combobox lyra-tzpicker consumer')
        ->and($root)->toContain('x-data="lyraTimeZonePicker(')
        ->and($root)->toContain('x-modelable="value"')
        ->and(timeZonePickerAttribute($field, 'class'))->toBe('lyra-field')
        ->and($field)->not->toContain('lyra-tzpicker')
        ->and($field)->not->toContain('x-data=');
});

it('serializes the complete time zone binding input as valid JSON', function (): void {
    $zones = [[
        'value' => 'America/Sao_Paulo',
        'label' => 'São Paulo',
        'region' => 'Americas',
        'keywords' => 'brasil brt',
    ]];
    $labels = [
        'placeholder' => 'Escolha o fuso',
        'searchPlaceholder' => 'Busque uma cidade',
        'emptyMessage' => 'Nenhum fuso encontrado.',
        'detectedGroup' => 'Detectado',
        'recentGroup' => 'Recentes',
    ];
    $options = timeZonePickerOptions(renderTimeZonePicker([
        'id' => 'zone',
        'value' => 'America/Sao_Paulo',
        'zones' => $zones,
        'recentZones' => ['Europe/London'],
        'detectedZone' => 'America/Sao_Paulo',
        'referenceDate' => '2026-08-09',
        'locale' => 'pt-BR',
        'labels' => $labels,
        'disabled' => true,
    ]));

    expect($options)->toMatchArray([
        'zones' => $zones,
        'recentZones' => ['Europe/London'],
        'detectedZone' => 'America/Sao_Paulo',
        'referenceDate' => '2026-08-09',
        'locale' => 'pt-BR',
        'labels' => $labels,
        'value' => 'America/Sao_Paulo',
        'disabled' => true,
    ]);
});

it('leaves the curated zone list to the Alpine binding', function (): void {
    expect(timeZonePickerOptions(renderTimeZonePicker()))
        ->not->toHaveKey('zones')
        ->toHaveKey('recentZones', []);
});

it('serves the English time zone labels in their binding and accessible positions', function (): void {
    $html = renderTimeZonePicker();
    $options = timeZonePickerOptions($html);

    expect($options['placeholder'])->toBe('Select time zone')
        ->and($options['searchPlaceholder'])->toBe('Search city, country, or abbreviation…')
        ->and($options['emptyMessage'])->toBe('No time zones found.')
        ->and($options['labels'])->toMatchArray([
            'placeholder' => 'Select time zone',
            'searchPlaceholder' => 'Search city, country, or abbreviation…',
            'emptyMessage' => 'No time zones found.',
            'detectedGroup' => 'Detected',
            'recentGroup' => 'Recent',
        ])
        ->and(timeZonePickerOpeningTag($html, 'search'))
        ->toContain('aria-label="Search city, country, or abbreviation…"');
});

it('merges translated labels and lets an explicit placeholder win', function (): void {
    $labels = [
        'placeholder' => 'Escolha um fuso',
        'searchPlaceholder' => 'Buscar fusos',
        'emptyMessage' => 'Nada encontrado.',
        'detectedGroup' => 'Detectado',
        'recentGroup' => 'Recentes',
    ];
    $html = renderTimeZonePicker([
        'labels' => $labels,
        'placeholder' => 'Fuso da conta',
    ]);
    $options = timeZonePickerOptions($html);

    expect($options['labels'])->toBe($labels)
        ->and($options['placeholder'])->toBe('Fuso da conta')
        ->and($options['searchPlaceholder'])->toBe('Buscar fusos')
        ->and($options['emptyMessage'])->toBe('Nada encontrado.')
        ->and(timeZonePickerOpeningTag($html, 'search'))->toContain('aria-label="Buscar fusos"');
});

it('renders the binding-provided live local time in the trailing slot', function (): void {
    $html = renderTimeZonePicker();

    expect($html)->toMatch('/<span class="lyra-combobox__trailing">\s*<span x-show="option\.trailing" x-text="option\.trailing"><\/span>\s*<\/span>/s');
});

it('renders an error instead of the hint and links it through the trigger binding', function (): void {
    $html = renderTimeZonePicker([
        'id' => 'zone',
        'hint' => 'Choose one',
        'error' => 'Required',
    ]);
    $message = timeZonePickerOpeningTag($html, 'message');
    $messageId = timeZonePickerAttribute($message, 'id');
    $options = timeZonePickerOptions($html);

    expect($message)->toContain('class="lyra-hint lyra-hint--error"')
        ->and($html)->toContain('>Required</span>')
        ->and($html)->not->toContain('>Choose one</span>')
        ->and(timeZonePickerOpeningTag($html, 'trigger'))->toContain('x-bind="trigger"')
        ->and($options['error'])->toBeTrue()
        ->and($options['describedBy'])->toBe($messageId);
});

it('treats a zero string label as present', function (): void {
    $html = renderTimeZonePicker(['label' => '0', 'id' => 'zone']);

    expect(timeZonePickerAttribute(timeZonePickerOpeningTag($html, 'root'), 'class'))
        ->toBe('lyra-combobox lyra-tzpicker')
        ->and($html)->toContain('<label id="zone-label" class="lyra-label" for="zone">0</label>');
});

it('keeps hostile values inside the encoded JSON literal', function (): void {
    $payload = "'); window.pwned=1; //\"\\</script>";
    $zones = [[
        'value' => $payload,
        'label' => $payload,
        'region' => $payload,
        'keywords' => $payload,
    ]];
    $labels = [
        'placeholder' => $payload,
        'searchPlaceholder' => $payload,
        'emptyMessage' => $payload,
        'detectedGroup' => $payload,
        'recentGroup' => $payload,
    ];
    $html = renderTimeZonePicker([
        'zones' => $zones,
        'recentZones' => [$payload],
        'detectedZone' => $payload,
        'referenceDate' => $payload,
        'placeholder' => $payload,
        'labels' => $labels,
    ]);
    $root = timeZonePickerOpeningTag($html, 'root');
    $options = timeZonePickerOptions($html);

    expect($options)->toMatchArray([
        'zones' => $zones,
        'recentZones' => [$payload],
        'detectedZone' => $payload,
        'referenceDate' => $payload,
        'placeholder' => $payload,
        'labels' => $labels,
    ])->and($root)->toContain('\\u003C/script\\u003E')
        ->and($root)->not->toContain($payload)
        ->and($html)->not->toContain('</script>');
});

it('renders the short and namespaced syntaxes equivalently', function (): void {
    $props = [
        'id' => 'zone',
        'value' => 'Europe/Lisbon',
        'label' => 'Time zone',
        'class' => 'consumer',
    ];

    expect(renderTimeZonePicker($props, true))->toBe(renderTimeZonePicker($props));
});
