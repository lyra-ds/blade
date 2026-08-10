<?php

declare(strict_types=1);

namespace LyraDs\Blade;

final class ThemeScript
{
    public function compile(string $expression): string
    {
        $expression = trim($expression);

        if ($expression === '') {
            return '<?php echo \\LyraDs\\Blade\\ThemeScript::render(); ?>';
        }

        return "<?php echo \\LyraDs\\Blade\\ThemeScript::render({$expression}); ?>";
    }

    public static function render(?string $storageKey = null): string
    {
        if ($storageKey === null || trim($storageKey) === '') {
            $keyScript = "var k = document.documentElement.dataset.lyraThemeKey || 'lyra-theme';";
        } else {
            $keyLiteral = json_encode(
                $storageKey,
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
            );

            $keyScript = <<<JS
var k = {$keyLiteral};
        document.documentElement.dataset.lyraThemeKey = k;
JS;
        }

        return <<<HTML
<script>
(function () {
    try {
        {$keyScript}
        var s = localStorage.getItem(k);
        var d = matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.dataset.theme = s === 'light' || s === 'dark' ? s : d ? 'dark' : 'light';
    } catch (e) {}
})();
</script>
HTML;
    }
}
