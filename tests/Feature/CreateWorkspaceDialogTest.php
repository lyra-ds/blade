<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

function renderCreateWorkspaceDialog(array $data = [], string $attrs = ''): string
{
    return Blade::render('<x-lyra::create-workspace-dialog :title="$title" :messages="$messages" '.$attrs.' />', [
        'title' => $data['title'] ?? 'Create workspace',
        'messages' => $data['messages'] ?? [],
    ]);
}

it('emits the documented Alpine form and model contract', function (): void {
    $html = renderCreateWorkspaceDialog(attrs: 'x-model="open" return-focus-to="#trigger" x-on:lyra:create-workspace="handle($event)"');

    expect($html)->toContain('x-data="lyraCreateWorkspaceDialog({')
        ->toContain('x-modelable="open"')
        ->toContain('x-model="open"')
        ->toContain('x-on:lyra:create-workspace="handle($event)"')
        ->toContain('returnFocusTo: () =&gt; document.querySelector(&quot;#trigger&quot;)')
        ->toContain('x-bind="overlay"')
        ->toContain('x-bind="panel"')
        ->toContain('x-bind="form"')
        ->toContain('x-bind="createButton"')
        ->toContain('x-bind="errorSummary"')
        ->toContain('data-lyra-wscreate-name')
        ->toContain('data-lyra-wscreate-slug')
        ->toContain('lyra-btn__spinner')
        ->toContain('aria-label="Create workspace"');
});

it('renders translated copy and safely compiles message overrides', function (): void {
    $html = renderCreateWorkspaceDialog([
        'title' => 'Criar workspace',
        'messages' => ['nameRequired' => "Nome <script>alert('x')</script>"],
    ], 'close-label="Fechar" name-label="Nome do workspace" slug-hint="Letras minúsculas."');

    expect($html)->toContain('aria-label="Criar workspace"')
        ->toContain('aria-label="Fechar"')
        ->toContain('Nome do workspace')
        ->toContain('Letras minúsculas.')
        ->not->toContain('<script>');
});

it('rejects consumer x-data on its owned root', function (): void {
    renderCreateWorkspaceDialog(attrs: 'x-data="{}"');
})->throws(ViewException::class, 'owns x-data');
