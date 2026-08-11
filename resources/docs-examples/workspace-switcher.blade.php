<lyra:workspace-switcher
    current="acme"
    create
    create-label="Create workspace"
    :workspaces="[
        ['id' => 'acme', 'name' => 'Acme Inc.', 'plan' => 'Pro', 'members' => 24],
        ['id' => 'lyra', 'name' => 'Lyra Design', 'plan' => 'Starter', 'members' => 6],
    ]"
/>
