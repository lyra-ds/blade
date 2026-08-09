<?php

declare(strict_types=1);

namespace LyraDs\Blade;

final class ThemeScript
{
    public function compile(string $expression): string
    {
        return <<<'HTML'
<script>
(function () {
    try {
        var s = localStorage.getItem('lyra-theme');
        var d = matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.dataset.theme = s === 'light' || s === 'dark' ? s : d ? 'dark' : 'light';
    } catch (e) {}
})();
</script>
HTML;
    }
}
