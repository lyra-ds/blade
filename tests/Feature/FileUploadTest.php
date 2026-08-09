<?php

use Illuminate\Support\Facades\Blade;
use Livewire\Component;
use Livewire\Livewire;

function renderFileUpload(array $props = []): string
{
    $label = $props['label'] ?? 'Drag files here or click to select';
    $hint = $props['hint'] ?? null;
    $accept = $props['accept'] ?? null;
    $maxSizeMB = $props['maxSizeMB'] ?? null;
    $multiple = $props['multiple'] ?? true;
    $uploadDuration = $props['uploadDuration'] ?? 1800;
    $defaultItems = $props['defaultItems'] ?? [];
    $doneLabel = $props['doneLabel'] ?? 'Upload complete';
    $removeLabel = $props['removeLabel'] ?? 'Remove';
    unset(
        $props['label'],
        $props['hint'],
        $props['accept'],
        $props['maxSizeMB'],
        $props['multiple'],
        $props['uploadDuration'],
        $props['defaultItems'],
        $props['doneLabel'],
        $props['removeLabel'],
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
            '<x-lyra::file-upload :label="$label" :hint="$hint" :accept="$accept" :max-size-m-b="$maxSizeMB" :multiple="$multiple" :upload-duration="$uploadDuration" :default-items="$defaultItems" :done-label="$doneLabel" :remove-label="$removeLabel" %s />',
            $attributes,
        ),
        compact(
            'label',
            'hint',
            'accept',
            'maxSizeMB',
            'multiple',
            'uploadDuration',
            'defaultItems',
            'doneLabel',
            'removeLabel',
        ),
    );
}

function fileUploadOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<div\b(?=[^>]*\bclass="lyra-upload(?: [^"]*)?")[^>]*>/',
        'zone' => '/<button\b(?=[^>]*\bclass="lyra-upload__zone")[^>]*>/',
        'input' => '/<input\b(?=[^>]*\btype="file")[^>]*>/',
        'zone_icon' => '/<span\b(?=[^>]*\bclass="lyra-upload__zone-icon")[^>]*>/',
        'zone_label' => '/<span\b(?=[^>]*\bclass="lyra-upload__zone-label")[^>]*>/',
        'zone_hint' => '/<span\b(?=[^>]*\bclass="lyra-upload__zone-hint")[^>]*>/',
        'list' => '/<ul\b(?=[^>]*\bclass="lyra-upload__list")[^>]*>/',
        'item' => '/<li\b(?=[^>]*\bclass="lyra-upload__item")[^>]*>/',
        'item_icon' => '/<span\b(?=[^>]*\bclass="lyra-upload__item-icon")[^>]*>/',
        'item_body' => '/<span\b(?=[^>]*\bclass="lyra-upload__item-body")[^>]*>/',
        'item_row' => '/<span\b(?=[^>]*\bclass="lyra-upload__item-row")[^>]*>/',
        'item_name' => '/<span\b(?=[^>]*\bclass="lyra-upload__item-name")[^>]*>/',
        'item_meta' => '/<span\b(?=[^>]*\bclass="lyra-upload__item-meta")[^>]*>/',
        'bar' => '/<span\b(?=[^>]*\bclass="lyra-upload__bar")[^>]*>/',
        'bar_fill' => '/<span\b(?=[^>]*\bclass="lyra-upload__bar-fill")[^>]*>/',
        'check' => '/<span\b(?=[^>]*\bclass="lyra-upload__check")[^>]*>/',
        'remove' => '/<button\b(?=[^>]*\bclass="lyra-upload__remove")[^>]*>/',
    };
    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1);

    return $matches[0];
}

function fileUploadClass(string $html, string $target): string
{
    $tag = fileUploadOpeningTag($html, $target);
    $matched = preg_match('/\bclass="([^"]*)"/', $tag, $matches);

    return $matched === 1 ? $matches[1] : '';
}

dataset('file upload class emission', function (): array {
    $contents = file_get_contents(dirname(__DIR__).'/Fixtures/class-emission/file-upload.json');

    if ($contents === false) {
        throw new RuntimeException('Unable to read the file-upload class-emission fixture.');
    }

    $cases = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

    return collect($cases)
        ->mapWithKeys(fn (array $case, int $index): array => [
            sprintf('class parity case %02d', $index + 1) => [$case],
        ])
        ->all();
});

