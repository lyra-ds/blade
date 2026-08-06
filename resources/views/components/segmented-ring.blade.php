@props([
    'segments' => [],
    'total' => null,
    'centerValue' => null,
    'centerLabel' => null,
    'size' => 'lg',
    'stacked' => false,
    'showLegend' => true,
])

@php
    $px = $size === 'md' ? 96 : 160;
    $stroke = $size === 'md' ? 9 : 12;
    $radius = ($px - $stroke) / 2;
    $circumference = 2 * M_PI * $radius;
    $toneColors = [
        'success' => 'var(--success)',
        'accent' => 'var(--accent)',
        'danger' => 'var(--danger)',
        'warning' => 'var(--warning)',
        'neutral' => 'var(--border-strong)',
    ];
    $segmentColor = fn (array $segment): string => $segment['color']
        ?? $toneColors[$segment['tone'] ?? 'neutral']
        ?? $toneColors['neutral'];
    $visibleSegments = array_values(array_filter(
        $segments,
        fn (array $segment): bool => is_finite($segment['value']) && $segment['value'] > 0,
    ));
    $largestSegment = max([0, ...array_column($visibleSegments, 'value')]);
    $scaledTotal = array_sum(array_map(
        fn (array $segment): float => fdiv($segment['value'], $largestSegment),
        $visibleSegments,
    ));
    $scaledDenominator = $total !== null && is_finite($total) && $total > 0
        ? fdiv($total, $largestSegment)
        : $scaledTotal;
    $remaining = 1.0;
    $boundedSegments = [];

    foreach ($visibleSegments as $segment) {
        $requestedFraction = fdiv(
            fdiv($segment['value'], $largestSegment),
            $scaledDenominator,
        );
        $fraction = is_finite($requestedFraction)
            ? max(0, min($requestedFraction, $remaining))
            : $remaining;
        $remaining -= $fraction;

        if ($fraction > 0) {
            $boundedSegments[] = compact('segment', 'fraction');
        }
    }

    $gap = count($boundedSegments) > 1 ? 2.5 : 0;
    $accumulated = 0.0;
    $arcs = [];

    foreach ($boundedSegments as $boundedSegment) {
        $segment = $boundedSegment['segment'];
        $fraction = $boundedSegment['fraction'];
        $length = max(0, $fraction * $circumference - $gap);
        $dash = $length.' '.($circumference - $length);
        $offset = -$accumulated * $circumference + $circumference / 4;
        $accumulated += $fraction;
        $arcs[] = compact('segment', 'dash', 'offset');
    }

    $hiddenSegments = array_map(
        fn (array $segment): string => $segment['value'].' '.$segment['label'],
        $visibleSegments,
    );
    $hiddenPrefix = $centerValue !== null
        ? ($centerLabel !== null ? $centerLabel.' ' : '').$centerValue.' — '
        : '';
@endphp

<div {{ $attributes->class([
    'lyra-ring',
    "lyra-ring--{$size}",
    'lyra-ring--stacked' => $stacked,
]) }}>
    <span class="lyra-ring__wrap" aria-hidden="true">
        <svg width="{{ $px }}" height="{{ $px }}" viewBox="0 0 {{ $px }} {{ $px }}" aria-hidden="true">
            <circle cx="{{ $px / 2 }}" cy="{{ $px / 2 }}" r="{{ $radius }}" fill="none" stroke="var(--surface-sunken)" stroke-width="{{ $stroke }}"></circle>
            @foreach ($arcs as $arc)
                <circle cx="{{ $px / 2 }}" cy="{{ $px / 2 }}" r="{{ $radius }}" fill="none" stroke="{{ $segmentColor($arc['segment']) }}" stroke-width="{{ $stroke }}" stroke-linecap="{{ $gap ? 'round' : 'butt' }}" stroke-dasharray="{{ $arc['dash'] }}" stroke-dashoffset="{{ $arc['offset'] }}"></circle>
            @endforeach
        </svg>
        <span class="lyra-ring__center">
            @if ($centerValue !== null)
                <span class="lyra-ring__num">{{ $centerValue }}</span>
            @endif
            @if ($centerLabel !== null)
                <span class="lyra-ring__cap">{{ $centerLabel }}</span>
            @endif
        </span>
    </span>
    <span class="lyra-visually-hidden">{{ $hiddenPrefix }}{{ implode(', ', $hiddenSegments) }}</span>
    @if ($showLegend)
        <ul class="lyra-ring__legend" aria-hidden="true">
            @foreach ($segments as $segment)
                <li class="lyra-ring__li"><span class="lyra-ring__swatch" style="background-color: {{ $segmentColor($segment) }}"></span><span>{{ $segment['label'] }}</span><span class="lyra-ring__val">{{ $segment['value'] }}</span></li>
            @endforeach
        </ul>
    @endif
</div>
