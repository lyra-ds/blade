@props([
    'open' => true,
    'count' => null,
    'label' => 'selected',
])

{{-- onClear/clearLabel: interactive clear button, phase 2 --}}
@if ($open && $count !== 0)
<div {{ $attributes->class(['lyra-actionbar'])->merge(['role' => 'toolbar']) }}>
    @if ($count !== null)
    <span class="lyra-actionbar__count" role="status" aria-live="polite"><strong>{{ $count }}</strong> {{ $label }}</span>
    @endif
    {{ $slot }}
    <span class="lyra-actionbar__actions">{{ $actions ?? '' }}</span>
</div>
@endif
