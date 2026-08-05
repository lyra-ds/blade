<div {{ $attributes->class('lyra-empty') }}>
    @if (isset($icon))
    <div class="lyra-empty__icon">{{ $icon }}</div>
    @endif
    <h3 class="lyra-empty__title">{{ $title }}</h3>
    @if (isset($description))
    <p class="lyra-empty__desc">{{ $description }}</p>
    @endif
    @if (isset($action))
    <div class="lyra-empty__action">{{ $action }}</div>
    @endif
</div>
