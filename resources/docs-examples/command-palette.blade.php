<lyra:command-palette
    placeholder="Type a command or search…"
    empty-message="No results found."
    hotkey="k"
    :groups="[
        ['label' => 'Navigation', 'items' => [
            ['id' => 'go-projects', 'label' => 'Go to projects'],
            ['id' => 'go-billing', 'label' => 'Go to billing'],
        ]],
        ['label' => 'Actions', 'items' => [
            ['id' => 'new-project', 'label' => 'Create a project'],
        ]],
    ]"
/>
