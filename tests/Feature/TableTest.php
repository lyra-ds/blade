<?php

use Illuminate\Support\Facades\Blade;

function renderTable(array $props = []): string
{
    $columns = $props['columns'] ?? [
        ['key' => 'name', 'label' => 'Nome'],
        ['key' => 'quantity', 'label' => 'Qtd', 'align' => 'right'],
    ];
    $rows = $props['rows'] ?? [
        ['name' => 'A', 'quantity' => 1],
    ];
    $hover = $props['hover'] ?? false;
    unset($props['columns'], $props['rows'], $props['hover']);

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');

    return Blade::render(
        sprintf(
            '<x-lyra::table :columns="$columns" :rows="$rows" :hover="$hover" %s />',
            $attributes,
        ),
        compact('columns', 'rows', 'hover'),
    );
}

function tableWrapperClass(string $html): string
{
    $matched = preg_match('/<div\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function tableClass(string $html): string
{
    $matched = preg_match('/<table\b[^>]*\bclass="([^"]*)"/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

function tableWrapperOpeningTag(string $html): string
{
    $matched = preg_match('/<div\b[^>]*>/', $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

dataset('table class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/table.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the table class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits the exact React wrapper and table class strings', function (array $case): void {
    $html = renderTable($case['props']);

    expect(tableWrapperClass($html))->toBe($case['expected_wrapper_class'])
        ->and(tableClass($html))->toBe($case['expected_table_class']);
})->with('table class emission');

it('renders the exact table section and row structure', function (): void {
    $html = renderTable([
        'columns' => [
            ['key' => 'name', 'label' => 'Nome'],
            ['key' => 'quantity', 'label' => 'Qtd'],
        ],
        'rows' => [
            ['name' => 'A', 'quantity' => 1],
            ['name' => 'B', 'quantity' => 2],
        ],
    ]);

    expect($html)->toMatch('/<table\b[^>]*>\s*<thead>\s*<tr>\s*<th\b[^>]*>Nome<\/th>\s*<th\b[^>]*>Qtd<\/th>\s*<\/tr>\s*<\/thead>\s*<tbody>\s*<tr>.*<\/tr>\s*<tr>.*<\/tr>\s*<\/tbody>\s*<\/table>/s')
        ->and(substr_count($html, '<thead>'))->toBe(1)
        ->and(substr_count($html, '<tbody>'))->toBe(1);
});

it('marks only the first cell in each row as primary', function (): void {
    $html = renderTable([
        'rows' => [
            ['name' => 'A', 'quantity' => 1],
            ['name' => 'B', 'quantity' => 2],
        ],
    ]);

    expect(substr_count($html, 'class="lyra-table__primary"'))->toBe(2)
        ->and($html)->toMatch('/<tr>\s*<td class="lyra-table__primary"[^>]*>A<\/td>\s*<td[^>]*>1<\/td>\s*<\/tr>/s')
        ->and($html)->not->toMatch('/<th\b[^>]*class="[^"]*lyra-table__primary/');
});

it('applies column alignment to its header and cells only when set', function (): void {
    $html = renderTable([
        'columns' => [
            ['key' => 'name', 'label' => 'Nome'],
            ['key' => 'quantity', 'label' => 'Qtd', 'align' => 'right'],
        ],
        'rows' => [
            ['name' => 'A', 'quantity' => 1],
        ],
    ]);

    expect($html)->toMatch('/<th(?![^>]*style=)[^>]*>Nome<\/th>/')
        ->and($html)->toContain('<th style="text-align: right">Qtd</th>')
        ->and($html)->toMatch('/<td class="lyra-table__primary"(?![^>]*style=)[^>]*>A<\/td>/')
        ->and($html)->toContain('<td style="text-align: right">1</td>')
        ->and(substr_count($html, 'style="text-align: right"'))->toBe(2);
});

it('renders an empty cell when a row key is missing', function (): void {
    $html = renderTable([
        'rows' => [
            ['name' => 'A'],
        ],
    ]);

    expect($html)->toMatch('/<td class="lyra-table__primary"[^>]*>A<\/td>\s*<td[^>]*>\s*<\/td>/s');
});

it('passes attributes to the wrapper and keeps user classes last', function (): void {
    $html = renderTable([
        'class' => 'x y',
        'id' => 'inventory',
        'data-track' => 'table',
    ]);
    $openingTag = tableWrapperOpeningTag($html);

    expect(tableWrapperClass($html))->toBe('lyra-table-wrap x y')
        ->and($openingTag)->toContain('id="inventory"')
        ->and($openingTag)->toContain('data-track="table"')
        ->and($html)->not->toMatch('/<table\b[^>]*(?:id="inventory"|data-track="table")/');
});
