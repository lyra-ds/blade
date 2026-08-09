<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

function renderDataTable(array $props = [], string $slots = ''): string
{
    $columns = $props['columns'] ?? [
        ['key' => 'name', 'label' => 'Name', 'sortable' => true],
        ['key' => 'quantity', 'label' => 'Quantity', 'align' => 'right'],
    ];
    $rows = $props['rows'] ?? [
        ['id' => 'a', 'name' => 'Alpha', 'quantity' => 2],
    ];
    $sorting = $props['sorting'] ?? null;
    $selectable = $props['selectable'] ?? false;
    $selected = $props['selected'] ?? [];
    $clientSort = $props['clientSort'] ?? false;
    $stickyHeader = $props['stickyHeader'] ?? false;
    $maxHeight = $props['maxHeight'] ?? null;
    $density = $props['density'] ?? 'comfortable';
    $loading = $props['loading'] ?? false;
    $empty = $props['empty'] ?? null;
    $hover = $props['hover'] ?? false;
    $labels = $props['labels'] ?? [];
    unset(
        $props['columns'],
        $props['rows'],
        $props['sorting'],
        $props['selectable'],
        $props['selected'],
        $props['clientSort'],
        $props['stickyHeader'],
        $props['maxHeight'],
        $props['density'],
        $props['loading'],
        $props['empty'],
        $props['hover'],
        $props['labels'],
    );

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf(
            '<x-lyra::data-table :columns="$columns" :rows="$rows" :sorting="$sorting" :selectable="$selectable" :selected="$selected" :client-sort="$clientSort" :sticky-header="$stickyHeader" :max-height="$maxHeight" :density="$density" :loading="$loading" :empty="$empty" :hover="$hover" :labels="$labels" %s>%s</x-lyra::data-table>',
            $attributes,
            $slots,
        ),
        compact(
            'columns',
            'rows',
            'sorting',
            'selectable',
            'selected',
            'clientSort',
            'stickyHeader',
            'maxHeight',
            'density',
            'loading',
            'empty',
            'hover',
            'labels',
        ),
    );
}

/** @return array{DOMDocument, DOMXPath} */
function dataTableDocument(string $html): array
{
    $document = new DOMDocument;
    $loaded = @$document->loadHTML(
        '<!DOCTYPE html><html><body>'.$html.'</body></html>',
        LIBXML_NOERROR | LIBXML_NOWARNING,
    );

    expect($loaded)->toBeTrue();

    return [$document, new DOMXPath($document)];
}

function dataTableElement(string $html, string $target): DOMElement
{
    [, $xpath] = dataTableDocument($html);
    $query = match ($target) {
        'wrapper' => '//div[contains(concat(" ", normalize-space(@class), " "), " lyra-table-wrap ")]',
        'scroll' => '//div[contains(concat(" ", normalize-space(@class), " "), " lyra-table-scroll ")]',
        'table' => '//table[contains(concat(" ", normalize-space(@class), " "), " lyra-table ")]',
        'header-check' => '//thead/tr/th[contains(concat(" ", normalize-space(@class), " "), " lyra-table__check ")]',
        'sort-button' => '//thead/tr/th/button[contains(concat(" ", normalize-space(@class), " "), " lyra-table__sortbtn ")][1]',
        'row' => '//tbody/tr[@data-row-id][1]',
        'row-check' => '//tbody/tr[@data-row-id][1]/td[contains(concat(" ", normalize-space(@class), " "), " lyra-table__check ")]',
        'primary' => '//tbody/tr[@data-row-id][1]/td[contains(concat(" ", normalize-space(@class), " "), " lyra-table__primary ")]',
        'empty' => '//tbody/tr/td[contains(concat(" ", normalize-space(@class), " "), " lyra-table__emptycell ")]',
        'footer' => '//div[contains(concat(" ", normalize-space(@class), " "), " lyra-table__footer ")]',
    };
    $element = $xpath->query($query)?->item(0);

    expect($element)->toBeInstanceOf(DOMElement::class);

    return $element;
}

