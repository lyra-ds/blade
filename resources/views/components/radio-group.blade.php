@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'options' => [],
    'value' => null,
    'defaultValue' => null,
    'name' => null,
    'direction' => 'column',
])

@php
    $hasLabel = (bool) $label;
    $hasError = (bool) $error;
    $hasHint = ! $hasError && (bool) $hint;
    $labelId = $hasLabel ? 'lyra-radio-group-label-'.uniqid() : null;
    $consumerLabelledBy = $attributes->get('aria-labelledby');
    $labelledBy = $hasLabel
        ? trim(implode(' ', array_filter([$consumerLabelledBy, $labelId])))
        : $consumerLabelledBy;
    $selectedValue = $value ?? $defaultValue;
    $groupName = $name ?? 'lyra-radio-group-'.uniqid();
    $rootAttributes = $attributes
        ->except('aria-labelledby')
        ->class('lyra-field')
        ->merge(array_filter([
            'role' => 'radiogroup',
            'aria-labelledby' => $labelledBy,
        ], fn (mixed $attribute): bool => $attribute !== null));
@endphp

<div {{ $rootAttributes }}>
    @if ($hasLabel)
    <span id="{{ $labelId }}" class="lyra-label">{{ $label }}</span>
    @endif
    <div @class([
        'lyra-choicegroup',
        'lyra-choicegroup--row' => $direction === 'row',
    ])>
        @foreach ($options as $option)
        <label class="lyra-check-row">
            <input type="radio"
                name="{{ $groupName }}"
                value="{{ $option['value'] }}"
                @checked($option['value'] === $selectedValue)
                @disabled($option['disabled'] ?? false)
                class="lyra-radio">
            <span>@if (! empty($option['hint']))<span class="lyra-choice"><span>{{ $option['label'] }}</span><span class="lyra-choice__hint">{{ $option['hint'] }}</span></span>@else{{ $option['label'] }}@endif</span>
        </label>
        @endforeach
    </div>
    @if ($hasError)
    <span class="lyra-hint lyra-hint--error">{{ $error }}</span>
    @elseif ($hasHint)
    <span class="lyra-hint">{{ $hint }}</span>
    @endif
</div>
