<?php

use Illuminate\Support\Facades\Blade;

function renderThemeScript(): string
{
    return trim(Blade::render("@lyraThemeScript\n", deleteCachedView: true));
}

it('registers the theme script directive', function (): void {
    expect(Blade::getCustomDirectives())->toHaveKey('lyraThemeScript');
});

it('compiles the theme script directive', function (): void {
    expect(Blade::compileString('@lyraThemeScript'))
        ->toContain("localStorage.getItem('lyra-theme')");
});

it('renders the theme script with the Alpine store storage key', function (): void {
    expect(renderThemeScript())
        ->toContain("localStorage.getItem('lyra-theme')");
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

it('renders one blocking inline script without external references', function (): void {
    $output = renderThemeScript();

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
});