/** @return array<string, mixed> */
function dataTableOptions(string $html): array
{
    $expression = dataTableElement($html, 'wrapper')->getAttribute('x-data');

    expect($expression)->toStartWith('lyraDataTable(')->toEndWith(')');

    return json_decode(
        substr($expression, strlen('lyraDataTable('), -1),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
}

/** @return list<string> */
function dataTableRowIds(string $html): array
{
    [, $xpath] = dataTableDocument($html);

    return collect($xpath->query('//tbody/tr[@data-row-id]'))
        ->map(fn (DOMElement $row): string => $row->getAttribute('data-row-id'))
        ->all();
}

/** @return array<string, string> */
function dataTableStyle(DOMElement $element): array
{
    if (! $element->hasAttribute('style')) {
        return [];
    }

    return collect(explode(';', $element->getAttribute('style')))
        ->map(fn (string $declaration): string => trim($declaration))
        ->filter()
        ->mapWithKeys(function (string $declaration): array {
            [$property, $value] = array_map('trim', explode(':', $declaration, 2));

            return [$property => $value];
        })
        ->all();
}

dataset('data table class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/data-table.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the data-table class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits every React-derived class string from the independent fixture', function (array $case): void {
    $html = renderDataTable($case['props'], $case['slots'] ?? '');
    $element = dataTableElement($html, $case['target']);

    expect($element->getAttribute('class'))->toBe($case['expected_class']);
})->with('data table class emission');

it('renders the complete React wrapper scroll table and footer topology', function (): void {
    $html = renderDataTable([
        'class' => 'inventory-table',
        'id' => 'inventory',
        'data-track' => 'stock',
    ], '<x-slot:footer><nav aria-label="Pagination">Pages</nav></x-slot:footer>');
    [$document, $xpath] = dataTableDocument($html);
    $wrapper = dataTableElement($html, 'wrapper');
    $elementChildren = [];

    foreach ($wrapper->childNodes as $child) {
        if ($child instanceof DOMElement) {
            $elementChildren[] = [$child->tagName, $child->getAttribute('class')];
        }
    }

    expect($elementChildren)->toBe([
        ['div', 'lyra-table-scroll'],
        ['div', 'lyra-table__footer'],
    ])->and($wrapper->getAttribute('class'))->toBe('lyra-table-wrap inventory-table')
        ->and($wrapper->getAttribute('id'))->toBe('inventory')
        ->and($wrapper->getAttribute('data-track'))->toBe('stock')
        ->and($xpath->query('//div[contains(@class, "lyra-table-scroll")]/table/thead/tr')->length)->toBe(1)
        ->and($xpath->query('//div[contains(@class, "lyra-table-scroll")]/table/tbody')->length)->toBe(1)
        ->and($xpath->query('//div[contains(@class, "lyra-table__footer")]/nav[@aria-label="Pagination"]')->length)->toBe(1)
        ->and($document->textContent)->toContain('Pages');
});

it('serves hover compact sticky and max-height variants exactly where React does', function (): void {
    $numeric = renderDataTable([
        'hover' => true,
        'density' => 'compact',
        'stickyHeader' => true,
        'maxHeight' => 320,
    ]);
    $cssLength = renderDataTable(['maxHeight' => '24rem']);

    expect(dataTableElement($numeric, 'table')->getAttribute('class'))
        ->toBe('lyra-table lyra-table--hover lyra-table--compact lyra-table--sticky')
        ->and(dataTableStyle(dataTableElement($numeric, 'scroll')))->toBe(['max-height' => '320px'])
        ->and(dataTableStyle(dataTableElement($cssLength, 'scroll')))->toBe(['max-height' => '24rem'])
        ->and(dataTableElement(renderDataTable(), 'scroll')->hasAttribute('style'))->toBeFalse();
});

it('rejects CSS dimension strings that could escape the React style property', function (): void {
    $html = renderDataTable([
        'columns' => [[
            'key' => 'name',
            'label' => 'Name',
            'width' => '12rem; color: red',
        ]],
        'maxHeight' => '24rem; overflow: visible',
    ]);
    [, $xpath] = dataTableDocument($html);
    $header = $xpath->query('//thead/tr/th')->item(0);
    $cell = $xpath->query('//tbody/tr[@data-row-id]/td')->item(0);

    expect(dataTableElement($html, 'scroll')->hasAttribute('style'))->toBeFalse()
        ->and($header->hasAttribute('style'))->toBeFalse()
        ->and($cell->hasAttribute('style'))->toBeFalse()
        ->and($html)->not->toContain('color: red')
        ->and($html)->not->toContain('overflow: visible');
});

it('serves selectable controls labels literal binding hooks and checked state', function (): void {
    $html = renderDataTable([
        'rows' => [
            ['id' => 'ada', 'name' => 'Ada', 'quantity' => 1],
            ['id' => 7, 'name' => 'Grace', 'quantity' => 2],
        ],
        'selectable' => true,
        'selected' => ['ada', 7],
        'labels' => [
            'selectAll' => 'Select every person',
            'selectRow' => 'Select {name}',
        ],
    ]);
    [, $xpath] = dataTableDocument($html);
    $selectAll = $xpath->query('//thead/tr/th[@class="lyra-table__check"]/input')->item(0);
    $rowInputs = $xpath->query('//tbody/tr[@data-row-id]/td[@class="lyra-table__check"]/input');

    expect($selectAll)->toBeInstanceOf(DOMElement::class)
        ->and($selectAll->getAttribute('type'))->toBe('checkbox')
        ->and($selectAll->getAttribute('class'))->toBe('lyra-checkbox')
        ->and($selectAll->getAttribute('aria-label'))->toBe('Select every person')
        ->and($selectAll->getAttribute('x-bind'))->toBe('selectAll')
        ->and($selectAll->hasAttribute('checked'))->toBeTrue()
        ->and($rowInputs->length)->toBe(2)
        ->and($rowInputs->item(0)->getAttribute('x-bind'))->toBe('rowCheckbox')
        ->and($rowInputs->item(0)->getAttribute('aria-label'))->toBe('Select Ada')
        ->and($rowInputs->item(1)->getAttribute('aria-label'))->toBe('Select Grace')
        ->and($rowInputs->item(0)->hasAttribute('checked'))->toBeTrue()
        ->and($rowInputs->item(1)->hasAttribute('checked'))->toBeTrue()
        ->and(substr_count($html, 'x-bind="selectAll"'))->toBe(1);
});

it('serves stable row ids selection classes and the default row label', function (): void {
    $html = renderDataTable([
        'rows' => [
            ['id' => 'north', 'name' => 'North', 'quantity' => 1],
            ['name' => 'Fallback', 'quantity' => 2],
        ],
        'selectable' => true,
        'selected' => ['north'],
    ]);
    [, $xpath] = dataTableDocument($html);
    $rows = $xpath->query('//tbody/tr[@data-row-id]');
    $inputs = $xpath->query('//tbody/tr[@data-row-id]//input[@x-bind="rowCheckbox"]');

    expect($rows->length)->toBe(2)
        ->and($rows->item(0)->getAttribute('data-row-id'))->toBe('north')
        ->and($rows->item(1)->getAttribute('data-row-id'))->toBe('1')
        ->and($rows->item(0)->getAttribute('x-bind'))->toBe('row')
        ->and($rows->item(1)->getAttribute('x-bind'))->toBe('row')
        ->and($rows->item(0)->getAttribute('class'))->toBe('lyra-table__row--selected')
        ->and($rows->item(1)->hasAttribute('class'))->toBeFalse()
        ->and($inputs->item(0)->getAttribute('aria-label'))->toBe('Select row')
        ->and($inputs->item(0)->hasAttribute('checked'))->toBeTrue()
        ->and($inputs->item(1)->hasAttribute('checked'))->toBeFalse();
});

it('serves sortable headers column styles and client sort values from the configured row key', function (): void {
    $html = renderDataTable([
        'columns' => [
            ['key' => 'name', 'label' => 'Name', 'align' => 'center', 'width' => 160, 'sortable' => true],
            ['key' => 'score', 'label' => 'Score', 'width' => '20%', 'sortable' => true, 'sortValueKey' => 'rank'],
            ['key' => 'note', 'label' => 'Note'],
        ],
        'rows' => [[
            'id' => 'ada',
            'name' => 'Ada',
            'score' => 'Gold',
            'rank' => 2,
            'note' => 'Ready',
        ]],
        'clientSort' => true,
    ]);
    [, $xpath] = dataTableDocument($html);
    $headers = $xpath->query('//thead/tr/th');
    $cells = $xpath->query('//tbody/tr[@data-row-id]/td');

    expect($headers->length)->toBe(3)
        ->and($headers->item(0)->getAttribute('data-sort-key'))->toBe('name')
        ->and($headers->item(0)->getAttribute('x-bind'))->toBe('header')
        ->and(dataTableStyle($headers->item(0)))->toBe(['text-align' => 'center', 'width' => '160px'])
        ->and($headers->item(1)->getAttribute('data-sort-key'))->toBe('score')
        ->and(dataTableStyle($headers->item(1)))->toBe(['width' => '20%'])
        ->and($headers->item(2)->hasAttribute('data-sort-key'))->toBeFalse()
        ->and($headers->item(2)->hasAttribute('x-bind'))->toBeFalse()
        ->and($xpath->query('//thead/tr/th[3]/button')->length)->toBe(0)
        ->and($cells->item(0)->getAttribute('class'))->toBe('lyra-table__primary')
        ->and(dataTableStyle($cells->item(0)))->toBe(['text-align' => 'center', 'width' => '160px'])
        ->and($cells->item(0)->getAttribute('data-sort-value'))->toBe('Ada')
        ->and(dataTableStyle($cells->item(1)))->toBe(['width' => '20%'])
        ->and($cells->item(1)->getAttribute('data-sort-value'))->toBe('2')
        ->and($cells->item(2)->hasAttribute('style'))->toBeFalse()
        ->and($cells->item(2)->hasAttribute('data-sort-value'))->toBeFalse()
        ->and(dataTableOptions($html)['clientSort'])->toBeTrue();
});

it('serves initially client-sorted rows with natural ordering and nulls last', function (string $dir, array $expectedIds): void {
    $html = renderDataTable([
        'columns' => [[
            'key' => 'score',
            'label' => 'Score',
            'sortable' => true,
            'sortValueKey' => 'rank',
        ]],
        'rows' => [
            ['id' => 'zoe', 'score' => 'Zoe', 'rank' => 'item10'],
            ['id' => 'missing', 'score' => 'Missing rank'],
            ['id' => 'ana', 'score' => 'Ana', 'rank' => 'Item2'],
            ['id' => 'null', 'score' => 'Null rank', 'rank' => null],
            ['id' => 'mia', 'score' => 'Mia', 'rank' => 'item1'],
        ],
        'sorting' => ['key' => 'score', 'dir' => $dir],
        'clientSort' => true,
    ]);
    [, $xpath] = dataTableDocument($html);
    $lastCells = $xpath->query('//tbody/tr[position() > last() - 2]/td[1]');

    expect(dataTableRowIds($html))->toBe($expectedIds)
        ->and($lastCells->length)->toBe(2)
        ->and($lastCells->item(0)->hasAttribute('data-sort-value'))->toBeFalse()
        ->and($lastCells->item(1)->hasAttribute('data-sort-value'))->toBeFalse();
})->with([
    'ascending' => ['asc', ['mia', 'ana', 'zoe', 'missing', 'null']],
    'descending' => ['desc', ['zoe', 'ana', 'mia', 'missing', 'null']],
]);

it('preserves the application row order for initial sorting without client sort', function (): void {
    $html = renderDataTable([
        'rows' => [
            ['id' => 'zoe', 'name' => 'Zoe'],
            ['id' => 'ana', 'name' => 'Ana'],
            ['id' => 'mia', 'name' => 'Mia'],
        ],
        'sorting' => ['key' => 'name', 'dir' => 'asc'],
    ]);

    expect(dataTableRowIds($html))->toBe(['zoe', 'ana', 'mia']);
});

it('serves aria-sort and active sort class only on the active column', function (string $dir, string $ariaSort): void {
    $html = renderDataTable([
        'columns' => [
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'quantity', 'label' => 'Quantity', 'sortable' => true],
        ],
        'sorting' => ['key' => 'quantity', 'dir' => $dir],
    ]);
    [, $xpath] = dataTableDocument($html);
    $headers = $xpath->query('//thead/tr/th');
    $buttons = $xpath->query('//thead/tr/th/button');

    expect($headers->item(0)->hasAttribute('aria-sort'))->toBeFalse()
        ->and($headers->item(1)->getAttribute('aria-sort'))->toBe($ariaSort)
        ->and($buttons->item(0)->getAttribute('class'))->toBe('lyra-table__sortbtn')
        ->and($buttons->item(1)->getAttribute('class'))->toBe('lyra-table__sortbtn lyra-table__sortbtn--active')
        ->and($buttons->item(0)->getAttribute('type'))->toBe('button')
        ->and($buttons->item(0)->getAttribute('x-bind'))->toBe('sortButton');
})->with([
    'ascending' => ['asc', 'ascending'],
    'descending' => ['desc', 'descending'],
]);

