@props([
    'linksLabel' => null,
])

@php
    $hasBrand = isset($brand) && trim((string) $brand) !== '';
    $hasNote = isset($note) && trim((string) $note) !== '';
    $hasLinks = isset($links) && trim((string) $links) !== '';
@endphp

<footer {{ $attributes->class('lyra-footer') }}>
    <x-lyra::container class="lyra-footer__inner">
        @if ($hasBrand)
        <div class="lyra-footer__brand">{{ $brand }}</div>
        @endif
        @if ($hasNote)
        <div class="lyra-footer__note">{{ $note }}</div>
        @endif
        @if ($hasLinks)
        <nav class="lyra-footer__links"@if ($linksLabel !== null) aria-label="{{ $linksLabel }}"@endif>{{ $links }}</nav>
        @endif
    </x-lyra::container>
</footer>
