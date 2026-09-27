@props([
    'tone' => 'info',
    'dismissible' => false,
    'closeLabel' => 'Close notification',
])

@php
    $hasIcon = isset($icon) && trim((string) $icon) !== '';
@endphp

<div {{ $attributes->class(['lyra-toast'])->merge(['role' => 'status']) }}>
    @if ($hasIcon)
    <span class="lyra-toast__icon lyra-toast__icon--{{ $tone }}">{{ $icon }}</span>
    @endif
    <span>{{ $slot }}</span>
    @if ($dismissible)
    <button type="button" class="lyra-toast__close" aria-label="{{ $closeLabel }}" x-on:click="$dispatch('lyra:close', {})">×</button>
    @endif
</div>
