<?php

use Illuminate\Support\Facades\Blade;
use Livewire\Component;
use Livewire\Livewire;

function renderFileUpload(array $props = [], string $attributes = ''): string
{
    $bindings = ['label', 'hint', 'accept', 'maxSizeMB', 'multiple', 'items', 'messages', 'statusLabels', 'cancelLabel', 'retryLabel', 'name', 'id', 'disabled', 'required'];
    $defaults = ['multiple' => true, 'items' => [], 'messages' => [], 'statusLabels' => [], 'disabled' => false, 'required' => false];
    $data = [];
    $tag = [];

    foreach ($bindings as $key) {
        if (array_key_exists($key, $props) || array_key_exists($key, $defaults)) {
            $data[$key] = $props[$key] ?? $defaults[$key];
            $tag[] = sprintf(':%s="$%s"', strtolower((string) preg_replace('/(?<!^)([A-Z])/', '-$1', $key)), $key);
        }
    }

    $extra = collect(array_diff_key($props, array_flip($bindings)))
        ->map(fn (mixed $value, string $name): string => sprintf('%s="%s"', $name, htmlspecialchars((string) $value, ENT_QUOTES)))
        ->implode(' ');

    return Blade::render(
        sprintf('<x-lyra::file-upload %s %s %s />', implode(' ', $tag), $extra, $attributes),
        $data,
    );
}

