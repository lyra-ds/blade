@props([
    'files' => [],
    'path' => [],
    'defaultView' => 'list',
    'defaultQuery' => '',
    'actions' => null,
    'searchPlaceholder' => 'Search files…',
    'emptyMessage' => 'No files found.',
    'labels' => [],
])

{{--
    Both view trees are served so lyraFileManager can filter and switch them without rebuilding
    markup. Consumers must order folders before documents in the files prop; this component
    deliberately preserves server order. Use the breadcrumb slot when navigation needs links or
    forms. The actions prop mirrors React's actions(file) callback and returns dropdown items.
--}}
@php
    $files = array_values($files);
    $path = array_values($path);
    $resolvedView = $defaultView === 'grid' ? 'grid' : 'list';
    $resolvedQuery = (string) $defaultQuery;
    $normalizedQuery = strtolower(trim($resolvedQuery));
    $resolvedLabels = array_merge([
        'viewMode' => 'View mode',
        'listView' => 'List view',
        'gridView' => 'Grid view',
        'currentFolder' => 'Current folder',
        'itemActions' => static fn (string $name): string => 'Actions for '.$name,
        'headerName' => 'Name',
        'headerSize' => 'Size',
        'headerModified' => 'Modified',
        'menuOpen' => 'Open',
        'menuRename' => 'Rename',
        'menuDownload' => 'Download',
        'menuDelete' => 'Delete',
        'itemsCount' => static fn (?int $items): string => ($items ?? '—').' items',
    ], is_array($labels) ? $labels : []);
    $hasBreadcrumb = isset($breadcrumb) && trim((string) $breadcrumb) !== '';

    $escapeJsString = static function (mixed $string): string {
        $escaped = str_replace(
            ['\\', "'", "\r", "\n"],
            ['\\\\', "\\'", '\\r', '\\n'],
            (string) $string,
        );

        return htmlspecialchars($escaped, ENT_COMPAT | ENT_SUBSTITUTE, 'UTF-8');
    };
    $escapedQuery = $escapeJsString($resolvedQuery);

    $formatBytes = static function (mixed $bytes): string {
        if ($bytes === null) {
            return '—';
        }

        $bytes = (int) $bytes;

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024).' KB';
        }

        if ($bytes < 1024 * 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 1, '.', '').' MB';
        }

        return number_format($bytes / (1024 * 1024 * 1024), 1, '.', '').' GB';
    };

    $iconFor = static function (array $file): string {
        if (($file['type'] ?? null) === 'folder') {
            return 'folder';
        }

        $name = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return match (true) {
            in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'], true) => 'image',
            in_array($extension, ['pdf', 'doc', 'docx', 'txt', 'md'], true) => 'file-text',
            in_array($extension, ['xls', 'xlsx', 'csv'], true) => 'file-spreadsheet',
            in_array($extension, ['zip', 'rar', '7z', 'tar', 'gz'], true) => 'file-archive',
            in_array($extension, ['mp4', 'mov', 'webm'], true) => 'film',
            in_array($extension, ['mp3', 'wav', 'ogg'], true) => 'music',
            default => 'file',
        };
    };

    $renderIcon = static function (string $name): \Illuminate\Support\HtmlString {
        return new \Illuminate\Support\HtmlString(
            \Illuminate\Support\Facades\Blade::render(
                '<x-lyra::icon :name="$name" :size="15" />',
                compact('name'),
            ),
        );
    };
    $defaultActions = [
        [
            'id' => 'open',
            'label' => $resolvedLabels['menuOpen'],
            'icon' => $renderIcon('external-link'),
        ],
        [
            'id' => 'rename',
            'label' => $resolvedLabels['menuRename'],
            'icon' => $renderIcon('pencil'),
        ],
        [
            'id' => 'download',
            'label' => $resolvedLabels['menuDownload'],
            'icon' => $renderIcon('download'),
        ],
        ['type' => 'separator'],
        [
            'id' => 'delete',
            'label' => $resolvedLabels['menuDelete'],
            'icon' => $renderIcon('trash-2'),
            'danger' => true,
        ],
    ];

    $managedFiles = [];
    $initialMatchCount = 0;

    foreach ($files as $file) {
        $name = (string) ($file['name'] ?? '');
        $matchesQuery = $normalizedQuery === '' || str_contains(strtolower($name), $normalizedQuery);
        $isFolder = ($file['type'] ?? null) === 'folder';
        $itemCount = array_key_exists('items', $file) ? (int) $file['items'] : null;
        $itemActions = is_callable($actions) ? $actions($file) : $defaultActions;

        if ($matchesQuery) {
            $initialMatchCount++;
        }

        $managedFiles[] = [
            'file' => $file,
            'name' => $name,
            'matchesQuery' => $matchesQuery,
            'isFolder' => $isFolder,
            'icon' => $iconFor($file),
            'meta' => $isFolder
                ? $resolvedLabels['itemsCount']($itemCount)
                : $formatBytes($file['size'] ?? null),
            'updated' => ($file['updated'] ?? null) ?: '—',
            'actions' => $itemActions,
            'actionsLabel' => $resolvedLabels['itemActions']($name),
        ];
    }
@endphp

<div
    x-data="lyraFileManager({ defaultView: '{!! $resolvedView !!}', defaultQuery: '{!! $escapedQuery !!}' })"
    x-modelable="view"
    {{ $attributes->class('lyra-fm') }}
