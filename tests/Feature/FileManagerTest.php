<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

function fileManagerFiles(): array
{
    return [
        [
            'id' => 'design',
            'name' => 'Design',
            'type' => 'folder',
            'items' => 4,
            'updated' => 'Today',
            'shared' => true,
        ],
        [
            'id' => 'brief',
            'name' => 'brief.pdf',
            'size' => 1536,
            'updated' => 'Yesterday',
        ],
    ];
}

function renderFileManager(
    array $props = [],
    ?string $breadcrumb = null,
    ?Closure $actions = null,
): string {
    $files = $props['files'] ?? fileManagerFiles();
    $path = $props['path'] ?? ['Projects', 'Lyra'];
    $defaultView = $props['defaultView'] ?? 'list';
    $defaultQuery = $props['defaultQuery'] ?? '';
    $searchPlaceholder = $props['searchPlaceholder'] ?? 'Search files…';
    $emptyMessage = $props['emptyMessage'] ?? 'No files found.';
    $labels = $props['labels'] ?? [];
    $actions ??= $props['actions'] ?? null;
    unset(
        $props['files'],
        $props['path'],
        $props['defaultView'],
        $props['defaultQuery'],
        $props['searchPlaceholder'],
        $props['emptyMessage'],
        $props['labels'],
        $props['actions'],
    );

    $attributes = collect($props)
        ->map(fn (mixed $value, string $name): string => sprintf(
            '%s="%s"',
            $name,
            htmlspecialchars((string) $value, ENT_QUOTES),
        ))
        ->implode(' ');
    $breadcrumbSlot = $breadcrumb === null
        ? ''
        : '<x-slot:breadcrumb>{{ $breadcrumb }}</x-slot:breadcrumb>';

    return Blade::render(
        sprintf(
            '<x-lyra::file-manager :files="$files" :path="$path" :default-view="$defaultView" :default-query="$defaultQuery" :search-placeholder="$searchPlaceholder" :empty-message="$emptyMessage" :labels="$labels" :actions="$actions" %s>%s</x-lyra::file-manager>',
            $attributes,
            $breadcrumbSlot,
        ),
        [
            'files' => $files,
            'path' => $path,
            'defaultView' => $defaultView,
            'defaultQuery' => $defaultQuery,
            'searchPlaceholder' => $searchPlaceholder,
            'emptyMessage' => $emptyMessage,
            'labels' => $labels,
            'actions' => $actions,
            'breadcrumb' => new HtmlString($breadcrumb ?? ''),
        ],
    );
}

function fileManagerOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<div\b(?=[^>]*\bclass="lyra-fm(?: [^"]*)?")[^>]*>/',
        'toolbar' => '/<div\b(?=[^>]*\bclass="lyra-fm__toolbar")[^>]*>/',
        'search' => '/<div\b(?=[^>]*\bclass="lyra-fm__search")[^>]*>/',
        'search_input' => '/<input\b(?=[^>]*\bplaceholder=)[^>]*>/',
        'views' => '/<div\b(?=[^>]*\bclass="lyra-fm__views")[^>]*>/',
        'list_button' => '/<button\b(?=[^>]*\baria-label="List view")[^>]*>/',
        'grid_button' => '/<button\b(?=[^>]*\baria-label="Grid view")[^>]*>/',
        'path' => '/<nav\b(?=[^>]*\bclass="lyra-fm__path")[^>]*>/',
        'crumb' => '/<button\b(?=[^>]*\bclass="lyra-fm__crumb")[^>]*>/',
        'list' => '/<ul\b(?=[^>]*\bclass="lyra-fm__list")[^>]*>/',
        'head' => '/<li\b(?=[^>]*\bclass="lyra-fm__head")[^>]*>/',
        'row' => '/<li\b(?=[^>]*\bclass="lyra-fm__row")[^>]*>/',
        'name' => '/<button\b(?=[^>]*\bclass="lyra-fm__name")[^>]*>/',
        'list_icon' => '/<span\b(?=[^>]*\bclass="lyra-fm__icon lyra-fm__icon--folder")[^>]*>/',
        'label' => '/<span\b(?=[^>]*\bclass="lyra-fm__label")[^>]*>/',
        'shared' => '/<span\b(?=[^>]*\bclass="lyra-fm__shared")[^>]*>/',
        'cell' => '/<span\b(?=[^>]*\bclass="lyra-fm__cell")[^>]*>/',
        'actions' => '/<span\b(?=[^>]*\bclass="lyra-fm__actions")[^>]*>/',
        'more' => '/<span\b(?=[^>]*\bclass="lyra-fm__more")[^>]*>/',
        'grid' => '/<div\b(?=[^>]*\bclass="lyra-fm__grid")[^>]*>/',
        'card' => '/<div\b(?=[^>]*\bclass="lyra-fm__card")[^>]*>/',
        'card_actions' => '/<span\b(?=[^>]*\bclass="lyra-fm__card-actions")[^>]*>/',
        'card_body' => '/<button\b(?=[^>]*\bclass="lyra-fm__card-body")[^>]*>/',
        'grid_icon' => '/<span\b(?=[^>]*\bclass="lyra-fm__icon lyra-fm__icon--big lyra-fm__icon--folder")[^>]*>/',
        'card_meta' => '/<span\b(?=[^>]*\bclass="lyra-fm__card-meta")[^>]*>/',
        'empty' => '/<p\b(?=[^>]*\bclass="lyra-fm__empty")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function fileManagerClass(string $html, string $target): string
{
    $tag = fileManagerOpeningTag($html, $target);
    $matched = preg_match('/\bclass="([^"]*)"/', $tag, $matches);

    expect($matched)->toBe(1);

    return $matches[1];
}

