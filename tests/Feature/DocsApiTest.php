<?php

use Illuminate\Support\Facades\Blade;
use LyraDs\Blade\DocsApiGenerator;

function generatedDocsApi(): array
{
    $root = dirname(__DIR__, 2);
    $manifest = json_decode((string) file_get_contents($root.'/.release-please-manifest.json'), true);

    $json = (new DocsApiGenerator)->generate(
        $root.'/resources/views/components',
        $root.'/resources/docs-examples',
        $root.'/tests/Fixtures/class-emission',
        $manifest['.'],
        static fn (string $template): string => Blade::render($template),
    );

    return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
}

it('stamps the released version on the artifact', function (): void {
    $manifest = json_decode(
        (string) file_get_contents(dirname(__DIR__, 2).'/.release-please-manifest.json'),
        true,
    );

    expect(generatedDocsApi()['version'])->toBe($manifest['.']);
});

it('covers every component, sorted by slug', function (): void {
    $slugs = array_column(generatedDocsApi()['components'], 'slug');
    $expected = documentedComponentSlugs();

    expect($slugs)->toBe($expected);
});

it('carries usage and rendered html for every component', function (): void {
    foreach (generatedDocsApi()['components'] as $component) {
        expect($component['usage'])->not->toBe('', "usage vazio em {$component['slug']}")
            ->and($component['html'])->toContain('lyra-');
    }
});

it('reports button props with defaults and observed values', function (): void {
    $button = collect(generatedDocsApi()['components'])->firstWhere('slug', 'button');

    $variant = collect($button['props'])->firstWhere('name', 'variant');

    expect($variant['required'])->toBeFalse()
        ->and($variant['values'])->toContain('primary')
        ->and($variant['values'])->toContain('danger');
});

it('renders a stable html for components whose ids come from uniqid', function (): void {
    $first = collect(generatedDocsApi()['components'])->keyBy('slug');
    $second = collect(generatedDocsApi()['components'])->keyBy('slug');

    expect($first['input']['html'])->toBe($second['input']['html'])
        ->and($first['input']['html'])->toContain('lyra-input-id1')
        ->and($first['input']['html'])->not->toMatch('/\b[0-9a-f]{13}\b/');
});

it('keeps the committed artifact synchronized with the sources', function (): void {
    $root = dirname(__DIR__, 2);
    $manifest = json_decode((string) file_get_contents($root.'/.release-please-manifest.json'), true);

    $fresh = (new DocsApiGenerator)->generate(
        $root.'/resources/views/components',
        $root.'/resources/docs-examples',
        $root.'/tests/Fixtures/class-emission',
        $manifest['.'],
        static fn (string $template): string => Blade::render($template),
    );

    expect(is_file($root.'/docs/api.json'))->toBeTrue();
    expect(file_get_contents($root.'/docs/api.json'))->toBe($fresh);
});
