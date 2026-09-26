<lyra:app-sidebar
    :groups="[
        ['heading' => 'Workspace', 'items' => [
            ['id' => 'overview', 'label' => 'Overview', 'href' => '/overview', 'active' => true],
            ['id' => 'projects', 'label' => 'Projects', 'badge' => '12'],
        ]],
        ['heading' => 'Account', 'items' => [
            ['id' => 'billing', 'label' => 'Billing', 'href' => 'https://billing.example.com', 'target' => '_blank', 'rel' => 'noopener noreferrer'],
            ['id' => 'settings', 'label' => 'Settings'],
        ]],
    ]"
    collapsible
/>
