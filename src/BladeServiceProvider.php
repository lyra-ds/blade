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

        // Class-backed (not anonymous) so its lyraToastStackContext public
        // property lands in Blade's componentData before the slot renders,
        // making it visible to nested <x-lyra::toast>'s @aware. See
        // ToastStackComponent for why no static guard/reset is needed.
        Blade::component(ToastStackComponent::class, 'lyra::toast-stack');

        $themeScript = new ThemeScript;

        Blade::directive('lyraThemeScript', $themeScript->compile(...));

        $shortComponentSyntax = new ShortComponentSyntax($componentPath);

        Blade::prepareStringsForCompilationUsing($shortComponentSyntax->compile(...));
    }
}
