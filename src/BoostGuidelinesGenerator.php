<?php

declare(strict_types=1);

namespace LyraDs\Blade;

use JsonException;
use RuntimeException;

final class BoostGuidelinesGenerator
{
    private BladePropParser $propParser;

    public function __construct()
    {
        $this->propParser = new BladePropParser;
    }

    public function generate(string $componentsDirectory, string $fixturesDirectory): string
    {
        $componentPaths = glob($componentsDirectory.'/*.blade.php');

        if ($componentPaths === false) {
            throw new RuntimeException("Unable to discover components in {$componentsDirectory}.");
        }

        if ($componentPaths === []) {
            throw new RuntimeException("No components discovered in {$componentsDirectory}.");
        }

        sort($componentPaths, SORT_STRING);

        $components = array_map(
            fn (string $componentPath): string => $this->generateComponent($componentPath, $fixturesDirectory),
            $componentPaths,
        );

        return $this->preamble()
            ."\n\n## Generated component reference\n\n"
            .'This section is generated from `resources/views/components/*.blade.php` and '
            .'`tests/Fixtures/class-emission/*.json`. Regenerate it with '
            .'`php bin/generate-boost-guidelines`.'
            ."\n\n<!-- Generated component entries: do not edit by hand. -->\n\n"
            .implode("\n\n", $components)
            ."\n";
    }

    private function preamble(): string
    {
        return <<<'MARKDOWN'
# Lyra Blade component guidelines

## Non-negotiable rules

- Use only the components and props listed in the generated reference below. Never invent a Blade component or prop. React design-system components absent from this list have not been ported to Blade and are not available here yet.
- The React component contracts are the source of truth and API parity is mandatory. Do not introduce a Blade-only prop.
- Both tag syntaxes are supported and are exact aliases: `<x-lyra::button>Save</x-lyra::button>` and `<lyra:button>Save</lyra:button>`.
- This package never ships CSS. All appearance comes from the npm package `@lyra-ds/styles`; never write or override `.lyra-*` CSS for these components.
- Phase 1 components are static-only. Do not attribute interactivity, JavaScript behavior, or Alpine directives such as `x-data` and `x-on` to them.
MARKDOWN;
    }

