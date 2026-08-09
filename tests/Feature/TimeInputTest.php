<?php

use Illuminate\Support\Facades\Blade;
use Livewire\Component;
use Livewire\Livewire;

function renderTimeInput(array $props = []): string
{
    $label = $props['label'] ?? null;
    $hint = $props['hint'] ?? null;
    $error = $props['error'] ?? null;
    $value = $props['value'] ?? null;
    $defaultValue = $props['defaultValue'] ?? null;
    $step = $props['step'] ?? 15;
    $min = $props['min'] ?? null;
    $max = $props['max'] ?? null;
    $size = $props['size'] ?? 'md';
    $invalid = $props['invalid'] ?? false;
    $labels = $props['labels'] ?? [];
    $disabled = $props['disabled'] ?? false;
    unset(
        $props['label'],
        $props['hint'],
        $props['error'],
        $props['value'],
        $props['defaultValue'],
        $props['step'],
        $props['min'],
        $props['max'],
        $props['size'],
        $props['invalid'],
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
            '<x-lyra::time-input :label="$label" :hint="$hint" :error="$error" :value="$value" :default-value="$defaultValue" :step="$step" :min="$min" :max="$max" :size="$size" :invalid="$invalid" :labels="$labels" :disabled="$disabled" %s />',
            $attributes,
        ),
        compact(
            'label',
            'hint',
            'error',
            'value',
            'defaultValue',
            'step',
            'min',
            'max',
            'size',
            'invalid',
            'labels',
            'disabled',
        ),
    );
}

function timeInputOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'field' => '/<div\b(?=[^>]*\bclass="lyra-field")[^>]*>/',
        'label' => '/<label\b(?=[^>]*\bclass="lyra-label")[^>]*>/',
        'control' => '/<span\b(?=[^>]*\bclass="lyra-timeinput")[^>]*>/',
        'input' => '/<input\b[^>]*>/',
        'steppers' => '/<span\b(?=[^>]*\bclass="lyra-timeinput__steppers")[^>]*>/',
        'later' => '/<button\b(?=[^>]*\baria-label="(?:Later|Increase time)")[^>]*>/',
        'earlier' => '/<button\b(?=[^>]*\baria-label="(?:Earlier|Decrease time)")[^>]*>/',
        'hint' => '/<span\b(?=[^>]*\bclass="lyra-hint(?: lyra-hint--error)?")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function timeInputClass(string $html, string $target): string
{
    $tag = $target === 'step'
        ? timeInputOpeningTag($html, 'later')
        : timeInputOpeningTag($html, $target);
    $matched = preg_match('/\bclass="([^"]*)"/', $tag, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function timeInputAttribute(string $html, string $target, string $attribute): ?string
{
    $tag = timeInputOpeningTag($html, $target);
    $matched = preg_match(
        sprintf('/\b%s="([^"]*)"/', preg_quote($attribute, '/')),
        $tag,
        $matches,
    );

    return $matched === 1 ? $matches[1] : null;
}

dataset('time input class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/time-input.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the time-input class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React class strings', function (array $case): void {
    expect(timeInputClass(renderTimeInput($case['props']), $case['target']))
        ->toBe($case['expected_class']);
})->with('time input class emission');

it('renders namespaced and short syntax identically', function (): void {
    $namespaced = Blade::render('<x-lyra::time-input id="start-time" label="Start time" value="09:30" />');
    $short = Blade::render('<lyra:time-input id="start-time" label="Start time" value="09:30" />');

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('class="lyra-timeinput"');
});

it('serves the empty spinbutton state and default limits', function (): void {
    $html = renderTimeInput();
    $input = timeInputOpeningTag($html, 'input');

    expect($input)->toContain('type="text"')
        ->and($input)->toContain('role="spinbutton"')
        ->and($input)->toContain('inputmode="numeric"')
        ->and($input)->toContain('autocomplete="off"')
        ->and($input)->toContain('placeholder="--:--"')
        ->and($input)->toContain('value=""')
        ->and(timeInputAttribute($html, 'input', 'aria-valuemin'))->toBe('0')
        ->and(timeInputAttribute($html, 'input', 'aria-valuemax'))->toBe('1439')
        ->and($input)->not->toContain('aria-valuenow=')
        ->and($input)->not->toContain('aria-valuetext=')
        ->and($input)->not->toContain('aria-invalid=');
});

it('serves selected value ARIA and minute limits from time props', function (): void {
    $html = renderTimeInput([
        'value' => '09:30',
        'min' => '08:15',
        'max' => '17:45',
    ]);

    expect(timeInputAttribute($html, 'input', 'value'))->toBe('09:30')
        ->and(timeInputAttribute($html, 'input', 'aria-valuemin'))->toBe('495')
        ->and(timeInputAttribute($html, 'input', 'aria-valuemax'))->toBe('1065')
        ->and(timeInputAttribute($html, 'input', 'aria-valuenow'))->toBe('570')
        ->and(timeInputAttribute($html, 'input', 'aria-valuetext'))->toBe('9 hours and 30 minutes');
});

it('serves defaultValue as the initial value when value is absent', function (): void {
    $html = renderTimeInput(['defaultValue' => '07:05']);

    expect(timeInputAttribute($html, 'input', 'value'))->toBe('07:05')
        ->and(timeInputAttribute($html, 'input', 'aria-valuenow'))->toBe('425')
        ->and(timeInputAttribute($html, 'input', 'aria-valuetext'))->toBe('7 hours and 5 minutes');
});

it('serves both stepper controls with static behavior and accessible labels', function (): void {
    $html = renderTimeInput([
        'labels' => [
            'later' => 'Increase time',
            'earlier' => 'Decrease time',
        ],
    ]);
    $later = timeInputOpeningTag($html, 'later');
    $earlier = timeInputOpeningTag($html, 'earlier');

    expect($later)->toContain('type="button"')
        ->and($later)->toContain('tabindex="-1"')
        ->and($later)->toContain('aria-label="Increase time"')
        ->and($later)->toContain('x-bind="up"')
        ->and($earlier)->toContain('type="button"')
        ->and($earlier)->toContain('tabindex="-1"')
        ->and($earlier)->toContain('aria-label="Decrease time"')
        ->and($earlier)->toContain('x-bind="down"')
        ->and(substr_count($html, 'class="lyra-timeinput__step"'))->toBe(2)
        ->and(substr_count($html, '<svg'))->toBe(2)
        ->and(substr_count($html, 'aria-hidden="true"'))->toBe(2);
});

it('disables the input and steppers and hides their container from accessibility APIs', function (): void {
    $html = renderTimeInput(['disabled' => true]);

    expect(timeInputOpeningTag($html, 'input'))->toContain('disabled')
        ->and(timeInputOpeningTag($html, 'later'))->toContain('disabled')
        ->and(timeInputOpeningTag($html, 'earlier'))->toContain('disabled')
        ->and(timeInputOpeningTag($html, 'steppers'))->toContain('aria-hidden="true"');
});

it('omits disabled-only markup when enabled', function (): void {
    $html = renderTimeInput();

    expect(timeInputOpeningTag($html, 'input'))->not->toContain('disabled')
        ->and(timeInputOpeningTag($html, 'later'))->not->toContain('disabled')
        ->and(timeInputOpeningTag($html, 'earlier'))->not->toContain('disabled')
        ->and(timeInputOpeningTag($html, 'steppers'))->not->toContain('aria-hidden=');
});

it('wires the field label and hint through unique ids', function (): void {
    $html = renderTimeInput([
        'label' => 'Start time',
        'hint' => 'Use a 24-hour time',
        'aria-describedby' => 'schedule-help',
    ]);
    $inputId = timeInputAttribute($html, 'input', 'id');
    $describedBy = timeInputAttribute($html, 'input', 'aria-describedby');
    $messageId = substr((string) $describedBy, strlen('schedule-help '));

    expect($html)->toContain('<div class="lyra-field">')
        ->and($inputId)->toMatch('/^lyra-time-input-/')
        ->and(timeInputOpeningTag($html, 'label'))->toContain('for="'.$inputId.'"')
        ->and($html)->toContain('>Start time</label>')
        ->and($describedBy)->toMatch('/^schedule-help lyra-time-input-message-/')
        ->and($html)->toContain(sprintf(
            '<span id="%s" class="lyra-hint">Use a 24-hour time</span>',
            $messageId,
        ));
});

it('replaces hint with error and serves invalid state', function (): void {
    $html = renderTimeInput([
        'hint' => 'Use a 24-hour time',
        'error' => 'Time is required',
    ]);
    $messageId = timeInputAttribute($html, 'input', 'aria-describedby');

    expect(timeInputAttribute($html, 'input', 'aria-invalid'))->toBe('true')
        ->and(timeInputClass($html, 'input'))->toBe('lyra-input lyra-input--error')
        ->and($html)->toContain(sprintf(
            '<span id="%s" class="lyra-hint lyra-hint--error">Time is required</span>',
            $messageId,
        ))
        ->and($html)->not->toContain('Use a 24-hour time');
});

it('preserves consumer ARIA overrides when the component has no served invalid state', function (): void {
    $html = renderTimeInput([
        'aria-invalid' => 'grammar',
        'aria-valuetext' => 'Half past nine',
        'value' => '09:30',
    ]);

    expect(timeInputAttribute($html, 'input', 'aria-invalid'))->toBe('grammar')
        ->and(timeInputAttribute($html, 'input', 'aria-valuetext'))->toBe('Half past nine');
});

it('wires only authoritative Alpine options, binding objects, and modelable state', function (): void {
    $html = renderTimeInput([
        'value' => '09:30',
        'step' => 30,
        'min' => '08:00',
        'max' => '18:00',
        'invalid' => true,
    ]);
    $control = timeInputOpeningTag($html, 'control');

    expect($control)->toContain("x-data=\"lyraTimeInput({ defaultValue: '09:30', step: 30, min: '08:00', max: '18:00', invalid: true })\"")
        ->and($control)->toContain('x-modelable="selected"')
        ->and($control)->not->toContain('x-bind="root"')
        ->and(timeInputOpeningTag($html, 'input'))->toContain('x-bind="input"')
        ->and(timeInputOpeningTag($html, 'later'))->toContain('x-bind="up"')
        ->and(timeInputOpeningTag($html, 'earlier'))->toContain('x-bind="down"');
});

it('escapes string options for JavaScript and HTML contexts', function (): void {
    $value = "a'b\\c\r\nd";
    $html = renderTimeInput([
        'value' => $value,
        'min' => "e'f\\g",
        'max' => '23:59',
    ]);
    $control = timeInputOpeningTag($html, 'control');

    expect($control)->toContain("defaultValue: 'a\\'b\\\\c\\r\\nd'")
        ->and($control)->toContain("min: 'e\\'f\\\\g'")
        ->and(html_entity_decode(
            (string) timeInputAttribute($html, 'input', 'value'),
            ENT_QUOTES,
        ))->toBe("a'b\\c\r\nd");
});

it('supports Livewire model binding through selected', function (): void {
    $component = new class extends Component
    {
        public ?string $time = '09:30';

        public function render(): string
        {
            return <<<'BLADE'
                <x-lyra::time-input label="Start time" :value="$time" wire:model.live="time" />
            BLADE;
        }
    };

    $html = Livewire::test($component)->html();
    $control = timeInputOpeningTag($html, 'control');

    expect($control)->toContain('x-modelable="selected"')
        ->and($control)->toContain('wire:model.live="time"')
        ->and($control)->toContain("defaultValue: '09:30'")
        ->and(timeInputOpeningTag($html, 'input'))->not->toContain('wire:model');
});

it('passes native attributes to the input and keeps user classes last', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::time-input
            id="meeting-time"
            name="meeting_time"
            value="09:30"
            required
            readonly
            class="first second"
            data-track="schedule"
            aria-label="Meeting time"
        />
        BLADE);
    $input = timeInputOpeningTag($html, 'input');
    $control = timeInputOpeningTag($html, 'control');

    expect(timeInputClass($html, 'input'))->toBe('lyra-input first second')
        ->and($input)->toContain('id="meeting-time"')
        ->and($input)->toContain('name="meeting_time"')
        ->and($input)->toContain('required')
        ->and($input)->toContain('readonly')
        ->and($input)->toContain('data-track="schedule"')
        ->and($input)->toContain('aria-label="Meeting time"')
        ->and($control)->not->toContain('name="meeting_time"')
        ->and($control)->not->toContain('data-track="schedule"');
});
