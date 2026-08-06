@props([
    'page',
    'total',
    'url',
    'previousLabel' => 'Previous page',
    'nextLabel' => 'Next page',
])

@php
    /**
     * React's onChange callback is intentionally adapted to href navigation for
     * the static phase-one Blade component.
     */
    $pageUrl = fn (int $target): string => str_replace('{page}', (string) $target, $url);

    if ($total <= 7) {
        $pages = range(1, $total);
    } elseif ($page <= 4) {
        $pages = [1, 2, 3, 4, 5, 'gap', $total];
    } elseif ($page >= $total - 3) {
        $pages = [1, 'gap', $total - 4, $total - 3, $total - 2, $total - 1, $total];
    } else {
        $pages = [1, 'gap', $page - 1, $page, $page + 1, 'gap', $total];
    }
@endphp

<nav {{ $attributes->class('lyra-pagination')->merge([
    'aria-label' => 'Pagination',
]) }}>
    @if ($page > 1)
        <a class="lyra-page" href="{{ $pageUrl($page - 1) }}" aria-label="{{ $previousLabel }}">‹</a>
    @else
        <span class="lyra-page" aria-disabled="true">‹</span>
    @endif

    @foreach ($pages as $item)
        @if ($item === 'gap')
            <span class="lyra-page lyra-page--gap" aria-hidden="true">…</span>
        @elseif ($item === $page)
            <a class="lyra-page lyra-page--active" href="{{ $pageUrl($item) }}" aria-current="page">{{ $item }}</a>
        @else
            <a class="lyra-page" href="{{ $pageUrl($item) }}">{{ $item }}</a>
        @endif
    @endforeach

    @if ($page < $total)
        <a class="lyra-page" href="{{ $pageUrl($page + 1) }}" aria-label="{{ $nextLabel }}">›</a>
    @else
        <span class="lyra-page" aria-disabled="true">›</span>
    @endif
</nav>
