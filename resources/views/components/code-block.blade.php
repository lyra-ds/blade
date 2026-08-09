@props([
    'language' => null,
    'lineNumbers' => false,
    'wrap' => false,
    'copyLabel' => null,
    'copiedLabel' => null,
    'copyText' => null,
])

@php
    $canCopy = $copyLabel !== null && $copiedLabel !== null;
    $escapedCopyLabel = str_replace(['\\', "'", "\r", "\n"], ['\\\\', "\\'", '\\r', '\\n'], (string) $copyLabel);
    $escapedCopyLabel = htmlspecialchars($escapedCopyLabel, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
    $escapedCopiedLabel = str_replace(['\\', "'", "\r", "\n"], ['\\\\', "\\'", '\\r', '\\n'], (string) $copiedLabel);
    $escapedCopiedLabel = htmlspecialchars($escapedCopiedLabel, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
    $escapedCopyText = $copyText === null
        ? null
        : str_replace(
            ["\r", "\n"],
            ['&#13;', '&#10;'],
            htmlspecialchars((string) $copyText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
@endphp

<div
    @if ($canCopy)
        x-data="lyraCodeBlock()"
    @endif
    @if ($escapedCopyText !== null)
        data-copy-text="{!! $escapedCopyText !!}"
    @endif
    {{ $attributes->class([
        'lyra-code',
        'lyra-code--line-numbers' => $lineNumbers,
        'lyra-code--wrap' => $wrap,
    ]) }}
>
    <div class="lyra-code__bar">
        @if ($language === null)
            <span aria-hidden="true"></span>
        @else
            <span class="lyra-code__lang">{{ $language }}</span>
        @endif
        @if ($canCopy)
            <button
                type="button"
                x-bind="copyButton"
                x-text="copied ? '{!! $escapedCopiedLabel !!}' : '{!! $escapedCopyLabel !!}'"
                class="lyra-code__copy"
            >{{ $copyLabel }}</button>
        @endif
    </div>
    <pre
        class="lyra-code__pre"
        @if (! $wrap)
            tabindex="0"
        @endif
    >{{ $slot }}</pre>
    @if ($canCopy)
        <span
            x-bind="status"
            x-text="copied ? '{!! $escapedCopiedLabel !!}' : ''"
            class="lyra-code__status"
        ></span>
    @endif
</div>