function fileUploadOpeningTag(string $html, string $target): string
{
    $pattern = match ($target) {
        'root' => '/<div\b(?=[^>]*\bclass="lyra-upload(?: [^"]*)?")[^>]*>/',
        'zone' => '/<label\b(?=[^>]*\bclass="lyra-upload__zone")[^>]*>/',
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
        'bar' => '/<progress\b(?=[^>]*\bclass="lyra-upload__bar")[^>]*>/',
        'cancel' => '/<button\b(?=[^>]*\bclass="lyra-upload__cancel")[^>]*>/',
        'retry' => '/<button\b(?=[^>]*\bclass="lyra-upload__retry")[^>]*>/',
        'remove' => '/<button\b(?=[^>]*\bclass="lyra-upload__remove")[^>]*>/',
        'live' => '/<span\b(?=[^>]*\bclass="lyra-upload__live[^"]*")[^>]*>/',
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

it('emits every exact anatomy class string', function (array $case): void {
    $html = renderFileUpload($case['props']);

    expect(fileUploadClass($html, 'root'))->toBe($case['expected_class']);

    if ($case['expected_classes'] === []) {
        return;
    }

    $classes = $case['expected_classes'];
    $staticTargets = [
        'zone', 'input', 'zone_icon', 'zone_label', 'zone_hint', 'list', 'item', 'item_icon',
        'item_body', 'item_row', 'item_name', 'item_meta', 'bar', 'cancel', 'retry', 'remove', 'live',
    ];

    foreach ($staticTargets as $target) {
        expect(fileUploadClass($html, $target))->toBe($classes[$target]);
    }

    preg_match_all('/<svg\b[^>]*\bclass="([^"]*)"[^>]*>/', $html, $iconMatches);

    expect($classes['zone_drag'])->toBe($classes['zone'].' lyra-upload__zone--drag')
        ->and($classes['item_error'])->toBe($classes['item'].' lyra-upload__item--error')
        ->and($iconMatches[1])->toHaveCount(9)
        ->each->toBe($classes['icon']);
})->with('file upload class emission');

it('renders namespaced and short syntax identically', function (): void {
    $namespaced = Blade::render('<x-lyra::file-upload id="documents" accept=".pdf" />');
    $short = Blade::render('<lyra:file-upload id="documents" accept=".pdf" />');

    expect($short)->toBe($namespaced)
        ->and($short)->toContain('class="lyra-upload"');
});

it('serves the root with a unique id and idle state', function (): void {
    $explicit = renderFileUpload(['id' => 'documents']);
    $first = renderFileUpload();
    $second = renderFileUpload();

    preg_match('/\bid="(lyra-upload-[0-9a-f]+)"/', fileUploadOpeningTag($first, 'root'), $firstId);
    preg_match('/\bid="(lyra-upload-[0-9a-f]+)"/', fileUploadOpeningTag($second, 'root'), $secondId);

    expect(fileUploadOpeningTag($explicit, 'root'))->toContain('id="documents"')
        ->and(fileUploadOpeningTag($explicit, 'root'))->toContain('data-state="idle"')
        ->and($firstId)->toHaveCount(2)
        ->and($secondId)->toHaveCount(2)
        ->and($firstId[1])->not->toBe($secondId[1])
        ->and(fileUploadOpeningTag($first, 'input'))->toContain('id="'.$firstId[1].'-input"')
        ->and(fileUploadOpeningTag($first, 'zone'))->toContain('for="'.$firstId[1].'-input"');
});

it('serves a label dropzone with a sibling native input outside any button', function (): void {
    $html = renderFileUpload([
        'id' => 'documents',
        'accept' => '.pdf,image/*',
        'multiple' => false,
    ]);
    $zone = fileUploadOpeningTag($html, 'zone');
    $input = fileUploadOpeningTag($html, 'input');

    expect($zone)->toContain('for="documents-input"')
        ->and($zone)->toContain('x-bind="zone"')
        ->and($input)->toContain('id="documents-input"')
        ->and($input)->toContain('type="file"')
        ->and($input)->toContain('accept=".pdf,image/*"')
        ->and($input)->not->toContain('hidden')
        ->and($input)->not->toContain('tabindex')
        ->and($input)->toContain('x-bind="input"')
        ->and($input)->not->toContain('multiple')
        ->and($html)->toMatch('#</label>\s*<input#')
        ->and($html)->not->toContain('<button type="button" class="lyra-upload__zone"');
});

it('serves multiple by default and omits an absent accept attribute', function (): void {
    $input = fileUploadOpeningTag(renderFileUpload(), 'input');

    expect($input)->toContain('multiple')
        ->and($input)->not->toContain('accept=')
        ->and($input)->not->toContain('name=');
});

it('serves native form attributes on the input', function (): void {
    $input = fileUploadOpeningTag(renderFileUpload(['name' => 'attachments[]', 'required' => true, 'disabled' => true]), 'input');

    expect($input)->toContain('name="attachments[]"')
        ->and($input)->toContain('required')
        ->and($input)->toContain('disabled');
});

it('generates the helper text from accept and maximum size', function (): void {
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

it('serves an empty polite live region bound to Alpine', function (): void {
    $live = fileUploadOpeningTag(renderFileUpload(), 'live');

    expect($live)->toContain('class="lyra-upload__live lyra-visually-hidden"')
        ->and($live)->toContain('aria-live="polite"')
        ->and($live)->toContain('aria-atomic="true"')
        ->and($live)->toContain('x-bind="liveRegion"')
        ->and(renderFileUpload())->toMatch('#x-bind="liveRegion"\s*></span>#');
});

it('serves the runtime item template with binding objects and actions inside x-if', function (): void {
    $html = renderFileUpload();

    expect($html)->toContain('<template x-for="item in items" :key="item.id">')
        ->and(fileUploadOpeningTag($html, 'item'))->toContain('x-bind="itemBindings(item)"')
        ->and(fileUploadOpeningTag($html, 'item_icon'))->toContain('aria-hidden="true"')
        ->and(fileUploadOpeningTag($html, 'item_name'))->toContain('x-text="item.name"')
        ->and(fileUploadOpeningTag($html, 'item_meta'))->toContain('item.error.message')
        ->and($html)->toContain('<template x-if="item.status === \'uploading\' || item.status === \'canceling\'">')
        ->and(fileUploadOpeningTag($html, 'bar'))->toContain('x-bind="progressBindings(item)"')
        ->and(fileUploadOpeningTag($html, 'bar'))->toContain('x-effect="item.progress.kind === \'determinate\' ? $el.setAttribute(\'value\', item.progress.value) : $el.removeAttribute(\'value\')"')
        ->and($html)->toMatch('#<template x-if="item.status === \'uploading\'">\s*<button[^>]*lyra-upload__cancel#')
        ->and(fileUploadOpeningTag($html, 'cancel'))->toContain("x-bind=\"actionBindings('cancel', item)\"")
        ->and($html)->toMatch('#<template x-if="item.status === \'canceled\' \|\| \(item.status === \'error\' && item.error.retryable\)">\s*<button[^>]*lyra-upload__retry#')
        ->and(fileUploadOpeningTag($html, 'retry'))->toContain("x-bind=\"actionBindings('retry', item)\"")
        ->and($html)->toMatch('#<template x-if="item.status === \'selected\' \|\| [^"]*">\s*<button[^>]*lyra-upload__remove#')
        ->and(fileUploadOpeningTag($html, 'remove'))->toContain("x-bind=\"actionBindings('remove', item)\"");
});

it('translates status text, action labels, and alpine messages', function (): void {
    $html = renderFileUpload([
        'statusLabels' => ['success' => 'Concluído'],
        'cancelLabel' => 'Cancelar',
        'retryLabel' => 'Tentar de novo',
        'messages' => ['remove' => 'Remover {name}', 'success' => '{name} enviado.'],
    ]);
    $root = html_entity_decode(fileUploadOpeningTag($html, 'root'), ENT_QUOTES);

    expect($html)->toContain('Concluído')
        ->and($html)->toContain('Uploading')
        ->and($html)->toContain('>Cancelar</button>')
        ->and($html)->toContain('>Tentar de novo</button>')
        ->and($root)->toContain('messages: {"remove":"Remover {name}","success":"{name} enviado."}');
});

it('serves every extension icon branch and the status icons at React sizes', function (): void {
    $html = renderFileUpload();

    expect($html)->toContain('<template x-if="item.status === \'error\'">');

    foreach (['image', 'file-text', 'file-spreadsheet', 'file-archive', 'film', 'file'] as $name) {
        expect($html)->toContain("=== '{$name}'");
    }

    expect(substr_count($html, 'width="17"'))->toBeGreaterThanOrEqual(7)
        ->and($html)->toContain('width="15"');
});

it('wires only the controlled Alpine options and modelable items', function (): void {
    $items = [[
        'id' => 'seed-1',
        'name' => 'brief.pdf',
        'size' => 2048,
        'type' => 'application/pdf',
        'status' => 'success',
        'attemptId' => 'seed-attempt',
    ]];
    $html = renderFileUpload([
        'id' => 'documents',
        'name' => 'attachments[]',
        'accept' => '.pdf',
        'maxSizeMB' => 8,
        'multiple' => false,
        'items' => $items,
    ]);
    $root = html_entity_decode(fileUploadOpeningTag($html, 'root'), ENT_QUOTES);

    expect($root)->toContain('x-data="lyraFileUpload({ name: "attachments[]", accept: ".pdf", maxSizeMB: 8, multiple: false, items: [{"id":"seed-1","name":"brief.pdf","size":2048,"type":"application/pdf","status":"success","attemptId":"seed-attempt"}] })"')
        ->and($root)->toContain('x-modelable="items"')
        ->and($root)->not->toContain('x-bind="root"');

    foreach (['uploadDuration', 'defaultItems', 'doneLabel', "status === 'done'", 'lyra-upload__bar-fill', 'lyra-upload__check'] as $obsolete) {
        expect($html)->not->toContain($obsolete);
    }
});

it('forwards x-model and lifecycle listeners through the root attributes', function (): void {
    $html = renderFileUpload(['id' => 'documents'], 'x-model="uploadItems" x-on:lyra:file-upload:select="start($event.detail)" x-on:lyra:file-upload:retry="retry($event.detail)" x-on:lyra:file-upload:cancel="cancel($event.detail)" x-on:lyra:file-upload:remove="remove($event.detail)"');
    $root = fileUploadOpeningTag($html, 'root');

    expect($root)->toContain('x-model="uploadItems"')
        ->and($root)->toContain('x-on:lyra:file-upload:select="start($event.detail)"')
        ->and($root)->toContain('x-on:lyra:file-upload:retry="retry($event.detail)"')
        ->and($root)->toContain('x-on:lyra:file-upload:cancel="cancel($event.detail)"')
        ->and($root)->toContain('x-on:lyra:file-upload:remove="remove($event.detail)"')
        ->and(fileUploadOpeningTag($html, 'input'))->not->toContain('x-model');
});

it('supports Livewire model binding through items', function (): void {
    $component = new class extends Component
    {
        public array $uploads = [[
            'id' => 'seed-1',
            'name' => 'brief.pdf',
            'size' => 10,
            'type' => 'application/pdf',
            'status' => 'success',
            'attemptId' => 'a1',
        ]];

        public function render(): string
        {
            return <<<'BLADE'
                <lyra:file-upload id="docs" :items="$uploads" wire:model.live="uploads" />
            BLADE;
        }
    };

    $html = Livewire::test($component)->html();
    $root = html_entity_decode(fileUploadOpeningTag($html, 'root'), ENT_QUOTES);

    expect($root)->toContain('x-modelable="items"')
        ->and($root)->toContain('wire:model.live="uploads"')
        ->and($root)->toContain('items: [{"id":"seed-1","name":"brief.pdf","size":10,"type":"application/pdf","status":"success","attemptId":"a1"}]')
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
