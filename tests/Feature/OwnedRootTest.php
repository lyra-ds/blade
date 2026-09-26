<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ViewException;
use LyraDs\Blade\OwnedRoot;

dataset('owned roots', [
    'accordion', 'app-sidebar', 'bottom-sheet', 'cookie-banner', 'dialog', 'drawer',
    'dropdown', 'file-manager', 'file-upload', 'popover', 'segmented-control',
    'sidebar-group', 'table-of-contents', 'tooltip', 'workspace-switcher',
    'toast-stack', 'calendar', 'date-picker', 'date-range-picker', 'time-picker',
    'time-input', 'tabs', 'combobox', 'command-palette', 'data-table',
    'recurrence-selector', 'slot-picker', 'weekly-schedule-editor',
    'time-zone-picker',
]);

function ownedRootExample(string $slug): string
{
    $source = file_get_contents(dirname(__DIR__, 2)."/resources/docs-examples/{$slug}.blade.php");
    expect($source)->not->toBeFalse();

    return preg_replace('/<lyra:'.preg_quote($slug, '/').'(?=[\s>\/])/', '<lyra:'.$slug.' x-data="consumerState"', $source, 1);
}

it('rejects consumer x-data on an owned root in development', function (string $slug): void {
    expect(fn () => Blade::render(ownedRootExample($slug)))
        ->toThrow(ViewException::class, "<lyra:{$slug}> owns x-data");
})->with('owned roots');

it('throws an InvalidArgumentException from the guard in local and testing', function (): void {
    foreach (['local', 'testing'] as $environment) {
        app()->detectEnvironment(fn () => $environment);

        expect(fn () => OwnedRoot::guard(new ComponentAttributeBag(['x-data' => 'consumerState']), 'tabs'))
            ->toThrow(InvalidArgumentException::class, 'wrap it in a parent element');
    }
});

it('silently removes consumer x-data from an owned root in production', function (string $slug): void {
    $previous = app()->environment();
    app()->detectEnvironment(fn () => 'production');

    try {
        $html = Blade::render(ownedRootExample($slug));
        expect(preg_match('/<[^>]*\bx-data="lyra[^>]*>/s', $html, $root))->toBe(1);
        expect($html)->not->toContain('consumerState')
            ->and(substr_count($root[0], 'x-data='))->toBe(1);
    } finally {
        app()->detectEnvironment(fn () => $previous);
    }
})->with('owned roots');

it('passes consumer x-data through when code-block has no copy control', function (): void {
    expect(Blade::render('<lyra:code-block x-data="consumerState">Code</lyra:code-block>'))
        ->toContain('x-data="consumerState"');
});

it('passes consumer x-data through on a component without a Lyra binding', function (): void {
    expect(Blade::render('<lyra:badge x-data="consumerState">Label</lyra:badge>'))
        ->toContain('x-data="consumerState"');
});

it('removes duplicate component-owned Alpine attributes', function (string $slug, string $attribute): void {
    $source = ownedRootExample($slug);
    $source = str_replace(' x-data="consumerState"', ' '.$attribute.'="consumerBinding"', $source);
    $html = Blade::render($source);

    expect($html)->not->toContain('consumerBinding');
})->with([
    ['accordion', 'x-modelable'],
    ['dropdown', 'x-modelable'],
    ['dialog', 'x-bind'],
    ['sidebar-group', 'x-bind'],
]);

it('rejects consumer x-data when code-block has a copy control', function (): void {
    expect(fn () => Blade::render('<lyra:code-block copy-label="Copy" copied-label="Copied" x-data="consumerState">Code</lyra:code-block>'))
        ->toThrow(ViewException::class, '<lyra:code-block> owns x-data');
});

it('renders documentation examples without duplicate x-data on any opening tag', function (string $slug): void {
    $html = Blade::render(file_get_contents(dirname(__DIR__, 2)."/resources/docs-examples/{$slug}.blade.php"));
    preg_match_all('/<[^>]+>/s', $html, $tags);

    foreach ($tags[0] as $tag) {
        expect(preg_match_all('/\bx-data\s*=/', $tag))->toBeLessThanOrEqual(1);
    }
})->with('owned roots');