it('emits every exact React anatomy class string', function (array $case): void {
    $html = renderFileUpload($case['props']);

    expect(fileUploadClass($html, 'root'))->toBe($case['expected_class']);

    if ($case['expected_classes'] === []) {
        return;
    }

    $classes = $case['expected_classes'];
    $staticTargets = [
        'zone',
        'input',
        'zone_icon',
        'zone_label',
        'zone_hint',
        'list',
        'item',
        'item_icon',
        'item_body',
        'item_row',
        'item_name',
        'bar',
        'bar_fill',
        'check',
        'remove',
    ];

    foreach ($staticTargets as $target) {
        expect(fileUploadClass($html, $target))->toBe($classes[$target]);
    }

    preg_match_all('/<svg\b[^>]*\bclass="([^"]*)"[^>]*>/', $html, $iconMatches);

    expect($classes['zone_drag'])->toBe($classes['zone'].' lyra-upload__zone--drag')
        ->and($classes['item_error'])->toBe($classes['item'].' lyra-upload__item--error')
        ->and(fileUploadClass($html, 'item_meta'))->toBe($classes['item_meta_uploading'])
        ->and($classes['item_meta_done'])->toBe($classes['item_meta_uploading'])
        ->and($classes['item_meta_error'])->toBe($classes['item_meta_uploading'])
        ->and($iconMatches[1])->toHaveCount(10)
        ->each->toBe($classes['icon']);
})->with('file upload class emission');

it('renders namespaced and short syntax identically', function (): void {
    $namespaced = Blade::render('<x-lyra::file-upload id="documents" accept=".pdf" />');
    $short = Blade::render('<lyra:file-upload id="documents" accept=".pdf" />');

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('class="lyra-upload"');
});

it('serves a native dropzone button and hidden file input', function (): void {
    $html = renderFileUpload([
        'accept' => '.pdf,image/*',
        'multiple' => false,
    ]);
    $zone = fileUploadOpeningTag($html, 'zone');
    $input = fileUploadOpeningTag($html, 'input');

    expect($zone)->toContain('type="button"')
        ->and($zone)->toContain('x-bind="zone"')
        ->and($input)->toContain('type="file"')
        ->and($input)->toContain('accept=".pdf,image/*"')
        ->and($input)->toContain('hidden')
        ->and($input)->toContain('tabindex="-1"')
        ->and($input)->toContain('x-bind="input"')
        ->and($input)->not->toContain('multiple');
});

it('serves multiple by default and omits an absent accept attribute', function (): void {
    $input = fileUploadOpeningTag(renderFileUpload(), 'input');

    expect($input)->toContain('multiple')
        ->and($input)->not->toContain('accept=');
});

it('generates the React helper text from accept and maximum size', function (): void {
    $html = renderFileUpload([
        'accept' => '.pdf,image/*',
        'maxSizeMB' => 12,
    ]);

    expect($html)->toContain('>Drag files here or click to select</span>')
        ->and($html)->toContain('>.pdf,image/* · Up to 12 MB per file</span>');
});

it('uses explicit helper text and omits an empty generated hint', function (): void {
    $explicit = renderFileUpload([
        'hint' => 'PDF or image, please',
        'accept' => '.pdf',
        'maxSizeMB' => 12,
    ]);
    $empty = renderFileUpload();

    expect($explicit)->toContain('>PDF or image, please</span>')
        ->and($explicit)->not->toContain('>.pdf · Up to 12 MB per file</span>')
        ->and($empty)->not->toContain('lyra-upload__zone-hint');
});

