@props([
    'options' => [],
    'value' => null,
    'defaultOpen' => false,
    'label' => null,
    'hint' => null,
    'error' => null,
    'placeholder' => null,
    'searchPlaceholder' => null,
    'emptyMessage' => null,
    'searchLabel' => null,
    'disabled' => false,
    'factory' => 'lyraCombobox',
    'extraOptions' => [],
])

{{--
    APG keyboard, focus, placement, model synchronization, and axe coverage live in the upstream
    lyraCombobox browser suite. This server-render suite verifies the complete ARIA scaffold and
    binding hooks instead of pretending to execute JavaScript.

    Options intentionally render through x-for: filtering and order belong to the data-driven
    binding. The optionIcon and optionTrailing named slots render inside that template, where the
    Alpine option, index, and filteredIndex variables are available to consumer markup.

    factory and extraOptions exist for internal package composition with specialized combobox
    factories. The factory whitelist is deliberate: unsupported names fall back to lyraCombobox
    instead of injecting an arbitrary x-data expression. extraOptions are merged into the same
    safely encoded options object, and component-owned combobox keys always take precedence.
--}}
@php
    $hasLabel = $label !== null && $label !== '';
    $hasError = $error !== null && $error !== '';
    $hasHint = ! $hasError && $hint !== null && $hint !== '';
    $hasMessage = $hasError || $hasHint;
    $hasField = $hasLabel || $hasMessage;
    $triggerId = $attributes->get('id') ?? 'lyra-combobox-'.uniqid();
    $messageId = $hasMessage ? 'lyra-combobox-message-'.uniqid() : null;
    $resolvedSearchLabel = $searchLabel ?? $searchPlaceholder ?? 'Search…';
    $allowedFactories = ['lyraCombobox', 'lyraTimeZonePicker'];
    $resolvedFactory = in_array($factory, $allowedFactories, true) ? $factory : 'lyraCombobox';
    $modelAttributes = $attributes->whereStartsWith(['wire:model', 'x-model']);
    $rootAttributes = $attributes
        ->whereDoesntStartWith(['wire:model', 'x-model'])
        ->except(['id', 'class', 'x-data']);
    $consumerClasses = preg_split('/\s+/', trim((string) $attributes->get('class', ''))) ?: [];
    $rootClasses = array_values(array_unique(array_filter(
        [...($hasField ? ['lyra-field'] : []), ...$consumerClasses],
        static fn (string $class): bool => $class !== '',
    )));
    $rootClass = implode(' ', $rootClasses);

    $resolvedOptions = [];

    if (is_array($options)) {
        foreach ($options as $option) {
            if (
                ! is_array($option)
                || ! array_key_exists('value', $option)
                || ! is_string($option['value'])
                || ! array_key_exists('label', $option)
                || ! is_string($option['label'])
            ) {
                continue;
            }

            $resolvedOption = [
                'value' => $option['value'],
                'label' => $option['label'],
            ];

            foreach (['hint', 'group', 'keywords'] as $optionalKey) {
                if (array_key_exists($optionalKey, $option) && is_string($option[$optionalKey])) {
                    $resolvedOption[$optionalKey] = $option[$optionalKey];
                }
            }

            $resolvedOptions[] = $resolvedOption;
        }
    }

    $componentOptionKeys = array_fill_keys([
        'options',
        'value',
        'open',
        'placeholder',
        'searchPlaceholder',
        'emptyMessage',
        'disabled',
        'id',
        'error',
        'describedBy',
    ], true);
    $bindingOptions = is_array($extraOptions)
        ? array_diff_key($extraOptions, $componentOptionKeys)
        : [];
    $bindingOptions['options'] = $resolvedOptions;

    if (is_string($value)) {
        $bindingOptions['value'] = $value;
    }

    $bindingOptions['open'] = (bool) $defaultOpen;

    if (is_string($placeholder)) {
        $bindingOptions['placeholder'] = $placeholder;
    }

    if (is_string($searchPlaceholder)) {
        $bindingOptions['searchPlaceholder'] = $searchPlaceholder;
    }

    if (is_string($emptyMessage)) {
        $bindingOptions['emptyMessage'] = $emptyMessage;
    }

    $bindingOptions['disabled'] = (bool) $disabled;
    $bindingOptions['id'] = (string) $triggerId;
    $bindingOptions['error'] = $hasError;

    if ($messageId !== null) {
        $bindingOptions['describedBy'] = $messageId;
    }

    $optionsLiteral = json_encode(
        $bindingOptions,
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
    x-data="{{ $resolvedFactory.'('.$optionsLiteral.')' }}"
    x-modelable="value"
    @if ($rootClass !== '')
        class="{{ $rootClass }}"
    @endif
    {{ $modelAttributes }}
    {{ $rootAttributes }}
>
    @if ($hasLabel)
        <label class="lyra-label" for="{{ $triggerId }}">{{ $label }}</label>
    @endif

    <div class="lyra-combobox">
        <button
            type="button"
            id="{{ $triggerId }}"
            class="lyra-input lyra-combobox__trigger"
            x-bind="trigger"
        >
            <span x-bind="triggerValue"></span>
        </button>
        <div class="lyra-combobox__pop" x-bind="pop">
            <div class="lyra-combobox__search">
                <input x-bind="search" aria-label="{{ $resolvedSearchLabel }}">
            </div>
            <div class="lyra-combobox__list" x-bind="list">
                <span
                    class="lyra-combobox__empty"
                    x-bind="empty"
                    x-text="emptyMessage"
                ></span>
                <template x-for="({ option, index }, filteredIndex) in filtered()" :key="option.value">
                    <div>
                        <template x-if="showGroup(filteredIndex)">
                            <span
                                class="lyra-combobox__group"
                                role="presentation"
                                x-text="option.group"
                            ></span>
                        </template>
                        <button
                            class="lyra-combobox__option"
                            type="button"
                            tabindex="-1"
                            role="option"
                            :id="optionId(index)"
                            :class="optionClass(filteredIndex)"
                            :aria-selected="optionSelected(option)"
                            @mouseenter="setActive(filteredIndex)"
                            @click="pick(option)"
                        >
                            @isset($optionIcon)
                                {{ $optionIcon }}
                            @endisset
                            <span class="lyra-combobox__option-label">
                                <span x-text="option.label"></span>
                                <span
                                    class="lyra-combobox__option-hint"
                                    x-show="option.hint"
                                    x-text="option.hint"
                                ></span>
                            </span>
                            <span class="lyra-combobox__trailing">
                                @isset($optionTrailing)
                                    {{ $optionTrailing }}
                                @endisset
                            </span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    @if ($hasError)
        <span id="{{ $messageId }}" class="lyra-hint lyra-hint--error">{{ $error }}</span>
    @elseif ($hasHint)
        <span id="{{ $messageId }}" class="lyra-hint">{{ $hint }}</span>
    @endif
</div>
