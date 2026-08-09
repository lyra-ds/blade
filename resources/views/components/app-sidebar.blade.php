@props([
    'brand' => null,
    'groups' => [],
    'footer' => null,
    'width' => 260,
    'collapsible' => false,
    'defaultCollapsed' => false,
    'labels' => [],
])

{{--
    Group headings and data-item titles stay in the served DOM in both states so CSS can reveal or
    hide them as the rail changes without Alpine rebuilding markup. Unlike React's addRailLinkLabels,
    composition-slot links are not inspected; consumers must serve their own title and aria-label.
--}}
@php
    $groups = array_values($groups);
    $numericWidth = is_numeric($width) ? (float) $width : 260.0;
    $resolvedWidth = is_finite($numericWidth) ? $numericWidth : 260.0;
    $resolvedWidthLiteral = json_encode($resolvedWidth, JSON_THROW_ON_ERROR);
    $startsCollapsed = (bool) $defaultCollapsed;
    $defaultCollapsedLiteral = $startsCollapsed ? 'true' : 'false';
    $labels = is_array($labels) ? $labels : [];
    $resolvedLabels = [
        'collapse' => $labels['collapse'] ?? 'Collapse sidebar',
        'expand' => $labels['expand'] ?? 'Expand sidebar',
    ];
    $escapeJsString = static function (mixed $string): string {
        $escaped = str_replace(
            ['\\', "'", "\r", "\n"],
            ['\\\\', "\\'", '\\r', '\\n'],
            (string) $string,
        );

        return htmlspecialchars($escaped, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
    };
    $escapedCollapseLabel = $escapeJsString($resolvedLabels['collapse']);
    $escapedExpandLabel = $escapeJsString($resolvedLabels['expand']);
    $controlLabel = $startsCollapsed
        ? $resolvedLabels['expand']
        : $resolvedLabels['collapse'];
    $servedWidth = $startsCollapsed ? '64' : $resolvedWidthLiteral;
    $rootAttributes = $attributes
        ->class([
            'lyra-appsidebar',
            'lyra-appsidebar--rail' => $startsCollapsed,
        ])
        ->merge([
            'style' => "--appsidebar-width: {$servedWidth}px; width: var(--appsidebar-width)",
        ]);
@endphp

<nav
    x-data="lyraAppSidebar({ defaultCollapsed: {!! $defaultCollapsedLiteral !!}, width: {!! $resolvedWidthLiteral !!}, labels: { collapse: '{!! $escapedCollapseLabel !!}', expand: '{!! $escapedExpandLabel !!}' } })"
    x-modelable="collapsed"
    x-bind="root"
    {{ $rootAttributes }}
>
    @if ($brand !== null)
        <div class="lyra-appsidebar__brand">{{ $brand }}</div>
    @endif
    <div class="lyra-appsidebar__groups">
        @foreach ($groups as $group)
            @php
                $groupLabel = $group['heading'] ?? null;
                $groupItems = array_map(
                    static fn (array $item): array => [
                        ...$item,
                        'title' => $item['label'],
                    ],
                    array_values($group['items']),
                );
            @endphp
            <x-lyra::sidebar-group :label="$groupLabel" :items="$groupItems" />
        @endforeach
        {{ $slot }}
    </div>
    @if ($footer !== null)
        <div class="lyra-appsidebar__footer">{{ $footer }}</div>
    @endif
    @if ($collapsible)
        <button
            type="button"
            class="lyra-appsidebar__toggle"
            aria-label="{{ $controlLabel }}"
            title="{{ $controlLabel }}"
            x-bind="toggle"
        >
            <svg
                aria-hidden="true"
                width="15"
                height="15"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path
                    d="m15 18-6-6 6-6"
                    x-show="!collapsed"
                    @if ($startsCollapsed)
                        x-cloak
                    @endif
                />
                <path
                    d="m9 18 6-6-6-6"
                    x-show="collapsed"
                    @if (! $startsCollapsed)
                        x-cloak
                    @endif
                />
            </svg>
        </button>
    @endif
</nav>
