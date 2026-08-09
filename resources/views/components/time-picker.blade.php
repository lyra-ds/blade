@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'defaultValue' => null,
    'placeholder' => null,
    'step' => 30,
    'min' => null,
    'max' => null,
    'locale' => 'en-US',
    'labels' => [],
    'disabled' => false,
])

{{--
    The responsive controls are served in x-if templates because matchMedia is the only
    authoritative branch selector. Without Alpine neither branch renders; choosing one on the
    server would give half of users the wrong layout. The disabled control remains static. Alpine
    scope aliases add classless divs that React does not need; they prevent nested modelables from
    resolving open back to themselves.

    The option list and trigger text are runtime-only because Intl formats each time with the
    consumer locale, which PHP cannot reproduce without ext-intl. The list binding currently fixes
    its accessible name to "Time options" and has no label option, so the same string is served here
    instead of exposing a Blade prop that Alpine would overwrite after boot.
--}}
@php
    $hasLabel = $label !== null && $label !== '';
    $hasError = $error !== null && $error !== '';
    $hasHint = ! $hasError && $hint !== null && $hint !== '';
    $hasField = $hasLabel || $hasError || $hasHint;
    $resolvedLabels = array_merge([
        'placeholder' => 'Select time',
        'popover' => 'Time picker',
        'sheetTitle' => 'Select time',
        'close' => 'Close',
    ], is_array($labels) ? $labels : []);
    $resolvedPlaceholder = $placeholder ?? $resolvedLabels['placeholder'];
    $sheetTitle = $label ?? $resolvedLabels['sheetTitle'];
    $popoverLabel = $resolvedLabels['popover'];
    $closeLabel = $resolvedLabels['close'];
    $triggerId = $attributes->get('id') ?? 'lyra-time-picker-'.uniqid();
    $modelAttributes = $attributes->whereStartsWith(['wire:model', 'x-model']);
    $rootAttributes = $attributes
        ->whereDoesntStartWith(['wire:model', 'x-model'])
        ->except('id');

    if ($hasField) {
        $rootAttributes = $rootAttributes->class('lyra-field');
    }

    $options = [];

    if (is_numeric($step)) {
        $numericStep = (float) $step;

        if (is_finite($numericStep)) {
            $integerStep = (int) $numericStep;

            if ($integerStep >= 1) {
                $options['step'] = $integerStep;
            }
        }
    }

    if ($min !== null) {
        $options['min'] = $min;
    }

    if ($max !== null) {
        $options['max'] = $max;
    }

    $options['locale'] = $locale;
    $options['placeholder'] = $resolvedPlaceholder;

    if ($defaultValue !== null) {
        $options = ['defaultValue' => $defaultValue] + $options;
    }

    $optionsLiteral = json_encode(
        $options,
        JSON_THROW_ON_ERROR
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE,
    );
@endphp

<div
    x-data="{{ 'lyraTimePicker('.$optionsLiteral.')' }}"
    x-modelable="selected"
    {{ $modelAttributes }}
    {{ $rootAttributes }}
>
    @if ($hasLabel)
        <label class="lyra-label" for="{{ $triggerId }}">{{ $label }}</label>
    @endif

    @if ($disabled)
        <span class="lyra-datepicker">
            <button
                type="button"
                id="{{ $triggerId }}"
                class="lyra-input lyra-datepicker__btn{{ $hasError ? ' lyra-input--error' : '' }}"
                disabled
            >
                <svg
                    width="15"
                    height="15"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <circle cx="12" cy="12" r="10" />
                    <path d="M12 6v6l4 2" />
                </svg>
                <span
                    :class="{ 'lyra-datepicker__ph': !hasSelection() }"
                    x-text="triggerText()"
                ></span>
            </button>
        </span>
    @else
        <template x-if="!mobile">
            <div x-data="{ get pickerOpen() { return open }, set pickerOpen(v) { open = v } }">
                <x-lyra::popover
                    :aria-label="$popoverLabel"
                    :wrap-trigger="false"
                    x-model="pickerOpen"
                    class="lyra-datepicker"
                >
                    <x-slot:trigger>
                        <button
                            type="button"
                            id="{{ $triggerId }}"
                            class="lyra-input lyra-datepicker__btn{{ $hasError ? ' lyra-input--error' : '' }}"
                            x-bind="trigger"
                        >
                            <svg
                                width="15"
                                height="15"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 6v6l4 2" />
                            </svg>
                            <span
                                :class="{ 'lyra-datepicker__ph': !hasSelection() }"
                                x-text="triggerText()"
                            ></span>
                        </button>
                    </x-slot:trigger>

                    <div
                        class="lyra-timelist"
                        aria-label="Time options"
                        x-bind="list"
                    >
                        <template x-for="time in options()" :key="time">
                            <button
                                class="lyra-timelist__item"
                                type="button"
                                role="option"
                                :class="{ 'lyra-timelist__item--selected': time === selected }"
                                :aria-selected="time === selected ? 'true' : false"
                                @click="pick(time)"
                                x-text="formatTime(time)"
                            ></button>
                        </template>
                    </div>
                </x-lyra::popover>
            </div>
        </template>

        <template x-if="mobile">
            <div x-data="{ get pickerOpen() { return open }, set pickerOpen(v) { open = v } }">
                <span class="lyra-datepicker">
                    <button
                        type="button"
                        id="{{ $triggerId }}"
                        class="lyra-input lyra-datepicker__btn{{ $hasError ? ' lyra-input--error' : '' }}"
                        @click="open = true"
                    >
                        <svg
                            width="15"
                            height="15"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <circle cx="12" cy="12" r="10" />
                            <path d="M12 6v6l4 2" />
                        </svg>
                        <span
                            :class="{ 'lyra-datepicker__ph': !hasSelection() }"
                            x-text="triggerText()"
                        ></span>
                    </button>
                </span>

                <x-lyra::bottom-sheet
                    :title="$sheetTitle"
                    :close-label="$closeLabel"
                    x-model="pickerOpen"
                >
                    <div
                        class="lyra-timelist"
                        aria-label="Time options"
                        x-bind="list"
                    >
                        <template x-for="time in options()" :key="time">
                            <button
                                class="lyra-timelist__item"
                                type="button"
                                role="option"
                                :class="{ 'lyra-timelist__item--selected': time === selected }"
                                :aria-selected="time === selected ? 'true' : false"
                                @click="pick(time)"
                                x-text="formatTime(time)"
                            ></button>
                        </template>
                    </div>
                </x-lyra::bottom-sheet>
            </div>
        </template>
    @endif

    @if ($hasError)
        <span class="lyra-hint lyra-hint--error">{{ $error }}</span>
    @elseif ($hasHint)
        <span class="lyra-hint">{{ $hint }}</span>
    @endif
</div>
