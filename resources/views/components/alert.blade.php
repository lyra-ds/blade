@props([
    'tone' => 'info',
])

@php
    $hasIcon = isset($icon) && trim((string) $icon) !== '';
    $hasTitle = isset($title) && trim((string) $title) !== '';
@endphp

<div {{ $attributes->except('role')->class([
    'lyra-alert',
    "lyra-alert--{$tone}",
])->merge(['role' => 'status']) }}>
    @if ($hasIcon)
    <span class="lyra-alert__icon">{{ $icon }}</span>
    @endif
    <div>
        @if ($hasTitle)
        <p class="lyra-alert__title">{{ $title }}</p>
        @endif
        <p class="lyra-alert__body">{{ $slot }}</p>
    </div>
</div>
