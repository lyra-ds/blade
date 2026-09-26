<lyra:workspace-switcher
    current="acme"
    create
    create-label="Create workspace"
    :labels="['listLabel' => 'Workspaces', 'placeholder' => 'Select workspace', 'members' => ['one' => ':count member', 'other' => ':count members']]"
    :workspaces="[
        ['id' => 'acme', 'name' => 'Acme Inc.', 'plan' => 'Pro', 'members' => 24, 'href' => '/acme'],
        ['id' => 'lyra', 'name' => 'Lyra Design', 'plan' => 'Starter', 'members' => 6, 'href' => '/lyra'],
    ]"
/>
