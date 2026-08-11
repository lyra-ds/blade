<lyra:data-table
    :columns="[
        ['key' => 'name', 'label' => 'Project', 'sortable' => true],
        ['key' => 'owner', 'label' => 'Owner'],
        ['key' => 'issues', 'label' => 'Open issues', 'align' => 'end'],
    ]"
    :rows="[
        ['id' => '1', 'name' => 'Website redesign', 'owner' => 'Ana Ribeiro', 'issues' => 12],
        ['id' => '2', 'name' => 'Mobile app', 'owner' => 'João Martins', 'issues' => 4],
    ]"
    density="comfortable"
    hover
/>
