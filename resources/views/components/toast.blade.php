@props([
    'tone' => 'info',
])

@php
    $hasIcon = isset($icon) && trim((string) $icon) !== '';
@endphp

{{-- onClose/closeLabel: interactive close button, phase 2 --}}
<div {{ $attributes->class(['lyra-toast'])->merge(['role' => 'status']) }}>
    @if ($hasIcon)
    <span class="lyra-toast__icon lyra-toast__icon--{{ $tone }}">{{ $icon }}</span>
    @endif
    <span>{{ $slot }}</span>
</div>
