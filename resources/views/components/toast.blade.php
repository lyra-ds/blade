@props([
    'tone' => 'info',
    'dismissible' => false,
    'closeLabel' => 'Close notification',
])
@aware(['lyraToastStackContext' => false])

{{--
    The close button dispatches a bubbling lyra:close event ({ id }) through an inline Alpine
    handler, id being the root's own id attribute when the consumer set one (null otherwise; React's
    onClose takes no arguments, so there is nothing else to carry). The root only serves x-data
    when dismissible is true and the consumer supplies none (a consumer x-data always wins; a
    duplicate attribute would be dropped by the parser), so the markup stays inert without Alpine
    and no scope is added when there is nothing to dispatch.

    role="status" is only emitted standalone. A toast rendered inside <x-lyra::toast-stack> reads
    the lyraToastStackContext marker ToastStackComponent exposes as a public property (consumed
    above with @aware) and skips the role, because the stack's own aria-live regions already
    announce it. @aware resolves against Blade's own component-data stack, which
    Illuminate\View\View::render() unwinds on any exception (catch + flushState() before rethrow) —
    no static guard, no attribute-order-dependent string surgery on the rendered HTML.
--}}
@php
    $hasIcon = isset($icon) && trim((string) $icon) !== '';
    $rootId = $attributes->get('id');
    $rootAttributes = $attributes->class(['lyra-toast']);
    if (! $lyraToastStackContext) {
        $rootAttributes = $rootAttributes->merge(['role' => 'status']);
    }
@endphp

<div
    @if ($dismissible && ! $attributes->has('x-data'))
        x-data
    @endif
    {{ $rootAttributes }}
>
    @if ($hasIcon)
    <span class="lyra-toast__icon lyra-toast__icon--{{ $tone }}">{{ $icon }}</span>
    @endif
    <span>{{ $slot }}</span>
    @if ($dismissible)
    <button type="button" class="lyra-toast__close" aria-label="{{ $closeLabel }}" x-on:click="$dispatch('lyra:close', { id: @js($rootId) })">×</button>
    @endif
</div>