it('serves all three exact sort icons and cloaks the two non-served states', function (?array $sorting, string $visible): void {
    $html = renderDataTable(['sorting' => $sorting]);
    [, $xpath] = dataTableDocument($html);
    $icons = $xpath->query('//thead/tr/th[@data-sort-key="name"]/button/svg');
    $states = [];

    foreach ($icons as $icon) {
        expect($icon)->toBeInstanceOf(DOMElement::class)
            ->and($icon->getAttribute('aria-hidden'))->toBe('true')
            ->and($icon->getAttribute('width'))->toBe('12')
            ->and($icon->getAttribute('height'))->toBe('12')
            ->and($icon->getAttribute('viewbox'))->toBe('0 0 24 24')
            ->and($icon->getAttribute('fill'))->toBe('none')
            ->and($icon->getAttribute('stroke'))->toBe('currentColor')
            ->and($icon->getAttribute('stroke-width'))->toBe('2.5')
            ->and($icon->getAttribute('stroke-linecap'))->toBe('round')
            ->and($icon->getAttribute('stroke-linejoin'))->toBe('round');

        $paths = [];

        foreach ($icon->getElementsByTagName('path') as $path) {
            $paths[] = $path->getAttribute('d');
        }

        $state = match ($paths) {
            ['m18 15-6-6-6 6'] => 'asc',
            ['m6 9 6 6 6-6'] => 'desc',
            ['m7 15 5 5 5-5', 'm7 9 5-5 5 5'] => 'unsorted',
        };
        $states[$state] = [
            'show' => $icon->getAttribute('x-show'),
            'cloaked' => $icon->hasAttribute('x-cloak'),
        ];
    }

    expect($icons->length)->toBe(3)
        ->and(array_keys($states))->toBe(['unsorted', 'asc', 'desc'])
        ->and($states['unsorted']['show'])->toBe('sortDir("name") === null')
        ->and($states['asc']['show'])->toBe('sortDir("name") === \'asc\'')
        ->and($states['desc']['show'])->toBe('sortDir("name") === \'desc\'')
        ->and($states[$visible]['cloaked'])->toBeFalse()
        ->and(collect($states)->except($visible)->pluck('cloaked')->all())->toBe([true, true]);
})->with([
    'unsorted' => [null, 'unsorted'],
    'ascending' => [['key' => 'name', 'dir' => 'asc'], 'asc'],
    'descending' => [['key' => 'name', 'dir' => 'desc'], 'desc'],
]);

