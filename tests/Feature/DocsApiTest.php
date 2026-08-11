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

it('names the Alpine binding of interactive components', function (): void {
    $components = collect(generatedDocsApi()['components'])->keyBy('slug');

    expect($components['dropdown']['binding'])->toBe('lyraDropdown')
        ->and($components['time-picker']['binding'])->toBe('lyraTimePicker')
        ->and($components['combobox']['binding'])->toBe('lyraCombobox')
        ->and($components['time-zone-picker']['binding'])->toBe('lyraTimeZonePicker')
        ->and($components['button']['binding'])->toBeNull();
});

it('finds a binding for every component whose template carries x-data', function (): void {
    $root = dirname(__DIR__, 2);
    $components = collect(generatedDocsApi()['components'])->keyBy('slug');

    foreach (glob($root.'/resources/views/components/*.blade.php') ?: [] as $path) {
        $slug = basename($path, '.blade.php');
        // O template só reconhece o factory escrito literalmente — direto ou por echo
        // Blade. Ele é limite inferior: quem o carrega tem binding obrigatoriamente.
        $carriesFactory = (bool) preg_match(
            '/x-data="(?:\{\{\s*\')?(lyra[A-Z]\w*)\(/',
            (string) file_get_contents($path),
        );

        if ($carriesFactory) {
            expect($components[$slug]['binding'])->not->toBeNull("binding ausente em {$slug}");
        }
    }
});

it('never borrows the binding of a component nested in the example', function (): void {
    foreach (generatedDocsApi()['components'] as $component) {
        if ($component['binding'] === null) {
            continue;
        }

        $own = 'lyra'.str_replace('-', '', ucwords($component['slug'], '-'));

        expect($component['binding'])->toBe(
            $own,
            "o exemplo de {$component['slug']} expõe o binding de outro componente antes do seu",
        );
    }
});

it('backs every reported binding with the x-data of the rendered example', function (): void {
    foreach (generatedDocsApi()['components'] as $component) {
        if ($component['binding'] === null) {
            expect($component['html'])->not->toMatch('/x-data="lyra[A-Z]/', "binding ausente em {$component['slug']}");

            continue;
        }

        expect($component['html'])->toContain('x-data="'.$component['binding'].'(');
    }
});

it('renders a stable html for components whose ids come from uniqid', function (): void {
    $first = collect(generatedDocsApi()['components'])->keyBy('slug');
    $second = collect(generatedDocsApi()['components'])->keyBy('slug');

    expect($first['input']['html'])->toBe($second['input']['html'])
        ->and($first['input']['html'])->toContain('lyra-input-id1')
        ->and($first['input']['html'])->not->toMatch('/\b[0-9a-f]{13}\b/');
});

// O guard vigia o CONTEÚDO, não o carimbo de versão. O release-please sobe o manifesto
// no seu próprio PR, sem rodar o gerador, então exigir a versão do manifesto aqui
// deixaria todo release PR vermelho por construção. A versão certa entra no artefato
// que sai anexado à release, gerada pelo workflow depois do bump — e o teste
// 'stamps the released version' cobre esse caminho.
it('keeps the committed artifact synchronized with the sources', function (): void {
    $root = dirname(__DIR__, 2);

    expect(is_file($root.'/docs/api.json'))->toBeTrue();

    $committed = (string) file_get_contents($root.'/docs/api.json');
    $stampedVersion = json_decode($committed, true, flags: JSON_THROW_ON_ERROR)['version'];

    $fresh = (new DocsApiGenerator)->generate(
        $root.'/resources/views/components',
        $root.'/resources/docs-examples',
        $root.'/tests/Fixtures/class-emission',
        $stampedVersion,
        static fn (string $template): string => Blade::render($template),
    );

    expect($committed)->toBe($fresh);
});
