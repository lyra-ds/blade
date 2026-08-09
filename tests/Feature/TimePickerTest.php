<?php

use Illuminate\Support\Facades\Blade;
use Livewire\Component;
use Livewire\Livewire;

function renderTimePicker(array $props = []): string
{
    $label = $props['label'] ?? null;
    $hint = $props['hint'] ?? null;
    $error = $props['error'] ?? null;
    $defaultValue = $props['defaultValue'] ?? null;
    $placeholder = $props['placeholder'] ?? null;
    $step = $props['step'] ?? 30;
    $min = $props['min'] ?? null;
    $max = $props['max'] ?? null;
    $locale = $props['locale'] ?? 'en-US';
    $labels = $props['labels'] ?? [];
    $disabled = $props['disabled'] ?? false;
    unset(
        $props['label'],
        $props['hint'],
        $props['error'],
        $props['defaultValue'],
        $props['placeholder'],
        $props['step'],
        $props['min'],
        $props['max'],
        $props['locale'],
        $props['labels'],
        $props['disabled'],
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
            '<x-lyra::time-picker :label="$label" :hint="$hint" :error="$error" :default-value="$defaultValue" :placeholder="$placeholder" :step="$step" :min="$min" :max="$max" :locale="$locale" :labels="$labels" :disabled="$disabled" %s />',
            $attributes,
        ),
        compact(
            'label',
            'hint',
            'error',
            'defaultValue',
            'placeholder',
            'step',
            'min',
            'max',
            'locale',
            'labels',
            'disabled',
        ),
    );
}

function timePickerOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<div\b(?=[^>]*\bx-data="lyraTimePicker\()[^>]*>/',
        'trigger' => '/<button\b(?=[^>]*\bclass="lyra-input lyra-datepicker__btn(?: lyra-input--error)?")[^>]*>/',
        'label' => '/<label\b(?=[^>]*\bclass="lyra-label")[^>]*>/',
        'list' => '/<div\b(?=[^>]*\bclass="lyra-timelist")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

/** @return array<int, string> */
function timePickerTriggerTags(string $html): array
{
    preg_match_all(
        '/<button\b(?=[^>]*\bclass="lyra-input lyra-datepicker__btn(?: lyra-input--error)?")[^>]*>/',
        $html,
        $matches,
    );

    return $matches[0];
}

/** @return array<int, string> */
function timePickerListTags(string $html): array
{
    preg_match_all('/<div\b(?=[^>]*\bclass="lyra-timelist")[^>]*>/', $html, $matches);

    return $matches[0];
}

function timePickerRootClass(string $html): string
{
    $root = timePickerOpeningTag($html, 'root');
    $matched = preg_match('/\bclass="([^"]*)"/', $root, $matches);

    return $matched === 1 ? $matches[1] : '';
}

function timePickerDesktopBranch(string $html): string
{
    $desktop = strpos($html, '<template x-if="!mobile">');
    $mobile = strpos($html, '<template x-if="mobile">');

    expect($desktop)->toBeInt()
        ->and($mobile)->toBeInt()
        ->and($desktop)->toBeLessThan($mobile);

    return substr($html, $desktop, $mobile - $desktop);
}

function timePickerAttribute(string $tag, string $attribute): ?string
{
    $matched = preg_match(
        sprintf('/\b%s="([^"]*)"/', preg_quote($attribute, '/')),
        $tag,
        $matches,
    );

    return $matched === 1 ? $matches[1] : null;
}

/** @return array<string, mixed> */
function timePickerOptions(string $html): array
{
    $root = timePickerOpeningTag($html, 'root');
    $expression = html_entity_decode(
        (string) timePickerAttribute($root, 'x-data'),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8',
    );

    expect($expression)->toStartWith('lyraTimePicker(')->toEndWith(')');

    return json_decode(
        substr($expression, strlen('lyraTimePicker('), -1),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

dataset('time picker class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/time-picker.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the time-picker class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React root class string', function (array $case): void {
    $html = renderTimePicker($case['props']);

    expect(timePickerRootClass($html))->toBe($case['expected_class'])
        ->and(substr_count(timePickerOpeningTag($html, 'root'), 'class='))->toBeLessThanOrEqual(1);
})->with('time picker class emission');

it('serializes every coordinator option as valid JSON and exposes selected as modelable', function (): void {
    $html = renderTimePicker([
        'defaultValue' => '09:30',
        'placeholder' => 'Pick a time',
        'step' => 15,
        'min' => '08:00',
        'max' => '17:00',
        'locale' => 'pt-BR',
    ]);
    $root = timePickerOpeningTag($html, 'root');

    expect(timePickerOptions($html))->toBe([
        'defaultValue' => '09:30',
        'step' => 15,
        'min' => '08:00',
        'max' => '17:00',
        'locale' => 'pt-BR',
        'placeholder' => 'Pick a time',
    ])->and($root)->toContain('x-modelable="selected"');
});

it('uses binding defaults without inventing optional values', function (): void {
    expect(timePickerOptions(renderTimePicker()))->toBe([
        'step' => 30,
        'locale' => 'en-US',
        'placeholder' => 'Select time',
    ]);
});

it('omits steps that would not produce a positive integer interval', function (mixed $step): void {
    expect(timePickerOptions(renderTimePicker(['step' => $step])))->not->toHaveKey('step');
})->with([
    'fraction below one' => 0.5,
    'zero' => 0,
    'negative number' => -5,
    'non numeric string' => 'abc',
    'infinity' => INF,
]);

it('serializes valid steps as positive integers', function (mixed $step, int $expected): void {
    expect(timePickerOptions(renderTimePicker(['step' => $step]))['step'])->toBe($expected);
})->with([
    'integer' => [15, 15],
    'numeric string' => ['15', 15],
    'fraction above one' => [15.9, 15],
]);

it('lets the explicit placeholder override the translated label', function (): void {
    $html = renderTimePicker([
        'placeholder' => 'Pick one',
        'labels' => ['placeholder' => 'Choose a time'],
    ]);

    expect(timePickerOptions($html)['placeholder'])->toBe('Pick one');
});

it('renders both responsive branches with the real overlays and default labels', function (): void {
    $html = renderTimePicker();
    $desktop = strpos($html, '<template x-if="!mobile">');
    $mobile = strpos($html, '<template x-if="mobile">');

    expect($desktop)->toBeInt()
        ->and($mobile)->toBeInt()
        ->and($desktop)->toBeLessThan($mobile)
        ->and($html)->toContain('x-data="lyraPopover(')
        ->and($html)->toContain('aria-label="Time picker"')
        ->and($html)->toContain('x-data="lyraBottomSheet(')
        ->and($html)->toContain('>Select time</h2>')
        ->and($html)->toContain('aria-label="Close"');
});

it('renders every trigger with the exact button and clock icon contract', function (): void {
    $html = renderTimePicker(['id' => 'booking-time']);
    $triggers = timePickerTriggerTags($html);

    expect($triggers)->toHaveCount(2);

    foreach ($triggers as $trigger) {
        expect($trigger)->toContain('type="button"')
            ->and($trigger)->toContain('id="booking-time"')
            ->and($trigger)->toContain('class="lyra-input lyra-datepicker__btn"')
            ->and($trigger)->not->toContain('disabled');
    }

    expect(substr_count($html, '<svg'))->toBeGreaterThanOrEqual(2)
        ->and($html)->toMatch('/<svg\s+width="15"\s+height="15"\s+viewBox="0 0 24 24"\s+fill="none"\s+stroke="currentColor"\s+stroke-width="2"\s+stroke-linecap="round"\s+stroke-linejoin="round"\s+aria-hidden="true"\s*>\s*<circle cx="12" cy="12" r="10" \/>\s*<path d="M12 6v6l4 2" \/>\s*<\/svg>/s')
        ->and(substr_count($html, ':class="{ \'lyra-datepicker__ph\': !hasSelection() }"'))->toBe(2)
        ->and(substr_count($html, 'x-text="triggerText()"'))->toBe(2);
});

it('generates a trigger id and wires the field label to it', function (): void {
    $html = renderTimePicker(['label' => 'Appointment time']);
    $triggerId = timePickerAttribute(timePickerOpeningTag($html, 'trigger'), 'id');

    expect($triggerId)->toMatch('/^lyra-time-picker-/')
        ->and(timePickerOpeningTag($html, 'label'))->toContain('for="'.$triggerId.'"')
        ->and($html)->toContain('>Appointment time</label>');
});

it('treats zero strings as present label hint and error content', function (): void {
    $label = renderTimePicker(['label' => '0']);
    $hint = renderTimePicker(['hint' => '0']);
    $error = renderTimePicker(['error' => '0']);

    expect(timePickerRootClass($label))->toBe('lyra-field')
        ->and($label)->toContain('<label class="lyra-label"')
        ->and($label)->toContain('>0</label>')
        ->and(timePickerRootClass($hint))->toBe('lyra-field')
        ->and($hint)->toContain('<span class="lyra-hint">0</span>')
        ->and(timePickerRootClass($error))->toBe('lyra-field')
        ->and($error)->toContain('<span class="lyra-hint lyra-hint--error">0</span>');
});

it('renders both listboxes with the exact Alpine option contract', function (): void {
    $html = renderTimePicker();
    $lists = timePickerListTags($html);
    preg_match_all(
        '/<button\b(?=[^>]*\bclass="lyra-timelist__item")[^>]*>/',
        $html,
        $options,
    );

    expect($lists)->toHaveCount(2);

    foreach ($lists as $list) {
        expect($list)->toContain('class="lyra-timelist"')
            ->and($list)->toContain('x-bind="list"')
            ->and($list)->toContain('aria-label="Time options"');
    }

    expect(substr_count($html, '<template x-for="time in options()" :key="time">'))->toBe(2)
        ->and(substr_count($html, 'class="lyra-timelist__item"'))->toBe(2)
        ->and(substr_count($html, ':class="{ \'lyra-timelist__item--selected\': time === selected }"'))->toBe(2)
        ->and(substr_count($html, ':aria-selected="time === selected ? \'true\' : false"'))->toBe(2)
        ->and(substr_count($html, '@click="pick(time)"'))->toBe(2)
        ->and(substr_count($html, 'x-text="formatTime(time)"'))->toBe(2);

    expect($options[0])->toHaveCount(2);

    foreach ($options[0] as $option) {
        expect($option)->toContain('type="button"')
            ->and($option)->toContain('role="option"');
    }
});

it('uses the desktop trigger button as the only bound interactive trigger', function (): void {
    $desktop = timePickerDesktopBranch(renderTimePicker(['id' => 'booking-time']));
    preg_match_all('/<([a-z][a-z0-9-]*)\b(?=[^>]*\bx-bind="trigger")[^>]*>/', $desktop, $matches);

    expect($matches[0])->toHaveCount(1)
        ->and($matches[1][0])->toBe('button')
        ->and($matches[0][0])->toContain('x-bind="trigger"')
        ->and($matches[0][0])->toContain('id="booking-time"')
        ->and($matches[0][0])->toContain('class="lyra-input lyra-datepicker__btn"')
        ->and($desktop)->not->toMatch('/<span\b[^>]*role="button"[^>]*>\s*<button\b/s');
});

it('adds error styling to every trigger and suppresses the hint', function (): void {
    $html = renderTimePicker([
        'hint' => 'Choose your arrival time',
        'error' => 'Time is required',
    ]);

    expect(timePickerTriggerTags($html))->toHaveCount(2);

    foreach (timePickerTriggerTags($html) as $trigger) {
        expect($trigger)->toContain('class="lyra-input lyra-datepicker__btn lyra-input--error"');
    }

    expect(timePickerRootClass($html))->toBe('lyra-field')
        ->and($html)->toContain('<span class="lyra-hint lyra-hint--error">Time is required</span>')
        ->and($html)->not->toContain('Choose your arrival time');
});

it('renders only the hint when there is no error and omits messages otherwise', function (): void {
    $hint = renderTimePicker(['hint' => 'Choose your arrival time']);
    $plain = renderTimePicker();

    expect(timePickerRootClass($hint))->toBe('lyra-field')
        ->and($hint)->toContain('<span class="lyra-hint">Choose your arrival time</span>')
        ->and($hint)->not->toContain('lyra-hint--error')
        ->and(timePickerRootClass($plain))->toBe('')
        ->and($plain)->not->toContain('class="lyra-hint');
});

it('serves the disabled control statically without responsive overlay or list markup', function (): void {
    $html = renderTimePicker(['disabled' => true]);
    $trigger = timePickerOpeningTag($html, 'trigger');

    expect($html)->toMatch('/<span class="lyra-datepicker">\s*<button\b.*?<\/button>\s*<\/span>/s')
        ->and($trigger)->toContain('disabled')
        ->and($html)->not->toContain('<template x-if="!mobile">')
        ->and($html)->not->toContain('<template x-if="mobile">')
        ->and($html)->not->toContain('lyraPopover(')
        ->and($html)->not->toContain('lyraBottomSheet(')
        ->and($html)->not->toContain('class="lyra-timelist"');
});

it('uses non colliding open aliases in both responsive branches', function (): void {
    $html = renderTimePicker();

    expect(substr_count($html, 'get pickerOpen() { return open }'))->toBe(2)
        ->and(substr_count($html, 'set pickerOpen(v) { open = v }'))->toBe(2)
        ->and(substr_count($html, 'x-model="pickerOpen"'))->toBe(2);
});

it('keeps hostile option and label values inside JSON literals', function (): void {
    $payload = "'); window.pwned=1; //\"\\</script>";
    $html = renderTimePicker([
        'defaultValue' => $payload,
        'locale' => $payload,
        'placeholder' => $payload,
        'step' => 7,
        'min' => $payload,
        'max' => $payload,
        'labels' => [
            'placeholder' => $payload,
            'popover' => $payload,
            'sheetTitle' => $payload,
            'close' => $payload,
        ],
    ]);
    $root = timePickerOpeningTag($html, 'root');
    preg_match_all('/\bx-data="([^"]*)"/', $html, $dataExpressions);

    expect(timePickerOptions($html))->toBe([
        'defaultValue' => $payload,
        'step' => 7,
        'min' => $payload,
        'max' => $payload,
        'locale' => $payload,
        'placeholder' => $payload,
    ])->and(substr_count($root, 'x-data='))->toBe(1)
        ->and($dataExpressions[1])->not->toBeEmpty()
        ->and($html)->not->toContain('</script>')
        ->and($root)->toContain('\\u003C/script\\u003E');

    foreach ($dataExpressions[1] as $expression) {
        $decoded = html_entity_decode($expression, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $withoutEscapedPayload = str_replace("\\'); window.pwned=1; //", '', $decoded);

        expect($withoutEscapedPayload)->not->toContain("'); window.pwned=1; //");
    }
});

it('splits model attributes onto the modelable root and preserves native attributes', function (): void {
    $html = renderTimePicker([
        'id' => 'travel-time',
        'class' => 'wide elevated',
        'wire:model.live' => 'time',
        'x-model.fill' => 'selectedTime',
        'data-track' => 'time-picker',
        'aria-label' => 'Travel time',
    ]);
    $root = timePickerOpeningTag($html, 'root');

    expect(timePickerRootClass($html))->toBe('wide elevated')
        ->and($root)->toContain('wire:model.live="time"')
        ->and($root)->toContain('x-model.fill="selectedTime"')
        ->and($root)->toContain('data-track="time-picker"')
        ->and($root)->toContain('aria-label="Travel time"')
        ->and($root)->not->toContain('id="travel-time"')
        ->and(timePickerOpeningTag($html, 'trigger'))->toContain('id="travel-time"')
        ->and(strpos($root, 'x-modelable="selected"'))->toBeLessThan(strpos($root, 'wire:model.live="time"'));
});

it('supports Livewire model binding through selected without a hidden input', function (): void {
    $component = new class extends Component
    {
        public ?string $time = '09:30';

        public function render(): string
        {
            return <<<'BLADE'
                <x-lyra::time-picker :default-value="$time" wire:model.live="time" />
            BLADE;
        }
    };

    $html = Livewire::test($component)->html();
    $root = timePickerOpeningTag($html, 'root');

    expect($root)->toContain('x-modelable="selected"')
        ->and($root)->toContain('wire:model.live="time"')
        ->and(timePickerOptions($html)['defaultValue'])->toBe('09:30')
        ->and(timePickerOpeningTag($html, 'trigger'))->not->toContain('wire:model')
        ->and($html)->not->toContain('type="hidden"');
});

it('renders namespaced and short syntax identically', function (): void {
    $namespaced = Blade::render('<x-lyra::time-picker id="arrival" label="Arrival" disabled />');
    $short = Blade::render('<lyra:time-picker id="arrival" label="Arrival" disabled />');

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('lyraTimePicker(');
});
