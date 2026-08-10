<?php

use Illuminate\Support\Facades\Blade;

function renderThemeScript(string $directive = '@lyraThemeScript', array $data = []): string
{
    return trim(Blade::render("{$directive}\n", $data, deleteCachedView: true));
}

it('registers the theme script directive', function (): void {
    expect(Blade::getCustomDirectives())->toHaveKey('lyraThemeScript');
});

it('compiles the theme script directive', function (): void {
    expect(Blade::compileString('@lyraThemeScript'))
        ->toContain('<?php echo \\LyraDs\\Blade\\ThemeScript::render(); ?>');
});

it('renders the theme script with the Alpine store storage key', function (): void {
    expect(renderThemeScript())
        ->toContain("document.documentElement.dataset.lyraThemeKey || 'lyra-theme'")
        ->toContain('localStorage.getItem(k)');
});

it('reads a custom storage key from the document element dataset without writing it', function (): void {
    expect(renderThemeScript())
        ->toContain("var k = document.documentElement.dataset.lyraThemeKey || 'lyra-theme';")
        ->not->toContain('dataset.lyraThemeKey =');
});

it('writes and reads a literal custom storage key', function (): void {
    expect(renderThemeScript("@lyraThemeScript('tenant-a')"))
        ->toContain('var k = "tenant-a";')
        ->toContain('document.documentElement.dataset.lyraThemeKey = k;')
        ->toContain('localStorage.getItem(k)');
});

it('evaluates a PHP expression before embedding the storage key', function (): void {
    expect(renderThemeScript('@lyraThemeScript($key)', ['key' => 'tenant-a']))
        ->toContain('var k = "tenant-a";')
        ->not->toContain('$key');
});

it('uses the dataset fallback for an empty or whitespace-only storage key', function (?string $key): void {
    $output = renderThemeScript('@lyraThemeScript($key)', ['key' => $key]);

    expect($output)
        ->toContain("var k = document.documentElement.dataset.lyraThemeKey || 'lyra-theme';")
        ->not->toContain('dataset.lyraThemeKey =');
})->with([
    'null' => null,
    'empty' => '',
    'spaces' => '   ',
]);

it('safely embeds a hostile storage key without closing the inline script', function (): void {
    $output = renderThemeScript('@lyraThemeScript($key)', [
        'key' => '</script>"\'',
    ]);

    expect(substr($output, 0, -strlen('</script>')))
        ->not->toContain('</script>')
        ->and($output)
        ->toContain('\\u003C\\/script\\u003E\\u0022\\u0027');
});

it('applies a stored light or dark theme to the document element dataset', function (): void {
    expect(renderThemeScript())
        ->toContain("s === 'light' || s === 'dark' ? s")
        ->toContain('document.documentElement.dataset.theme =');
});

it('resolves system and invalid stored values from the system color scheme', function (): void {
    expect(renderThemeScript())
        ->toContain("matchMedia('(prefers-color-scheme: dark)').matches")
        ->toContain("d ? 'dark' : 'light'");
});

it('guards browser API access so failures do not break the page', function (): void {
    expect(renderThemeScript())
        ->toMatch('/try\s*\{.*localStorage\.getItem.*\}\s*catch\s*\([^)]*\)\s*\{\}/s');
});

it('renders one blocking inline script without external references', function (string $directive): void {
    $output = renderThemeScript($directive);

    expect(preg_match_all('/<script\b[^>]*>.*?<\/script>/s', $output))->toBe(1)
        ->and($output)->toStartWith('<script>')
        ->toEndWith('</script>')
        ->not->toContain(' src=')
        ->not->toContain('<link')
        ->not->toContain('http://')
        ->not->toContain('https://')
        ->not->toContain(' defer')
        ->not->toContain(' async')
        ->not->toContain('type="module"');
})->with([
    'without a storage key' => '@lyraThemeScript',
    'with a storage key' => "@lyraThemeScript('tenant-a')",
]);
