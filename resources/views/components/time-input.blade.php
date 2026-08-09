@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'value' => null,
    'defaultValue' => null,
    'step' => 15,
    'min' => null,
    'max' => null,
    'size' => 'md',
    'invalid' => false,
    'labels' => [],
    'disabled' => false,
])

{{-- Parsing and interaction stay in lyraTimeInput; this template serves the complete first render. --}}
@php
    $hasLabel = (bool) $label;
    $hasError = (bool) $error;
    $hasHint = ! $hasError && (bool) $hint;
    $hasMessage = $hasError || $hasHint;
    $hasField = $hasLabel || $hasMessage;
    $isInvalid = $hasError || (bool) $invalid;
    $selected = $value ?? $defaultValue;
    $resolvedLabels = array_merge([
        'later' => 'Later',
        'earlier' => 'Earlier',
    ], is_array($labels) ? $labels : []);
    $modelAttributes = $attributes->whereStartsWith(['wire:model', 'x-model']);
    $nativeAttributes = $attributes->whereDoesntStartWith(['wire:model', 'x-model']);
    $inputId = $nativeAttributes->get('id') ?? 'lyra-time-input-'.uniqid();
    $messageId = 'lyra-time-input-message-'.uniqid();
    $consumerDescribedBy = $nativeAttributes->get('aria-describedby');
    $describedBy = $hasMessage
        ? trim(implode(' ', array_filter([$consumerDescribedBy, $messageId])))
        : $consumerDescribedBy;
    $ariaInvalid = $isInvalid ? 'true' : $nativeAttributes->get('aria-invalid');
    $placeholder = $nativeAttributes->get('placeholder', '--:--');

    $minutesFromTime = static function (mixed $time): ?int {
        if (! is_string($time) || $time === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2}):(\d{1,2})$/', $time, $matches) !== 1) {
            return null;
        }

        $hours = (int) $matches[1];
        $minutes = (int) $matches[2];

        return $hours >= 0 && $hours <= 23 && $minutes >= 0 && $minutes <= 59
            ? ($hours * 60) + $minutes
            : null;
    };

    $lowerLimit = $minutesFromTime($min) ?? 0;
    $upperLimit = $minutesFromTime($max) ?? 1439;
    $selectedMinutes = $minutesFromTime($selected);
    $consumerValueText = $nativeAttributes->get('aria-valuetext');
    $generatedValueText = $selectedMinutes === null
        ? null
        : intdiv($selectedMinutes, 60).' hours and '.($selectedMinutes % 60).' minutes';
    $ariaValueText = $consumerValueText ?? $generatedValueText;

    $inputAttributes = $nativeAttributes
        ->except([
            'id',
            'type',
            'role',
            'inputmode',
            'autocomplete',
            'placeholder',
            'aria-invalid',
            'aria-describedby',
            'aria-valuemin',
            'aria-valuemax',
            'aria-valuenow',
            'aria-valuetext',
        ])
        ->class([
            'lyra-input',
            'lyra-input--sm' => $size === 'sm',
            'lyra-input--lg' => $size === 'lg',
            'lyra-input--error' => $isInvalid,
        ]);

    $escapeJsString = static function (mixed $string): string {
        $escaped = str_replace(
            ['\\', "'", "\r", "\n"],
            ['\\\\', "\\'", '\\r', '\\n'],
            (string) $string,
        );

        return htmlspecialchars($escaped, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
    };

    $options = [];

    if ($selected !== null) {
        $options[] = "defaultValue: '".$escapeJsString($selected)."'";
    }

    $options[] = 'step: '.(int) $step;

    if ($min !== null) {
        $options[] = "min: '".$escapeJsString($min)."'";
    }

    if ($max !== null) {
        $options[] = "max: '".$escapeJsString($max)."'";
    }

    $options[] = 'invalid: '.($isInvalid ? 'true' : 'false');
    $optionsLiteral = implode(', ', $options);
@endphp

@if ($hasField)
<div class="lyra-field">
    @if ($hasLabel)
    <label class="lyra-label" for="{{ $inputId }}">{{ $label }}</label>
    @endif
@endif
    <span
        class="lyra-timeinput"
        x-data="lyraTimeInput({!! '{ '.$optionsLiteral.' }' !!})"
        x-modelable="selected"
        {{ $modelAttributes }}
    >
        <input
            type="text"
            role="spinbutton"
            inputmode="numeric"
            autocomplete="off"
            placeholder="{{ $placeholder }}"
            value="{{ $selected }}"
            id="{{ $inputId }}"
            aria-valuemin="{{ $lowerLimit }}"
            aria-valuemax="{{ $upperLimit }}"
            @if ($selectedMinutes !== null)
                aria-valuenow="{{ $selectedMinutes }}"
            @endif
            @if ($ariaValueText !== null)
                aria-valuetext="{{ $ariaValueText }}"
            @endif
            @if ($ariaInvalid !== null)
                aria-invalid="{{ $ariaInvalid }}"
            @endif
            @if ($describedBy !== null && $describedBy !== '')
                aria-describedby="{{ $describedBy }}"
            @endif
            @if ($disabled)
                disabled
            @endif
            x-bind="input"
            {{ $inputAttributes }}
        >
        <span
            class="lyra-timeinput__steppers"
            @if ($disabled)
                aria-hidden="true"
            @endif
        >
            <button
                type="button"
                tabindex="-1"
                class="lyra-timeinput__step"
                aria-label="{{ $resolvedLabels['later'] }}"
                @if ($disabled)
                    disabled
                @endif
                x-bind="up"
            >
                <svg
                    width="12"
                    height="12"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="m18 15-6-6-6 6" />
                </svg>
            </button>
            <button
                type="button"
                tabindex="-1"
                class="lyra-timeinput__step"
                aria-label="{{ $resolvedLabels['earlier'] }}"
                @if ($disabled)
                    disabled
                @endif
                x-bind="down"
            >
                <svg
                    width="12"
                    height="12"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2.5"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </button>
        </span>
    </span>
@if ($hasField)
    @if ($hasError)
    <span id="{{ $messageId }}" class="lyra-hint lyra-hint--error">{{ $error }}</span>
    @elseif ($hasHint)
    <span id="{{ $messageId }}" class="lyra-hint">{{ $hint }}</span>
    @endif
</div>
@endif
