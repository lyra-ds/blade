{{-- lyra:close bubbles from the root with event.detail = { id }: the id you gave the toast (string), or null if you left it unset. --}}
<div x-data="{ lastClosedId: null }" x-on:lyra:close="lastClosedId = $event.detail.id">
    <lyra:toast id="settings-saved" tone="success" :dismissible="true" close-label="Close notification">Project settings saved.</lyra:toast>
</div>
