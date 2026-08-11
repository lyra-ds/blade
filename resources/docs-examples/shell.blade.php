<lyra:shell sidebar-label="Workspace navigation" main-as="main" scroll="page">
    <x-slot:topbar>
        <lyra:navbar>
            <lyra:nav-link href="/overview" active>Overview</lyra:nav-link>
        </lyra:navbar>
    </x-slot:topbar>
    <x-slot:sidebar>
        <lyra:sidebar-group label="Workspace" :items="[
            ['id' => 'overview', 'label' => 'Overview', 'active' => true],
            ['id' => 'projects', 'label' => 'Projects'],
        ]" />
    </x-slot:sidebar>
    <lyra:page-header title="Overview" />
</lyra:shell>
