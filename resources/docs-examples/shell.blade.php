<lyra:shell sidebar-label="Workspace navigation" main-as="main" scroll="page">
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
