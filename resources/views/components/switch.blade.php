@props([
    'label' => null,
])

@php
    $labelAttributes = $attributes
        ->only('class')
        ->class('lyra-switch');
    $inputAttributes = $attributes
        ->except(['class', 'type', 'role']);
@endphp

<label {{ $labelAttributes }}>
    <input type="checkbox" role="switch" {{ $inputAttributes }}>
    <span class="lyra-switch__track" aria-hidden="true"></span>
    @if ($label)
    <span>{{ $label }}</span>
    @endif
</label>
