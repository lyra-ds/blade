<?php

declare(strict_types=1);

namespace LyraDs\Blade;

use Illuminate\View\Component;

/**
 * Thin class-backed wrapper so <x-lyra::toast-stack> exposes a marker its
 * <x-lyra::toast> children can read with @aware.
 *
 * $lyraToastStackContext is a public property, so Illuminate\View\Component::data()
 * (plain reflection over public properties) includes it automatically. That
 * data is captured into Blade's own componentData stack by
 * ManagesComponents::startComponent() — which runs, and records it, before
 * the slot content between the opening and closing tags is captured — so a
 * nested <x-lyra::toast> reading it via @aware sees it deterministically,
 * with no post-render regex on the rendered HTML.
 *
 * No static state, no destructor: Blade's componentData/slot stacks are
 * local to Illuminate\View\View::render(), which already catches any
 * exception, calls Factory::flushState(), and rethrows — so a slot that
 * throws can never leave a stale marker behind, for this component or a
 * standalone toast rendered later in the same request.
 */
final class ToastStackComponent extends Component
{
    public bool $lyraToastStackContext = true;

    public function render(): callable
    {
        return fn (array $data) => view()->file(__DIR__.'/../resources/views/components/toast-stack.blade.php', $data);
    }
}
