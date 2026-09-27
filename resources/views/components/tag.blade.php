@props([
    'removable' => false,
    'removeLabel' => 'Remove',
])

<span {{ $attributes->class(['lyra-tag']) }}>
    {{ $slot }}
    @if ($removable)
    <button type="button" class="lyra-tag__remove" aria-label="{{ $removeLabel }}" x-on:click="$dispatch('lyra:remove', {})">
        <svg class="lyra-icon" aria-hidden="true" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
    </button>
    @endif
</span>
