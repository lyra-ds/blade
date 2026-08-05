@props([
    'orientation' => 'horizontal',
])

@if (isset($label) && trim((string) $label) !== '')
<div {{ $attributes->class([
    'lyra-separator--label',
])->merge([
    'role' => 'separator',
]) }}>{{ $label }}</div>
@elseif ($orientation === 'vertical')
<span {{ $attributes->class([
    'lyra-separator',
    'lyra-separator--vertical',
])->merge([
    'role' => 'separator',
    'aria-orientation' => 'vertical',
]) }}></span>
@else
<hr {{ $attributes->class([
    'lyra-separator',
]) }}>
@endif
