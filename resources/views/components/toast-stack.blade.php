@php
    // Slot toasts render before the stack view, so they cannot see it; the region announces them instead.
    $staticToasts = new Illuminate\Support\HtmlString(
        (string) preg_replace('/(<div\b)\s+role="status"(?=[^>]*\bclass="lyra-toast\b)/', '$1', (string) $slot)
    );
@endphp

{{--
    The stack root is not a live region: the two persistent regions below announce each added toast
    (danger goes assertive, everything else polite) and rows carry no role of their own.
    Statically served <x-lyra::toast> children render inside the polite region and drop their default role="status".
    The close button label comes from Alpine.store('lyraToasts').closeLabel (default 'Close notification').
--}}
<div
    x-data="lyraToastStack()"
    {{ $attributes->class(['lyra-toast-stack']) }}
>
    <div data-lyra-toast-region="polite" aria-live="polite" aria-relevant="additions" style="display: contents">
        <template x-for="toast in politeToasts" :key="toast.id">
            <div class="lyra-toast">
                <span class="lyra-toast__icon" :class="toneClass(toast.tone)">
                    <svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        x-show="toast.tone === 'success'"
                    >
                        <circle cx="12" cy="12" r="10" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                    <svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        x-show="toast.tone === 'danger'"
                    >
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" x2="12" y1="8" y2="12" />
                        <line x1="12" x2="12.01" y1="16" y2="16" />
                    </svg>
                    <svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        x-show="toast.tone === 'info'"
                    >
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 16v-4" />
                        <path d="M12 8h.01" />
                    </svg>
                </span>
                <span x-text="toast.message"></span>
                <button class="lyra-toast__close" :data-toast-id="toast.id" x-bind="closeButton">×</button>
            </div>
        </template>
        {{ $staticToasts }}
    </div>
    <div data-lyra-toast-region="assertive" aria-live="assertive" aria-relevant="additions" style="display: contents">
        <template x-for="toast in assertiveToasts" :key="toast.id">
            <div class="lyra-toast">
                <span class="lyra-toast__icon" :class="toneClass(toast.tone)">
                    <svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        x-show="toast.tone === 'success'"
                    >
                        <circle cx="12" cy="12" r="10" />
                        <path d="m9 12 2 2 4-4" />
                    </svg>
                    <svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        x-show="toast.tone === 'danger'"
                    >
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" x2="12" y1="8" y2="12" />
                        <line x1="12" x2="12.01" y1="16" y2="16" />
                    </svg>
                    <svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        x-show="toast.tone === 'info'"
                    >
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 16v-4" />
                        <path d="M12 8h.01" />
                    </svg>
                </span>
                <span x-text="toast.message"></span>
                <button class="lyra-toast__close" :data-toast-id="toast.id" x-bind="closeButton">×</button>
            </div>
        </template>
    </div>
</div>
