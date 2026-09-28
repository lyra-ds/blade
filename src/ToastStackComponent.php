<?php

declare(strict_types=1);

namespace LyraDs\Blade;

use Illuminate\View\Component;

/**
 * Class-backed wrapper around the toast-stack view. The constructor runs
 * before the default slot renders (Blade evaluates a component's constructor
 * at the tag's opening point, before capturing its slot content), and render()
 * runs only once the slot is fully captured — the exact window ToastStackScope
 * needs to bracket so nested <x-lyra::toast> children can see it.
 */
final class ToastStackComponent extends Component
{
    public function __construct()
    {
        ToastStackScope::push();
    }

    public function render(): callable
    {
        return function (array $data) {
            ToastStackScope::pop();

            return view()->file(__DIR__.'/../resources/views/components/toast-stack.blade.php', $data);
        };
    }
}
