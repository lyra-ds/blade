@props([
    'label' => null,
])

@php
    $inputAttributes = $attributes
        ->except('type')
        ->class('lyra-checkbox');
@endphp

@if ($label)
<label class="lyra-check-row">
    <input type="checkbox" {{ $inputAttributes }}>
    <span>{{ $label }}</span>
</label>
@else
<input type="checkbox" {{ $inputAttributes }}>
@endif
