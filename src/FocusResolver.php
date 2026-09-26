<?php

declare(strict_types=1);

namespace LyraDs\Blade;

/**
 * Compiles consumer-supplied focus targets into the `returnFocusTo` callbacks that
 *
 * @lyra-ds/alpine overlays expect. Consumer input is only ever embedded as an escaped
 * JavaScript string literal, never as raw JavaScript.
 */
final class FocusResolver
{
    /** Named resolvers, resolved relative to the overlay element. */
    private const NAMED = [
        'datepicker-trigger' => "() => \$el.closest('.lyra-datepicker-root')?.querySelector('.lyra-datepicker__btn') ?? null",
    ];

    /** Resolver for a CSS selector (or a whitelisted name); null when empty or not a string. */
    public static function selector(mixed $value): ?string
    {
        $value = self::normalize($value);

        if ($value === null) {
            return null;
        }

        return self::NAMED[$value] ?? '() => document.querySelector('.self::literal($value).')';
    }

    /** Resolver for an element id. */
    public static function id(mixed $value): ?string
    {
        $value = self::normalize($value);

        return $value === null ? null : '() => document.getElementById('.self::literal($value).')';
    }

    private static function normalize(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private static function literal(string $value): string
    {
        return json_encode(
            $value,
            JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES,
        );
    }
}
