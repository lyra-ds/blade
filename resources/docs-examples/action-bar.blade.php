{{-- lyra:clear bubbles from the root with event.detail = { id }: the id you gave the action bar (string), or null if you left it unset. --}}
<div x-data="{ lastClearedId: null }" x-on:lyra:clear="lastClearedId = $event.detail.id">
    <lyra:action-bar id="file-selection" :count="3" label="files selected" :clearable="true" clear-label="Clear selection">
        <lyra:button variant="secondary" size="sm">Move</lyra:button>
        <lyra:button variant="danger" size="sm">Delete</lyra:button>
    </lyra:action-bar>
</div>
