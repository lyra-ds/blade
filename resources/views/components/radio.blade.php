@props([
    'label' => null,
])

@php
    $inputAttributes = $attributes
        ->except('type')
        ->class('lyra-radio');
@endphp

@if ($label)
<label class="lyra-check-row">
    <input type="radio" {{ $inputAttributes }}>
    <span>{{ $label }}</span>
</label>
@else
<input type="radio" {{ $inputAttributes }}>
@endif
