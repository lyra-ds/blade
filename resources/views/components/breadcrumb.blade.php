@props([
    'items',
])

<nav {{ $attributes->class('lyra-breadcrumb')->merge([
    'aria-label' => 'Breadcrumb',
]) }}>
    @foreach ($items as $item)
        @if (! $loop->first)
        <span class="lyra-breadcrumb__sep" aria-hidden="true"></span>
        @endif
        @if ($loop->last)
        <span class="lyra-breadcrumb__current" aria-current="page">{{ $item['label'] }}</span>
        @else
        @if (isset($item['href']) && $item['href'] !== '')
        <a {{ new \Illuminate\View\ComponentAttributeBag(array_filter([
            'href' => $item['href'],
            'target' => $item['target'] ?? null,
            'rel' => $item['rel'] ?? null,
        ], fn ($v) => $v !== null)) }}>{{ $item['label'] }}</a>
        @else
        <span>{{ $item['label'] }}</span>
        @endif
        @endif
    @endforeach
</nav>
