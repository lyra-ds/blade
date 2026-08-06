<?php

namespace LyraDs\Blade;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

final class BladeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $componentPath = __DIR__.'/../resources/views/components';

        Blade::anonymousComponentPath(
            $componentPath,
            'lyra',
        );

        $shortComponentSyntax = new ShortComponentSyntax($componentPath);

        Blade::prepareStringsForCompilationUsing($shortComponentSyntax->compile(...));
    }
}
