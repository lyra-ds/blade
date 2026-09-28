<?php

declare(strict_types=1);

namespace LyraDs\Blade;

/**
 * Tracks whether the current render is nested inside <x-lyra::toast-stack>, so
 * <x-lyra::toast> can skip its default role="status" deterministically.
 *
 * A regex on the rendered HTML is order-dependent (attribute order varies with
 * consumer-supplied attributes like x-data). This instead pushes before the
 * stack's slot renders and pops once the slot content is fully captured
 * (see ToastStackComponent), so nested toasts read a plain counter instead of
 * inferring context from markup shape.
 *
 * Blade captures a component's default slot as plain inline PHP in the
 * caller's own compiled view (Illuminate\View\Concerns\ManagesComponents::
 * startComponent/renderComponent) before ToastStackComponent::render() ever
 * runs, so there is no call frame of ours around slot rendering to wrap in a
 * try/finally. `within()` restores the depth that existed before entry
 * (not zero), so nested stacks are still exception-safe, and is the API
 * ToastStackComponent relies on: PHP tears down local variables (and runs
 * their destructors) as an exception unwinds past the frame that held them,
 * so a destructor-backed guard is the only place that can run even when the
 * slot throws before render() is reached.
 */
final class ToastStackScope
{
    private static int $depth = 0;

    public static function push(): void
    {
        self::$depth++;
    }

    public static function pop(): void
    {
        self::$depth = max(0, self::$depth - 1);
    }

    public static function active(): bool
    {
        return self::$depth > 0;
    }

    /**
     * Run $callback with the scope entered, guaranteeing depth is restored to
     * whatever it was before entry — even if $callback throws.
     */
    public static function within(callable $callback): mixed
    {
        self::push();

        try {
            return $callback();
        } finally {
            self::pop();
        }
    }

    /**
     * Reset to a clean slate. Call on each request in long-lived workers
     * (e.g. Octane) so a leaked depth from one request never reaches the
     * next.
     */
    public static function reset(): void
    {
        self::$depth = 0;
    }
}