it('renders the requested loading rows with React skeleton dimensions and no column styles', function (): void {
    $html = renderDataTable([
        'columns' => [
            ['key' => 'name', 'label' => 'Name', 'align' => 'center', 'width' => 160],
            ['key' => 'quantity', 'label' => 'Quantity', 'width' => '20%'],
        ],
        'selectable' => true,
        'loading' => 2,
    ]);
    [, $xpath] = dataTableDocument($html);
    $rows = $xpath->query('//tbody/tr');
    $firstCells = $xpath->query('//tbody/tr[1]/td');
    $checkSkeleton = $xpath->query('//tbody/tr[1]/td[1]/span[@class="lyra-skeleton"]')->item(0);
    $dataSkeletons = $xpath->query('//tbody/tr[1]/td[position() > 1]/span[@class="lyra-skeleton"]');

    expect($rows->length)->toBe(2)
        ->and($xpath->query('//tbody/tr[@data-row-id]')->length)->toBe(0)
        ->and($firstCells->length)->toBe(3)
        ->and($firstCells->item(0)->getAttribute('class'))->toBe('lyra-table__check')
        ->and($firstCells->item(1)->getAttribute('class'))->toBe('lyra-table__primary')
        ->and($firstCells->item(2)->hasAttribute('class'))->toBeFalse()
        ->and($firstCells->item(0)->hasAttribute('style'))->toBeFalse()
        ->and($firstCells->item(1)->hasAttribute('style'))->toBeFalse()
        ->and($firstCells->item(2)->hasAttribute('style'))->toBeFalse()
        ->and(dataTableStyle($checkSkeleton))->toBe([
            'width' => '16px',
            'height' => '16px',
            'display' => 'inline-block',
        ])->and($dataSkeletons->length)->toBe(2)
        ->and(dataTableStyle($dataSkeletons->item(0)))->toBe([
            'width' => '60%',
            'height' => '12px',
            'display' => 'inline-block',
        ]);
});

