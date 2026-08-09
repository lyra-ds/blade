@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'defaultValue' => null,
    'placeholder' => null,
    'min' => null,
    'max' => null,
    'locale' => 'en-US',
    'labels' => [],
    'disabled' => false,
    'name' => null,
])

{{--
    The responsive controls are served in x-if templates because matchMedia is the only
    authoritative branch selector. Without Alpine neither branch renders; choosing one on the
    server would give half of users the wrong layout. The disabled control remains static. Alpine
    scope aliases add classless divs that React does not need; they prevent nested modelables from
    resolving open/selected back to themselves.
--}}
@php
    $hasLabel = $label !== null && $label !== '';
    $hasError = $error !== null && $error !== '';
    $hasHint = ! $hasError && $hint !== null && $hint !== '';
    $hasField = $hasLabel || $hasError || $hasHint;
    $resolvedLabels = array_merge([
        'placeholder' => 'Select date',
        'popover' => 'Date picker',
        'sheetTitle' => 'Select date',
        'close' => 'Close',
        'calendar' => [],
    ], is_array($labels) ? $labels : []);
    $resolvedPlaceholder = $placeholder ?? $resolvedLabels['placeholder'];
    $calendarLabels = is_array($resolvedLabels['calendar']) ? $resolvedLabels['calendar'] : [];
    $sheetTitle = $label ?? $resolvedLabels['sheetTitle'];
    $popoverLabel = $resolvedLabels['popover'];
    $closeLabel = $resolvedLabels['close'];
    $triggerId = $attributes->get('id') ?? 'lyra-date-picker-'.uniqid();
    $modelAttributes = $attributes->whereStartsWith(['wire:model', 'x-model']);
    $rootAttributes = $attributes
        ->whereDoesntStartWith(['wire:model', 'x-model'])
        ->except('id');

    if ($hasField) {
        $rootAttributes = $rootAttributes->class('lyra-field');
    }

    $options = [
        'locale' => $locale,
        'placeholder' => $resolvedPlaceholder,
    ];

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
    x-data="{{ 'lyraDatePicker('.$optionsLiteral.')' }}"
    x-modelable="selected"
    @if ($name !== null)
        @lyra:change="$refs.native.value = $event.detail.value"
    @endif
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
                    <path d="M8 2v4M16 2v4" />
                    <rect width="18" height="18" x="3" y="4" rx="2" />
                    <path d="M3 10h18" />
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
                                <path d="M8 2v4M16 2v4" />
                                <rect width="18" height="18" x="3" y="4" rx="2" />
                                <path d="M3 10h18" />
                            </svg>
                            <span
                                :class="{ 'lyra-datepicker__ph': !hasSelection() }"
                                x-text="triggerText()"
                            ></span>
                        </button>
                    </x-slot:trigger>

                    <div x-data="{ get pickerSelected() { return selected }, set pickerSelected(v) { selected = v } }">
                        <x-lyra::calendar
                            :min="$min"
                            :max="$max"
                            :locale="$locale"
                            :labels="$calendarLabels"
                            x-model="pickerSelected"
                        />
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
                            <path d="M8 2v4M16 2v4" />
                            <rect width="18" height="18" x="3" y="4" rx="2" />
                            <path d="M3 10h18" />
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
                    <div class="lyra-cal--sheet">
                        <div x-data="{ get pickerSelected() { return selected }, set pickerSelected(v) { selected = v } }">
                            <x-lyra::calendar
                                :min="$min"
                                :max="$max"
                                :locale="$locale"
                                :labels="$calendarLabels"
                                x-model="pickerSelected"
                            />
                        </div>
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

    @if ($name !== null)
        <input
            type="hidden"
            name="{{ $name }}"
            value="{{ $defaultValue }}"
            x-ref="native"
        >
    @endif
</div>
