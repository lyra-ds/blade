<lyra:radio-group
    name="plan"
    label="Plan"
    hint="You can switch plans at any time."
    :options="[
        ['value' => 'starter', 'label' => 'Starter', 'hint' => 'Up to 3 projects'],
        ['value' => 'pro', 'label' => 'Pro', 'hint' => 'Unlimited projects'],
        ['value' => 'enterprise', 'label' => 'Enterprise', 'disabled' => true],
    ]"
    default-value="pro"
/>
