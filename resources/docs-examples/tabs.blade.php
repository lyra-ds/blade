<div x-data="{ n: 0 }">
    <button type="button" @click="n++">Increase count</button>
    <span x-text="n"></span>
    <lyra:tabs id="project-tabs" label="Project sections" active="issues" variant="line" :items="[
        ['id' => 'overview', 'label' => 'Overview', 'panel' => 'Everything that happened this week.'],
        ['id' => 'issues', 'label' => 'Issues', 'count' => 12, 'panel' => '12 issues are open.'],
        ['id' => 'settings', 'label' => 'Settings', 'panel' => 'Rename or archive this project.'],
    ]" />
</div>
