@props([
    'ariaLabel' => 'Cookie notice',
    'storageKey' => 'lyra-cookie-consent',
    'policyHref' => null,
    'essentialsLabel' => 'Only essentials',
    'acceptLabel' => 'Accept all',
])

{{--
    Unlike Lyra's static-first interactive components, this banner must remain x-cloaked until
    Alpine checks persisted consent. Without Alpine it intentionally never appears, because the
    server cannot know whether this visitor has already made a choice.

    The buttons deliberately serve type="button" even though the Alpine binding also provides it:
    this prevents accidental form submission before Alpine initializes.
--}}
@php
    $escapedStorageKey = str_replace(['\\', "'", "\r", "\n"], ['\\\\', "\\'", '\\r', '\\n'], (string) $storageKey);
    $escapedStorageKey = htmlspecialchars($escapedStorageKey, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
@endphp

<div
    role="region"
    aria-label="{{ $ariaLabel }}"
    x-data="lyraCookieBanner({ storageKey: '{!! $escapedStorageKey !!}' })"
    x-bind="root"
    x-cloak
    {{ $attributes->except('role')->class('lyra-cookies') }}
>
    <p class="lyra-cookies__text">@if ($slot->isEmpty())We use cookies to improve your experience in accordance with LGPD. You can accept all cookies or keep only essential ones.@if ($policyHref !== null && $policyHref !== '') <a href="{{ $policyHref }}">Privacy policy</a>@endif
@else{{ $slot }}@endif</p>
    <div class="lyra-cookies__actions">
        <lyra:button
            type="button"
            variant="secondary"
            size="sm"
            x-bind="essentials"
        >{{ $essentialsLabel }}</lyra:button>
        <lyra:button
            type="button"
            size="sm"
            x-bind="accept"
        >{{ $acceptLabel }}</lyra:button>
    </div>
</div>
