{{-- lyra:remove bubbles from the root with event.detail = { id }: the id you gave the tag (string), or null if you left it unset. --}}
<div x-data="{ lastRemovedId: null }" x-on:lyra:remove="lastRemovedId = $event.detail.id">
    <lyra:tag id="design-system" :removable="true" remove-label="Remove filter">design-system</lyra:tag>
</div>
