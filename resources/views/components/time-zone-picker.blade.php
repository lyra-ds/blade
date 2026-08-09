@props([
    'value' => null,
    'zones' => null,
    'recentZones' => [],
    'detectedZone' => null,
    'referenceDate' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'placeholder' => null,
    'locale' => 'en-US',
    'labels' => [],
    'disabled' => false,
])

{{--
    Zone offsets and trailing local times stay in lyraTimeZonePicker: they depend on Intl, the
    reference date, and a minute clock. This component only supplies data and translations to the
    canonical combobox markup. Omitting zones deliberately selects the binding's curated list.
    React's defaultValue and onChange props have no server prop: value seeds the modelable binding,
    which owns subsequent changes and dispatches its standard lyra:change event.
--}}
@php
    $resolvedLabels = array_merge([
        'placeholder' => 'Select time zone',
        'searchPlaceholder' => 'Search city, country, or abbreviation…',
        'emptyMessage' => 'No time zones found.',
        'detectedGroup' => 'Detected',
        'recentGroup' => 'Recent',
    ], is_array($labels) ? $labels : []);
    $resolvedPlaceholder = $placeholder ?? $resolvedLabels['placeholder'];
    $extraOptions = [
        'recentZones' => $recentZones,
        'locale' => $locale,
        'labels' => $resolvedLabels,
    ];

    if ($zones !== null) {
        $extraOptions['zones'] = $zones;
    }

    if ($detectedZone !== null) {
        $extraOptions['detectedZone'] = $detectedZone;
    }

    if ($referenceDate !== null) {
        $extraOptions['referenceDate'] = $referenceDate;
    }
@endphp

<x-lyra::combobox
    :value="$value"
    :label="$label"
    :hint="$hint"
    :error="$error"
    :placeholder="$resolvedPlaceholder"
    :search-placeholder="$resolvedLabels['searchPlaceholder']"
    :empty-message="$resolvedLabels['emptyMessage']"
    :disabled="$disabled"
    factory="lyraTimeZonePicker"
    :extra-options="$extraOptions"
    {{ $attributes->class('lyra-tzpicker') }}
>
    <x-slot:optionTrailing>
        <span x-show="option.trailing" x-text="option.trailing"></span>
    </x-slot:optionTrailing>
</x-lyra::combobox>
