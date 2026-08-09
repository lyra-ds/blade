<div
    x-data="lyraToastStack()"
    {{ $attributes->class(['lyra-toast-stack']) }}
>
    <template x-for="toast in toasts" :key="toast.id">
        <div class="lyra-toast" role="status">
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
    {{ $slot }}
</div>
