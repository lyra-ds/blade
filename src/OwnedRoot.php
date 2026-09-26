<?php

declare(strict_types=1);

namespace LyraDs\Blade;

use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;

/** Enforces the exclusive Alpine scope of a component that emits lyra* x-data. */
final class OwnedRoot
{
    /** @param list<string> $extra Other attributes already emitted by the component. */
    public static function guard(ComponentAttributeBag $attributes, string $component, array $extra = []): ComponentAttributeBag
    {
        if ($attributes->has('x-data') && app()->environment(['local', 'testing'])) {
            throw new InvalidArgumentException(
                "<lyra:{$component}> owns x-data; wrap it in a parent element that owns your state."
            );
        }

        return $attributes->except(['x-data', ...$extra]);
    }
}
