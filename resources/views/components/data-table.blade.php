@props([
    'columns',
    'rows',
    'sorting' => null,
    'selectable' => false,
    'selected' => [],
    'clientSort' => false,
    'stickyHeader' => false,
    'maxHeight' => null,
    'density' => 'comfortable',
    'loading' => false,
    'empty' => null,
    'hover' => false,
    'labels' => [],
    'caption' => null,
    'captionHidden' => false,
    'scrollLabel' => null,
])

{{--
    React callbacks are represented by lyra:sort and lyra:selection plus modelable state, so this
    component exposes no PHP callback props. selected is modelable by default; consumers may pass
    x-modelable="sorting" when sorting is the state wired to x-model or wire:model.

    Rows have no click hook. Consumers put their own interactive Htmlable/Stringable content, such
    as a link, inside a cell; hover only composes the table's appearance.

    aria-label and aria-labelledby name the table itself; every other attribute lands on the
    wrapper. The focusable scroll region takes scrollLabel, else the caption id, else the table's
    own name, else "Data table". A column with rowHeader: true serves its body cells as
    th[scope=row].

    A sortable column may name sortValueKey to translate React's sortValue(row) closure into
    server data. It defaults to the column key. Rich cell values remain renderable so specialized
    components can compose this table without replacing its structure. The selectRow label accepts
    {key} interpolation over scalar row values. Named empty and footer slots provide rich content;
    the empty slot takes the place of the empty prop or labels.empty string.
--}}
{{-- Root Alpine scope is owned by Lyra; put consumer x-data on a parent wrapper. --}}
@php
    $jsonFlags = JSON_THROW_ON_ERROR
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE;

    $formatDimension = static function (mixed $value): ?string {
        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            $numeric = (float) $value;

            if (! is_finite($numeric)) {
                return null;
            }

            return json_encode($numeric, JSON_THROW_ON_ERROR).'px';
        }

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if (
            $trimmed === ''
            || preg_match('/\A[a-zA-Z0-9\s.%+*\/(),_-]+\z/u', $trimmed) !== 1
        ) {
            return null;
        }

        return $trimmed;
    };

    $resolveSortValue = static function (array $row, string $key): ?string {
        if (! array_key_exists($key, $row)) {
            return null;
        }

        $value = $row[$key];

        return match (true) {
            is_string($value), is_int($value) => (string) $value,
            is_float($value) && is_finite($value) => (string) $value,
            default => null,
        };
    };

    $resolvedColumns = [];

    if (is_array($columns)) {
        foreach ($columns as $column) {
            if (
                ! is_array($column)
                || ! array_key_exists('key', $column)
                || ! is_string($column['key'])
                || ! array_key_exists('label', $column)
                || ! (
                    is_string($column['label'])
                    || is_int($column['label'])
                    || is_float($column['label'])
                    || $column['label'] instanceof \Illuminate\Contracts\Support\Htmlable
                    || $column['label'] instanceof \Stringable
                )
            ) {
                continue;
            }

            $align = in_array($column['align'] ?? null, ['left', 'center', 'right'], true)
                ? $column['align']
                : null;
            $width = array_key_exists('width', $column)
                ? $formatDimension($column['width'])
                : null;
            $styles = [];

            if ($align !== null) {
                $styles[] = 'text-align: '.$align;
            }

            if ($width !== null) {
                $styles[] = 'width: '.$width;
            }

            $sortable = ($column['sortable'] ?? false) === true;
            $sortValueKey = is_string($column['sortValueKey'] ?? null)
                ? $column['sortValueKey']
                : $column['key'];

            $resolvedColumns[] = [
                'rowHeader' => ($column['rowHeader'] ?? false) === true,
                'key' => $column['key'],
                'keyLiteral' => json_encode($column['key'], $jsonFlags),
                'label' => $column['label'],
                'sortable' => $sortable,
                'sortValueKey' => $sortValueKey,
                'style' => $styles === [] ? null : implode('; ', $styles),
            ];
        }
    }

    $resolvedRows = [];

    if (is_array($rows)) {
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $fallbackId = count($resolvedRows);
            $candidateId = $row['id'] ?? $fallbackId;
            $rowId = match (true) {
                is_string($candidateId), is_int($candidateId) => (string) $candidateId,
                is_float($candidateId) && is_finite($candidateId) => (string) $candidateId,
                default => (string) $fallbackId,
            };

            $resolvedRows[] = [
                'id' => $rowId,
                'values' => $row,
            ];
        }
    }

    $resolvedSorting = null;

    if (
        is_array($sorting)
        && is_string($sorting['key'] ?? null)
        && in_array($sorting['dir'] ?? null, ['asc', 'desc'], true)
    ) {
        $resolvedSorting = [
            'key' => $sorting['key'],
            'dir' => $sorting['dir'],
        ];
    }

    $resolvedSelected = [];

    if (is_array($selected)) {
        foreach ($selected as $selectedId) {
            $resolvedId = match (true) {
                is_string($selectedId), is_int($selectedId) => (string) $selectedId,
                is_float($selectedId) && is_finite($selectedId) => (string) $selectedId,
                default => null,
            };

            if ($resolvedId !== null && ! in_array($resolvedId, $resolvedSelected, true)) {
                $resolvedSelected[] = $resolvedId;
            }
        }
    }

    $resolvedLabels = [
        'selectAll' => 'Select all',
        'selectRow' => 'Select row',
        'empty' => 'No records.',
        'loading' => 'Loading data…',
    ];

    if (is_array($labels)) {
        foreach (array_keys($resolvedLabels) as $labelKey) {
            if (array_key_exists($labelKey, $labels) && is_string($labels[$labelKey])) {
                $resolvedLabels[$labelKey] = $labels[$labelKey];
            }
        }
    }

    $resolvedSelectable = (bool) $selectable;
    $resolvedClientSort = (bool) $clientSort;

    if ($resolvedClientSort && $resolvedSorting !== null) {
        $sortColumn = null;

        foreach ($resolvedColumns as $column) {
            if ($column['sortable'] && $column['key'] === $resolvedSorting['key']) {
                $sortColumn = $column;

                break;
            }
        }

        if ($sortColumn !== null) {
            $nonNullRows = [];
            $nullRows = [];

            foreach ($resolvedRows as $resolvedRow) {
                $sortValue = $resolveSortValue($resolvedRow['values'], $sortColumn['sortValueKey']);

                if ($sortValue === null) {
                    $nullRows[] = $resolvedRow;
                } else {
                    $nonNullRows[] = [
                        'row' => $resolvedRow,
                        'sortValue' => $sortValue,
                    ];
                }
            }

            usort($nonNullRows, static function (array $left, array $right): int {
                // Closest PHP match for localeCompare numeric/base; exotic locale ties may differ.
                return strnatcasecmp($left['sortValue'], $right['sortValue']);
            });

            if ($resolvedSorting['dir'] === 'desc') {
                $nonNullRows = array_reverse($nonNullRows);
            }

            $resolvedRows = array_merge(array_column($nonNullRows, 'row'), $nullRows);
        }
    }

    $resolvedStickyHeader = (bool) $stickyHeader;
    $resolvedHover = (bool) $hover;
    $resolvedDensity = $density === 'compact' ? 'compact' : 'comfortable';
    $resolvedMaxHeight = $maxHeight === null ? null : $formatDimension($maxHeight);
    $resolvedLoading = match (true) {
        is_bool($loading) => $loading,
        is_int($loading) => max(0, $loading),
        is_float($loading) && is_finite($loading) => max(0, (int) floor($loading)),
        default => false,
    };
    $isLoading = $resolvedLoading === true || (is_int($resolvedLoading) && $resolvedLoading > 0);
    $loadingRows = $resolvedLoading === true ? 5 : (int) $resolvedLoading;
    $rowIds = array_column($resolvedRows, 'id');
    $allSelected = $rowIds !== [];

    foreach ($rowIds as $rowId) {
        if (! in_array($rowId, $resolvedSelected, true)) {
            $allSelected = false;

            break;
        }
    }

    $columnSpan = max(1, count($resolvedColumns) + ($resolvedSelectable ? 1 : 0));
    $resolvedScrollLabel = is_string($scrollLabel) && $scrollLabel !== '' ? $scrollLabel : null;
    $bindingOptions = [
        'sorting' => $resolvedSorting,
        'selected' => $resolvedSelected,
        'clientSort' => $resolvedClientSort,
    ] + ($resolvedScrollLabel === null ? [] : ['scrollLabel' => $resolvedScrollLabel]);

    $optionsLiteral = json_encode($bindingOptions, $jsonFlags);
    $requestedModelable = $attributes->get('x-modelable');
    $attributes = \LyraDs\Blade\OwnedRoot::guard($attributes, 'data-table', ['x-modelable']);
    $resolvedModelable = is_string($requestedModelable)
        && in_array($requestedModelable, ['selected', 'sorting'], true)
            ? $requestedModelable
            : 'selected';
    $hasCaption = $caption !== null && $caption !== '' && $caption !== false;
    $tableLabel = is_string($attributes->get('aria-label')) && $attributes->get('aria-label') !== ''
        ? $attributes->get('aria-label')
        : null;
    $tableLabelledBy = is_string($attributes->get('aria-labelledby')) && $attributes->get('aria-labelledby') !== ''
        ? $attributes->get('aria-labelledby')
        : null;
    $captionId = 'lyra-data-table-caption-'.uniqid();
    $regionLabelledBy = match (true) {
        $resolvedScrollLabel !== null => null,
        $hasCaption => $captionId,
        default => $tableLabelledBy,
    };
    $regionLabel = match (true) {
        $resolvedScrollLabel !== null => $resolvedScrollLabel,
        $hasCaption || $tableLabelledBy !== null => null,
        default => $tableLabel ?? 'Data table',
    };
    $rootAttributes = $attributes
        ->except(['x-data', 'x-modelable', 'aria-label', 'aria-labelledby'])
        ->class('lyra-table-wrap');
