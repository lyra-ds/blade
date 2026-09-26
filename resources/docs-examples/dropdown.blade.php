{{-- Note: items with 'disabled' => true render with aria-disabled="true". Under Alpine 1.1.0, arrow-key navigation still visits disabled items (activation is inert; disabled links drop href). Tracking: https://github.com/lyra-ds/lyra/issues/289 --}}
<lyra:dropdown align="end" trigger-variant="secondary" :items="[
    ['type' => 'label', 'label' => 'Project'],
    ['label' => 'Rename project', 'id' => 'rename'],
    ['label' => 'Open in browser', 'id' => 'open', 'href' => '/project'],
    ['label' => 'Transfer ownership', 'id' => 'transfer', 'disabled' => true],
    ['type' => 'separator'],
    ['label' => 'Archive project', 'id' => 'archive', 'danger' => true],
]">
    <x-slot:trigger>Project actions</x-slot:trigger>
</lyra:dropdown>
