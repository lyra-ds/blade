<?php

declare(strict_types=1);

namespace LyraDs\Blade;

use RuntimeException;

final class ShortComponentSyntax
{
    private const string PHP_BLOCK_PATTERN = '/((?<!@)@php.*?@endphp)/s';

    private const string PHP_EXPRESSION_PATTERN = '/(?<!@)@php[ \t]*\(/';

    private const string VERBATIM_BLOCK_PATTERN = '/((?<!@)@verbatim\s*.*?@endverbatim)/s';

    private readonly ?string $tagPattern;

    public function __construct(string $componentPath)
    {
        $componentFiles = glob($componentPath.'/*.blade.php') ?: [];
        $componentNames = array_map(
            static fn (string $path): string => preg_quote(basename($path, '.blade.php'), '/'),
            $componentFiles,
        );

        $this->tagPattern = $componentNames === []
            ? null
            : '/(?<=<)(\/?)(?:lyra:)('.implode('|', $componentNames).')(?=[\s\/>])/';
    }

    public function compile(string $value): string
    {
        if ($this->tagPattern === null) {
            return $value;
        }

        return $this->compileOutsideRawBlocks($value);
    }

    private function compileOutsideRawBlocks(string $value): string
    {
        return $this->compileOutsidePattern(
            $value,
            self::VERBATIM_BLOCK_PATTERN,
            fn (string $outsideVerbatim): string => $this->compileOutsidePattern(
                $outsideVerbatim,
                self::PHP_BLOCK_PATTERN,
                $this->compileOutsidePhpExpressions(...),
            ),
        );
    }

    /**
     * @param  callable(string): string  $compile
     */
    private function compileOutsidePattern(string $value, string $pattern, callable $compile): string
    {
        $segments = preg_split($pattern, $value, flags: PREG_SPLIT_DELIM_CAPTURE);

        if ($segments === false) {
            throw new RuntimeException('Unable to identify raw Blade blocks.');
        }

        foreach ($segments as $index => $segment) {
            if ($index % 2 === 0) {
                $segments[$index] = $compile($segment);
            }
        }

        return implode('', $segments);
    }

    private function compileOutsidePhpExpressions(string $value): string
    {
        $compiled = '';
        $offset = 0;

        while (preg_match(self::PHP_EXPRESSION_PATTERN, $value, $matches, PREG_OFFSET_CAPTURE, $offset) === 1) {
            $directive = $matches[0][0];
            $directiveOffset = $matches[0][1];
            $openingParenthesisOffset = $directiveOffset + strrpos($directive, '(');
            $closingParenthesisOffset = $this->findClosingParenthesis($value, $openingParenthesisOffset);

            if ($closingParenthesisOffset === null) {
                break;
            }

            $compiled .= $this->compileTags(substr($value, $offset, $directiveOffset - $offset));
            $compiled .= substr($value, $directiveOffset, $closingParenthesisOffset - $directiveOffset + 1);
            $offset = $closingParenthesisOffset + 1;
        }

        return $compiled.$this->compileTags(substr($value, $offset));
    }

    private function findClosingParenthesis(string $value, int $openingParenthesisOffset): ?int
    {
        $tokens = token_get_all('<?php '.substr($value, $openingParenthesisOffset));
        array_shift($tokens);
        $depth = 0;
        $offset = 0;

        foreach ($tokens as $token) {
            $contents = is_array($token) ? $token[1] : $token;

            if (! is_array($token)) {
                for ($index = 0, $length = strlen($contents); $index < $length; $index++) {
                    if ($contents[$index] === '(') {
                        $depth++;
                    } elseif ($contents[$index] === ')' && --$depth === 0) {
                        return $openingParenthesisOffset + $offset + $index;
                    }
                }
            }

            $offset += strlen($contents);
        }

        return null;
    }

    private function compileTags(string $value): string
    {

        $compiled = preg_replace($this->tagPattern, '$1x-lyra::$2', $value);

        if ($compiled === null) {
            throw new RuntimeException('Unable to compile the Lyra short component syntax.');
        }

        return $compiled;
    }
}