@endphp

<div
    x-data="{{ 'lyraDataTable('.$optionsLiteral.')' }}"
    x-modelable="{{ $resolvedModelable }}"
    {{ $rootAttributes }}
>
    <div
        class="lyra-table-scroll"
        x-bind="scrollRegion"
        role="region"
        tabindex="0"
        @if ($regionLabel !== null)
            aria-label="{{ $regionLabel }}"
        @endif
        @if ($regionLabelledBy !== null)
            aria-labelledby="{{ $regionLabelledBy }}"
        @endif
        @if ($resolvedMaxHeight !== null)
            style="max-height: {{ $resolvedMaxHeight }}"
        @endif
    >
        <table
            @if ($tableLabel !== null)
                aria-label="{{ $tableLabel }}"
            @endif
            @if ($tableLabelledBy !== null)
                aria-labelledby="{{ $tableLabelledBy }}"
            @endif
            @if ($isLoading)
                aria-busy="true"
            @endif
            @class([
                'lyra-table',
                'lyra-table--hover' => $resolvedHover,
                'lyra-table--compact' => $resolvedDensity === 'compact',
                'lyra-table--sticky' => $resolvedStickyHeader,
            ])
        >
            @if ($hasCaption)
                <caption
                    id="{{ $captionId }}"
                    @if ($captionHidden)
                        class="lyra-visually-hidden"
                    @endif
                >{{ $caption }}</caption>
            @endif
            <thead>
                <tr>
                    @if ($resolvedSelectable)
                        <th scope="col" class="lyra-table__check">
                            <input
                                type="checkbox"
                                class="lyra-checkbox"
                                aria-label="{{ $resolvedLabels['selectAll'] }}"
                                x-bind="selectAll"
                                @if ($allSelected)
                                    checked
                                @endif
                            >
                        </th>
                    @endif

                    @foreach ($resolvedColumns as $column)
                        @php
                            $active = $resolvedSorting !== null
                                && $resolvedSorting['key'] === $column['key'];
                            $direction = $active ? $resolvedSorting['dir'] : null;
                        @endphp

                        <th
                            scope="col"
                            @if ($column['style'] !== null)
                                style="{{ $column['style'] }}"
                            @endif
                            @if ($column['sortable'])
                                data-sort-key="{{ $column['key'] }}"
                                x-bind="header"
                            @endif
                            @if ($active)
                                aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}"
                            @endif
                        >
                            @if ($column['sortable'])
                                <button
                                    type="button"
                                    class="lyra-table__sortbtn{{ $active ? ' lyra-table__sortbtn--active' : '' }}"
                                    x-bind="sortButton"
                                >
                                    {{ $column['label'] }}
                                    <svg
                                        aria-hidden="true"
                                        width="12"
                                        height="12"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        x-show="{{ 'sortDir('.$column['keyLiteral'].') === null' }}"
                                        @if ($direction !== null)
                                            x-cloak
                                        @endif
                                    >
                                        <path d="m7 15 5 5 5-5" />
                                        <path d="m7 9 5-5 5 5" />
                                    </svg>
                                    <svg
                                        aria-hidden="true"
                                        width="12"
                                        height="12"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        x-show="{{ 'sortDir('.$column['keyLiteral'].") === 'asc'" }}"
                                        @if ($direction !== 'asc')
                                            x-cloak
                                        @endif
                                    >
                                        <path d="m18 15-6-6-6 6" />
                                    </svg>
                                    <svg
                                        aria-hidden="true"
                                        width="12"
                                        height="12"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        x-show="{{ 'sortDir('.$column['keyLiteral'].") === 'desc'" }}"
                                        @if ($direction !== 'desc')
                                            x-cloak
                                        @endif
                                    >
                                        <path d="m6 9 6 6 6-6" />
                                    </svg>
                                </button>
                            @else
                                {{ $column['label'] }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @if ($isLoading)
                    @for ($loadingIndex = 0; $loadingIndex < $loadingRows; $loadingIndex++)
                        <tr>
                            @if ($resolvedSelectable)
                                <td class="lyra-table__check">
                                    <x-lyra::skeleton :width="16" :height="16" style="display: inline-block" />
                                </td>
                            @endif

                            @foreach ($resolvedColumns as $column)
                                <td
                                    @if ($loop->first)
                                        class="lyra-table__primary"
                                    @endif
                                >
                                    <x-lyra::skeleton width="60%" :height="12" style="display: inline-block" />
                                </td>
                            @endforeach
                        </tr>
                    @endfor
                @elseif ($resolvedRows === [])
                    <tr>
                        <td colspan="{{ $columnSpan }}" class="lyra-table__emptycell">
                            @if ($empty instanceof \Illuminate\View\ComponentSlot)
                                {{ $empty }}
                            @elseif (
                                is_string($empty)
                                || is_int($empty)
                                || is_float($empty)
                                || $empty instanceof \Illuminate\Contracts\Support\Htmlable
                                || $empty instanceof \Stringable
                            )
                                {{ $empty }}
                            @else
                                {{ $resolvedLabels['empty'] }}
                            @endif
                        </td>
                    </tr>
                @else
                    @foreach ($resolvedRows as $resolvedRow)
                        @php
                            $row = $resolvedRow['values'];
                            $rowId = $resolvedRow['id'];
                            $rowSelected = in_array($rowId, $resolvedSelected, true);
                            $interpolations = [];

                            foreach ($row as $rowKey => $rowValue) {
                                if (
                                    is_string($rowValue)
                                    || is_int($rowValue)
                                    || (is_float($rowValue) && is_finite($rowValue))
                                ) {
                                    $interpolations['{'.(string) $rowKey.'}'] = (string) $rowValue;
                                }
                            }

                            $selectRowLabel = strtr($resolvedLabels['selectRow'], $interpolations);
                        @endphp

                        <tr
                            data-row-id="{{ $rowId }}"
                            x-bind="row"
                            @if ($rowSelected)
                                class="lyra-table__row--selected"
                            @endif
                        >
                            @if ($resolvedSelectable)
                                <td class="lyra-table__check">
                                    <input
                                        type="checkbox"
                                        class="lyra-checkbox"
                                        aria-label="{{ $selectRowLabel }}"
                                        x-bind="rowCheckbox"
                                        @if ($rowSelected)
                                            checked
                                        @endif
                                    >
                                </td>
                            @endif

                            @foreach ($resolvedColumns as $column)
                                @php
                                    $cellValue = array_key_exists($column['key'], $row)
                                        ? $row[$column['key']]
                                        : null;
                                    $renderedValue = match (true) {
                                        is_string($cellValue), is_int($cellValue), is_float($cellValue) => $cellValue,
                                        $cellValue instanceof \Illuminate\Contracts\Support\Htmlable => $cellValue,
                                        $cellValue instanceof \Stringable => $cellValue,
                                        default => null,
                                    };
                                    $sortValue = $resolveSortValue($row, $column['sortValueKey']);
                                @endphp

                                <{{ $column['rowHeader'] ? 'th' : 'td' }}
                                    @if ($column['rowHeader'])
                                        scope="row"
                                        x-bind="rowHeader"
                                    @endif
                                    @if ($loop->first)
                                        class="lyra-table__primary"
                                    @endif
                                    @if ($column['style'] !== null)
                                        style="{{ $column['style'] }}"
                                    @endif
                                    @if ($column['sortable'] && $sortValue !== null)
                                        data-sort-value="{{ $sortValue }}"
                                    @endif
                                >{{ $renderedValue }}</{{ $column['rowHeader'] ? 'th' : 'td' }}>
                            @endforeach
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
        @if ($isLoading)
            <span role="status" class="lyra-visually-hidden">{{ $resolvedLabels['loading'] }}</span>
        @endif
    </div>

    @if (isset($footer) && trim((string) $footer) !== '')
        <div class="lyra-table__footer">{{ $footer }}</div>
    @endif
</div>
