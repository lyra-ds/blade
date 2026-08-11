<lyra:checkbox-group
    name="notifications"
    label="Email notifications"
    hint="You can change this at any time."
    :options="[
        ['value' => 'mentions', 'label' => 'Mentions', 'hint' => 'When someone mentions you in a comment'],
        ['value' => 'deploys', 'label' => 'Deploys'],
        ['value' => 'billing', 'label' => 'Billing', 'disabled' => true],
    ]"
    :default-value="['mentions']"
/>
