@props([
    'steps',
    'active',
])

<div {{ $attributes->class('lyra-stepper') }}>
    @foreach ($steps as $step)
        @if (! $loop->first)
        <span @class([
            'lyra-step__line',
            'lyra-step__line--done' => $loop->index <= $active,
        ])></span>
        @endif
        <span
            @class([
                'lyra-step',
                'lyra-step--active' => $loop->index === $active,
                'lyra-step--done' => $loop->index < $active,
            ])
            @if ($loop->index === $active)
            aria-current="step"
            @endif
        >
            <span class="lyra-step__dot">
                @if ($loop->index < $active)
                <svg aria-hidden="true" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                @else
                {{ $loop->iteration }}
                @endif
            </span>
            <span class="lyra-step__label">{{ $step }}</span>
        </span>
    @endforeach
</div>
