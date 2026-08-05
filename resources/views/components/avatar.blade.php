@props([
    'src' => null,
    'name' => null,
    'size' => 'md',
    'shape' => 'circle',
    'status' => null,
    'statusLabel' => null,
])

@php
    $words = preg_split('/\s+/', trim($name ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = collect(array_slice($words, 0, 2))
        ->map(static fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
        ->implode('');
@endphp

<span {{ $attributes->except('title')->class([
    'lyra-avatar',
    "lyra-avatar--{$size}",
    'lyra-avatar--square' => $shape === 'square',
])->merge($name !== null ? ['title' => $name] : []) }}>
    @if ($src !== null)
        <img src="{{ $src }}" alt="{{ $name }}">
    @else
        <span aria-hidden="true">{{ $initials }}</span>
    @endif
    @if ($status !== null)
        <span class="lyra-avatar__status lyra-avatar__status--{{ $status }}" role="status" aria-label="{{ $statusLabel ?? $status }}"></span>
    @endif
</span>
