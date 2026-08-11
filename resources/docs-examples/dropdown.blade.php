<lyra:dropdown align="end" :items="[
    ['type' => 'label', 'label' => 'Project'],
    ['label' => 'Rename project'],
    ['type' => 'separator'],
    ['label' => 'Archive project', 'danger' => true],
]">
    <x-slot:trigger>Project actions</x-slot:trigger>
</lyra:dropdown>
