@props([
    'name' => null,
    'size' => 20,
    'color' => null,
    'title' => null,
])

<?php /** icon escape hatch: JS-only, not ported. */ ?>

@if (\LyraDs\Blade\IconRegistry::contains($name))
    @php
        $class = $attributes->class(['lyra-icon'])->get('class');
        $svgAttributes = $attributes
            ->except(['class', 'width', 'height', 'stroke', 'role', 'aria-label', 'aria-hidden'])
            ->getAttributes();
        $svgAttributes = array_filter(
            $svgAttributes,
            static fn (mixed $value): bool => $value !== false && $value !== null,
        );
        $hasTitle = $title !== null && trim((string) $title) !== '';
        $svgAttributes = array_merge([
            'class' => $class,
        ], $svgAttributes, [
            'width' => (int) $size,
            'height' => (int) $size,
        ], $hasTitle ? [
            'role' => 'img',
            'aria-label' => e((string) $title, false),
        ] : [
            'aria-hidden' => 'true',
        ]);
        $svg = svg('lucide-'.$name, $svgAttributes)->toHtml();

        if ($color !== null) {
            $svg = preg_replace(
                '/stroke="currentColor"/',
                'stroke="'.e((string) $color, false).'"',
                $svg,
                1,
            );
        }
    @endphp

    {!! $svg !!}
@endif