it('uses five loading rows for boolean loading', function (): void {
    $html = renderDataTable(['loading' => true]);
    [, $xpath] = dataTableDocument($html);

    expect($xpath->query('//tbody/tr')->length)->toBe(5);
});

it('renders empty content with exact colspan arithmetic and supports an empty slot', function (): void {
    $plain = renderDataTable(['rows' => [], 'empty' => 'Nothing here']);
    $selectable = renderDataTable(['rows' => [], 'selectable' => true]);
    $minimum = renderDataTable(['columns' => [], 'rows' => []], '<x-slot:empty><strong data-empty-slot>Custom empty</strong></x-slot:empty>');
    $plainCell = dataTableElement($plain, 'empty');
    $selectableCell = dataTableElement($selectable, 'empty');
    $minimumCell = dataTableElement($minimum, 'empty');
    [, $xpath] = dataTableDocument($minimum);

    expect($plainCell->getAttribute('colspan'))->toBe('2')
        ->and(trim($plainCell->textContent))->toBe('Nothing here')
        ->and($selectableCell->getAttribute('colspan'))->toBe('3')
        ->and(trim($selectableCell->textContent))->toBe('No records.')
        ->and($minimumCell->getAttribute('colspan'))->toBe('1')
        ->and($xpath->query('//td[@class="lyra-table__emptycell"]/strong[@data-empty-slot]')->length)->toBe(1)
        ->and(trim($minimumCell->textContent))->toBe('Custom empty');
});

