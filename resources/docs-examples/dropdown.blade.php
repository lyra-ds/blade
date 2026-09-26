<lyra:dropdown align="end" :items="[
    ['type' => 'label', 'label' => 'Project'],
    ['label' => 'Rename project', 'id' => 'rename'],
    ['label' => 'Open in browser', 'id' => 'open', 'href' => '/project'],
    ['type' => 'separator'],
    ['label' => 'Archive project', 'id' => 'archive', 'danger' => true],
]">
    <x-slot:trigger>Project actions</x-slot:trigger>
</lyra:dropdown>
