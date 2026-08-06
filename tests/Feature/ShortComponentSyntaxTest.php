<?php

use Illuminate\Support\Facades\Blade;

dataset('lyra anonymous components', function (): array {
    $paths = glob(dirname(__DIR__, 2).'/resources/views/components/*.blade.php');

    if ($paths === false) {
        throw new RuntimeException('Unable to discover the Lyra anonymous components.');
    }

    return collect($paths)
        ->mapWithKeys(function (string $path): array {
            $name = basename($path, '.blade.php');

            return [$name => [$name]];
        })
        ->all();
});

it('renders the short button syntax exactly like the namespaced syntax', function (): void {
    $short = Blade::render('<lyra:button variant="primary">Save</lyra:button>');
    $namespaced = Blade::render('<x-lyra::button variant="primary">Save</x-lyra::button>');

    expect($short)->toBe($namespaced);
});

it('compiles every anonymous component exactly like the namespaced syntax', function (string $name): void {
    $shortSelfClosing = "<lyra:{$name} data-syntax=\"short\" />";
    $namespacedSelfClosing = "<x-lyra::{$name} data-syntax=\"short\" />";
    $shortPaired = "<lyra:{$name} data-syntax=\"short\">Slot content</lyra:{$name}>";
    $namespacedPaired = "<x-lyra::{$name} data-syntax=\"short\">Slot content</x-lyra::{$name}>";

    expect(Blade::compileString($shortSelfClosing))->toBe(Blade::compileString($namespacedSelfClosing))
        ->and(Blade::compileString($shortPaired))->toBe(Blade::compileString($namespacedPaired));
})->with('lyra anonymous components');

it('leaves short syntax inside verbatim blocks byte-identical', function (): void {
    $template = '@verbatim<lyra:button>literal</lyra:button>@endverbatim';

    expect(Blade::render($template))->toBe('<lyra:button>literal</lyra:button>');
});

it('leaves short syntax inside php blocks byte-identical', function (): void {
    $template = <<<'BLADE'
        @php
            $literal = '<lyra:button>literal</lyra:button>';
        @endphp
        {!! $literal !!}
        BLADE;

    expect(Blade::render($template))->toBe('<lyra:button>literal</lyra:button>');
});

it('leaves short syntax inside single-expression php directives byte-identical', function (): void {
    $template = <<<'BLADE'
        @php($literal = sprintf('<lyra:button>%s</lyra:button>', strtoupper('literal')))
        {!! $literal !!}
        BLADE;

    expect(Blade::render($template))->toBe('<lyra:button>LITERAL</lyra:button>');
});

it('preserves multiple raw regions and nested short tags independently', function (): void {
    $template = '@verbatim<lyra:card><lyra:button>one</lyra:button></lyra:card>@endverbatim'
        .'|outside|'
        .'@verbatim<lyra:button>two</lyra:button>@endverbatim';

    expect(Blade::render($template))->toBe(
        '<lyra:card><lyra:button>one</lyra:button></lyra:card>'
        .'|outside|'
        .'<lyra:button>two</lyra:button>',
    );
});

it('supports both self-closing forms', function (): void {
    $namespaced = Blade::render('<x-lyra::separator />');

    expect(Blade::render('<lyra:separator />'))->toBe($namespaced)
        ->and(Blade::render('<lyra:separator/>'))->toBe($namespaced);
});

it('leaves unknown component names byte-identical', function (): void {
    $paired = '<lyra:foo>bar</lyra:foo>';
    $selfClosing = '<lyra:not-a-component />';

    expect(Blade::render($paired))->toBe($paired)
        ->and(Blade::render($selfClosing))->toBe($selfClosing);
});

it('preserves bound directives quoted delimiters and multiline attributes', function (): void {
    $short = <<<'BLADE'
        <lyra:button
            :variant="$variant"
            @class(['tracking-wide', 'opacity-50' => ! $active])
            x-data="{ expanded: 2 > 1 }"
            data-label="Save > continue"
        >Save</lyra:button>
        BLADE;
    $namespaced = <<<'BLADE'
        <x-lyra::button
            :variant="$variant"
            @class(['tracking-wide', 'opacity-50' => ! $active])
            x-data="{ expanded: 2 > 1 }"
            data-label="Save > continue"
        >Save</x-lyra::button>
        BLADE;
    $data = ['variant' => 'danger', 'active' => true];

    expect(Blade::render($short, $data))->toBe(Blade::render($namespaced, $data));
});

it('renders nested short syntax like nested namespaced syntax', function (): void {
    $short = '<lyra:card><lyra:badge tone="success">Ready</lyra:badge></lyra:card>';
    $namespaced = '<x-lyra::card><x-lyra::badge tone="success">Ready</x-lyra::badge></x-lyra::card>';

    expect(Blade::render($short))->toBe(Blade::render($namespaced));
});

it('coexists with namespaced components in one template', function (): void {
    $mixed = '<lyra:card><x-lyra::badge tone="success">Ready</x-lyra::badge></lyra:card>';
    $namespaced = '<x-lyra::card><x-lyra::badge tone="success">Ready</x-lyra::badge></x-lyra::card>';

    expect(Blade::render($mixed))->toBe(Blade::render($namespaced));
});
