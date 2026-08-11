<?php

use Illuminate\Support\Facades\Blade;

/** @return list<string> */
function documentedComponentSlugs(): array
{
    $paths = glob(dirname(__DIR__, 2).'/resources/views/components/*.blade.php') ?: [];
    $slugs = array_map(
        static fn (string $path): string => basename($path, '.blade.php'),
        $paths,
    );
    sort($slugs, SORT_STRING);

    return $slugs;
}

function docsExamplePath(string $slug): string
{
    return dirname(__DIR__, 2)."/resources/docs-examples/{$slug}.blade.php";
}

it('ships a documentation example for every component', function (string $slug): void {
    expect(is_file(docsExamplePath($slug)))->toBeTrue(
        "Faltou resources/docs-examples/{$slug}.blade.php — todo componente precisa de um exemplo de uso.",
    );
})->with(documentedComponentSlugs());

it('renders every documentation example', function (string $slug): void {
    $example = file_get_contents(docsExamplePath($slug));

    expect($example)->not->toBeFalse();

    $html = Blade::render($example);

    expect(trim($html))->not->toBe('')
        ->and($html)->toContain('lyra-');
})->with(documentedComponentSlugs());

it('writes every example with the short tag syntax', function (string $slug): void {
    $example = file_get_contents(docsExamplePath($slug));

    expect($example)->toContain("<lyra:{$slug}")
        ->and($example)->not->toContain('<?php');
})->with(documentedComponentSlugs());
