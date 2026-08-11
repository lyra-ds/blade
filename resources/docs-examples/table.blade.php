<lyra:table
    hover
    :columns="[
        ['key' => 'name', 'label' => 'Project'],
        ['key' => 'owner', 'label' => 'Owner'],
        ['key' => 'issues', 'label' => 'Open issues', 'align' => 'end'],
    ]"
    :rows="[
        ['name' => 'Website redesign', 'owner' => 'Ana Ribeiro', 'issues' => 12],
        ['name' => 'Mobile app', 'owner' => 'João Martins', 'issues' => 4],
    ]"
/>