it('serves the runtime item template with the exact React item tree', function (): void {
    $html = renderFileUpload();

    expect($html)->toContain('<template x-if="items.length > 0">')
        ->and($html)->toContain('<template x-for="item in items" :key="item.id">')
        ->and(fileUploadOpeningTag($html, 'item'))->toContain("x-bind:class=\"{ 'lyra-upload__item--error': item.status === 'error' }\"")
        ->and(fileUploadOpeningTag($html, 'item_icon'))->toContain('aria-hidden="true"')
        ->and(fileUploadOpeningTag($html, 'item_name'))->toContain('x-text="item.name"')
        ->and(fileUploadOpeningTag($html, 'item_meta'))->toContain('x-text="item.status === \'error\' ? item.error : item.status === \'done\' ? formatBytes(item.size) : `${Math.round(item.progress)}%`"')
        ->and($html)->toContain('<template x-if="item.status === \'uploading\'">')
        ->and(fileUploadOpeningTag($html, 'bar_fill'))->toContain('x-bind:style="`width: ${item.progress}%`"')
        ->and($html)->toContain('<template x-if="item.status === \'done\'">')
        ->and(fileUploadOpeningTag($html, 'check'))->toContain('role="img"')
        ->and(fileUploadOpeningTag($html, 'check'))->toContain('aria-label="Upload complete"')
        ->and(fileUploadOpeningTag($html, 'remove'))->toContain('type="button"')
        ->and(fileUploadOpeningTag($html, 'remove'))->toContain("x-bind:aria-label=\"'Remove ' + item.name\"")
        ->and(fileUploadOpeningTag($html, 'remove'))->toContain('x-on:click="remove(item.id)"');
});

it('serves every extension icon branch and the status icons at React sizes', function (): void {
    $html = renderFileUpload();

    expect($html)->toContain('<template x-if="item.status === \'error\'">');

    foreach (['image', 'file-text', 'file-spreadsheet', 'file-archive', 'film', 'file'] as $name) {
        expect($html)->toContain("iconFor(item.name) === '{$name}'");
    }

    expect(substr_count($html, 'width="17"'))->toBeGreaterThanOrEqual(8)
        ->and($html)->toContain('width="15"')
        ->and(substr_count($html, '<template x-if='))->toBeGreaterThanOrEqual(10);
});

it('wires only authoritative Alpine options, binding objects, and modelable state', function (): void {
    $defaultItems = [[
        'id' => 'seed-1',
        'name' => 'brief.pdf',
        'size' => 2048,
        'progress' => 100,
        'status' => 'done',
    ]];
    $html = renderFileUpload([
        'maxSizeMB' => 8,
        'multiple' => false,
        'uploadDuration' => 900,
        'defaultItems' => $defaultItems,
    ]);
    $root = html_entity_decode(fileUploadOpeningTag($html, 'root'), ENT_QUOTES);

    expect($root)->toContain('x-data="lyraFileUpload({ maxSizeMB: 8, multiple: false, uploadDuration: 900, defaultItems: [{"id":"seed-1","name":"brief.pdf","size":2048,"progress":100,"status":"done"}] })"')
        ->and($root)->toContain('x-modelable="items"')
        ->and($root)->not->toContain('x-bind="root"')
        ->and(fileUploadOpeningTag($html, 'zone'))->toContain('x-bind="zone"')
        ->and(fileUploadOpeningTag($html, 'input'))->toContain('x-bind="input"')
        ->and($html)->not->toContain('x-bind="removeButton"');
});

it('supports Livewire model binding through items', function (): void {
    $component = new class extends Component
    {
        public array $uploads = [[
            'id' => 'seed-1',
            'name' => 'brief.pdf',
            'progress' => 100,
            'status' => 'done',
        ]];

        public function render(): string
        {
            return <<<'BLADE'
                <lyra:file-upload :default-items="$uploads" wire:model.live="uploads" />
            BLADE;
        }
    };

    $html = Livewire::test($component)->html();
    $root = html_entity_decode(fileUploadOpeningTag($html, 'root'), ENT_QUOTES);

    expect($root)->toContain('x-modelable="items"')
        ->and($root)->toContain('wire:model.live="uploads"')
        ->and($root)->toContain('defaultItems: [{"id":"seed-1","name":"brief.pdf","progress":100,"status":"done"}]')
        ->and(fileUploadOpeningTag($html, 'input'))->not->toContain('wire:model');
});

it('passes attributes to the root and keeps user classes last', function (): void {
    $html = Blade::render(<<<'BLADE'
        <x-lyra::file-upload
            id="documents"
            class="first second"
            data-track="upload"
            aria-label="Project files"
        />
        BLADE);
    $root = fileUploadOpeningTag($html, 'root');
    $input = fileUploadOpeningTag($html, 'input');

    expect(fileUploadClass($html, 'root'))->toBe('lyra-upload first second')
        ->and($root)->toContain('id="documents"')
        ->and($root)->toContain('data-track="upload"')
        ->and($root)->toContain('aria-label="Project files"')
        ->and($input)->not->toContain('data-track="upload"')
        ->and($input)->not->toContain('aria-label="Project files"');
});