>
    <div class="lyra-fm__toolbar">
        <div class="lyra-fm__search">
            <x-lyra::icon name="search" :size="15" color="var(--text-faint)" />
            <input
                value="{{ $resolvedQuery }}"
                placeholder="{{ $searchPlaceholder }}"
                x-modelable="query"
                x-bind="search"
            >
        </div>
        <div class="lyra-fm__views" role="group" aria-label="{{ $resolvedLabels['viewMode'] }}">
            <button
                type="button"
                @class([
                    'lyra-fm__view',
                    'lyra-fm__view--on' => $resolvedView === 'list',
                ])
                aria-pressed="{{ $resolvedView === 'list' ? 'true' : 'false' }}"
                aria-label="{{ $resolvedLabels['listView'] }}"
                x-bind="listButton"
            >
                <x-lyra::icon name="list" :size="15" />
            </button>
            <button
                type="button"
                @class([
                    'lyra-fm__view',
                    'lyra-fm__view--on' => $resolvedView === 'grid',
                ])
                aria-pressed="{{ $resolvedView === 'grid' ? 'true' : 'false' }}"
                aria-label="{{ $resolvedLabels['gridView'] }}"
                x-bind="gridButton"
            >
                <x-lyra::icon name="layout-grid" :size="15" />
            </button>
        </div>
    </div>

    @if ($hasBreadcrumb || $path !== [])
        <nav class="lyra-fm__path" aria-label="{{ $resolvedLabels['currentFolder'] }}">
            @if ($hasBreadcrumb)
                {{ $breadcrumb }}
            @else
                @foreach ($path as $index => $segment)
                    @if ($index > 0)
                        <x-lyra::icon name="chevron-right" :size="13" color="var(--text-faint)" />
                    @endif
                    <button
                        type="button"
                        class="lyra-fm__crumb"
                        @if ($index === array_key_last($path))
                            disabled
                        @endif
                    >
                        @if ($index === 0)
                            <x-lyra::icon name="folder-open" :size="15" />
                        @endif
                        {{ $segment }}
                    </button>
                @endforeach
            @endif
        </nav>
    @endif

    <ul
        class="lyra-fm__list"
        x-bind="list"
        @if ($resolvedView !== 'list')
            x-cloak
        @endif
    >
        <li class="lyra-fm__head" aria-hidden="true">
            <span>{{ $resolvedLabels['headerName'] }}</span>
            <span>{{ $resolvedLabels['headerSize'] }}</span>
            <span>{{ $resolvedLabels['headerModified'] }}</span>
            <span></span>
        </li>
        @foreach ($managedFiles as $managedFile)
            @php($file = $managedFile['file'])
            <li
                class="lyra-fm__row"
                data-name="{{ $managedFile['name'] }}"
                @if (! $managedFile['matchesQuery'])
                    hidden
                @endif
            >
                <button type="button" class="lyra-fm__name">
                    <span
                        @class([
                            'lyra-fm__icon',
                            'lyra-fm__icon--folder' => $managedFile['isFolder'],
                        ])
                    >
                        <x-lyra::icon :name="$managedFile['icon']" :size="17" />
                    </span>
                    <span class="lyra-fm__label">{{ $managedFile['name'] }}</span>
                    @if ($file['shared'] ?? false)
                        <span class="lyra-fm__shared">
                            <x-lyra::icon name="users" :size="13" />
                        </span>
                    @endif
                </button>
                <span class="lyra-fm__cell">{{ $managedFile['meta'] }}</span>
                <span class="lyra-fm__cell">{{ $managedFile['updated'] }}</span>
                <span class="lyra-fm__actions">
                    <x-lyra::dropdown :items="$managedFile['actions']" align="end">
                        <x-slot:trigger>
                            <span class="lyra-fm__more" aria-label="{{ $managedFile['actionsLabel'] }}">
                                <x-lyra::icon name="ellipsis" :size="17" />
                            </span>
                        </x-slot:trigger>
                    </x-lyra::dropdown>
                </span>
            </li>
        @endforeach
    </ul>

    <div
        class="lyra-fm__grid"
        x-bind="grid"
        @if ($resolvedView !== 'grid')
            x-cloak
        @endif
    >
        @foreach ($managedFiles as $managedFile)
            <div
                class="lyra-fm__card"
                data-name="{{ $managedFile['name'] }}"
                @if (! $managedFile['matchesQuery'])
                    hidden
                @endif
            >
                <span class="lyra-fm__card-actions">
                    <x-lyra::dropdown :items="$managedFile['actions']" align="end">
                        <x-slot:trigger>
                            <span class="lyra-fm__more" aria-label="{{ $managedFile['actionsLabel'] }}">
                                <x-lyra::icon name="ellipsis" :size="17" />
                            </span>
                        </x-slot:trigger>
                    </x-lyra::dropdown>
                </span>
                <button type="button" class="lyra-fm__card-body">
                    <span
                        @class([
                            'lyra-fm__icon',
                            'lyra-fm__icon--big',
                            'lyra-fm__icon--folder' => $managedFile['isFolder'],
                        ])
                    >
                        <x-lyra::icon :name="$managedFile['icon']" :size="26" />
                    </span>
                    <span class="lyra-fm__label">{{ $managedFile['name'] }}</span>
                    <span class="lyra-fm__card-meta">{{ $managedFile['meta'] }}</span>
                </button>
            </div>
        @endforeach
    </div>

    <p
        class="lyra-fm__empty"
        x-bind="empty"
        @if ($initialMatchCount > 0)
            x-cloak
        @endif
    >{{ $emptyMessage }}</p>
</div>
