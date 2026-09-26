@props([
    'options',
    'value',
    'label',
])

{{-- Root Alpine scope is owned by Lyra; put consumer x-data on a parent wrapper. --}}
@php
    $attributes = \LyraDs\Blade\OwnedRoot::guard($attributes, 'segmented-control', ['x-modelable']);
    $options = array_values($options);
    $selectedIndex = array_search($value, array_column($options, 'value'), true);
    $firstEnabledIndex = false;

    foreach ($options as $index => $option) {
        if (! ($option['disabled'] ?? false)) {
            $firstEnabledIndex = $index;
            break;
        }
    }

    $focusableIndex = $selectedIndex !== false
        && ! ($options[$selectedIndex]['disabled'] ?? false)
            ? $selectedIndex
            : $firstEnabledIndex;
    $escapedValue = str_replace(['\\', "'", "\r", "\n"], ['\\\\', "\\'", '\\r', '\\n'], (string) $value);
    $escapedValue = htmlspecialchars($escapedValue, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
@endphp

<div
    x-data="lyraSegmentedControl({ value: '{!! $escapedValue !!}' })"
    x-modelable="value"
    role="radiogroup"
    aria-label="{{ $label }}"
    {{ $attributes->class('lyra-segmented') }}
>
    @foreach ($options as $index => $option)
        @php
            $selected = $option['value'] === $value;
        @endphp
        <button
            type="button"
            role="radio"
            aria-checked="{{ $selected ? 'true' : 'false' }}"
            tabindex="{{ $index === $focusableIndex ? 0 : -1 }}"
            @class([
                'lyra-segmented__option',
                'lyra-segmented__option--active' => $selected,
            ])
            data-value="{{ $option['value'] }}"
            x-bind="option"
            @if ($option['disabled'] ?? false)
                disabled
            @endif
        >{{ $option['label'] }}</button>
    @endforeach
</div>
