<?php

use Illuminate\Support\Facades\Blade;
use Livewire\Component;
use Livewire\Livewire;

function renderDateRangePicker(array $props = []): string
{
    $label = $props['label'] ?? null;
    $hint = $props['hint'] ?? null;
    $error = $props['error'] ?? null;
    $defaultValue = $props['defaultValue'] ?? null;
    $placeholder = $props['placeholder'] ?? null;
    $min = $props['min'] ?? null;
    $max = $props['max'] ?? null;
    $locale = $props['locale'] ?? 'en-US';
    $labels = $props['labels'] ?? [];
    $disabled = $props['disabled'] ?? false;
    $name = $props['name'] ?? null;
    unset(
        $props['label'],
        $props['hint'],
        $props['error'],
        $props['defaultValue'],
        $props['placeholder'],
        $props['min'],
        $props['max'],
        $props['locale'],
        $props['labels'],
        $props['disabled'],
        $props['name'],
    );

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf(
            '<x-lyra::date-range-picker :label="$label" :hint="$hint" :error="$error" :default-value="$defaultValue" :placeholder="$placeholder" :min="$min" :max="$max" :locale="$locale" :labels="$labels" :disabled="$disabled" :name="$name" %s />',
            $attributes,
        ),
        compact(
            'label',
            'hint',
            'error',
            'defaultValue',
            'placeholder',
            'min',
            'max',
            'locale',
            'labels',
            'disabled',
            'name',
        ),
    );
}

function dateRangePickerOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<div\b(?=[^>]*\bx-data="lyraDateRangePicker\()[^>]*>/',
        'trigger' => '/<button\b(?=[^>]*\bclass="lyra-input lyra-datepicker__btn(?: lyra-input--error)?")[^>]*>/',
        'label' => '/<label\b(?=[^>]*\bclass="lyra-label")[^>]*>/',
        'hint' => '/<span\b(?=[^>]*\bclass="lyra-hint(?: lyra-hint--error)?")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

/** @return array<int, string> */
function dateRangePickerTriggerTags(string $html): array
{
    preg_match_all(
        '/<button\b(?=[^>]*\bclass="lyra-input lyra-datepicker__btn(?: lyra-input--error)?")[^>]*>/',
        $html,
        $matches,
    );

    return $matches[0];
}

/** @return array<int, string> */
function dateRangePickerHiddenTags(string $html): array
{
    preg_match_all('/<input\b(?=[^>]*\btype="hidden")[^>]*>/', $html, $matches);

    return $matches[0];
}

function dateRangePickerRootClass(string $html): string
{
    $root = dateRangePickerOpeningTag($html, 'root');
    $matched = preg_match('/\bclass="([^"]*)"/', $root, $matches);

    return $matched === 1 ? $matches[1] : '';
}

function dateRangePickerDesktopBranch(string $html): string
{
    $desktop = strpos($html, '<template x-if="!mobile">');
    $mobile = strpos($html, '<template x-if="mobile">');

    expect($desktop)->toBeInt()
        ->and($mobile)->toBeInt()
        ->and($desktop)->toBeLessThan($mobile);

    return substr($html, $desktop, $mobile - $desktop);
}

function dateRangePickerAttribute(string $tag, string $attribute): ?string
{
    $matched = preg_match(
        sprintf('/\b%s="([^"]*)"/', preg_quote($attribute, '/')),
        $tag,
        $matches,
    );

    return $matched === 1 ? $matches[1] : null;
}

/** @return array<string, mixed> */
function dateRangePickerOptions(string $html): array
{
    $root = dateRangePickerOpeningTag($html, 'root');
    $expression = html_entity_decode(
        (string) dateRangePickerAttribute($root, 'x-data'),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8',
    );

    expect($expression)->toStartWith('lyraDateRangePicker(')->toEndWith(')');

    return json_decode(
        substr($expression, strlen('lyraDateRangePicker('), -1),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

/** @return array<int, array<string, mixed>> */
function dateRangePickerCalendarOptions(string $html): array
{
    preg_match_all('/<div\b(?=[^>]*\bx-data="lyraCalendar\()[^>]*>/', $html, $matches);

    return array_map(function (string $tag): array {
        $expression = html_entity_decode(
            (string) dateRangePickerAttribute($tag, 'x-data'),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );

        expect($expression)->toStartWith('lyraCalendar(')->toEndWith(')');

        return json_decode(
            substr($expression, strlen('lyraCalendar('), -1),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }, $matches[0]);
}

dataset('date range picker class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/date-range-picker.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the date-range-picker class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React root class string', function (array $case): void {
    $html = renderDateRangePicker($case['props']);

    expect(dateRangePickerRootClass($html))->toBe($case['expected_class'])
        ->and(substr_count(dateRangePickerOpeningTag($html, 'root'), 'class='))->toBeLessThanOrEqual(1);
})->with('date range picker class emission');

it('serializes the coordinator range options as valid JSON and exposes selected as modelable', function (): void {
    $html = renderDateRangePicker([
        'defaultValue' => ['start' => '2026-08-09', 'end' => '2026-08-12'],
        'locale' => 'pt-BR',
        'labels' => [
            'placeholder' => 'Escolha um período',
            'rangeSeparator' => ' até ',
            'incompleteRange' => 'pendente',
        ],
    ]);
    $root = dateRangePickerOpeningTag($html, 'root');

    expect(dateRangePickerOptions($html))->toBe([
        'defaultValue' => ['start' => '2026-08-09', 'end' => '2026-08-12'],
        'locale' => 'pt-BR',
        'placeholder' => 'Escolha um período',
        'rangeSeparator' => ' até ',
        'incompleteRange' => 'pendente',
    ])->and($root)->toContain('x-modelable="selected"');
});

it('uses the exact range label defaults and lets labels override them', function (): void {
    $defaults = renderDateRangePicker();
    $calendarLabels = ['today' => 'Agora'];
    $translated = renderDateRangePicker([
        'labels' => [
            'placeholder' => 'Escolha o período',
            'popover' => 'Seletor de período',
            'sheetTitle' => 'Selecione o período',
            'close' => 'Fechar agora',
            'rangeSeparator' => ' até ',
            'incompleteRange' => 'incompleto',
            'calendar' => $calendarLabels,
        ],
    ]);

    expect(dateRangePickerOptions($defaults))->toBe([
        'locale' => 'en-US',
        'placeholder' => 'Select period',
        'rangeSeparator' => ' – ',
        'incompleteRange' => '…',
    ])->and($defaults)->toContain('aria-label="Date range picker"')
        ->and($defaults)->toContain('>Select period</h2>')
        ->and($defaults)->toContain('aria-label="Close"')
        ->and(dateRangePickerOptions($translated))->toMatchArray([
            'placeholder' => 'Escolha o período',
            'rangeSeparator' => ' até ',
            'incompleteRange' => 'incompleto',
        ])->and($translated)->toContain('aria-label="Seletor de período"')
        ->and($translated)->toContain('>Selecione o período</h2>')
        ->and($translated)->toContain('aria-label="Fechar agora"')
        ->and(dateRangePickerCalendarOptions($translated)[0]['labels'])->toBe($calendarLabels);
});

it('lets the explicit placeholder override the translated label', function (): void {
    $html = renderDateRangePicker([
        'placeholder' => 'Pick a period',
        'labels' => ['placeholder' => 'Choose a range'],
    ]);

    expect(dateRangePickerOptions($html)['placeholder'])->toBe('Pick a period');
});

it('renders both responsive branches and composes their real overlay components', function (): void {
    $html = renderDateRangePicker();
    $desktop = strpos($html, '<template x-if="!mobile">');
    $mobile = strpos($html, '<template x-if="mobile">');

    expect($desktop)->toBeInt()
        ->and($mobile)->toBeInt()
        ->and($desktop)->toBeLessThan($mobile)
        ->and($html)->toContain('x-data="lyraPopover(')
        ->and($html)->toContain('aria-label="Date range picker"')
        ->and($html)->toContain('x-data="lyraBottomSheet(')
        ->and($html)->toContain('class="lyra-cal--sheet"')
        ->and($html)->toContain('>Select period</h2>')
        ->and($html)->toContain('aria-label="Close"');
});

it('renders every trigger with the exact button and calendar icon contract', function (): void {
    $html = renderDateRangePicker(['id' => 'booking-period']);
    $triggers = dateRangePickerTriggerTags($html);

    expect($triggers)->toHaveCount(2);

    foreach ($triggers as $trigger) {
        expect($trigger)->toContain('type="button"')
            ->and($trigger)->toContain('id="booking-period"')
            ->and($trigger)->toContain('class="lyra-input lyra-datepicker__btn"')
            ->and($trigger)->not->toContain('disabled');
    }

    expect(substr_count($html, '<svg'))->toBeGreaterThanOrEqual(2)
        ->and($html)->toMatch('/<svg\s+width="15"\s+height="15"\s+viewBox="0 0 24 24"\s+fill="none"\s+stroke="currentColor"\s+stroke-width="2"\s+stroke-linecap="round"\s+stroke-linejoin="round"\s+aria-hidden="true"\s*>\s*<path d="M8 2v4M16 2v4" \/>\s*<rect width="18" height="18" x="3" y="4" rx="2" \/>\s*<path d="M3 10h18" \/>\s*<\/svg>/s')
        ->and(substr_count($html, ':class="{ \'lyra-datepicker__ph\': !hasSelection() }"'))->toBe(2)
        ->and(substr_count($html, 'x-text="triggerText()"'))->toBe(2);
});

it('generates a trigger id and wires the field label to it', function (): void {
    $html = renderDateRangePicker(['label' => 'Travel period']);
    $triggerId = dateRangePickerAttribute(dateRangePickerOpeningTag($html, 'trigger'), 'id');

    expect($triggerId)->toMatch('/^lyra-date-range-picker-/')
        ->and(dateRangePickerOpeningTag($html, 'label'))->toContain('for="'.$triggerId.'"')
        ->and($html)->toContain('>Travel period</label>');
});

it('treats zero strings as present label, hint, and error content', function (): void {
    $label = renderDateRangePicker(['label' => '0']);
    $hint = renderDateRangePicker(['hint' => '0']);
    $error = renderDateRangePicker(['error' => '0']);

    expect(dateRangePickerRootClass($label))->toBe('lyra-field')
        ->and($label)->toContain('<label class="lyra-label"')
        ->and($label)->toContain('>0</label>')
        ->and(dateRangePickerRootClass($hint))->toBe('lyra-field')
        ->and($hint)->toContain('<span class="lyra-hint">0</span>')
        ->and(dateRangePickerRootClass($error))->toBe('lyra-field')
        ->and($error)->toContain('<span class="lyra-hint lyra-hint--error">0</span>');
});

it('uses the desktop trigger button as the only bound interactive trigger', function (): void {
    $desktop = dateRangePickerDesktopBranch(renderDateRangePicker(['id' => 'booking-period']));
    preg_match_all('/<([a-z][a-z0-9-]*)\b(?=[^>]*\bx-bind="trigger")[^>]*>/', $desktop, $matches);

    expect($matches[0])->toHaveCount(1)
        ->and($matches[1][0])->toBe('button')
        ->and($matches[0][0])->toContain('x-bind="trigger"')
        ->and($matches[0][0])->toContain('id="booking-period"')
        ->and($matches[0][0])->toContain('class="lyra-input lyra-datepicker__btn"')
        ->and($desktop)->not->toMatch('/<span\b[^>]*role="button"[^>]*>\s*<button\b/s');
});

it('adds error styling to every trigger and suppresses the hint', function (): void {
    $html = renderDateRangePicker([
        'hint' => 'Choose your travel period',
        'error' => 'Period is required',
    ]);

    expect(dateRangePickerTriggerTags($html))->toHaveCount(2);

    foreach (dateRangePickerTriggerTags($html) as $trigger) {
        expect($trigger)->toContain('class="lyra-input lyra-datepicker__btn lyra-input--error"');
    }

    expect(dateRangePickerRootClass($html))->toBe('lyra-field')
        ->and($html)->toContain('<span class="lyra-hint lyra-hint--error">Period is required</span>')
        ->and($html)->not->toContain('Choose your travel period');
});

it('renders only the hint when there is no error and omits messages otherwise', function (): void {
    $hint = renderDateRangePicker(['hint' => 'Choose your travel period']);
    $plain = renderDateRangePicker();

    expect(dateRangePickerRootClass($hint))->toBe('lyra-field')
        ->and($hint)->toContain('<span class="lyra-hint">Choose your travel period</span>')
        ->and($hint)->not->toContain('lyra-hint--error')
        ->and(dateRangePickerRootClass($plain))->toBe('')
        ->and($plain)->not->toContain('class="lyra-hint');
});

it('serves the disabled control statically without responsive or overlay markup', function (): void {
    $html = renderDateRangePicker(['disabled' => true]);
    $trigger = dateRangePickerOpeningTag($html, 'trigger');

    expect($html)->toMatch('/<span class="lyra-datepicker">\s*<button\b.*?<\/button>\s*<\/span>/s')
        ->and($trigger)->toContain('disabled')
        ->and($html)->not->toContain('<template x-if="!mobile">')
        ->and($html)->not->toContain('<template x-if="mobile">')
        ->and($html)->not->toContain('lyraPopover(')
        ->and($html)->not->toContain('lyraBottomSheet(')
        ->and($html)->not->toContain('lyraCalendar(');
});

it('composes two range calendars with the exact forwarded constraints and labels', function (): void {
    $calendarLabels = [
        'previousMonth' => 'Mês anterior',
        'nextMonth' => 'Próximo mês',
        'today' => 'Hoje',
    ];
    $html = renderDateRangePicker([
        'min' => '2026-08-01',
        'max' => '2026-08-31',
        'locale' => 'pt-BR',
        'labels' => ['calendar' => $calendarLabels],
    ]);
    $calendars = dateRangePickerCalendarOptions($html);

    expect($calendars)->toHaveCount(2);

    foreach ($calendars as $options) {
        expect($options)->toBe([
            'range' => true,
            'min' => '2026-08-01',
            'max' => '2026-08-31',
            'locale' => 'pt-BR',
            'labels' => $calendarLabels,
        ]);
    }
});

it('uses non-colliding aliases for both nested model chains', function (): void {
    $html = renderDateRangePicker();

    expect(substr_count($html, 'get pickerOpen() { return open }'))->toBe(2)
        ->and(substr_count($html, 'set pickerOpen(v) { open = v }'))->toBe(2)
        ->and(substr_count($html, 'x-model="pickerOpen"'))->toBe(2)
        ->and(substr_count($html, 'get pickerSelected() { return selected }'))->toBe(2)
        ->and(substr_count($html, 'set pickerSelected(v) { selected = v }'))->toBe(2)
        ->and(substr_count($html, 'x-model="pickerSelected"'))->toBe(2);
});

it('emits and synchronizes two native hidden inputs only when name is provided', function (): void {
    $withoutName = renderDateRangePicker([
        'defaultValue' => ['start' => '2026-08-09', 'end' => '2026-08-12'],
    ]);
    $withName = renderDateRangePicker([
        'name' => 'periodo',
        'defaultValue' => ['start' => '2026-08-09', 'end' => '2026-08-12'],
    ]);
    $empty = renderDateRangePicker(['name' => 'periodo']);
    $root = dateRangePickerOpeningTag($withName, 'root');
    $hidden = dateRangePickerHiddenTags($withName);
    $emptyHidden = dateRangePickerHiddenTags($empty);

    expect($withoutName)->not->toContain('type="hidden"')
        ->and($hidden)->toHaveCount(2)
        ->and($hidden[0])->toContain('name="periodo[start]"')
        ->and($hidden[0])->toContain('value="2026-08-09"')
        ->and($hidden[0])->toContain('x-ref="nativeStart"')
        ->and($hidden[1])->toContain('name="periodo[end]"')
        ->and($hidden[1])->toContain('value="2026-08-12"')
        ->and($hidden[1])->toContain('x-ref="nativeEnd"')
        ->and($emptyHidden[0])->toContain('value=""')
        ->and($emptyHidden[1])->toContain('value=""')
        ->and($root)->toContain("@lyra:change=\"\$refs.nativeStart.value = \$event.detail.value?.start ?? ''; \$refs.nativeEnd.value = \$event.detail.value?.end ?? ''\"");
});

it('keeps hostile coordinator and calendar values inside JSON literals', function (): void {
    $payload = "'); window.pwned=1; //\"\\</script>";
    $defaultValue = ['start' => $payload, 'end' => $payload];
    $calendarLabels = ['today' => $payload];
    $html = renderDateRangePicker([
        'defaultValue' => $defaultValue,
        'locale' => $payload,
        'placeholder' => $payload,
        'labels' => [
            'placeholder' => $payload,
            'popover' => $payload,
            'sheetTitle' => $payload,
            'close' => $payload,
            'rangeSeparator' => $payload,
            'incompleteRange' => $payload,
            'calendar' => $calendarLabels,
        ],
        'min' => $payload,
        'max' => $payload,
    ]);
    $root = dateRangePickerOpeningTag($html, 'root');

    expect(dateRangePickerOptions($html))->toBe([
        'defaultValue' => $defaultValue,
        'locale' => $payload,
        'placeholder' => $payload,
        'rangeSeparator' => $payload,
        'incompleteRange' => $payload,
    ])->and(dateRangePickerCalendarOptions($html)[0])->toBe([
        'range' => true,
        'min' => $payload,
        'max' => $payload,
        'locale' => $payload,
        'labels' => $calendarLabels,
    ])->and(substr_count($root, 'x-data='))->toBe(1)
        ->and($root)->not->toContain("'); window.pwned")
        ->and($html)->not->toContain('</script>')
        ->and($root)->toContain('\\u003C/script\\u003E');
});

it('splits model attributes onto the modelable root and passes native attributes after fixed ones', function (): void {
    $html = renderDateRangePicker([
        'id' => 'travel-period',
        'class' => 'wide elevated',
        'wire:model.live' => 'period',
        'x-model.fill' => 'selectedPeriod',
        'data-track' => 'date-range-picker',
        'aria-label' => 'Travel period',
    ]);
    $root = dateRangePickerOpeningTag($html, 'root');

    expect(dateRangePickerRootClass($html))->toBe('wide elevated')
        ->and($root)->toContain('wire:model.live="period"')
        ->and($root)->toContain('x-model.fill="selectedPeriod"')
        ->and($root)->toContain('data-track="date-range-picker"')
        ->and($root)->toContain('aria-label="Travel period"')
        ->and($root)->not->toContain('id="travel-period"')
        ->and(dateRangePickerOpeningTag($html, 'trigger'))->toContain('id="travel-period"')
        ->and(strpos($root, 'x-modelable="selected"'))->toBeLessThan(strpos($root, 'wire:model.live="period"'));
});

it('supports Livewire model binding through selected', function (): void {
    $component = new class extends Component
    {
        /** @var array{start: ?string, end: ?string}|null */
        public ?array $period = ['start' => '2026-08-09', 'end' => '2026-08-12'];

        public function render(): string
        {
            return <<<'BLADE'
                <x-lyra::date-range-picker :default-value="$period" wire:model.live="period" />
            BLADE;
        }
    };

    $html = Livewire::test($component)->html();
    $root = dateRangePickerOpeningTag($html, 'root');

    expect($root)->toContain('x-modelable="selected"')
        ->and($root)->toContain('wire:model.live="period"')
        ->and(dateRangePickerOptions($html)['defaultValue'])->toBe([
            'start' => '2026-08-09',
            'end' => '2026-08-12',
        ])->and(dateRangePickerOpeningTag($html, 'trigger'))->not->toContain('wire:model');
});

it('renders namespaced and short syntax identically', function (): void {
    $namespaced = Blade::render('<x-lyra::date-range-picker id="travel" label="Travel" disabled />');
    $short = Blade::render('<lyra:date-range-picker id="travel" label="Travel" disabled />');

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('lyraDateRangePicker(');
});
