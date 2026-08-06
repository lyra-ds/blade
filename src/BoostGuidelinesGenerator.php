<?php

declare(strict_types=1);

namespace LyraDs\Blade;

use JsonException;
use RuntimeException;

final class BoostGuidelinesGenerator
{
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
        $props = $this->parseProps($template, $componentPath);
        $classSelectorProps = $this->classSelectorProps($template, $fixture, $props);
        $classCombinations = $this->rootClassCombinations($fixture);
        $lines = [
            "### {$name}",
            '',
            "Tags: `<x-lyra::{$name}>...</x-lyra::{$name}>` or `<lyra:{$name}>...</lyra:{$name}>`.",
            '',
            count($classCombinations) === 1
                ? 'Root class combination observed in fixtures: `'.$classCombinations[0].'`.'
                : 'Root class alternatives observed in fixtures: `'.implode('` | `', $classCombinations).'`.',
            '',
        ];

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
     * @return array<int, array{name: string, hasDefault: bool, default: string, valueKnown: bool, value: mixed}>
     */
    private function parseProps(string $template, string $componentPath): array
    {
        $templateWithoutBladeComments = preg_replace_callback(
            '/\{\{--.*?--\}\}/s',
            fn (array $matches): string => str_repeat(' ', strlen($matches[0])),
            $template,
        );

        if ($templateWithoutBladeComments === null) {
            throw new RuntimeException("Unable to inspect @props directives in {$componentPath}.");
        }

        preg_match_all('/@props\b/', $templateWithoutBladeComments, $matches, PREG_OFFSET_CAPTURE);
        $directives = $matches[0];

        if ($directives === []) {
            return [];
        }

        if (count($directives) !== 1) {
            throw new RuntimeException("Unable to locate an unambiguous @props directive in {$componentPath}.");
        }

        $afterDirective = $directives[0][1] + strlen('@props');
        $openingParenthesis = $afterDirective + strspn($templateWithoutBladeComments, " \t\r\n", $afterDirective);

        if (($templateWithoutBladeComments[$openingParenthesis] ?? null) !== '(') {
            throw new RuntimeException("Unable to locate an unambiguous @props directive in {$componentPath}.");
        }

        $expression = $this->extractDirectiveExpression(
            $templateWithoutBladeComments,
            $openingParenthesis + 1,
            $componentPath,
        );
        $array = trim($expression);

        if (! str_starts_with($array, '[') || ! str_ends_with($array, ']')) {
            throw new RuntimeException("The @props directive in {$componentPath} must contain an array literal.");
        }

        $items = $this->splitTopLevel(substr($array, 1, -1), ',');
        $props = [];

        foreach ($items as $item) {
            $item = trim($item);

            if ($item === '') {
                continue;
            }

            [$nameExpression, $defaultExpression] = $this->splitProp($item);
            $name = $this->parseStringLiteral(trim($nameExpression), $componentPath);
            [$valueKnown, $value] = $defaultExpression === null
                ? [false, null]
                : $this->parseLiteral(trim($defaultExpression));

            $props[] = [
                'name' => $name,
                'hasDefault' => $defaultExpression !== null,
                'default' => $defaultExpression === null ? '' : trim($defaultExpression),
                'valueKnown' => $valueKnown,
                'value' => $value,
            ];
        }

        return $props;
    }

    private function extractDirectiveExpression(string $template, int $offset, string $componentPath): string
    {
        $depth = 1;
        $quote = null;
        $escaped = false;
        $length = strlen($template);

        for ($index = $offset; $index < $length; $index++) {
            $character = $template[$index];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === "'" || $character === '"') {
                $quote = $character;
            } elseif ($character === '(') {
                $depth++;
            } elseif ($character === ')') {
                $depth--;

                if ($depth === 0) {
                    return substr($template, $offset, $index - $offset);
                }
            }
        }

        throw new RuntimeException("Unable to find the end of the @props directive in {$componentPath}.");
    }

    /**
     * @return array<int, string>
     */
    private function splitTopLevel(string $value, string $separator): array
    {
        $parts = [];
        $start = 0;
        $depth = 0;
        $quote = null;
        $escaped = false;
        $length = strlen($value);

        for ($index = 0; $index < $length; $index++) {
            $character = $value[$index];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === "'" || $character === '"') {
                $quote = $character;
            } elseif ($character === '[' || $character === '(' || $character === '{') {
                $depth++;
            } elseif ($character === ']' || $character === ')' || $character === '}') {
                $depth--;
            } elseif ($character === $separator && $depth === 0) {
                $parts[] = substr($value, $start, $index - $start);
                $start = $index + 1;
            }
        }

        $parts[] = substr($value, $start);

        return $parts;
    }

    /**
     * @return array{string, string|null}
     */
    private function splitProp(string $item): array
    {
        $match = preg_match('/^(.+?)\s*=>\s*(.+)$/s', $item, $matches);

        if ($match === 1) {
            return [$matches[1], $matches[2]];
        }

        return [$item, null];
    }

    private function parseStringLiteral(string $expression, string $componentPath = '@props default'): string
    {
        $singleQuoted = preg_match("/\\A'(?:[^'\\\\]|\\\\['\\\\])*'\\z/s", $expression) === 1;
        $doubleQuoted = preg_match('/\A"(?:[^"\\\\$]|\\\\[nrtvf\\\\$"])*"\z/s', $expression) === 1;

        if (! $singleQuoted && ! $doubleQuoted) {
            throw new RuntimeException("Invalid string literal {$expression} in {$componentPath}.");
        }

        $quote = $expression[0];
        $value = substr($expression, 1, -1);

        return $quote === "'"
            ? str_replace(['\\\\', "\\'"], ['\\', "'"], $value)
            : stripcslashes($value);
    }

    /**
     * @return array{bool, mixed}
     */
    private function parseLiteral(string $expression): array
    {
        $stringLiteral = preg_match("/\\A'(?:[^'\\\\]|\\\\['\\\\])*'\\z/s", $expression) === 1
            || preg_match('/\A"(?:[^"\\\\$]|\\\\[nrtvf\\\\$"])*"\z/s', $expression) === 1;

        return match (true) {
            $expression === 'null' => [true, null],
            $expression === 'true' => [true, true],
            $expression === 'false' => [true, false],
            preg_match('/\A-?(?:0|[1-9][0-9]*)\z/', $expression) === 1 => [true, (int) $expression],
            preg_match('/\A-?(?:[0-9]+\.[0-9]*|\.[0-9]+)\z/', $expression) === 1 => [true, (float) $expression],
            $stringLiteral => [true, $this->parseStringLiteral($expression)],
            default => [false, null],
        };
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
                    [$known, $value] = $this->parseLiteral($expression);

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
}
