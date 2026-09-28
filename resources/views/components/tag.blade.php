@props([
    'removable' => false,
    'removeLabel' => 'Remove',
])

{{--
    The remove button dispatches a bubbling lyra:remove event ({ id }) through an inline Alpine
    handler, id being the root's own id attribute when the consumer set one (null otherwise; React's
    onRemove takes no arguments, so there is nothing else to carry). The root only serves x-data
    when removable is true and the consumer supplies none (a consumer x-data always wins; a
    duplicate attribute would be dropped by the parser), so the markup stays inert without Alpine
    and no scope is added when there is nothing to dispatch.
--}}
@php
    $rootId = $attributes->get('id');
@endphp

<span
    @if ($removable && ! $attributes->has('x-data'))
        x-data
    @endif
    {{ $attributes->class(['lyra-tag']) }}
>
    {{ $slot }}
    @if ($removable)
    <button type="button" class="lyra-tag__remove" aria-label="{{ $removeLabel }}" x-on:click="$dispatch('lyra:remove', { id: @js($rootId) })">
        <svg class="lyra-icon" aria-hidden="true" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
    </button>
    @endif
</span>