dataset('file manager class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/file-manager.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the file-manager class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits every exact React anatomy class string', function (array $case): void {
    $html = renderFileManager($case['props']);

    expect(fileManagerClass($html, 'root'))->toBe($case['expected_class']);

    foreach ($case['expected_classes'] as $target => $expectedClass) {
        expect(fileManagerClass($html, $target))->toBe($expectedClass);
    }
})->with('file manager class emission');

it('renders namespaced and short syntax identically', function (): void {
    $files = fileManagerFiles();
    $namespaced = Blade::render('<x-lyra::file-manager :files="$files" />', compact('files'));
    $short = Blade::render('<lyra:file-manager :files="$files" />', compact('files'));

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('class="lyra-fm"');
});

it('serves both trees with one filter name per item and preserves server order', function (): void {
    $files = [
        ['id' => 'brief', 'name' => 'brief.pdf', 'size' => 1024],
        ['id' => 'design', 'name' => 'Design & UI', 'type' => 'folder', 'items' => 0],
    ];
    $html = renderFileManager(['files' => $files, 'path' => []]);

    expect(substr_count($html, 'data-name="brief.pdf"'))->toBe(2)
        ->and(substr_count($html, 'data-name="Design &amp; UI"'))->toBe(2)
        ->and($html)->toContain('x-bind="list"')
        ->and($html)->toContain('x-bind="grid"')
        ->and(strpos($html, 'data-name="brief.pdf"'))->toBeLessThan(strpos($html, 'data-name="Design &amp; UI"'))
        ->and(substr_count($html, '>1 KB</span>'))->toBe(2)
        ->and(substr_count($html, '>0 items</span>'))->toBe(2);
});

it('serves the initial view state while both Alpine panes remain present', function (): void {
    $list = renderFileManager();
    $grid = renderFileManager(['defaultView' => 'grid']);

    expect(fileManagerOpeningTag($list, 'list_button'))->toContain('aria-pressed="true"')
        ->and(fileManagerOpeningTag($list, 'list_button'))->toContain('x-bind="listButton"')
        ->and(fileManagerOpeningTag($list, 'grid_button'))->toContain('aria-pressed="false"')
        ->and(fileManagerOpeningTag($list, 'grid_button'))->toContain('x-bind="gridButton"')
        ->and(fileManagerOpeningTag($list, 'list'))->not->toContain('x-cloak')
        ->and(fileManagerOpeningTag($list, 'grid'))->toContain('x-cloak')
        ->and(fileManagerOpeningTag($grid, 'list_button'))->toContain('aria-pressed="false"')
        ->and(fileManagerOpeningTag($grid, 'grid_button'))->toContain('aria-pressed="true"')
        ->and(fileManagerOpeningTag($grid, 'list'))->toContain('x-cloak')
        ->and(fileManagerOpeningTag($grid, 'grid'))->not->toContain('x-cloak');
});

