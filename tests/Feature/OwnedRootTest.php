<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ComponentAttributeBag;
use Illuminate\View\ViewException;
use LyraDs\Blade\OwnedRoot;

function dataset_owned_roots(): array
{
    return [
        'accordion', 'app-sidebar', 'bottom-sheet', 'cookie-banner', 'create-workspace-dialog', 'dialog', 'drawer',
        'dropdown', 'file-manager', 'file-upload', 'popover', 'segmented-control',
        'sidebar-group', 'table-of-contents', 'tooltip', 'workspace-switcher',
        'toast-stack', 'calendar', 'calendar-view', 'date-picker', 'date-range-picker', 'time-picker',
        'time-input', 'tabs', 'combobox', 'command-palette', 'data-table',
        'recurrence-selector', 'slot-picker', 'weekly-schedule-editor',
        'time-zone-picker', 'otp-input',
    ];
}

dataset('owned roots', dataset_owned_roots());

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

it('guards every component that emits an Alpine x-data', function (): void {
    $components = glob(dirname(__DIR__, 2).'/resources/views/components/*.blade.php');
    expect($components)->not->toBeEmpty();

    foreach ($components as $file) {
        $source = file_get_contents($file);

        if (preg_match('/x-data\s*=/', $source) === 1) {
            expect(str_contains($source, 'OwnedRoot::guard'))->toBeTrue(basename($file).' emits x-data without OwnedRoot::guard');
        }
    }
});

it('covers every guarded component in the owned roots dataset', function (): void {
    $covered = collect(dataset_owned_roots());

    foreach (glob(dirname(__DIR__, 2).'/resources/views/components/*.blade.php') as $file) {
        if (str_contains(file_get_contents($file), 'OwnedRoot::guard')) {
            $slug = basename($file, '.blade.php');
            // code-block only owns x-data when it renders a copy control.
            if ($slug !== 'code-block') {
                expect($covered->contains($slug))->toBeTrue("{$slug} missing from the owned roots dataset");
            }
        }
    }
});

/**
 * Behavioral discovery: a component owns x-data when the HTML it emits binds a
 * Lyra Alpine factory, regardless of how the source assembles the attribute.
 *
 * @return array<string, bool> slug => emits a Lyra x-data binding
 */
function discoverOwnedRoots(): array
{
    $owners = [];

    foreach (glob(dirname(__DIR__, 2).'/resources/docs-examples/*.blade.php') as $file) {
        $slug = basename($file, '.blade.php');
        $html = Blade::render(file_get_contents($file));
        $owners[$slug] = preg_match('/\bx-data="lyra/', $html) === 1
            && preg_match('/<lyra:'.preg_quote($slug, '/').'[\s>\/]/', file_get_contents($file)) === 1;
    }

    return $owners;
}

it('enforces the owned-root policy on every component whose rendered HTML binds x-data', function (): void {
    $owners = discoverOwnedRoots();
    expect(array_filter($owners))->not->toBeEmpty();

    $previous = app()->environment();

    try {
        foreach ($owners as $slug => $owns) {
            $source = ownedRootExample($slug);

            app()->detectEnvironment(fn () => 'testing');

            if ($owns) {
                $thrown = null;
                try {
                    Blade::render($source);
                } catch (Throwable $e) {
                    $thrown = $e;
                }
                expect(str_contains((string) $thrown?->getMessage(), "<lyra:{$slug}> owns x-data"))
                    ->toBeTrue("{$slug} emits x-data=\"lyra…\" but does not guard consumer x-data");

                app()->detectEnvironment(fn () => 'production');
                $html = Blade::render($source);
                expect(str_contains($html, 'consumerState'))->toBeFalse("{$slug} leaks consumer x-data in production");
                expect(preg_match('/<[^>]*\bx-data="lyra[^>]*>/s', $html, $root))->toBe(1);
                expect(substr_count($root[0], 'x-data='))->toBe(1, "{$slug} root carries duplicate x-data");
            } else {
                expect(str_contains(Blade::render($source), 'x-data="consumerState"'))
                    ->toBeTrue("{$slug} has no binding and must pass consumer x-data");
            }
        }
    } finally {
        app()->detectEnvironment(fn () => $previous);
    }
});

it('covers every discovered owner in the owned roots dataset', function (): void {
    $discovered = array_keys(array_filter(discoverOwnedRoots()));
    $missing = array_diff($discovered, dataset_owned_roots(), ['code-block']);

    expect($missing)->toBe([], 'owners missing from dataset: '.implode(', ', $missing));
});
