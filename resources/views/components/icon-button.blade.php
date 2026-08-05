@props([
    'label',
    'variant' => 'secondary',
    'size' => 'md',
])

<button {{ $attributes->except(['aria-label', 'title'])->class([
    'lyra-btn',
    'lyra-btn--icon',
    "lyra-btn--{$variant}",
    "lyra-btn--{$size}",
])->merge([
    'aria-label' => $label,
    'title' => $label,
]) }}>{{ $slot }}</button>