it('serves React breadcrumb buttons or consumer navigation markup', function (): void {
    $default = renderFileManager(['path' => ['Projects', 'Lyra']]);
    $custom = renderFileManager(
        ['path' => ['Ignored']],
        '<a href="/projects">Projects</a><form action="/lyra"><button>Lyra</button></form>',
    );

    expect(fileManagerOpeningTag($default, 'path'))->toContain('aria-label="Current folder"')
        ->and(substr_count($default, 'class="lyra-fm__crumb"'))->toBe(2)
        ->and($default)->toMatch('/Projects\s*<\/button>/')
        ->and($default)->toContain('disabled')
        ->and($default)->toContain('width="15"')
        ->and($default)->toContain('width="13"')
        ->and($custom)->toContain('<a href="/projects">Projects</a>')
        ->and($custom)->toContain('<form action="/lyra"><button>Lyra</button></form>')
        ->and($custom)->not->toContain('class="lyra-fm__crumb"')
        ->and($custom)->not->toContain('>Ignored</button>');
});

it('composes one end-aligned dropdown per item in each tree', function (): void {
    $html = renderFileManager();

    expect(substr_count($html, 'class="lyra-dropdown"'))->toBe(4)
        ->and(substr_count($html, "align: 'end'"))->toBe(4)
        ->and(substr_count($html, 'aria-label="Actions for Design"'))->toBe(2)
        ->and(substr_count($html, 'aria-label="Actions for brief.pdf"'))->toBe(2)
        ->and(substr_count($html, 'Open</button>'))->toBe(4)
        ->and(substr_count($html, 'Rename</button>'))->toBe(4)
        ->and(substr_count($html, 'Download</button>'))->toBe(4)
        ->and(substr_count($html, 'Delete</button>'))->toBe(4)
        ->and(substr_count($html, 'lyra-menu__item--danger'))->toBe(4)
        ->and(substr_count($html, '<hr class="lyra-menu__sep">'))->toBe(4);
});

it('uses a custom action builder for every logical item', function (): void {
    $calls = [];
    $html = renderFileManager(actions: function (array $file) use (&$calls): array {
        $calls[] = $file['id'];

        return [['label' => 'Inspect '.$file['name']]];
    });

    expect($calls)->toBe(['design', 'brief'])
        ->and(substr_count($html, '>Inspect Design</button>'))->toBe(2)
        ->and(substr_count($html, '>Inspect brief.pdf</button>'))->toBe(2)
        ->and($html)->not->toContain('>Delete</button>');
});

it('always serves the bound empty state and reveals it for zero initial matches', function (): void {
    $matching = renderFileManager(['defaultQuery' => 'brief']);
    $missing = renderFileManager(['defaultQuery' => 'missing']);

    expect(fileManagerOpeningTag($matching, 'empty'))->toContain('x-bind="empty"')
        ->and(fileManagerOpeningTag($matching, 'empty'))->toContain('x-cloak')
        ->and(fileManagerOpeningTag($missing, 'empty'))->not->toContain('x-cloak')
        ->and($missing)->toContain('>No files found.</p>')
        ->and(preg_match_all('/data-name="Design"[^>]*\bhidden\b/s', $missing))->toBe(2)
        ->and(preg_match_all('/data-name="brief\.pdf"[^>]*\bhidden\b/s', $missing))->toBe(2);
});

