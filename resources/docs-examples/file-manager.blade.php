<div x-data="{ openedFileId: '', navigatedIndex: null }">
    <lyra:file-manager
        :path="['Workspace', 'Design']"
        :files="[
            ['id' => 'brand-assets', 'name' => 'Brand assets', 'type' => 'folder', 'items' => 12, 'updated' => '2026-03-14'],
            ['id' => 'homepage', 'name' => 'homepage.fig', 'type' => 'file', 'size' => 4823000, 'updated' => '2026-03-16', 'shared' => true],
            ['id' => 'style-guide', 'name' => 'style-guide.pdf', 'type' => 'file', 'size' => 918000, 'updated' => '2026-03-10'],
        ]"
        default-view="list"
        search-placeholder="Search files…"
        x-on:lyra:open="openedFileId = $event.detail.id"
        x-on:lyra:navigate="navigatedIndex = $event.detail"
    />
    <p x-show="openedFileId" x-text="`Opened: ${openedFileId}`"></p>
    <p x-show="navigatedIndex !== null" x-text="`Breadcrumb index: ${navigatedIndex}`"></p>
</div>
