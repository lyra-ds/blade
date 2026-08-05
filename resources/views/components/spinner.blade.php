@props([
    'size' => 'md',
])

<span {{ $attributes->except('role')->class([
    'lyra-spinner',
    "lyra-spinner--{$size}",
])->merge([
    'role' => 'status',
    'aria-label' => 'Loading',
]) }}></span>
