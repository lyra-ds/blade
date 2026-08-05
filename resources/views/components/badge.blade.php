@props([
    'tone' => 'neutral',
    'dot' => false,
])

<span {{ $attributes->class([
    'lyra-badge',
    "lyra-badge--{$tone}",
]) }}>@if ($dot)<span class="lyra-badge__dot" aria-hidden="true"></span>@endif{{ $slot }}</span>
