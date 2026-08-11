<?php

declare(strict_types=1);

namespace LyraDs\Blade;

use RuntimeException;

/**
 * Lê o `@props` de um template Blade e devolve os props estruturados. Extraído do
 * gerador de guidelines para que o artefato de API descreva exatamente os mesmos
 * props — uma segunda implementação divergiria em silêncio.
 */
final class BladePropParser
{
    /**
     * @return array<int, array{name: string, hasDefault: bool, default: string, valueKnown: bool, value: mixed}>
     */
    public function parse(string $template, string $componentPath): array
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

    public function parseStringLiteral(string $expression, string $componentPath = '@props default'): string
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
    public function parseLiteral(string $expression): array
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
}
