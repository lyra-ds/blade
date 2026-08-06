@props([
    'columns' => null,
])

@php
    if ($columns === null) {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrorHandling = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="lyra-formrow-slot-root">'.(string) $slot.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorHandling);

        $columns = 0;
        $slotRoot = $document->getElementById('lyra-formrow-slot-root');

        foreach ($slotRoot?->childNodes ?? [] as $child) {
            if ($child instanceof DOMElement) {
                $columns++;
            }
        }
    }

    $rootAttributes = $attributes
        ->class('lyra-formrow')
        ->merge([
            'style' => "--lyra-formrow-columns: repeat({$columns}, minmax(0, 1fr))",
        ]);
@endphp

<div {{ $rootAttributes }}>{{ $slot }}</div>
