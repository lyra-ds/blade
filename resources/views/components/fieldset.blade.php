@php
    $hasLegend = isset($legend) && trim((string) $legend) !== '';
    $hasDescription = isset($description) && trim((string) $description) !== '';
@endphp

<fieldset {{ $attributes->class('lyra-fieldset') }}>
    @if ($hasLegend)
    <legend class="lyra-fieldset__legend">{{ $legend }}</legend>
    @endif
    @if ($hasDescription)
    <p class="lyra-fieldset__desc">{{ $description }}</p>
    @endif
    <div class="lyra-fieldset__fields">{{ $slot }}</div>
</fieldset>