    private function generateComponent(string $componentPath, string $fixturesDirectory): string
    {
        $name = basename($componentPath, '.blade.php');
        $template = file_get_contents($componentPath);

        if ($template === false) {
            throw new RuntimeException("Unable to read component {$componentPath}.");
        }

        $fixturePath = $fixturesDirectory.'/'.$name.'.json';
        $fixture = $this->readFixture($fixturePath);
        $props = $this->propParser->parse($template, $componentPath);
        $classSelectorProps = $this->classSelectorProps($template, $fixture, $props);
        $classCombinations = $this->rootClassCombinations($fixture);
        $lines = array_merge([
            "### {$name}",
            '',
            "Tags: `<x-lyra::{$name}>...</x-lyra::{$name}>` or `<lyra:{$name}>...</lyra:{$name}>`.",
            '',
        ], $this->rootClassLines($classCombinations), [
            '',
        ]);

        if ($props === []) {
            $lines[] = 'Props: none (slots and pass-through attributes only).';

            return implode("\n", $lines);
        }

        $lines[] = 'Props:';

        foreach ($props as $prop) {
            $examples = $this->observedValues($fixture, $prop['name']);
            $default = match (true) {
                ! $prop['hasDefault'] => 'required',
                ! $prop['valueKnown'] => 'default: unknown',
                default => 'default: `'.$this->renderValue($prop['value']).'`',
            };

            if (in_array($prop['name'], $classSelectorProps, true)) {
                $values = $examples;

                if ($prop['valueKnown']) {
                    $values[] = $prop['value'];
                }

                $values = $this->uniqueValues($values);
                $observed = 'class-selector values evidenced by defaults and fixtures: '.implode(', ', array_map(
                    fn (mixed $value): string => '`'.$this->renderValue($value).'`',
                    $values,
                ));
            } else {
                $observed = $examples === []
                    ? 'no fixture examples (fixtures do not constrain this prop)'
                    : 'fixture examples (not constraints): '.implode(', ', array_map(
                        fn (mixed $value): string => '`'.$this->renderValue($value).'`',
                        $examples,
                    ));
            }

            $lines[] = "- `{$prop['name']}` — {$default}; {$observed}.";
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<int, array{props: array<string, mixed>} & array<string, mixed>>
     */
    private function readFixture(string $fixturePath): array
    {
        $contents = file_get_contents($fixturePath);

        if ($contents === false) {
            throw new RuntimeException("Unable to read class-emission fixture {$fixturePath}.");
        }

        try {
            $fixture = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Invalid class-emission fixture {$fixturePath}.", previous: $exception);
        }

        if (! is_array($fixture)) {
            throw new RuntimeException("Class-emission fixture {$fixturePath} must contain an array.");
        }

        if ($fixture === []) {
            throw new RuntimeException("Class-emission fixture {$fixturePath} must contain at least one case.");
        }

        return $fixture;
    }

    /**
     * @param  array<int, array{props: array<string, mixed>} & array<string, mixed>>  $fixture
     * @return array<int, mixed>
     */
    private function observedValues(array $fixture, string $propName): array
    {
        $values = [];

        foreach ($fixture as $case) {
            if (! array_key_exists($propName, $case['props'])) {
                continue;
            }

            $value = $case['props'][$propName];
            $values[$this->renderValue($value)] = $value;
        }

        ksort($values, SORT_STRING);

        return array_values($values);
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, mixed>
     */
    private function uniqueValues(array $values): array
    {
        $unique = [];

        foreach ($values as $value) {
            $unique[$this->renderValue($value)] = $value;
        }

        ksort($unique, SORT_STRING);

        return array_values($unique);
    }

    private function renderValue(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  array<int, array{props: array<string, mixed>} & array<string, mixed>>  $fixture
     * @param  array<int, array{name: string, hasDefault: bool, default: string, valueKnown: bool, value: mixed}>  $props
     * @return array<int, string>
     */
    private function classSelectorProps(string $template, array $fixture, array $props): array
    {
        $selectors = [];

        foreach ($props as $prop) {
            $name = $prop['name'];
            $quotedName = preg_quote($name, '/');
            $interpolatesModifier = preg_match('/--\{\$'.$quotedName.'\}/', $template) === 1
                || preg_match('/--\{\{\s*\$'.$quotedName.'\s*\}\}/', $template) === 1;
            $comparedValues = [];

            if (preg_match_all(
                '/\$'.$quotedName.'\s*===\s*((?:\'(?:[^\'\\\\]|\\\\.)*\')|(?:"(?:[^"\\\\$]|\\\\.)*"))/',
                $template,
                $matches,
            ) !== false) {
                foreach ($matches[1] as $expression) {
                    [$known, $value] = $this->propParser->parseLiteral($expression);

                    if ($known) {
                        $comparedValues[$this->renderValue($value)] = $value;
                    }
                }
            }

            foreach ($fixture as $case) {
                $value = $case['props'][$name] ?? ($prop['valueKnown'] ? $prop['value'] : null);

                if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
                    continue;
                }

                if (! $interpolatesModifier && ! array_key_exists($this->renderValue($value), $comparedValues)) {
                    continue;
                }

                foreach ($case as $key => $classString) {
                    if (! str_starts_with((string) $key, 'expected_') || ! str_ends_with((string) $key, '_class')) {
                        continue;
                    }

                    if (is_string($classString) && preg_match('/(?:^|\s)\S+--'.preg_quote((string) $value, '/').'(?=\s|$)/', $classString) === 1) {
                        $selectors[] = $name;

                        continue 3;
                    }
                }
            }
        }

        return $selectors;
    }

    /**
     * @param  array<int, array{props: array<string, mixed>} & array<string, mixed>>  $fixture
     * @return array<int, string>
     */
    private function rootClassCombinations(array $fixture): array
    {
        $combinations = [];

        foreach ($fixture as $case) {
            $classString = $case['expected_class'] ?? $case['expected_wrapper_class'] ?? null;

            if (! is_string($classString)) {
                throw new RuntimeException('Every class-emission case must describe its root class.');
            }

            $classes = preg_split('/\s+/', trim($classString), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $consumerClasses = preg_split('/\s+/', trim((string) ($case['props']['class'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($consumerClasses as $consumerClass) {
                foreach (array_reverse(array_keys($classes)) as $index) {
                    if ($classes[$index] === $consumerClass) {
                        unset($classes[$index]);

                        break;
                    }
                }
            }

            $combination = implode(' ', array_values($classes));

            if (! in_array($combination, $combinations, true)) {
                $combinations[] = $combination;
            }
        }

        return $combinations;
    }

    /**
     * @param  array<int, string>  $combinations
     * @return array<int, string>
     */
    private function rootClassLines(array $combinations): array
    {
        if (count($combinations) === 1) {
            return ['Root class combination observed in fixtures: `'.$combinations[0].'`.'];
        }

        $fallback = 'Root class alternatives observed in fixtures: `'.implode('` | `', $combinations).'`.';
        $groups = [];

        foreach ($combinations as $combination) {
            $tokens = preg_split('/\s+/', trim($combination), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $groups[count($tokens)][] = $combination;
        }

        $productLines = [];
        $factored = [];

        foreach ($groups as $group) {
            $factors = $this->exactRootClassProduct($group);

            if ($factors === null) {
                continue;
            }

            $line = 'All root class combinations in this product were observed: '
                .implode(' + ', array_map(
                    fn (array $choices): string => count($choices) === 1
                        ? '`'.$choices[0].'`'
                        : 'one of (`'.implode('`, `', $choices).'`)',
                    $factors,
                )).'.';
            $alternatives = 'Root class alternatives observed in fixtures: `'.implode('` | `', $group).'`.';

            if (strlen($line) >= strlen($alternatives)) {
                continue;
            }

            $productLines[] = $line;

            foreach ($group as $combination) {
                $factored[$combination] = true;
            }
        }

        if ($productLines === []) {
            return [$fallback];
        }

        $additional = array_values(array_filter(
            $combinations,
            fn (string $combination): bool => ! isset($factored[$combination]),
        ));

        if ($additional !== []) {
            $productLines[] = count($additional) === 1
                ? 'Additional specific root class combination observed: `'.$additional[0].'`.'
                : 'Additional specific root class combinations observed: `'.implode('` | `', $additional).'`.';
        }

        return strlen(implode("\n", $productLines)) < strlen($fallback) ? $productLines : [$fallback];
    }

    /**
     * @param  array<int, string>  $combinations
     * @return array<int, array<int, string>>|null
     */
    private function exactRootClassProduct(array $combinations): ?array
    {
        $factors = [];

        foreach ($combinations as $combination) {
            $tokens = preg_split('/\s+/', trim($combination), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($tokens as $index => $token) {
                if (! in_array($token, $factors[$index] ?? [], true)) {
                    $factors[$index][] = $token;
                }
            }
        }

        if (count(array_filter($factors, fn (array $choices): bool => count($choices) > 1)) < 2) {
            return null;
        }

        $productSize = array_product(array_map('count', $factors));

        if ($productSize !== count($combinations)) {
            return null;
        }

        $expanded = [[]];

        foreach ($factors as $choices) {
            $next = [];

            foreach ($expanded as $prefix) {
                foreach ($choices as $choice) {
                    $next[] = [...$prefix, $choice];
                }
            }

            $expanded = $next;
        }

        $observedSet = array_fill_keys($combinations, true);
        $expandedSet = array_fill_keys(array_map(fn (array $tokens): string => implode(' ', $tokens), $expanded), true);
        ksort($observedSet, SORT_STRING);
        ksort($expandedSet, SORT_STRING);

        return $expandedSet === $observedSet ? $factors : null;
    }
}
