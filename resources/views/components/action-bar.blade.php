@props([
    'open' => true,
    'count' => null,
    'label' => 'selected',
    'clearable' => false,
    'clearLabel' => 'Clear selection',
])

{{--
    The clear button dispatches a bubbling lyra:clear event ({ id }) through an inline Alpine
    handler, id being the root's own id attribute when the consumer set one (null otherwise; React's
    onClear takes no arguments, so there is nothing else to carry). The root only serves x-data
    when clearable is true and the consumer supplies none (a consumer x-data always wins; a
    duplicate attribute would be dropped by the parser), so the markup stays inert without Alpine
    and no scope is added when there is nothing to dispatch.
--}}
@php
    $rootId = $attributes->get('id');
@endphp

@if ($open && $count !== 0)
<div
    @if ($clearable && ! $attributes->has('x-data'))
        x-data
    @endif
    {{ $attributes->class(['lyra-actionbar'])->merge(['role' => 'toolbar']) }}
>
    @if ($count !== null)
    <span class="lyra-actionbar__count" role="status" aria-live="polite"><strong>{{ $count }}</strong> {{ $label }}</span>
    @endif
    {{ $slot }}
    <span class="lyra-actionbar__actions">
        {{ $actions ?? '' }}
        @if ($clearable)
        <button type="button" class="lyra-actionbar__clear" aria-label="{{ $clearLabel }}" x-on:click="$dispatch('lyra:clear', { id: @js($rootId) })">
            <svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
        </button>
        @endif
    </span>
</div>
@endif
