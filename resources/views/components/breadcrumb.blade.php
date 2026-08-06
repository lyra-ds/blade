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
        <a href="{{ $item['href'] ?? '#' }}">{{ $item['label'] }}</a>
        @endif
    @endforeach
</nav>