it('normalizes malformed columns rows state and labels instead of leaking PHP types', function (): void {
    $html = renderDataTable([
        'columns' => [
            ['key' => 'name', 'label' => 'Name', 'sortable' => true, 'align' => 'diagonal', 'width' => []],
            ['key' => 'bad-label', 'label' => ['No']],
            ['label' => 'Missing key'],
            'invalid',
        ],
        'rows' => [
            ['id' => 'good', 'name' => 'Valid'],
            ['id' => new stdClass, 'name' => 'Fallback'],
            'invalid',
        ],
        'sorting' => ['key' => [], 'dir' => 'sideways'],
        'selected' => ['good', 1, ['bad'], new stdClass, 'good'],
        'labels' => [
            'selectAll' => ['bad'],
            'selectRow' => 'Choose {name}',
            'empty' => new stdClass,
        ],
        'selectable' => true,
    ]);
    [, $xpath] = dataTableDocument($html);

    expect($xpath->query('//thead/tr/th[not(@class="lyra-table__check")]')->length)->toBe(1)
        ->and($xpath->query('//tbody/tr[@data-row-id]')->length)->toBe(2)
        ->and($xpath->query('//tbody/tr[@data-row-id="1"]')->length)->toBe(1)
        ->and(dataTableOptions($html))->toBe([
            'sorting' => null,
            'selected' => ['good', '1'],
            'clientSort' => false,
        ])->and($xpath->query('//thead//input')->item(0)->getAttribute('aria-label'))->toBe('Select all')
        ->and($html)->not->toContain('Array')
        ->and($html)->not->toContain('stdClass');
});

it('preserves rich safe cell content for specialized component composition', function (): void {
    $html = renderDataTable([
        'rows' => [[
            'id' => 'person',
            'name' => new HtmlString('<strong data-person-cell>Ada</strong>'),
            'quantity' => 1,
        ]],
    ]);
    [, $xpath] = dataTableDocument($html);

    expect($xpath->query('//tbody/tr[@data-row-id="person"]/td[1]/strong[@data-person-cell]')->length)->toBe(1);
});

