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

        Blade::component(ToastStackComponent::class, 'lyra::toast-stack');

        // Octane keeps the worker process (and this static state) alive across
        // requests; a leaked ToastStackScope depth from one request must never
        // reach the next.
        $this->app->terminating(static function (): void {
            ToastStackScope::reset();
        });

        $themeScript = new ThemeScript;

        Blade::directive('lyraThemeScript', $themeScript->compile(...));

        $shortComponentSyntax = new ShortComponentSyntax($componentPath);

        Blade::prepareStringsForCompilationUsing($shortComponentSyntax->compile(...));
    }
}
