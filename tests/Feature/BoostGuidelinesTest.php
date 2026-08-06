<?php

use LyraDs\Blade\BoostGuidelinesGenerator;

/**
 * @param  Closure(string): void  $assertions
 */
function withGuidelinesFixture(string $template, string $fixture, Closure $assertions): void
{
    $root = sys_get_temp_dir().'/lyra-blade-guidelines-'.bin2hex(random_bytes(8));
    $components = $root.'/components';
    $fixtures = $root.'/fixtures';
    mkdir($components, 0755, true);
    mkdir($fixtures, 0755, true);

    try {
        file_put_contents($components.'/sample.blade.php', $template);
        file_put_contents($fixtures.'/sample.json', $fixture);

        $assertions((new BoostGuidelinesGenerator)->generate($components, $fixtures));
    } finally {
        foreach (glob($components.'/*') ?: [] as $path) {
            unlink($path);
        }

        foreach (glob($fixtures.'/*') ?: [] as $path) {
            unlink($path);
        }

        rmdir($components);
        rmdir($fixtures);
        rmdir($root);
    }
}

it('keeps the committed Boost guidelines synchronized with the component sources', function (): void {
    expect(class_exists(BoostGuidelinesGenerator::class))->toBeTrue();

    $root = dirname(__DIR__, 2);
    $guidelines = (new BoostGuidelinesGenerator)->generate(
        $root.'/resources/views/components',
        $root.'/tests/Fixtures/class-emission',
    );
    $guidelinesPath = $root.'/resources/boost/guidelines/lyra-blade.md';

    expect(is_file($guidelinesPath))->toBeTrue();

    $committed = file_get_contents($guidelinesPath);

    expect($committed)->not->toBeFalse()
        ->and($guidelines)->toBe($committed);
});

it('renders only root class combinations that fixtures actually emit', function (): void {
    withGuidelinesFixture(
        <<<'BLADE'
@props([
    'orientation' => 'horizontal',
])
<div></div>
BLADE,
        <<<'JSON'
[
    {"props":{},"expected_class":"lyra-separator"},
    {"props":{"orientation":"vertical"},"expected_class":"lyra-separator lyra-separator--vertical"},
    {"props":{"label":"or"},"expected_class":"lyra-separator--label"}
]
JSON,
        function (string $guidelines): void {
            expect($guidelines)->toContain(
                'Root class alternatives observed in fixtures: `lyra-separator` | `lyra-separator lyra-separator--vertical` | `lyra-separator--label`.',
            )->not->toContain(
                '`lyra-separator lyra-separator--vertical lyra-separator--label`',
            );
        },
    );
});

it('does not infer class selectors from coincidental free-form fixture values', function (): void {
    withGuidelinesFixture(
        <<<'BLADE'
@props([
    'orientation' => 'horizontal',
    'label' => null,
])
<div @class([
    'lyra-separator',
    'lyra-separator--vertical' => $orientation === 'vertical',
])></div>
BLADE,
        <<<'JSON'
[
    {"props":{},"expected_class":"lyra-separator"},
    {"props":{"orientation":"vertical","label":"vertical"},"expected_class":"lyra-separator lyra-separator--vertical"}
]
JSON,
        function (string $guidelines): void {
            expect($guidelines)->toContain(
                '- `orientation` — default: `"horizontal"`; class-selector values evidenced by defaults and fixtures: `"horizontal"`, `"vertical"`.',
            )->toContain(
                '- `label` — default: `null`; fixture examples (not constraints): `"vertical"`.',
            );
        },
    );
});

it('preserves an intrinsic class when a consumer class has the same token', function (): void {
    withGuidelinesFixture(
        "<div></div>\n",
        <<<'JSON'
[
    {"props":{},"expected_class":"lyra-sample"},
    {"props":{"class":"lyra-sample"},"expected_class":"lyra-sample lyra-sample"}
]
JSON,
        function (string $guidelines): void {
            expect($guidelines)->toContain(
                'Root class combination observed in fixtures: `lyra-sample`.',
            );
        },
    );
});

it('parses a props directive with whitespace before its parenthesis', function (): void {
    withGuidelinesFixture(
        "@props (['tone' => 'info'])\n<div></div>\n",
        '[{"props":{},"expected_class":"lyra-sample"}]',
        function (string $guidelines): void {
            expect($guidelines)->toContain('- `tone` — default: `"info"`;');
        },
    );
});

it('ignores props examples inside Blade comments', function (): void {
    withGuidelinesFixture(
        "{{-- @props(['wrong' => 'example']) --}}\n@props(['right' => 'actual'])\n<div></div>\n",
        '[{"props":{},"expected_class":"lyra-sample"}]',
        function (string $guidelines): void {
            expect($guidelines)->toContain('- `right` — default: `"actual"`;')
                ->not->toContain('- `wrong`');
        },
    );
});

it('reports defaults it cannot parse with certainty as unknown', function (): void {
    withGuidelinesFixture(
        <<<'BLADE'
@props([
    'concatenated' => "in"."fo",
    'scientific' => 1e-3,
    'octal' => 012,
])
<div></div>
BLADE,
        '[{"props":{},"expected_class":"lyra-sample"}]',
        function (string $guidelines): void {
            expect($guidelines)->toContain('- `concatenated` — default: unknown;')
                ->toContain('- `scientific` — default: unknown;')
                ->toContain('- `octal` — default: unknown;');
        },
    );
});

it('fails with the component path when a props directive cannot be located', function (): void {
    withGuidelinesFixture(
        "@props ['tone' => 'info']\n<div></div>\n",
        '[{"props":{},"expected_class":"lyra-sample"}]',
        fn (string $guidelines): null => null,
    );
})->throws(RuntimeException::class, 'sample.blade.php');

it('fails with the component path when multiple props directives are present', function (): void {
    withGuidelinesFixture(
        "@props(['first' => true])\n@props(['second' => true])\n<div></div>\n",
        '[{"props":{},"expected_class":"lyra-sample"}]',
        fn (string $guidelines): null => null,
    );
})->throws(RuntimeException::class, 'sample.blade.php');

it('fails when component discovery returns no components', function (): void {
    $root = sys_get_temp_dir().'/lyra-blade-guidelines-'.bin2hex(random_bytes(8));
    $components = $root.'/components';
    $fixtures = $root.'/fixtures';
    mkdir($components, 0755, true);
    mkdir($fixtures, 0755, true);

    try {
        (new BoostGuidelinesGenerator)->generate($components, $fixtures);
    } finally {
        rmdir($components);
        rmdir($fixtures);
        rmdir($root);
    }
})->throws(RuntimeException::class, 'No components discovered');

it('fails when a component fixture contains no cases', function (): void {
    withGuidelinesFixture(
        "<div></div>\n",
        '[]',
        fn (string $guidelines): null => null,
    );
})->throws(RuntimeException::class, 'sample.json');

it('does not turn slot fixture values into root class placeholders', function (): void {
    withGuidelinesFixture(
        "@props(['orientation' => 'horizontal'])\n<div></div>\n",
        '[{"props":{"label":"or"},"expected_class":"lyra-separator--label"}]',
        function (string $guidelines): void {
            expect($guidelines)->toContain(
                'Root class combination observed in fixtures: `lyra-separator--label`.',
            );
        },
    );
});