it('hardens binding JSON sort expressions labels ids and values against markup breakout', function (): void {
    $payload = "'); window.pwned=1; //\"\\</script><img src=x onerror=alert(1)>";
    $html = renderDataTable([
        'columns' => [[
            'key' => $payload,
            'label' => $payload,
            'sortable' => true,
        ]],
        'rows' => [[
            'id' => $payload,
            $payload => $payload,
        ]],
        'sorting' => ['key' => $payload, 'dir' => 'asc'],
        'selected' => [$payload],
        'selectable' => true,
        'clientSort' => true,
        'labels' => ['selectRow' => 'Select {'.$payload.'}'],
    ]);
    [$document, $xpath] = dataTableDocument($html);
    $wrapper = dataTableElement($html, 'wrapper');
    $header = $xpath->query('//th[@data-sort-key]')->item(0);
    $row = $xpath->query('//tr[@data-row-id]')->item(0);
    $cell = $xpath->query('//tr[@data-row-id]/td[@data-sort-value]')->item(0);
    $rowCheckbox = $xpath->query('//tr[@data-row-id]//input')->item(0);
    $icon = $xpath->query('//th[@data-sort-key]/button/svg')->item(0);

    expect(dataTableOptions($html))->toBe([
        'sorting' => ['key' => $payload, 'dir' => 'asc'],
        'selected' => [$payload],
        'clientSort' => true,
    ])->and($wrapper->getAttribute('x-data'))->toContain('\\u003C/script\\u003E')
        ->and($header->getAttribute('data-sort-key'))->toBe($payload)
        ->and($row->getAttribute('data-row-id'))->toBe($payload)
        ->and($cell->getAttribute('data-sort-value'))->toBe($payload)
        ->and(trim($cell->textContent))->toBe($payload)
        ->and($rowCheckbox->getAttribute('aria-label'))->toBe('Select '.$payload)
        ->and($icon->getAttribute('x-show'))->toContain('\\u0027')
        ->and($icon->getAttribute('x-show'))->toContain('\\u0022')
        ->and($icon->getAttribute('x-show'))->toContain('\\u003C/script\\u003E')
        ->and($icon->getAttribute('x-show'))->not->toContain($payload)
        ->and($document->getElementsByTagName('script')->length)->toBe(0)
        ->and($document->getElementsByTagName('img')->length)->toBe(0)
        ->and($row->hasAttribute('onerror'))->toBeFalse()
        ->and($cell->hasAttribute('onerror'))->toBeFalse()
        ->and($html)->not->toContain('</script>');
});

it('always emits one hardened binding root and allows either public state to be modelable', function (): void {
    $selected = renderDataTable(['wire:model.live' => 'selectedRows']);
    $sorting = renderDataTable([
        'x-modelable' => 'sorting',
        'x-model' => 'sortState',
        'x-data' => 'alert(1)',
    ]);
    $invalid = renderDataTable(['x-modelable' => 'invented']);

    expect(dataTableElement($selected, 'wrapper')->getAttribute('x-modelable'))->toBe('selected')
        ->and(dataTableElement($selected, 'wrapper')->getAttribute('wire:model.live'))->toBe('selectedRows')
        ->and(dataTableElement($sorting, 'wrapper')->getAttribute('x-modelable'))->toBe('sorting')
        ->and(dataTableElement($sorting, 'wrapper')->getAttribute('x-model'))->toBe('sortState')
        ->and(dataTableElement($invalid, 'wrapper')->getAttribute('x-modelable'))->toBe('selected')
        ->and(substr_count($sorting, 'x-data='))->toBe(1)
        ->and($sorting)->not->toContain('alert(1)');
});

it('renders namespaced and short syntax identically', function (): void {
    $columns = [['key' => 'name', 'label' => 'Name', 'sortable' => true]];
    $rows = [['id' => 'ada', 'name' => 'Ada']];
    $namespaced = Blade::render(
        '<x-lyra::data-table :columns="$columns" :rows="$rows" selectable class="people" />',
        compact('columns', 'rows'),
    );
    $short = Blade::render(
        '<lyra:data-table :columns="$columns" :rows="$rows" selectable class="people" />',
        compact('columns', 'rows'),
    );

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('lyraDataTable(');
});
