<?php

declare(strict_types=1);

namespace LyraDs\Blade;

use RuntimeException;

/**
 * Monta o artefato de API consumido por lyra-ds.dev. Descreve o que os componentes já
 * são — props do @props, snippet curado e o HTML que ele realmente produz. Nada aqui
 * inventa contrato: valores observados saem das fixtures de class-emission, e ausência
 * de observação vira lista vazia, nunca um chute.
 */
final class DocsApiGenerator
{
    /** @param  callable(string): string  $render */
    public function generate(
        string $componentsDirectory,
        string $examplesDirectory,
        string $fixturesDirectory,
        string $version,
        callable $render,
    ): string {
        $paths = glob($componentsDirectory.'/*.blade.php');

        if ($paths === false || $paths === []) {
            throw new RuntimeException("Unable to discover components in {$componentsDirectory}.");
        }

        // Ordenado por slug, não por caminho: `checkbox-group.blade.php` precede
        // `checkbox.blade.php` como string de arquivo, e o contrato promete slug.
        $pathsBySlug = [];

        foreach ($paths as $path) {
            $pathsBySlug[basename($path, '.blade.php')] = $path;
        }

        ksort($pathsBySlug, SORT_STRING);

        $parser = new BladePropParser;
        $components = [];

        foreach ($pathsBySlug as $slug => $path) {
            $template = file_get_contents($path);
            $examplePath = $examplesDirectory."/{$slug}.blade.php";

            if ($template === false || ! is_file($examplePath)) {
                throw new RuntimeException("Missing template or documentation example for {$slug}.");
            }

            $usage = rtrim((string) file_get_contents($examplePath), "\n");
            $fixture = $this->readFixture($fixturesDirectory."/{$slug}.json");

            $components[] = [
                'slug' => $slug,
                'usage' => $usage,
                'html' => $this->stabilizeIds(trim($render($usage))),
                'binding' => null,
                'props' => array_map(
                    fn (array $prop): array => [
                        'name' => $prop['name'],
                        'default' => $prop['hasDefault'] ? $prop['default'] : null,
                        'required' => ! $prop['hasDefault'],
                        'values' => $this->observedValues($fixture, $prop['name']),
                    ],
                    $parser->parse($template, $path),
                ),
            ];
        }

        return json_encode(
            ['version' => $version, 'components' => $components],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        )."\n";
    }

    /**
     * Doze componentes derivam ids de `uniqid()`, então o mesmo snippet renderiza um
     * HTML diferente a cada execução. Sem isto o artefato geraria diff a cada
     * regeneração e o teste de frescor nunca poderia morder. Cada token volátil vira
     * `idN` na ordem em que aparece — determinístico e legível na documentação.
     */
    private function stabilizeIds(string $html): string
    {
        $tokens = [];

        return (string) preg_replace_callback(
            '/\b[0-9a-f]{13}\b/',
            function (array $matches) use (&$tokens): string {
                $tokens[$matches[0]] ??= 'id'.(count($tokens) + 1);

                return $tokens[$matches[0]];
            },
            $html,
        );
    }

    /** @return list<array<string, mixed>> */
    private function readFixture(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  list<array<string, mixed>>  $fixture
     * @return list<string>
     */
    private function observedValues(array $fixture, string $prop): array
    {
        $values = [];

        foreach ($fixture as $case) {
            $value = $case['props'][$prop] ?? null;

            if (! is_string($value) || in_array($value, $values, true)) {
                continue;
            }

            $values[] = $value;
        }

        sort($values, SORT_STRING);

        return $values;
    }
}
