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
 *
 * That capture happens as inline PHP in the caller's own compiled view
 * (Illuminate\View\Concerns\ManagesComponents), not inside any method call of
 * ours, so a try/finally in render() can't protect against an exception
 * thrown by a child during slot rendering — render() is simply never reached.
 * __destruct() is the backstop: PHP destroys (and destructs) this object as
 * soon as the exception unwinds past the compiled view's stack frame that
 * held it, so the scope is released exactly once either way.
 */
final class ToastStackComponent extends Component
{
    private bool $scopeReleased = false;

    public function __construct()
    {
        ToastStackScope::push();
    }

    public function render(): callable
    {
        return function (array $data) {
            $this->releaseScope();

            return view()->file(__DIR__.'/../resources/views/components/toast-stack.blade.php', $data);
        };
    }

    public function __destruct()
    {
        $this->releaseScope();
    }

    private function releaseScope(): void
    {
        if ($this->scopeReleased) {
            return;
        }

        $this->scopeReleased = true;
        ToastStackScope::pop();
    }
}
