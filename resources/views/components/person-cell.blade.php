@props([
    'name',
    'detail' => null,
    'src' => null,
])

<div {{ $attributes->class('lyra-personcell') }}>
    <x-lyra::avatar :src="$src" :name="$name" />
    <span class="lyra-personcell__text">
        <span class="lyra-personcell__name">{{ $name }}</span>
        @if ($detail !== null)
        <span class="lyra-personcell__detail">{{ $detail }}</span>
        @endif
    </span>
</div>
