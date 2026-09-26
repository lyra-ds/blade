<lyra:shell sidebar-label="Workspace navigation" main-as="main" scroll="page" :skip-link="['label' => 'Skip to content']" main-id="main-content">
    <x-slot:banner>
        <lyra:alert tone="info">Scheduled maintenance tonight</lyra:alert>
    </x-slot:banner>
    <x-slot:topbar>
        <lyra:navbar>
            <lyra:nav-link href="/overview" active>Overview</lyra:nav-link>
            <lyra:nav-link href="/projects">Projects</lyra:nav-link>
        </lyra:navbar>
    </x-slot:topbar>
    <x-slot:sidebar>
        <lyra:nav-link href="/overview" active>Overview</lyra:nav-link>
        <lyra:nav-link href="/projects">Projects</lyra:nav-link>
    </x-slot:sidebar>
    <lyra:page-header title="Overview" />
</lyra:shell>
