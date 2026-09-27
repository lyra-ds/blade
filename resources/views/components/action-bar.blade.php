@props([
    'open' => true,
    'count' => null,
    'label' => 'selected',
    'clearable' => false,
    'clearLabel' => 'Clear selection',
])

@if ($open && $count !== 0)
<div {{ $attributes->class(['lyra-actionbar'])->merge(['role' => 'toolbar']) }}>
    @if ($count !== null)
    <span class="lyra-actionbar__count" role="status" aria-live="polite"><strong>{{ $count }}</strong> {{ $label }}</span>
    @endif
    {{ $slot }}
    <span class="lyra-actionbar__actions">
        {{ $actions ?? '' }}
        @if ($clearable)
        <button type="button" class="lyra-actionbar__clear" aria-label="{{ $clearLabel }}" x-on:click="$dispatch('lyra:clear', {})">
            <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
        </button>
        @endif
    </span>
</div>
@endif