it('wires only the authoritative Alpine options, bindings, and modelable state', function (): void {
    $html = renderFileManager([
        'defaultView' => 'grid',
        'defaultQuery' => "Bob's\nfiles",
    ]);
    $root = html_entity_decode(fileManagerOpeningTag($html, 'root'), ENT_QUOTES);
    $search = fileManagerOpeningTag($html, 'search_input');

    expect($root)->toContain("x-data=\"lyraFileManager({ defaultView: 'grid', defaultQuery: 'Bob\\'s\\nfiles' })\"")
        ->and($root)->toContain('x-modelable="view"')
        ->and($root)->not->toContain('x-bind="root"')
        ->and($search)->toContain('x-modelable="query"')
        ->and($search)->toContain('x-bind="search"')
        ->and(fileManagerOpeningTag($html, 'list_button'))->toContain('x-bind="listButton"')
        ->and(fileManagerOpeningTag($html, 'grid_button'))->toContain('x-bind="gridButton"')
        ->and(fileManagerOpeningTag($html, 'list'))->toContain('x-bind="list"')
        ->and(fileManagerOpeningTag($html, 'grid'))->toContain('x-bind="grid"')
        ->and(fileManagerOpeningTag($html, 'empty'))->toContain('x-bind="empty"')
        ->and($html)->not->toContain('searchInput')
        ->and($html)->not->toContain('listPane')
        ->and($html)->not->toContain('gridPane');
});

it('maps file extensions and metadata exactly like React', function (): void {
    $files = [
        ['id' => 'image', 'name' => 'photo.JPG', 'size' => 512, 'icon' => 'image'],
        ['id' => 'sheet', 'name' => 'data.csv', 'size' => 1048576, 'icon' => 'file-spreadsheet'],
        ['id' => 'archive', 'name' => 'bundle.tar', 'size' => 1073741824, 'icon' => 'file-archive'],
        ['id' => 'video', 'name' => 'clip.webm', 'icon' => 'film'],
        ['id' => 'audio', 'name' => 'sound.ogg', 'icon' => 'music'],
        ['id' => 'other', 'name' => 'LICENSE', 'icon' => 'file'],
    ];
    $html = renderFileManager([
        'files' => array_map(
            static fn (array $file): array => array_diff_key($file, ['icon' => true]),
            $files,
        ),
        'path' => [],
    ]);

    foreach ($files as $file) {
        $single = renderFileManager([
            'files' => [array_diff_key($file, ['icon' => true])],
            'path' => [],
        ]);
        $icon = $file['icon'];
        $listIcon = trim(Blade::render('<x-lyra::icon :name="$icon" :size="17" />', compact('icon')));
        $gridIcon = trim(Blade::render('<x-lyra::icon :name="$icon" :size="26" />', compact('icon')));

        expect($single)->toContain($listIcon)
            ->and($single)->toContain($gridIcon);
    }

    expect(substr_count($html, '>512 B</span>'))->toBe(2)
        ->and(substr_count($html, '>1.0 MB</span>'))->toBe(2)
        ->and(substr_count($html, '>1.0 GB</span>'))->toBe(2)
        ->and(substr_count($html, '>—</span>'))->toBeGreaterThanOrEqual(8);
});

it('merges label overrides over the React defaults', function (): void {
    $html = renderFileManager([
        'labels' => [
            'viewMode' => 'Display',
            'listView' => 'Rows',
            'gridView' => 'Cards',
            'currentFolder' => 'Folder path',
            'headerName' => 'File',
            'itemsCount' => fn (?int $count): string => ($count ?? 0).' entries',
        ],
    ]);

    expect(fileManagerOpeningTag($html, 'views'))->toContain('aria-label="Display"')
        ->and($html)->toContain('aria-label="Rows"')
        ->and($html)->toContain('aria-label="Cards"')
        ->and(fileManagerOpeningTag($html, 'path'))->toContain('aria-label="Folder path"')
        ->and($html)->toContain('>File</span>')
        ->and(substr_count($html, '>4 entries</span>'))->toBe(2)
        ->and($html)->toContain('>Size</span>');
});

it('passes attributes to the root and keeps user classes last', function (): void {
    $html = renderFileManager([
        'class' => 'first second',
        'id' => 'documents',
        'data-track' => 'file-manager',
        'aria-label' => 'Project files',
    ]);
    $root = fileManagerOpeningTag($html, 'root');

    expect(fileManagerClass($html, 'root'))->toBe('lyra-fm first second')
        ->and($root)->toContain('id="documents"')
        ->and($root)->toContain('data-track="file-manager"')
        ->and($root)->toContain('aria-label="Project files"')
        ->and(fileManagerOpeningTag($html, 'search_input'))->not->toContain('data-track="file-manager"');
});
