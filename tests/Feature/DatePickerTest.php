<?php

use Illuminate\Support\Facades\Blade;
use Livewire\Component;
use Livewire\Livewire;

function renderDatePicker(array $props = []): string
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
            '<x-lyra::date-picker :label="$label" :hint="$hint" :error="$error" :default-value="$defaultValue" :placeholder="$placeholder" :min="$min" :max="$max" :locale="$locale" :labels="$labels" :disabled="$disabled" :name="$name" %s />',
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

function datePickerOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<div\b(?=[^>]*\bx-data="lyraDatePicker\()[^>]*>/',
        'trigger' => '/<button\b(?=[^>]*\bclass="lyra-input lyra-datepicker__btn(?: lyra-input--error)?")[^>]*>/',
        'label' => '/<label\b(?=[^>]*\bclass="lyra-label")[^>]*>/',
        'hint' => '/<span\b(?=[^>]*\bclass="lyra-hint(?: lyra-hint--error)?")[^>]*>/',
        'hidden' => '/<input\b(?=[^>]*\btype="hidden")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

/** @return array<int, string> */
function datePickerTriggerTags(string $html): array
{
    preg_match_all(
        '/<button\b(?=[^>]*\bclass="lyra-input lyra-datepicker__btn(?: lyra-input--error)?")[^>]*>/',
        $html,
        $matches,
    );

    return $matches[0];
}

function datePickerRootClass(string $html): string
{
    $root = datePickerOpeningTag($html, 'root');
    $matched = preg_match('/\bclass="([^"]*)"/', $root, $matches);

    return $matched === 1 ? $matches[1] : '';
}

function datePickerDesktopBranch(string $html): string
{
    $desktop = strpos($html, '<template x-if="!mobile">');
    $mobile = strpos($html, '<template x-if="mobile">');

    expect($desktop)->toBeInt()
        ->and($mobile)->toBeInt()
        ->and($desktop)->toBeLessThan($mobile);

    return substr($html, $desktop, $mobile - $desktop);
}

function datePickerAttribute(string $tag, string $attribute): ?string
{
    $matched = preg_match(
        sprintf('/\b%s="([^"]*)"/', preg_quote($attribute, '/')),
        $tag,
        $matches,
    );

    return $matched === 1 ? $matches[1] : null;
}

/** @return array<string, mixed> */
function datePickerOptions(string $html): array
{
    $root = datePickerOpeningTag($html, 'root');
    $expression = html_entity_decode(
        (string) datePickerAttribute($root, 'x-data'),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8',
    );

    expect($expression)->toStartWith('lyraDatePicker(')->toEndWith(')');

    return json_decode(
        substr($expression, strlen('lyraDatePicker('), -1),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

/** @return array<int, array<string, mixed>> */
function datePickerCalendarOptions(string $html): array
{
    preg_match_all('/<div\b(?=[^>]*\bx-data="lyraCalendar\()[^>]*>/', $html, $matches);

    return array_map(function (string $tag): array {
        $expression = html_entity_decode(
            (string) datePickerAttribute($tag, 'x-data'),
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

dataset('date picker class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/date-picker.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the date-picker class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React root class string', function (array $case): void {
    $html = renderDatePicker($case['props']);

    expect(datePickerRootClass($html))->toBe($case['expected_class'])
        ->and(substr_count(datePickerOpeningTag($html, 'root'), 'class='))->toBeLessThanOrEqual(1);
})->with('date picker class emission');

it('serializes the coordinator options as valid JSON and exposes selected as modelable', function (): void {
    $html = renderDatePicker([
        'defaultValue' => '2026-08-09',
        'locale' => 'pt-BR',
        'labels' => ['placeholder' => 'Escolha uma data'],
    ]);
    $root = datePickerOpeningTag($html, 'root');

    expect(datePickerOptions($html))->toBe([
        'defaultValue' => '2026-08-09',
        'locale' => 'pt-BR',
        'placeholder' => 'Escolha uma data',
    ])->and($root)->toContain('x-modelable="selected"');
});

it('lets the explicit placeholder override the translated label', function (): void {
    $html = renderDatePicker([
        'placeholder' => 'Pick one',
        'labels' => ['placeholder' => 'Choose a date'],
    ]);

    expect(datePickerOptions($html)['placeholder'])->toBe('Pick one');
});

it('renders both responsive branches and composes their real overlay components', function (): void {
    $html = renderDatePicker();
    $desktop = strpos($html, '<template x-if="!mobile">');
    $mobile = strpos($html, '<template x-if="mobile">');

    expect($desktop)->toBeInt()
        ->and($mobile)->toBeInt()
        ->and($desktop)->toBeLessThan($mobile)
        ->and($html)->toContain('x-data="lyraPopover(')
        ->and($html)->toContain('aria-label="Date picker"')
        ->and($html)->toContain('x-data="lyraBottomSheet(')
        ->and($html)->toContain('class="lyra-cal--sheet"')
        ->and($html)->toContain('>Select date</h2>')
        ->and($html)->toContain('aria-label="Close"');
});

it('renders every trigger with the exact button and calendar icon contract', function (): void {
    $html = renderDatePicker(['id' => 'booking-date']);
    $triggers = datePickerTriggerTags($html);

    expect($triggers)->toHaveCount(2);

    foreach ($triggers as $trigger) {
        expect($trigger)->toContain('type="button"')
            ->and($trigger)->toContain('id="booking-date"')
            ->and($trigger)->toContain('class="lyra-input lyra-datepicker__btn"')
            ->and($trigger)->not->toContain('disabled');
    }

    expect(substr_count($html, '<svg'))->toBeGreaterThanOrEqual(2)
        ->and($html)->toMatch('/<svg\s+width="15"\s+height="15"\s+viewBox="0 0 24 24"\s+fill="none"\s+stroke="currentColor"\s+stroke-width="2"\s+stroke-linecap="round"\s+stroke-linejoin="round"\s+aria-hidden="true"\s*>\s*<path d="M8 2v4M16 2v4" \/>\s*<rect width="18" height="18" x="3" y="4" rx="2" \/>\s*<path d="M3 10h18" \/>\s*<\/svg>/s')
        ->and(substr_count($html, ':class="{ \'lyra-datepicker__ph\': !hasSelection() }"'))->toBe(2)
        ->and(substr_count($html, 'x-text="triggerText()"'))->toBe(2);
});

it('generates a trigger id and wires the field label to it', function (): void {
    $html = renderDatePicker(['label' => 'Appointment date']);
    $triggerId = datePickerAttribute(datePickerOpeningTag($html, 'trigger'), 'id');

    expect($triggerId)->toMatch('/^lyra-date-picker-/')
        ->and(datePickerOpeningTag($html, 'label'))->toContain('for="'.$triggerId.'"')
        ->and($html)->toContain('>Appointment date</label>');
});

it('treats zero strings as present label, hint, and error content', function (): void {
    $label = renderDatePicker(['label' => '0']);
    $hint = renderDatePicker(['hint' => '0']);
    $error = renderDatePicker(['error' => '0']);

    expect(datePickerRootClass($label))->toBe('lyra-field')
        ->and($label)->toContain('<label class="lyra-label"')
        ->and($label)->toContain('>0</label>')
        ->and(datePickerRootClass($hint))->toBe('lyra-field')
        ->and($hint)->toContain('<span class="lyra-hint">0</span>')
        ->and(datePickerRootClass($error))->toBe('lyra-field')
        ->and($error)->toContain('<span class="lyra-hint lyra-hint--error">0</span>');
});

it('uses the desktop trigger button as the only bound interactive trigger', function (): void {
    $desktop = datePickerDesktopBranch(renderDatePicker(['id' => 'booking-date']));
    preg_match_all('/<([a-z][a-z0-9-]*)\b(?=[^>]*\bx-bind="trigger")[^>]*>/', $desktop, $matches);

    expect($matches[0])->toHaveCount(1)
        ->and($matches[1][0])->toBe('button')
        ->and($matches[0][0])->toContain('x-bind="trigger"')
        ->and($matches[0][0])->toContain('id="booking-date"')
        ->and($matches[0][0])->toContain('class="lyra-input lyra-datepicker__btn"')
        ->and($desktop)->not->toMatch('/<span\b[^>]*role="button"[^>]*>\s*<button\b/s');
});

it('adds error styling to every trigger and suppresses the hint', function (): void {
    $html = renderDatePicker([
        'hint' => 'Choose your arrival date',
        'error' => 'Date is required',
    ]);

    expect(datePickerTriggerTags($html))->toHaveCount(2);

    foreach (datePickerTriggerTags($html) as $trigger) {
        expect($trigger)->toContain('class="lyra-input lyra-datepicker__btn lyra-input--error"');
    }

    expect(datePickerRootClass($html))->toBe('lyra-field')
        ->and($html)->toContain('<span class="lyra-hint lyra-hint--error">Date is required</span>')
        ->and($html)->not->toContain('Choose your arrival date');
});

it('renders only the hint when there is no error and omits messages otherwise', function (): void {
    $hint = renderDatePicker(['hint' => 'Choose your arrival date']);
    $plain = renderDatePicker();

    expect(datePickerRootClass($hint))->toBe('lyra-field')
        ->and($hint)->toContain('<span class="lyra-hint">Choose your arrival date</span>')
        ->and($hint)->not->toContain('lyra-hint--error')
        ->and(datePickerRootClass($plain))->toBe('')
        ->and($plain)->not->toContain('class="lyra-hint');
});

it('serves the disabled control statically without responsive or overlay markup', function (): void {
    $html = renderDatePicker(['disabled' => true]);
    $trigger = datePickerOpeningTag($html, 'trigger');

    expect($html)->toMatch('/<span class="lyra-datepicker">\s*<button\b.*?<\/button>\s*<\/span>/s')
        ->and($trigger)->toContain('disabled')
        ->and($html)->not->toContain('<template x-if="!mobile">')
        ->and($html)->not->toContain('<template x-if="mobile">')
        ->and($html)->not->toContain('lyraPopover(')
        ->and($html)->not->toContain('lyraBottomSheet(')
        ->and($html)->not->toContain('lyraCalendar(');
});

it('composes two calendars with the exact forwarded constraints and labels', function (): void {
    $calendarLabels = [
        'previousMonth' => 'Mês anterior',
        'nextMonth' => 'Próximo mês',
        'today' => 'Hoje',
    ];
    $html = renderDatePicker([
        'min' => '2026-08-01',
        'max' => '2026-08-31',
        'locale' => 'pt-BR',
        'labels' => ['calendar' => $calendarLabels],
    ]);
    $calendars = datePickerCalendarOptions($html);

    expect($calendars)->toHaveCount(2);

    foreach ($calendars as $options) {
        expect($options)->toBe([
            'min' => '2026-08-01',
            'max' => '2026-08-31',
            'locale' => 'pt-BR',
            'labels' => $calendarLabels,
        ]);
    }
});

it('uses non-colliding aliases for both nested model chains', function (): void {
    $html = renderDatePicker();

    expect(substr_count($html, 'get pickerOpen() { return open }'))->toBe(2)
        ->and(substr_count($html, 'set pickerOpen(v) { open = v }'))->toBe(2)
        ->and(substr_count($html, 'x-model="pickerOpen"'))->toBe(2)
        ->and(substr_count($html, 'get pickerSelected() { return selected }'))->toBe(2)
        ->and(substr_count($html, 'set pickerSelected(v) { selected = v }'))->toBe(2)
        ->and(substr_count($html, 'x-model="pickerSelected"'))->toBe(2);
});

it('emits and synchronizes the native hidden input only when name is provided', function (): void {
    $withoutName = renderDatePicker(['defaultValue' => '2026-08-09']);
    $withName = renderDatePicker([
        'name' => 'appointment_date',
        'defaultValue' => '2026-08-09',
    ]);
    $empty = renderDatePicker(['name' => 'appointment_date']);
    $root = datePickerOpeningTag($withName, 'root');
    $hidden = datePickerOpeningTag($withName, 'hidden');

    expect($withoutName)->not->toContain('type="hidden"')
        ->and($hidden)->toContain('name="appointment_date"')
        ->and($hidden)->toContain('value="2026-08-09"')
        ->and($hidden)->toContain('x-ref="native"')
        ->and(datePickerOpeningTag($empty, 'hidden'))->toContain('value=""')
        ->and($root)->toContain('@lyra:change="$refs.native.value = $event.detail.value"');
});

it('keeps hostile coordinator and calendar values inside JSON literals', function (): void {
    $payload = "'); window.pwned=1; //\"\\</script>";
    $calendarLabels = ['today' => $payload];
    $html = renderDatePicker([
        'defaultValue' => $payload,
        'locale' => $payload,
        'placeholder' => $payload,
        'labels' => [
            'placeholder' => $payload,
            'calendar' => $calendarLabels,
        ],
        'min' => $payload,
        'max' => $payload,
    ]);
    $root = datePickerOpeningTag($html, 'root');

    expect(datePickerOptions($html))->toBe([
        'defaultValue' => $payload,
        'locale' => $payload,
        'placeholder' => $payload,
    ])->and(datePickerCalendarOptions($html)[0])->toBe([
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
    $html = renderDatePicker([
        'id' => 'travel-date',
        'class' => 'wide elevated',
        'wire:model.live' => 'date',
        'x-model.fill' => 'selectedDate',
        'data-track' => 'date-picker',
        'aria-label' => 'Travel date',
    ]);
    $root = datePickerOpeningTag($html, 'root');

    expect(datePickerRootClass($html))->toBe('wide elevated')
        ->and($root)->toContain('wire:model.live="date"')
        ->and($root)->toContain('x-model.fill="selectedDate"')
        ->and($root)->toContain('data-track="date-picker"')
        ->and($root)->toContain('aria-label="Travel date"')
        ->and($root)->not->toContain('id="travel-date"')
        ->and(datePickerOpeningTag($html, 'trigger'))->toContain('id="travel-date"')
        ->and(strpos($root, 'x-modelable="selected"'))->toBeLessThan(strpos($root, 'wire:model.live="date"'));
});

it('supports Livewire model binding through selected', function (): void {
    $component = new class extends Component
    {
        public ?string $date = '2026-08-09';

        public function render(): string
        {
            return <<<'BLADE'
                <x-lyra::date-picker :default-value="$date" wire:model.live="date" />
            BLADE;
        }
    };

    $html = Livewire::test($component)->html();
    $root = datePickerOpeningTag($html, 'root');

    expect($root)->toContain('x-modelable="selected"')
        ->and($root)->toContain('wire:model.live="date"')
        ->and(datePickerOptions($html)['defaultValue'])->toBe('2026-08-09')
        ->and(datePickerOpeningTag($html, 'trigger'))->not->toContain('wire:model');
});

it('renders namespaced and short syntax identically', function (): void {
    $namespaced = Blade::render('<x-lyra::date-picker id="arrival" label="Arrival" disabled />');
    $short = Blade::render('<lyra:date-picker id="arrival" label="Arrival" disabled />');

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('lyraDatePicker(');
});
