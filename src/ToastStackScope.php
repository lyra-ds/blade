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
}
